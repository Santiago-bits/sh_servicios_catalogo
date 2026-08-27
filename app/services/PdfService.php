<?php
/**
 * ARCHIVO: app/services/PdfService.php
 * ---------------------------------------------------------------------
 * Generación de los PDF del sistema (cotizaciones y catálogo) usando
 * el generador propio Lib\Pdf. No requiere Composer ni extensiones
 * adicionales más allá de GD.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Lib\Pdf;

final class PdfService
{
    private const YELLOW = '#F5C400';
    private const BLACK  = '#111111';
    private const DARK   = '#252525';
    private const GRAY   = '#6B6B6B';
    private const LIGHT  = '#F2F2F2';

    // =================================================================
    // Cotización
    // =================================================================

    /** @param array<string,mixed> $quote Cotización completa (findFull) */
    public static function quote(array $quote): Pdf
    {
        $pdf = new Pdf('P');
        $pdf->setMargins(14, 14, 14, 20);
        $pdf->setMeta('Cotización ' . $quote['number'], SettingService::companyName());

        $company = [
            'name'    => SettingService::companyName(),
            'taxid'   => (string) SettingService::get('company_taxid', ''),
            'address' => (string) SettingService::get('contact_address', ''),
            'city'    => (string) SettingService::get('contact_city', ''),
            'phone'   => (string) SettingService::get('contact_phone', ''),
            'email'   => SettingService::email(),
            'whatsapp'=> SettingService::whatsapp(),
            'web'     => SettingService::siteUrl(),
            'logo'    => (string) SettingService::get('company_logo', ''),
        ];

        $pdf->setHeader(static function (Pdf $pdf) use ($company, $quote): void {
            self::header($pdf, $company, 'COTIZACIÓN', (string) $quote['number']);
        });

        $pdf->setFooter(static function (Pdf $pdf) use ($company): void {
            self::footer($pdf, $company);
        });

        $pdf->addPage();

        // --- Datos del cliente y de la cotización --------------------
        $y = $pdf->getY();
        $w = $pdf->contentWidth();
        $half = ($w - 6) / 2;

        self::box($pdf, 14, $y, $half, 'DATOS DEL CLIENTE', [
            ['Cliente',  (string) $quote['customer_name']],
            ['Empresa',  (string) ($quote['customer_company'] ?? '—')],
            ['CUIT',     (string) ($quote['customer_taxid'] ?? '—')],
            ['Email',    (string) ($quote['customer_email'] ?? '—')],
            ['Teléfono', (string) ($quote['customer_phone'] ?? '—')],
        ]);

        self::box($pdf, 14 + $half + 6, $y, $half, 'DATOS DE LA COTIZACIÓN', [
            ['Número',   (string) $quote['number']],
            ['Fecha',    date_es((string) $quote['created_at'])],
            ['Validez',  date_es((string) ($quote['valid_until'] ?? ''))],
            ['Estado',   quote_status_badge((string) $quote['status'])['label']],
            ['Asesor',   (string) ($quote['user_name'] ?? '—')],
        ]);

        $pdf->setY($y + 40);

        // --- Tabla de ítems ------------------------------------------
        self::itemsTable($pdf, $quote);

        // --- Totales --------------------------------------------------
        self::totalsBlock($pdf, $quote);

        // --- Financiación ---------------------------------------------
        if (!empty($quote['payments'])) {
            self::paymentsBlock($pdf, $quote);
        }

        // --- Observaciones y condiciones ------------------------------
        if (!empty($quote['notes'])) {
            self::textBlock($pdf, 'OBSERVACIONES', (string) $quote['notes']);
        }
        if (!empty($quote['conditions'])) {
            self::textBlock($pdf, 'CONDICIONES COMERCIALES', (string) $quote['conditions']);
        }

        // --- Contacto -------------------------------------------------
        $pdf->ensureSpace(24);
        $y = $pdf->getY() + 4;
        $pdf->filledRect(14, $y, $pdf->contentWidth(), 18, self::LIGHT);
        $pdf->filledRect(14, $y, 2.5, 18, self::YELLOW);

        $pdf->setFont('B', 9);
        $pdf->setTextColor(self::BLACK);
        $pdf->text(20, $y + 6.5, 'Estamos a tu disposición');

        $pdf->setFont('', 8.5);
        $pdf->setTextColor(self::GRAY);
        $contact = array_filter([
            $company['phone']    !== '' ? 'Tel: ' . $company['phone'] : '',
            $company['whatsapp'] !== '' ? 'WhatsApp: +' . $company['whatsapp'] : '',
            $company['email']    !== '' ? $company['email'] : '',
        ]);
        $pdf->text(20, $y + 12.5, implode('   ·   ', $contact));

        $pdf->setY($y + 22);

        return $pdf;
    }

    /** @param array<string,mixed> $quote */
    private static function itemsTable(Pdf $pdf, array $quote): void
    {
        $currency = (string) $quote['currency'];
        $x        = 14.0;
        $w        = $pdf->contentWidth();

        // Anchos: código, descripción, cant., unitario, total
        $cols = [24.0, $w - 24 - 16 - 30 - 30, 16.0, 30.0, 30.0];

        $pdf->ensureSpace(20);

        // Encabezado
        $y = $pdf->getY();
        $pdf->filledRect($x, $y, $w, 8, self::BLACK);
        $pdf->setFont('B', 8);
        $pdf->setTextColor('#FFFFFF');

        $headers = ['CÓDIGO', 'DESCRIPCIÓN', 'CANT.', 'UNITARIO', 'TOTAL'];
        $aligns  = ['L', 'L', 'C', 'R', 'R'];
        $cx      = $x;

        foreach ($headers as $i => $header) {
            $pdf->cell($cx, $y, $cols[$i], 8, $header, $aligns[$i]);
            $cx += $cols[$i];
        }

        $pdf->setY($y + 8);

        // Filas
        $alternate = false;
        foreach ($quote['items'] as $item) {
            $pdf->setFont('', 8.5);
            $lines  = $pdf->wrap((string) $item['description'], $cols[1] - 3);
            $height = max(7.5, count($lines) * 4.2 + 3);

            $pdf->ensureSpace($height + 4);
            $y = $pdf->getY();

            if ($alternate) {
                $pdf->filledRect($x, $y, $w, $height, '#FAFAFA');
            }
            $alternate = !$alternate;

            $pdf->setTextColor(self::DARK);
            $cx = $x;

            $pdf->setFont('B', 8);
            $pdf->cell($cx, $y, $cols[0], $height, (string) ($item['code'] ?? '—'), 'L');
            $cx += $cols[0];

            $pdf->setFont('', 8.5);
            $textY = $y + 4.6;
            foreach ($lines as $line) {
                $pdf->text($cx + 1.5, $textY, $line);
                $textY += 4.2;
            }
            $cx += $cols[1];

            $pdf->cell($cx, $y, $cols[2], $height, number_es((float) $item['quantity'], 0), 'C');
            $cx += $cols[2];

            $pdf->cell($cx, $y, $cols[3], $height, money((float) $item['unit_price'], $currency), 'R');
            $cx += $cols[3];

            $pdf->setFont('B', 8.5);
            $pdf->cell($cx, $y, $cols[4], $height, money((float) $item['line_total'], $currency), 'R');

            $pdf->setDrawColor('#E5E5E5');
            $pdf->line($x, $y + $height, $x + $w, $y + $height);

            $pdf->setY($y + $height);
        }
    }

    /** @param array<string,mixed> $quote */
    private static function totalsBlock(Pdf $pdf, array $quote): void
    {
        $currency = (string) $quote['currency'];
        $w        = $pdf->contentWidth();
        $boxWidth = 78.0;
        $x        = 14 + $w - $boxWidth;

        $rows = [['Subtotal', (float) $quote['subtotal']]];

        if ((float) $quote['discount_amount'] > 0) {
            $rows[] = ['Descuento', -(float) $quote['discount_amount']];
        }
        if ((float) $quote['shipping_cost'] > 0) {
            $rows[] = ['Transporte', (float) $quote['shipping_cost']];
        }
        if ((float) $quote['other_costs'] > 0) {
            $rows[] = ['Otros costos', (float) $quote['other_costs']];
        }
        if ((float) $quote['interest_amount'] > 0) {
            $rows[] = ['Intereses de financiación', (float) $quote['interest_amount']];
        }

        $height = count($rows) * 6 + 14;
        $pdf->ensureSpace($height + 6);

        $y = $pdf->getY() + 4;

        $pdf->setFont('', 8.5);
        $pdf->setTextColor(self::GRAY);

        foreach ($rows as $row) {
            $pdf->cell($x, $y, $boxWidth - 34, 6, (string) $row[0], 'L');
            $pdf->setTextColor(self::DARK);
            $pdf->cell($x + $boxWidth - 34, $y, 34, 6, money((float) $row[1], $currency), 'R');
            $pdf->setTextColor(self::GRAY);
            $y += 6;
        }

        // Total destacado
        $pdf->filledRect($x, $y, $boxWidth, 11, self::YELLOW);
        $pdf->setFont('B', 10);
        $pdf->setTextColor(self::BLACK);
        $pdf->cell($x, $y, 30, 11, 'TOTAL', 'L', null, false, 3);
        $pdf->cell($x + 30, $y, $boxWidth - 30, 11, money((float) $quote['total'], $currency), 'R', null, false, 3);

        $y += 11;

        // Equivalencia en la otra moneda
        if (SettingService::bool('show_dual_currency', false)) {
            $other     = $currency === 'ARS' ? 'USD' : 'ARS';
            $converted = CurrencyService::convert((float) $quote['total'], $currency, $other);

            $pdf->setFont('', 7.5);
            $pdf->setTextColor(self::GRAY);
            $pdf->cell($x, $y, $boxWidth, 5, 'Equivalente aprox. ' . money($converted, $other, 0), 'R', null, false, 0);
            $y += 5;
        }

        $pdf->setY($y + 4);
    }

    /** @param array<string,mixed> $quote */
    private static function paymentsBlock(Pdf $pdf, array $quote): void
    {
        $currency = (string) $quote['currency'];

        $pdf->ensureSpace(30);
        $y = $pdf->getY() + 2;

        $pdf->setFont('B', 9);
        $pdf->setTextColor(self::BLACK);
        $pdf->text(14, $y + 4, 'PLAN DE PAGOS');

        if (!empty($quote['financing_name'])) {
            $pdf->setFont('', 8);
            $pdf->setTextColor(self::GRAY);
            $pdf->text(50, $y + 4, (string) $quote['financing_name']);
        }

        $y += 7;
        $pdf->setDrawColor(self::YELLOW);
        $pdf->setLineWidth(0.6);
        $pdf->line(14, $y, 44, $y);
        $pdf->setLineWidth(0.2);

        $y += 4;
        $colWidth = ($pdf->contentWidth()) / 3;

        foreach ($quote['payments'] as $index => $payment) {
            $column = $index % 3;
            if ($column === 0 && $index > 0) {
                $y += 12;
                $pdf->ensureSpace(16);
                $y = min($y, $pdf->pageHeight());
            }

            $x = 14 + ($column * $colWidth);

            $pdf->filledRect($x, $y, $colWidth - 4, 10, self::LIGHT);
            $pdf->setFont('B', 8);
            $pdf->setTextColor(self::DARK);
            $pdf->text($x + 2, $y + 4, $pdf->truncate((string) $payment['concept'], $colWidth - 8));

            $pdf->setFont('', 8);
            $pdf->setTextColor(self::GRAY);
            $pdf->text($x + 2, $y + 8, money((float) $payment['amount'], $currency) . '  ·  ' . date_es((string) ($payment['due_date'] ?? '')));
        }

        $pdf->setY($y + 16);
    }

    private static function textBlock(Pdf $pdf, string $title, string $text): void
    {
        $pdf->setFont('', 8);
        $lines  = $pdf->wrap($text, $pdf->contentWidth() - 4);
        $height = count($lines) * 3.8 + 12;

        $pdf->ensureSpace($height);
        $y = $pdf->getY() + 2;

        $pdf->setFont('B', 9);
        $pdf->setTextColor(self::BLACK);
        $pdf->text(14, $y + 4, $title);

        $y += 7;
        $pdf->setDrawColor(self::YELLOW);
        $pdf->setLineWidth(0.6);
        $pdf->line(14, $y, 44, $y);
        $pdf->setLineWidth(0.2);

        $pdf->setFont('', 8);
        $pdf->setTextColor(self::GRAY);
        $consumed = $pdf->multiCell(14, $y + 5, $pdf->contentWidth() - 4, $text, 3.8);

        $pdf->setY($y + 5 + $consumed);
    }

    /** @param array<int,array{0:string,1:string}> $rows */
    private static function box(Pdf $pdf, float $x, float $y, float $w, string $title, array $rows): void
    {
        $pdf->filledRect($x, $y, $w, 36, '#FAFAFA');
        $pdf->filledRect($x, $y, $w, 6, self::DARK);

        $pdf->setFont('B', 7.5);
        $pdf->setTextColor(self::YELLOW);
        $pdf->text($x + 2.5, $y + 4.2, $title);

        $rowY = $y + 10;

        foreach ($rows as $row) {
            $pdf->setFont('', 7.5);
            $pdf->setTextColor(self::GRAY);
            $pdf->text($x + 2.5, $rowY, $row[0]);

            $pdf->setFont('B', 8);
            $pdf->setTextColor(self::DARK);
            $pdf->text($x + 24, $rowY, $pdf->truncate($row[1] !== '' ? $row[1] : '—', $w - 26));

            $rowY += 5.2;
        }
    }

    /** @param array<string,string> $company */
    private static function header(Pdf $pdf, array $company, string $documentType, string $number): void
    {
        $w = $pdf->contentWidth();

        // Banda superior
        $pdf->filledRect(0, 0, $pdf->pageWidth(), 26, self::BLACK);
        $pdf->filledRect(0, 26, $pdf->pageWidth(), 1.6, self::YELLOW);

        $logoDrawn = false;
        if ($company['logo'] !== '') {
            $logoPath = PUBLIC_PATH . '/' . ltrim($company['logo'], '/');
            if (is_file($logoPath)) {
                $logoDrawn = $pdf->image($logoPath, 14, 6, 34);
            }
        }

        if (!$logoDrawn) {
            $pdf->setFont('B', 15);
            $pdf->setTextColor('#FFFFFF');
            $pdf->text(14, 13, mb_strtoupper($company['name']));

            $pdf->setFont('', 7.5);
            $pdf->setTextColor(self::YELLOW);
            $pdf->text(14, 18.5, mb_strtoupper((string) SettingService::get('company_slogan', '')));
        }

        // Datos de la empresa
        $pdf->setFont('', 7);
        $pdf->setTextColor('#BBBBBB');

        $lines = array_values(array_filter([
            $company['taxid']   !== '' ? 'CUIT ' . $company['taxid'] : '',
            trim($company['address'] . ' ' . $company['city']),
            $company['phone']   !== '' ? 'Tel. ' . $company['phone'] : '',
            $company['email'],
        ]));

        $y = 8;
        foreach (array_slice($lines, 0, 4) as $line) {
            $width = $pdf->textWidth($line, 7);
            $pdf->text($pdf->pageWidth() - 14 - $width, $y, $line);
            $y += 4;
        }

        // Título del documento
        $y = 34;
        $pdf->setFont('B', 16);
        $pdf->setTextColor(self::BLACK);
        $pdf->text(14, $y + 2, $documentType);

        $pdf->setFont('B', 12);
        $pdf->setTextColor(self::YELLOW);
        $numberWidth = $pdf->textWidth($number, 12, 'B');
        $pdf->filledRect($pdf->pageWidth() - 14 - $numberWidth - 8, $y - 5, $numberWidth + 8, 9, self::BLACK);
        $pdf->text($pdf->pageWidth() - 14 - $numberWidth - 4, $y + 1, $number);

        $pdf->setDrawColor('#DDDDDD');
        $pdf->line(14, $y + 6, 14 + $w, $y + 6);

        $pdf->setY($y + 11);
    }

    /** @param array<string,string> $company */
    private static function footer(Pdf $pdf, array $company): void
    {
        $y = $pdf->pageHeight() - 14;

        $pdf->setDrawColor('#DDDDDD');
        $pdf->line(14, $y - 4, $pdf->pageWidth() - 14, $y - 4);

        $pdf->setFont('', 7);
        $pdf->setTextColor(self::GRAY);
        $pdf->text(14, $y, $company['name'] . ($company['web'] !== '' ? '  ·  ' . preg_replace('#^https?://#', '', $company['web']) : ''));

        $footerText = (string) SettingService::get('quote_footer', '');
        if ($footerText !== '') {
            $width = $pdf->textWidth($footerText, 7);
            $pdf->text(($pdf->pageWidth() - $width) / 2, $y, $footerText);
        }

        $page  = 'Página ' . $pdf->pageNumber();
        $width = $pdf->textWidth($page, 7);
        $pdf->text($pdf->pageWidth() - 14 - $width, $y, $page);
    }

    // =================================================================
    // Catálogo
    // =================================================================

    /**
     * Catálogo PDF de productos seleccionados.
     *
     * @param array<int,array<string,mixed>> $products
     */
    public static function catalog(array $products, string $title = 'Catálogo de productos', bool $withPrices = true): Pdf
    {
        $pdf = new Pdf('P');
        $pdf->setMargins(14, 14, 14, 20);
        $pdf->setMeta($title, SettingService::companyName());

        $company = [
            'name'    => SettingService::companyName(),
            'taxid'   => (string) SettingService::get('company_taxid', ''),
            'address' => (string) SettingService::get('contact_address', ''),
            'city'    => (string) SettingService::get('contact_city', ''),
            'phone'   => (string) SettingService::get('contact_phone', ''),
            'email'   => SettingService::email(),
            'whatsapp'=> SettingService::whatsapp(),
            'web'     => SettingService::siteUrl(),
            'logo'    => (string) SettingService::get('company_logo', ''),
        ];

        $pdf->setHeader(static function (Pdf $pdf) use ($company, $title): void {
            self::header($pdf, $company, mb_strtoupper($title), date('m/Y'));
        });

        $pdf->setFooter(static function (Pdf $pdf) use ($company): void {
            self::footer($pdf, $company);
        });

        $pdf->addPage();

        $productModel = new Product();
        $cardHeight   = 42.0;

        foreach ($products as $product) {
            $pdf->ensureSpace($cardHeight + 4);
            $y = $pdf->getY();
            $w = $pdf->contentWidth();

            $pdf->filledRect(14, $y, $w, $cardHeight, '#FAFAFA');
            $pdf->filledRect(14, $y, 2, $cardHeight, self::YELLOW);

            // Imagen
            $textX = 20.0;
            if (!empty($product['image'])) {
                $imagePath = PUBLIC_PATH . '/' . ltrim((string) $product['image'], '/');
                if (is_file($imagePath) && $pdf->image($imagePath, 18, $y + 3, 46, 36)) {
                    $textX = 68.0;
                }
            }

            $textWidth = $w - ($textX - 14) - 4;

            $pdf->setFont('B', 10.5);
            $pdf->setTextColor(self::BLACK);
            $pdf->text($textX, $y + 8, $pdf->truncate((string) $product['name'], $textWidth));

            $pdf->setFont('', 7.5);
            $pdf->setTextColor(self::GRAY);

            $meta = array_filter([
                'Código: ' . $product['code'],
                !empty($product['brand_name']) ? (string) $product['brand_name'] : '',
                !empty($product['model']) ? 'Modelo ' . $product['model'] : '',
                !empty($product['year']) ? (string) $product['year'] : '',
                !empty($product['oem_code']) ? 'OEM ' . $product['oem_code'] : '',
            ]);
            $pdf->text($textX, $y + 13, $pdf->truncate(implode('  ·  ', $meta), $textWidth));

            $description = (string) ($product['short_description'] ?? '');
            if ($description !== '') {
                $pdf->setFont('', 8);
                $lines = array_slice($pdf->wrap($description, $textWidth), 0, 2);
                $lineY = $y + 19;
                foreach ($lines as $line) {
                    $pdf->text($textX, $lineY, $line);
                    $lineY += 4;
                }
            }

            // Ficha técnica resumida
            $specs = [];
            if (!empty($product['capacity_kg'])) {
                $specs[] = 'Capacidad: ' . kg_to_human((float) $product['capacity_kg']);
            }
            if (!empty($product['lift_height_mm'])) {
                $specs[] = 'Altura: ' . mm_to_human((int) $product['lift_height_mm']);
            }
            if (!empty($product['fuel'])) {
                $specs[] = fuel_label((string) $product['fuel']);
            }
            if (!empty($product['hours'])) {
                $specs[] = number_es((float) $product['hours']) . ' hs';
            }

            if ($specs !== []) {
                $pdf->setFont('B', 7.5);
                $pdf->setTextColor(self::DARK);
                $pdf->text($textX, $y + 30, $pdf->truncate(implode('   |   ', $specs), $textWidth));
            }

            // Precio
            if ($withPrices && PriceService::isPublicPriceVisible($product)) {
                $price      = money(PriceService::effectivePrice($product), (string) $product['currency']);
                $priceWidth = $pdf->textWidth($price, 11, 'B');

                $pdf->filledRect(14 + $w - $priceWidth - 12, $y + $cardHeight - 12, $priceWidth + 8, 9, self::YELLOW);
                $pdf->setFont('B', 11);
                $pdf->setTextColor(self::BLACK);
                $pdf->text(14 + $w - $priceWidth - 8, $y + $cardHeight - 5.5, $price);
            } else {
                $pdf->setFont('B', 8.5);
                $pdf->setTextColor(self::GRAY);
                $label = 'Consultar precio';
                $width = $pdf->textWidth($label, 8.5, 'B');
                $pdf->text(14 + $w - $width - 4, $y + $cardHeight - 5.5, $label);
            }

            $pdf->setY($y + $cardHeight + 4);
        }

        if ($products === []) {
            $pdf->setFont('', 10);
            $pdf->setTextColor(self::GRAY);
            $pdf->text(14, $pdf->getY() + 10, 'No hay productos que coincidan con la selección.');
        }

        return $pdf;
    }
}
