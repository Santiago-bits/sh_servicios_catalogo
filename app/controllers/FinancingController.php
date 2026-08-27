<?php
/**
 * ARCHIVO: app/controllers/FinancingController.php
 * ---------------------------------------------------------------------
 * Endpoint de la calculadora de financiación. El cálculo también corre
 * en el navegador para que sea instantáneo, pero el número que se
 * guarda o se imprime es siempre el que devuelve el servidor.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FinancingService;
use Core\Controller;
use Core\Request;

class FinancingController extends Controller
{
    public function calculate(): void
    {
        $price    = Request::float('precio');
        $optionId = Request::int('opcion');

        if ($price <= 0) {
            $this->json(['ok' => false, 'message' => 'Ingresá un precio válido.'], 422);
        }

        $currency = Request::post('moneda') === 'USD' ? 'USD' : 'ARS';

        // Cálculo con un plan preconfigurado
        if ($optionId > 0) {
            $result = FinancingService::calculateWithOption($price, $optionId, $currency);

            if ($result === null) {
                $this->json(['ok' => false, 'message' => 'El plan de financiación no está disponible.'], 404);
            }

            $this->json(['ok' => true, 'result' => $result, 'formatted' => $this->format($result)]);
        }

        // Cálculo libre (el usuario mueve anticipo, cuotas e interés)
        $result = FinancingService::calculate(
            $price,
            Request::float('anticipo'),
            Request::int('cuotas', 1),
            Request::float('interes'),
            Request::post('tipo_interes') === 'mensual' ? 'mensual' : 'total',
            $currency
        );

        $this->json([
            'ok'        => true,
            'result'    => $result,
            'formatted' => $this->format($result),
            'schedule'  => array_map(static fn (array $p): array => [
                'concept'  => $p['concept'],
                'amount'   => money($p['amount'], $result['currency']),
                'due_date' => date_es($p['due_date']),
            ], FinancingService::paymentSchedule($result)),
        ]);
    }

    /** @param array<string,mixed> $result @return array<string,string> */
    private function format(array $result): array
    {
        $currency = (string) $result['currency'];

        return [
            'price'              => money((float) $result['price'], $currency),
            'down_payment'       => money((float) $result['down_payment'], $currency),
            'balance'            => money((float) $result['balance'], $currency),
            'interest_amount'    => money((float) $result['interest_amount'], $currency),
            'financed_total'     => money((float) $result['financed_total'], $currency),
            'installment_amount' => money((float) $result['installment_amount'], $currency),
            'total'              => money((float) $result['total'], $currency),
            'installments'       => (string) $result['installments'],
        ];
    }
}
