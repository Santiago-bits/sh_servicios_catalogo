<?php
/**
 * ARCHIVO: core/Uploader.php
 * ---------------------------------------------------------------------
 * Subida segura de archivos.
 *
 * Controles aplicados:
 *  - is_uploaded_file() y move_uploaded_file()
 *  - lista blanca de extensiones y de tipos MIME reales (finfo)
 *  - límite de tamaño configurable
 *  - nombre de archivo generado por el servidor (nunca el del usuario)
 *  - las imágenes se re-generan con GD: si el archivo trae código PHP
 *    embebido, se pierde en el proceso
 *  - la carpeta /uploads tiene un .htaccess que impide ejecutar PHP
 */

declare(strict_types=1);

namespace Core;

final class Uploader
{
    private const IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/pjpeg'=> 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    private const DOC_TYPES = [
        'application/pdf'   => 'pdf',
        'application/msword'=> 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'text/plain'        => 'txt',
        'image/jpeg'        => 'jpg',
        'image/png'         => 'png',
    ];

    /**
     * Sube una imagen, la normaliza y opcionalmente genera una miniatura.
     *
     * @param array<string,mixed> $file  Elemento de $_FILES
     * @return array{ok:bool,path?:string,thumb?:string,message?:string}
     */
    public static function image(array $file, string $folder, int $maxWidth = 1600, bool $thumbnail = true): array
    {
        $check = self::validate($file, array_keys(self::IMAGE_TYPES), Env::int('UPLOAD_MAX_MB', 8));
        if (!$check['ok']) {
            return $check;
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            return ['ok' => false, 'message' => 'El archivo no es una imagen válida.'];
        }

        $directory = rtrim(UPLOAD_PATH . '/' . trim($folder, '/'), '/');
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return ['ok' => false, 'message' => 'No se pudo crear la carpeta de destino.'];
        }

        /* Sin GD (pasa en varios hostings gratuitos) no se puede reescalar
           ni generar miniaturas. En vez de fallar, se guarda la imagen tal
           cual: ya pasó la validación de tipo real y la carpeta /uploads
           tiene un .htaccess que impide ejecutar PHP ahí. */
        if (!self::hasGd()) {
            $extension = self::IMAGE_TYPES[$check['mime']] ?? 'jpg';
            $name      = self::uniqueName($extension);

            if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
                return ['ok' => false, 'message' => 'No se pudo guardar la imagen.'];
            }

            @chmod($directory . '/' . $name, 0644);

            return [
                'ok'   => true,
                'path' => 'uploads/' . trim($folder, '/') . '/' . $name,
            ];
        }

        $name     = self::uniqueName('jpg');
        $fullPath = $directory . '/' . $name;

        if (!self::processImage($file['tmp_name'], $fullPath, $maxWidth, 82)) {
            return ['ok' => false, 'message' => 'No se pudo procesar la imagen.'];
        }

        $relative = 'uploads/' . trim($folder, '/') . '/' . $name;
        $result   = ['ok' => true, 'path' => $relative];

        if ($thumbnail) {
            $thumbName = 'thumb_' . $name;
            if (self::processImage($file['tmp_name'], $directory . '/' . $thumbName, 480, 78)) {
                $result['thumb'] = 'uploads/' . trim($folder, '/') . '/' . $thumbName;
            }
        }

        return $result;
    }

    /** ¿Está disponible la extensión GD para procesar imágenes? */
    public static function hasGd(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor');
    }

    /**
     * Sube un documento (PDF, Word, Excel, imagen).
     *
     * @param array<string,mixed> $file
     * @return array{ok:bool,path?:string,mime?:string,size?:int,message?:string}
     */
    public static function document(array $file, string $folder): array
    {
        $check = self::validate($file, array_keys(self::DOC_TYPES), Env::int('UPLOAD_MAX_MB_DOC', 15));
        if (!$check['ok']) {
            return $check;
        }

        $extension = self::DOC_TYPES[$check['mime']] ?? 'bin';
        $directory = rtrim(UPLOAD_PATH . '/' . trim($folder, '/'), '/');

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return ['ok' => false, 'message' => 'No se pudo crear la carpeta de destino.'];
        }

        $name = self::uniqueName($extension);

        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
            return ['ok' => false, 'message' => 'No se pudo guardar el archivo.'];
        }

        @chmod($directory . '/' . $name, 0644);

        return [
            'ok'   => true,
            'path' => 'uploads/' . trim($folder, '/') . '/' . $name,
            'mime' => $check['mime'],
            'size' => (int) $file['size'],
        ];
    }

    /**
     * @param array<string,mixed> $file
     * @param array<int,string>   $allowedMimes
     * @return array{ok:bool,mime?:string,message?:string}
     */
    private static function validate(array $file, array $allowedMimes, int $maxMb): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => self::errorMessage((int) ($file['error'] ?? 4))];
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'message' => 'Origen del archivo no válido.'];
        }

        $maxBytes = $maxMb * 1024 * 1024;
        if ((int) $file['size'] > $maxBytes) {
            return ['ok' => false, 'message' => 'El archivo supera el máximo de ' . $maxMb . ' MB.'];
        }

        // El MIME real se toma del contenido, no de lo que informa el navegador.
        if (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = (string) $finfo->file($file['tmp_name']);
        } else {
            // Sin la extensión fileinfo se deduce del contenido real del
            // archivo (nunca del tipo que informa el navegador).
            $mime = self::guessMime($file['tmp_name']);
        }

        if (!in_array($mime, $allowedMimes, true)) {
            return ['ok' => false, 'message' => 'Tipo de archivo no permitido (' . $mime . ').'];
        }

        // Doble control por extensión declarada
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $blocked   = ['php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar', 'htaccess', 'exe', 'sh', 'js', 'html', 'svg'];
        if (in_array($extension, $blocked, true)) {
            return ['ok' => false, 'message' => 'Extensión de archivo no permitida.'];
        }

        return ['ok' => true, 'mime' => $mime];
    }

    private static function processImage(string $source, string $destination, int $maxWidth, int $quality): bool
    {
        $info = @getimagesize($source);
        if ($info === false) {
            return false;
        }

        [$width, $height] = $info;

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG  => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            IMAGETYPE_GIF  => @imagecreatefromgif($source),
            default        => false,
        };

        if ($image === false) {
            return false;
        }

        $ratio     = $width > $maxWidth ? $maxWidth / $width : 1.0;
        $newWidth  = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        // Fondo blanco: evita transparencias negras al pasar a JPG
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $white);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $saved = imagejpeg($canvas, $destination, $quality);

        imagedestroy($canvas);
        imagedestroy($image);

        if ($saved) {
            @chmod($destination, 0644);
        }

        return $saved;
    }

    private static function uniqueName(string $extension): string
    {
        return date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }

    /**
     * Detecta el tipo real leyendo la firma del archivo. Sólo se usa como
     * respaldo cuando el hosting no tiene la extensión fileinfo.
     */
    private static function guessMime(string $path): string
    {
        // Las imágenes se identifican con getimagesize, que lee la cabecera real
        $info = @getimagesize($path);
        if ($info !== false && isset($info['mime'])) {
            return (string) $info['mime'];
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return 'application/octet-stream';
        }

        $head = (string) fread($handle, 8);
        fclose($handle);

        return match (true) {
            str_starts_with($head, '%PDF')            => 'application/pdf',
            str_starts_with($head, "PK\x03\x04")      => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            str_starts_with($head, "\xD0\xCF\x11\xE0") => 'application/msword',
            default                                    => 'application/octet-stream',
        };
    }

    public static function delete(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '' || !str_starts_with($relativePath, 'uploads/')) {
            return;
        }

        $full = PUBLIC_PATH . '/' . $relativePath;
        // realpath evita que un "../" saque el borrado de la carpeta uploads
        $real = realpath($full);
        $base = realpath(UPLOAD_PATH);

        if ($real !== false && $base !== false && str_starts_with($real, $base) && is_file($real)) {
            @unlink($real);
        }
    }

    private static function errorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo es demasiado grande.',
            UPLOAD_ERR_PARTIAL    => 'La subida se interrumpió. Reintentá.',
            UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
            UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP bloqueó la subida.',
            default               => 'Error desconocido al subir el archivo.',
        };
    }
}
