<?php
/**
 * ARCHIVO: app/services/EmailService.php
 * ---------------------------------------------------------------------
 * Envío de correo. Tres modos configurables en .env:
 *   log  → guarda el mail en storage/logs (ideal para XAMPP sin SMTP)
 *   mail → usa la función mail() de PHP
 *   smtp → cliente SMTP propio (sin librerías externas)
 */

declare(strict_types=1);

namespace App\Services;

use Core\Env;

final class EmailService
{
    /**
     * @param array<int,array{path:string,name:string}> $attachments
     * @return array{ok:bool,message:string}
     */
    public static function send(string $to, string $subject, string $htmlBody, array $attachments = [], ?string $replyTo = null): array
    {
        $mailer = strtolower((string) Env::get('MAIL_MAILER', 'log'));

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'La dirección de correo no es válida.'];
        }

        $from     = (string) Env::get('MAIL_FROM_ADDRESS', 'no-reply@localhost');
        $fromName = (string) Env::get('MAIL_FROM_NAME', SettingService::companyName());

        // Muchos hostings gratuitos deshabilitan mail(): en vez de romper,
        // se registra el correo y se avisa qué configurar.
        if ($mailer === 'mail' && !self::mailFunctionAvailable()) {
            $result = self::viaLog($to, $subject, $htmlBody, $attachments);
            $result['message'] = 'Este hosting tiene deshabilitada la función mail() de PHP. '
                . 'El correo quedó registrado en storage/logs/emails.log. '
                . 'Para enviarlo de verdad configurá MAIL_MAILER=api con una cuenta de Brevo o SendGrid.';
            return $result;
        }

        return match ($mailer) {
            'mail' => self::viaMail($to, $subject, $htmlBody, $from, $fromName, $attachments, $replyTo),
            'smtp' => self::viaSmtp($to, $subject, $htmlBody, $from, $fromName, $attachments, $replyTo),
            'api'  => self::viaApi($to, $subject, $htmlBody, $from, $fromName, $attachments, $replyTo),
            default => self::viaLog($to, $subject, $htmlBody, $attachments),
        };
    }

    /** ¿El hosting permite usar la función mail() de PHP? */
    public static function mailFunctionAvailable(): bool
    {
        if (!function_exists('mail')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return !in_array('mail', $disabled, true);
    }

    /**
     * Envío por API HTTPS (Brevo o SendGrid).
     *
     * Es la única forma de mandar correos en hostings que bloquean el
     * puerto SMTP (587), como los planes gratuitos. Ambos servicios tienen
     * plan gratuito y sólo necesitan una clave de API.
     *
     * @param array<int,array{path:string,name:string}> $attachments
     * @return array{ok:bool,message:string}
     */
    private static function viaApi(string $to, string $subject, string $body, string $from, string $fromName, array $attachments, ?string $replyTo): array
    {
        $provider = strtolower((string) Env::get('MAIL_API_PROVIDER', 'brevo'));
        $key      = (string) Env::get('MAIL_API_KEY', '');

        if ($key === '') {
            return ['ok' => false, 'message' => 'Falta cargar MAIL_API_KEY en el archivo .env.'];
        }

        // Los adjuntos viajan en base64 (el PDF de la cotización, por ejemplo)
        $files = [];
        foreach ($attachments as $attachment) {
            if (is_file($attachment['path'])) {
                $files[] = [
                    'name'    => $attachment['name'],
                    'content' => base64_encode((string) file_get_contents($attachment['path'])),
                ];
            }
        }

        if ($provider === 'sendgrid') {
            $url     = 'https://api.sendgrid.com/v3/mail/send';
            $headers = ['Authorization: Bearer ' . $key, 'Content-Type: application/json'];
            $payload = [
                'personalizations' => [['to' => [['email' => $to]]]],
                'from'             => ['email' => $from, 'name' => $fromName],
                'subject'          => $subject,
                'content'          => [['type' => 'text/html', 'value' => $body]],
            ];
            if ($replyTo !== null) {
                $payload['reply_to'] = ['email' => $replyTo];
            }
            foreach ($files as $file) {
                $payload['attachments'][] = ['filename' => $file['name'], 'content' => $file['content'], 'type' => 'application/pdf'];
            }
        } else {
            // Brevo (ex Sendinblue) — 300 correos por día en el plan gratuito
            $url     = 'https://api.brevo.com/v3/smtp/email';
            $headers = ['api-key: ' . $key, 'Content-Type: application/json', 'Accept: application/json'];
            $payload = [
                'sender'      => ['email' => $from, 'name' => $fromName],
                'to'          => [['email' => $to]],
                'subject'     => $subject,
                'htmlContent' => $body,
            ];
            if ($replyTo !== null) {
                $payload['replyTo'] = ['email' => $replyTo];
            }
            foreach ($files as $file) {
                $payload['attachment'][] = ['name' => $file['name'], 'content' => $file['content']];
            }
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        [$status, $response] = self::httpPost($url, $headers, (string) $json);

        if ($status >= 200 && $status < 300) {
            return ['ok' => true, 'message' => 'Correo enviado a ' . $to . '.'];
        }

        error_log('[MAIL API] ' . $status . ' ' . $response);

        return [
            'ok'      => false,
            'message' => $status === 0
                ? 'El servidor no pudo conectarse al servicio de correo (puede estar bloqueado por el hosting).'
                : 'El servicio de correo rechazó el envío (código ' . $status . '). Revisá la clave de API y que el remitente esté verificado.',
        ];
    }

    /**
     * POST HTTPS con cURL y, si no está disponible, con streams.
     *
     * @param array<int,string> $headers
     * @return array{0:int,1:string}
     */
    private static function httpPost(string $url, array $headers, string $body): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = (string) curl_exec($ch);
            $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return [$status, $response];
        }

        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", $headers),
                'content'       => $body,
                'timeout'       => 20,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $status   = 0;

        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m)) {
                $status = (int) $m[1];
            }
        }

        return [$status, $response === false ? '' : $response];
    }

    /** @return array{ok:bool,message:string} */
    private static function viaLog(string $to, string $subject, string $body, array $attachments): array
    {
        $file = STORAGE_PATH . '/logs/emails.log';

        $entry = str_repeat('=', 70) . "\n"
            . 'FECHA:   ' . date('d/m/Y H:i:s') . "\n"
            . 'PARA:    ' . $to . "\n"
            . 'ASUNTO:  ' . $subject . "\n"
            . 'ADJUNTOS:' . ($attachments === [] ? ' (ninguno)' : ' ' . implode(', ', array_column($attachments, 'name'))) . "\n"
            . str_repeat('-', 70) . "\n"
            . strip_tags(str_replace(['</p>', '<br>', '<br/>'], "\n", $body)) . "\n\n";

        @file_put_contents($file, $entry, FILE_APPEND);

        return [
            'ok'      => true,
            'message' => 'Correo registrado en storage/logs/emails.log (modo de prueba). '
                       . 'Configurá MAIL_MAILER=smtp en el archivo .env para enviarlo de verdad.',
        ];
    }

    /** @return array{ok:bool,message:string} */
    private static function viaMail(string $to, string $subject, string $body, string $from, string $fromName, array $attachments, ?string $replyTo): array
    {
        $boundary = 'shs_' . bin2hex(random_bytes(8));

        $headers = [
            'MIME-Version: 1.0',
            'From: ' . self::encodeHeader($fromName) . ' <' . $from . '>',
            'Reply-To: ' . ($replyTo ?? $from),
            'X-Mailer: SH Servicios',
        ];

        if ($attachments === []) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $message   = $body;
        } else {
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
            $message   = self::multipartBody($body, $attachments, $boundary);
        }

        $sent = @mail($to, self::encodeHeader($subject), $message, implode("\r\n", $headers));

        return $sent
            ? ['ok' => true, 'message' => 'Correo enviado a ' . $to . '.']
            : ['ok' => false, 'message' => 'El servidor no pudo enviar el correo. Revisá la configuración de mail() o usá SMTP.'];
    }

    /** Cliente SMTP mínimo (AUTH LOGIN + STARTTLS opcional). */
    private static function viaSmtp(string $to, string $subject, string $body, string $from, string $fromName, array $attachments, ?string $replyTo): array
    {
        $host       = (string) Env::get('MAIL_HOST', '');
        $port       = Env::int('MAIL_PORT', 587);
        $username   = (string) Env::get('MAIL_USERNAME', '');
        $password   = (string) Env::get('MAIL_PASSWORD', '');
        $encryption = strtolower((string) Env::get('MAIL_ENCRYPTION', 'tls'));

        if ($host === '') {
            return ['ok' => false, 'message' => 'Falta configurar MAIL_HOST en el archivo .env.'];
        }

        $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $socket = @stream_socket_client($remote, $errno, $errstr, 15);

        if ($socket === false) {
            return ['ok' => false, 'message' => 'No se pudo conectar al servidor SMTP: ' . $errstr];
        }

        $read = static function () use ($socket): string {
            $response = '';
            while (($line = fgets($socket, 515)) !== false) {
                $response .= $line;
                if (strlen($line) < 4 || $line[3] === ' ') {
                    break;
                }
            }
            return $response;
        };

        $write = static function (string $command) use ($socket): void {
            fwrite($socket, $command . "\r\n");
        };

        $expect = static function (string $response, string $code): bool {
            return str_starts_with(trim($response), $code);
        };

        try {
            $read();
            $write('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $read();

            if ($encryption === 'tls') {
                $write('STARTTLS');
                if (!$expect($read(), '220')) {
                    throw new \RuntimeException('El servidor rechazó STARTTLS.');
                }
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('No se pudo iniciar el cifrado TLS.');
                }
                $write('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
                $read();
            }

            if ($username !== '') {
                $write('AUTH LOGIN');
                $read();
                $write(base64_encode($username));
                $read();
                $write(base64_encode($password));
                if (!$expect($read(), '235')) {
                    throw new \RuntimeException('Usuario o contraseña SMTP incorrectos.');
                }
            }

            $write('MAIL FROM:<' . $from . '>');
            $read();
            $write('RCPT TO:<' . $to . '>');
            if (!$expect($read(), '250')) {
                throw new \RuntimeException('El servidor rechazó el destinatario.');
            }

            $write('DATA');
            $read();

            $boundary = 'shs_' . bin2hex(random_bytes(8));
            $headers  = [
                'From: ' . self::encodeHeader($fromName) . ' <' . $from . '>',
                'To: <' . $to . '>',
                'Reply-To: ' . ($replyTo ?? $from),
                'Subject: ' . self::encodeHeader($subject),
                'Date: ' . date('r'),
                'MIME-Version: 1.0',
            ];

            if ($attachments === []) {
                $headers[] = 'Content-Type: text/html; charset=UTF-8';
                $content   = $body;
            } else {
                $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
                $content   = self::multipartBody($body, $attachments, $boundary);
            }

            $write(implode("\r\n", $headers) . "\r\n\r\n" . $content . "\r\n.");

            if (!$expect($read(), '250')) {
                throw new \RuntimeException('El servidor no aceptó el mensaje.');
            }

            $write('QUIT');
            fclose($socket);

            return ['ok' => true, 'message' => 'Correo enviado a ' . $to . '.'];
        } catch (\Throwable $e) {
            @fclose($socket);
            error_log('[SMTP] ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Error SMTP: ' . $e->getMessage()];
        }
    }

    /** @param array<int,array{path:string,name:string}> $attachments */
    private static function multipartBody(string $html, array $attachments, string $boundary): string
    {
        $body = '--' . $boundary . "\r\n"
              . "Content-Type: text/html; charset=UTF-8\r\n"
              . "Content-Transfer-Encoding: 8bit\r\n\r\n"
              . $html . "\r\n";

        foreach ($attachments as $attachment) {
            if (!is_file($attachment['path'])) {
                continue;
            }
            $content = chunk_split(base64_encode((string) file_get_contents($attachment['path'])));

            $body .= '--' . $boundary . "\r\n"
                   . 'Content-Type: application/octet-stream; name="' . $attachment['name'] . "\"\r\n"
                   . "Content-Transfer-Encoding: base64\r\n"
                   . 'Content-Disposition: attachment; filename="' . $attachment['name'] . "\"\r\n\r\n"
                   . $content . "\r\n";
        }

        return $body . '--' . $boundary . "--\r\n";
    }

    private static function encodeHeader(string $text): string
    {
        return preg_match('/[\x80-\xFF]/', $text)
            ? '=?UTF-8?B?' . base64_encode($text) . '?='
            : $text;
    }

    // ----------------------------------------------------------------
    // Plantillas
    // ----------------------------------------------------------------

    /** Aviso interno de nueva consulta. */
    public static function newInquiry(array $inquiry): array
    {
        $to = SettingService::email();
        if ($to === '') {
            return ['ok' => false, 'message' => 'No hay email de destino configurado.'];
        }

        $rows = [
            'Nombre'   => $inquiry['name'],
            'Email'    => $inquiry['email'] ?? '—',
            'Teléfono' => $inquiry['phone'] ?? '—',
            'Empresa'  => $inquiry['company'] ?? '—',
            'Producto' => $inquiry['product_name'] ?? '—',
        ];

        $html = self::layout(
            'Nueva consulta desde el sitio web',
            self::table($rows) . '<p style="margin-top:16px"><strong>Mensaje:</strong><br>' . nl2br(e((string) $inquiry['message'])) . '</p>'
        );

        return self::send($to, 'Nueva consulta web · ' . $inquiry['name'], $html, [], $inquiry['email'] ?? null);
    }

    /** Envío de la cotización al cliente. */
    public static function quote(array $quote, string $pdfPath): array
    {
        $to = (string) ($quote['customer_email'] ?? '');
        if ($to === '') {
            return ['ok' => false, 'message' => 'La cotización no tiene email del cliente.'];
        }

        $html = self::layout(
            'Cotización ' . $quote['number'],
            '<p>Hola ' . e((string) $quote['customer_name']) . ',</p>'
            . '<p>Te enviamos adjunta la cotización <strong>' . e((string) $quote['number']) . '</strong> solicitada.</p>'
            . self::table([
                'Número'  => $quote['number'],
                'Fecha'   => date_es((string) $quote['created_at']),
                'Validez' => date_es((string) ($quote['valid_until'] ?? '')),
                'Total'   => money((float) $quote['total'], (string) $quote['currency']),
            ])
            . '<p>Quedamos a tu disposición por cualquier consulta.</p>'
        );

        return self::send($to, 'Cotización ' . $quote['number'] . ' · ' . SettingService::companyName(), $html, [
            ['path' => $pdfPath, 'name' => 'Cotizacion-' . $quote['number'] . '.pdf'],
        ]);
    }

    private static function layout(string $title, string $content): string
    {
        $company = e(SettingService::companyName());

        return '<!doctype html><html lang="es"><body style="margin:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#252525">'
            . '<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px">'
            . '<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden">'
            . '<tr><td style="background:#111111;padding:20px 24px">'
            . '<span style="color:#ffffff;font-size:19px;font-weight:bold;letter-spacing:.5px">' . $company . '</span>'
            . '<div style="height:3px;width:48px;background:#F5C400;margin-top:8px"></div></td></tr>'
            . '<tr><td style="padding:24px">'
            . '<h2 style="margin:0 0 16px;font-size:18px;color:#111111">' . e($title) . '</h2>'
            . $content
            . '</td></tr>'
            . '<tr><td style="background:#F7F7F7;padding:16px 24px;font-size:12px;color:#6B6B6B">'
            . $company . ' · ' . e(SettingService::email())
            . '</td></tr></table></td></tr></table></body></html>';
    }

    /** @param array<string,mixed> $rows */
    private static function table(array $rows): string
    {
        $html = '<table cellpadding="6" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:14px">';

        foreach ($rows as $label => $value) {
            $html .= '<tr>'
                . '<td style="border-bottom:1px solid #eee;color:#6B6B6B;width:35%">' . e($label) . '</td>'
                . '<td style="border-bottom:1px solid #eee;font-weight:bold">' . e((string) $value) . '</td>'
                . '</tr>';
        }

        return $html . '</table>';
    }
}
