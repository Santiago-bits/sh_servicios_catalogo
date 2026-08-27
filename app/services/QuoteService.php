<?php
/**
 * ARCHIVO: app/services/QuoteService.php
 * ---------------------------------------------------------------------
 * Cotizador: carrito de sesión para el visitante, cálculo de totales y
 * alta de cotizaciones (desde la web o desde el panel).
 *
 * El precio de cada ítem SIEMPRE se relee de la base de datos: nunca se
 * confía en el precio que llega desde el navegador.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Quote;
use Core\Auth;
use Core\Database;
use Core\Session;

final class QuoteService
{
    private const CART_KEY = '_quote_cart';
    private const MAX_ITEMS = 30;

    // =================================================================
    // Carrito de cotización (sesión)
    // =================================================================

    /** @return array<int,array{product_id:int,quantity:float}> */
    public static function cart(): array
    {
        $cart = Session::get(self::CART_KEY, []);
        return is_array($cart) ? $cart : [];
    }

    /** @return array{ok:bool,message:string,count:int} */
    public static function addToCart(int $productId, float $quantity = 1.0): array
    {
        $product = Database::selectOne(
            'SELECT id, name FROM products WHERE id = :id AND active = 1 AND deleted_at IS NULL',
            ['id' => $productId]
        );

        if ($product === null) {
            return ['ok' => false, 'message' => 'El producto no está disponible.', 'count' => self::count()];
        }

        $cart = self::cart();

        if (!isset($cart[$productId]) && count($cart) >= self::MAX_ITEMS) {
            return ['ok' => false, 'message' => 'Alcanzaste el máximo de ' . self::MAX_ITEMS . ' ítems.', 'count' => self::count()];
        }

        $quantity = max(1.0, min(999.0, $quantity));

        $cart[$productId] = [
            'product_id' => $productId,
            'quantity'   => isset($cart[$productId]) ? min(999.0, (float) $cart[$productId]['quantity'] + $quantity) : $quantity,
        ];

        Session::set(self::CART_KEY, $cart);

        return ['ok' => true, 'message' => $product['name'] . ' se agregó a tu cotización.', 'count' => count($cart)];
    }

    public static function removeFromCart(int $productId): int
    {
        $cart = self::cart();
        unset($cart[$productId]);
        Session::set(self::CART_KEY, $cart);
        return count($cart);
    }

    public static function setQuantity(int $productId, float $quantity): void
    {
        $cart = self::cart();
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = max(1.0, min(999.0, $quantity));
            Session::set(self::CART_KEY, $cart);
        }
    }

    public static function clearCart(): void
    {
        Session::forget(self::CART_KEY);
    }

    public static function count(): int
    {
        return count(self::cart());
    }

    /**
     * Ítems del carrito con los datos actuales del producto.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function cartItems(): array
    {
        $cart = self::cart();
        if ($cart === []) {
            return [];
        }

        $products = (new Product())->findMany(array_keys($cart));
        $items    = [];

        foreach ($products as $product) {
            $quantity  = (float) ($cart[(int) $product['id']]['quantity'] ?? 1);
            $unitPrice = PriceService::effectivePrice($product);

            $items[] = [
                'product'     => $product,
                'product_id'  => (int) $product['id'],
                'item_type'   => (string) $product['type'],
                'code'        => (string) $product['code'],
                'description' => (string) $product['name'],
                'quantity'    => $quantity,
                'unit_price'  => $unitPrice,
                'line_total'  => round($unitPrice * $quantity, 2),
                'price_hidden'=> !PriceService::isPublicPriceVisible($product),
            ];
        }

        return $items;
    }

    // =================================================================
    // Cálculo de totales
    // =================================================================

    /**
     * @param array<int,array<string,mixed>> $items
     * @param array<string,mixed>            $options
     * @return array<string,float>
     */
    public static function totals(array $items, array $options = []): array
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $quantity   = (float) ($item['quantity'] ?? 1);
            $unitPrice  = (float) ($item['unit_price'] ?? 0);
            $discount   = (float) ($item['discount_percent'] ?? 0);
            $lineTotal  = $unitPrice * $quantity * (1 - $discount / 100);
            $subtotal  += $lineTotal;
        }

        $subtotal = round($subtotal, 2);

        $discountPercent = max(0.0, (float) ($options['discount_percent'] ?? 0));
        $discountAmount  = (float) ($options['discount_amount'] ?? 0);

        if ($discountPercent > 0 && $discountAmount <= 0) {
            $discountAmount = round($subtotal * ($discountPercent / 100), 2);
        }
        $discountAmount = min($discountAmount, $subtotal);

        $shipping   = max(0.0, (float) ($options['shipping_cost'] ?? 0));
        $otherCosts = max(0.0, (float) ($options['other_costs'] ?? 0));
        $interest   = max(0.0, (float) ($options['interest_amount'] ?? 0));

        $total = round($subtotal - $discountAmount + $shipping + $otherCosts + $interest, 2);

        return [
            'subtotal'         => $subtotal,
            'discount_percent' => round($discountPercent, 2),
            'discount_amount'  => round($discountAmount, 2),
            'shipping_cost'    => round($shipping, 2),
            'other_costs'      => round($otherCosts, 2),
            'interest_amount'  => round($interest, 2),
            'total'            => max(0.0, $total),
        ];
    }

    // =================================================================
    // Alta de cotizaciones
    // =================================================================

    /**
     * Crea una cotización completa dentro de una transacción.
     *
     * @param array<string,mixed>            $customer
     * @param array<int,array<string,mixed>> $items
     * @param array<string,mixed>            $options
     * @return array{ok:bool,message:string,id?:int,number?:string}
     */
    public static function create(array $customer, array $items, array $options = []): array
    {
        if ($items === []) {
            return ['ok' => false, 'message' => 'La cotización no tiene ítems.'];
        }

        $quoteModel = new Quote();

        try {
            return Database::transaction(static function () use ($quoteModel, $customer, $items, $options): array {
                $prefix  = (string) SettingService::get('quote_prefix', 'COT-');
                $padding = SettingService::int('quote_padding', 6);
                $number  = $quoteModel->nextNumber($prefix, $padding);

                $totals = self::totals($items, $options);

                $validityDays = SettingService::int('quote_validity_days', 15);
                $validUntil   = $options['valid_until'] ?? date('Y-m-d', strtotime('+' . $validityDays . ' days'));

                $financing = null;
                if (!empty($options['financing_option_id'])) {
                    $financing = FinancingService::calculateWithOption(
                        $totals['total'],
                        (int) $options['financing_option_id'],
                        (string) ($options['currency'] ?? 'ARS')
                    );

                    if ($financing !== null) {
                        $totals['interest_amount'] = $financing['interest_amount'];
                        $totals['total']           = $financing['total'];
                    }
                }

                $quoteId = $quoteModel->create([
                    'number'              => $number,
                    'status'              => (string) ($options['status'] ?? 'borrador'),
                    'source'              => (string) ($options['source'] ?? 'admin'),
                    'customer_name'       => mb_substr((string) $customer['name'], 0, 160),
                    'customer_company'    => isset($customer['company']) ? mb_substr((string) $customer['company'], 0, 160) : null,
                    'customer_email'      => isset($customer['email']) ? mb_substr((string) $customer['email'], 0, 160) : null,
                    'customer_phone'      => isset($customer['phone']) ? mb_substr((string) $customer['phone'], 0, 40) : null,
                    'customer_taxid'      => isset($customer['taxid']) ? mb_substr((string) $customer['taxid'], 0, 40) : null,
                    'customer_address'    => isset($customer['address']) ? mb_substr((string) $customer['address'], 0, 255) : null,
                    'subtotal'            => $totals['subtotal'],
                    'discount_percent'    => $totals['discount_percent'],
                    'discount_amount'     => $totals['discount_amount'],
                    'shipping_cost'       => $totals['shipping_cost'],
                    'other_costs'         => $totals['other_costs'],
                    'interest_amount'     => $totals['interest_amount'],
                    'total'               => $totals['total'],
                    'currency'            => (string) ($options['currency'] ?? 'ARS'),
                    'exchange_rate'       => CurrencyService::rate('USD'),
                    'financing_option_id' => $options['financing_option_id'] ?? null,
                    'down_payment'        => $financing['down_payment'] ?? 0,
                    'installments'        => $financing['installments'] ?? 0,
                    'installment_amount'  => $financing['installment_amount'] ?? 0,
                    'notes'               => isset($options['notes']) ? mb_substr((string) $options['notes'], 0, 4000) : null,
                    'conditions'          => (string) ($options['conditions'] ?? SettingService::get('quote_conditions', '')),
                    'valid_until'         => $validUntil,
                    'user_id'             => Auth::id(),
                ]);

                $quoteModel->replaceItems($quoteId, $items);

                if ($financing !== null && $financing['installments'] > 0) {
                    $quoteModel->replacePayments($quoteId, array_map(
                        static fn (array $p): array => [
                            'concept'  => $p['concept'],
                            'amount'   => $p['amount'],
                            'due_date' => $p['due_date'],
                        ],
                        FinancingService::paymentSchedule($financing)
                    ));
                }

                // Contador de cotizaciones por producto (estadísticas)
                $productModel = new Product();
                foreach ($items as $item) {
                    if (!empty($item['product_id'])) {
                        $productModel->incrementCounter((int) $item['product_id'], 'quotes_count');
                    }
                }

                AuditService::log('create', 'quotes', 'quote', $quoteId, 'Cotización ' . $number . ' creada');

                return ['ok' => true, 'message' => 'Cotización ' . $number . ' creada.', 'id' => $quoteId, 'number' => $number];
            });
        } catch (\Throwable $e) {
            error_log('[QUOTE] ' . $e->getMessage());
            return ['ok' => false, 'message' => 'No se pudo generar la cotización. Intentá nuevamente.'];
        }
    }

    /**
     * Normaliza los ítems que llegan de un formulario del panel,
     * releyendo el precio desde la base cuando hay producto asociado.
     *
     * @param array<int,array<string,mixed>> $rawItems
     * @return array<int,array<string,mixed>>
     */
    public static function normalizeItems(array $rawItems, bool $trustPrices = false): array
    {
        $items = [];

        foreach ($rawItems as $raw) {
            $description = trim((string) ($raw['description'] ?? ''));
            $productId   = (int) ($raw['product_id'] ?? 0);

            if ($description === '' && $productId <= 0) {
                continue;
            }

            $quantity  = max(0.01, normalize_decimal((string) ($raw['quantity'] ?? '1'), 1));
            $discount  = max(0.0, min(100.0, normalize_decimal((string) ($raw['discount_percent'] ?? '0'), 0)));
            $unitPrice = normalize_decimal((string) ($raw['unit_price'] ?? '0'), 0);
            $itemType  = (string) ($raw['item_type'] ?? 'other');
            $code      = trim((string) ($raw['code'] ?? ''));

            if ($productId > 0) {
                $product = Database::selectOne(
                    'SELECT id, code, name, type, final_price, offer_price, is_offer FROM products WHERE id = :id AND deleted_at IS NULL',
                    ['id' => $productId]
                );

                if ($product === null) {
                    $productId = 0;
                } else {
                    $code        = $code !== '' ? $code : (string) $product['code'];
                    $description = $description !== '' ? $description : (string) $product['name'];
                    $itemType    = (string) $product['type'];

                    // Un operario puede pisar el precio; el público no.
                    if (!$trustPrices || $unitPrice <= 0) {
                        $unitPrice = PriceService::effectivePrice($product);
                    }
                }
            }

            $items[] = [
                'product_id'       => $productId ?: null,
                'item_type'        => in_array($itemType, ['machine', 'spare_part', 'service', 'other'], true) ? $itemType : 'other',
                'code'             => $code !== '' ? mb_substr($code, 0, 60) : null,
                'description'      => mb_substr($description, 0, 255),
                'quantity'         => $quantity,
                'unit_price'       => round($unitPrice, 2),
                'discount_percent' => $discount,
                'line_total'       => round($unitPrice * $quantity * (1 - $discount / 100), 2),
            ];
        }

        return $items;
    }
}
