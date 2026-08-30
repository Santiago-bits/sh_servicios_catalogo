<?php
/**
 * ARCHIVO: app/services/WhatsAppService.php
 * ---------------------------------------------------------------------
 * Armado de enlaces wa.me con mensajes prellenados. El número sale de
 * la configuración, nunca del código.
 */

declare(strict_types=1);

namespace App\Services;

final class WhatsAppService
{
    public static function number(): string
    {
        return SettingService::whatsapp();
    }

    /** Número específico para repuestos; si no se cargó, usa el general. */
    public static function partsNumber(): string
    {
        $parts = preg_replace('/\D+/', '', (string) SettingService::get('contact_whatsapp_parts', '')) ?? '';
        return $parts !== '' ? $parts : self::number();
    }

    public static function isConfigured(): bool
    {
        return self::number() !== '';
    }

    public static function link(string $message = '', ?string $number = null): string
    {
        $number ??= self::number();
        if ($number === '') {
            return '#';
        }

        return 'https://wa.me/' . $number . ($message === '' ? '' : '?text=' . rawurlencode($message));
    }

    /** Mensaje de consulta por una máquina. */
    public static function machineLink(array $product): string
    {
        $lines = [
            'Hola, estoy interesado en:',
            '',
            'Producto:',
            (string) $product['name'],
            '',
            'Código:',
            (string) $product['code'],
        ];

        if (PriceService::isPublicPriceVisible($product)) {
            $lines[] = '';
            $lines[] = 'Precio:';
            $lines[] = money(PriceService::effectivePrice($product), (string) ($product['currency'] ?? 'ARS'));
        }

        $lines[] = '';
        $lines[] = 'Quisiera recibir más información.';

        return self::link(implode("\n", $lines));
    }

    /** Mensaje de consulta por un repuesto. */
    public static function partLink(array $product): string
    {
        $lines = [
            'Hola, estoy interesado en el repuesto:',
            '',
            (string) $product['name'],
            '',
            'Código:',
            (string) $product['code'],
        ];

        if (!empty($product['oem_code'])) {
            $lines[] = '';
            $lines[] = 'Código OEM:';
            $lines[] = (string) $product['oem_code'];
        }

        $lines[] = '';
        $lines[] = '¿Tienen disponibilidad?';

        return self::link(implode("\n", $lines), self::partsNumber());
    }

    public static function productLink(array $product): string
    {
        return ($product['type'] ?? 'machine') === 'machine'
            ? self::machineLink($product)
            : self::partLink($product);
    }

    /** Envío de una cotización al cliente por WhatsApp. */
    public static function quoteLink(array $quote, ?string $phone = null): string
    {
        $number = preg_replace('/\D+/', '', (string) ($phone ?? $quote['customer_phone'] ?? '')) ?? '';
        if ($number === '') {
            $number = self::number();
        }
        if ($number === '') {
            return '#';
        }
        // Números argentinos cargados como "11-5555-1122" → se antepone 54
        if (strlen($number) <= 11 && !str_starts_with($number, '54')) {
            $number = '54' . $number;
        }

        $message = implode("\n", [
            'Hola ' . $quote['customer_name'] . ',',
            '',
            'Te enviamos la cotización ' . $quote['number'] . ' de ' . SettingService::companyName() . '.',
            '',
            'Total: ' . money((float) $quote['total'], (string) $quote['currency']),
            'Validez: ' . date_es((string) ($quote['valid_until'] ?? '')),
            '',
            'Quedamos a disposición por cualquier consulta.',
        ]);

        return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
    }

    /** Consulta general (botón flotante). */
    public static function generalLink(): string
    {
        return self::link('Hola, quisiera hacer una consulta.');
    }
}
