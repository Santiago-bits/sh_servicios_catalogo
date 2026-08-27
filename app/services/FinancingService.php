<?php
/**
 * ARCHIVO: app/services/FinancingService.php
 * ---------------------------------------------------------------------
 * Cálculo de planes de financiación.
 *
 *   Saldo          = Precio − Anticipo
 *   Interés        = Saldo × (Interés % / 100)      [interés total]
 *                  = Saldo × (i/100) × cuotas       [interés mensual]
 *   Total financiado = Saldo + Interés
 *   Valor cuota    = Total financiado / cuotas
 *   Total final    = Anticipo + Total financiado
 *
 * La misma fórmula corre en el navegador (financing.js) para el
 * cálculo en tiempo real y acá para todo lo que se guarda o imprime:
 * el resultado que vale es el del servidor.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\FinancingOption;

final class FinancingService
{
    /**
     * @return array{
     *   price:float, down_payment:float, balance:float, interest_percent:float,
     *   interest_amount:float, financed_total:float, installments:int,
     *   installment_amount:float, total:float, currency:string
     * }
     */
    public static function calculate(
        float $price,
        float $downPayment = 0.0,
        int $installments = 1,
        float $interestPercent = 0.0,
        string $interestType = 'total',
        string $currency = 'ARS'
    ): array {
        $price        = max(0.0, round($price, 2));
        $downPayment  = min(max(0.0, round($downPayment, 2)), $price);
        $installments = max(1, min(120, $installments));
        $interestPercent = max(0.0, $interestPercent);

        $balance = round($price - $downPayment, 2);

        $interestAmount = $interestType === 'mensual'
            ? round($balance * ($interestPercent / 100) * $installments, 2)
            : round($balance * ($interestPercent / 100), 2);

        $financedTotal     = round($balance + $interestAmount, 2);
        $installmentAmount = $installments > 0 ? round($financedTotal / $installments, 2) : $financedTotal;
        $total             = round($downPayment + $financedTotal, 2);

        return [
            'price'              => $price,
            'down_payment'       => $downPayment,
            'balance'            => $balance,
            'interest_percent'   => round($interestPercent, 2),
            'interest_amount'    => $interestAmount,
            'financed_total'     => $financedTotal,
            'installments'       => $installments,
            'installment_amount' => $installmentAmount,
            'total'              => $total,
            'currency'           => $currency,
        ];
    }

    /**
     * Calcula usando un plan configurado desde el panel.
     *
     * @return array<string,mixed>|null
     */
    public static function calculateWithOption(float $price, int $optionId, string $currency = 'ARS'): ?array
    {
        $option = (new FinancingOption())->find($optionId);
        if ($option === null || (int) $option['active'] !== 1) {
            return null;
        }

        $downPayment = round($price * ((float) $option['down_payment_percent'] / 100), 2);

        $result = self::calculate(
            $price,
            $downPayment,
            (int) $option['installments'],
            (float) $option['interest_percent'],
            (string) $option['interest_type'],
            $currency
        );

        $result['option_id']   = (int) $option['id'];
        $result['option_name'] = (string) $option['name'];

        return $result;
    }

    /**
     * Todos los planes aplicables a un importe, ya calculados. Es lo que
     * se muestra en la ficha del producto.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function plansFor(float $price, string $type, string $currency = 'ARS'): array
    {
        if ($price <= 0) {
            return [];
        }

        $options = (new FinancingOption())->activeFor($type, $price);
        $plans   = [];

        foreach ($options as $option) {
            $downPayment = round($price * ((float) $option['down_payment_percent'] / 100), 2);

            $calc = self::calculate(
                $price,
                $downPayment,
                (int) $option['installments'],
                (float) $option['interest_percent'],
                (string) $option['interest_type'],
                $currency
            );

            // Descuento del método de pago (ej. contado −5%)
            $discount = (float) ($option['method_discount'] ?? 0);
            if ($discount > 0 && (int) $option['installments'] <= 1) {
                $calc['discount_percent'] = $discount;
                $calc['total']            = round($price * (1 - $discount / 100), 2);
                $calc['installment_amount'] = $calc['total'];
                $calc['down_payment']     = $calc['total'];
            }

            $calc['option_id']    = (int) $option['id'];
            $calc['option_name']  = (string) $option['name'];
            $calc['description']  = (string) ($option['description'] ?? '');
            $calc['method_name']  = (string) ($option['method_name'] ?? '');
            $calc['method_icon']  = (string) ($option['method_icon'] ?? 'bi-cash-coin');
            $calc['featured']     = (int) $option['featured'] === 1;

            $plans[] = $calc;
        }

        return $plans;
    }

    /**
     * Plan de pagos con fechas, para el PDF de la cotización.
     *
     * @return array<int,array{concept:string,amount:float,due_date:string}>
     */
    public static function paymentSchedule(array $calculation, ?string $startDate = null): array
    {
        $schedule = [];
        $start    = $startDate !== null ? strtotime($startDate) : time();

        if ($calculation['down_payment'] > 0) {
            $schedule[] = [
                'concept'  => 'Anticipo',
                'amount'   => (float) $calculation['down_payment'],
                'due_date' => date('Y-m-d', $start),
            ];
        }

        $installments = (int) $calculation['installments'];
        if ($installments > 1 || $calculation['financed_total'] > 0) {
            for ($i = 1; $i <= $installments; $i++) {
                $schedule[] = [
                    'concept'  => sprintf('Cuota %d de %d', $i, $installments),
                    'amount'   => (float) $calculation['installment_amount'],
                    'due_date' => date('Y-m-d', strtotime('+' . $i . ' month', $start)),
                ];
            }
        }

        return $schedule;
    }
}
