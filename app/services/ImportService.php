<?php
/**
 * ARCHIVO: app/services/ImportService.php
 * ---------------------------------------------------------------------
 * Importación masiva de maquinaria y repuestos desde CSV (y desde
 * Excel guardado como CSV). Valida TODO antes de tocar la base y
 * muestra fila por fila qué está bien y qué está mal.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Machine;
use App\Models\Product;
use App\Models\SparePart;
use Core\Auth;
use Core\Database;

final class ImportService
{
    /** Columnas de la plantilla de maquinaria. */
    public const MACHINE_COLUMNS = [
        'codigo', 'nombre', 'marca', 'modelo', 'categoria', 'anio', 'horas',
        'combustible', 'capacidad_kg', 'altura_mm', 'condicion', 'ubicacion',
        'costo', 'ganancia', 'precio', 'estado', 'descripcion',
    ];

    /** Columnas de la plantilla de repuestos. */
    public const PART_COLUMNS = [
        'codigo', 'nombre', 'marca', 'categoria', 'codigo_oem', 'codigo_fabricante',
        'fabricante', 'costo', 'ganancia', 'precio', 'compatibilidad', 'descripcion',
    ];

    private const FUELS      = ['electrico', 'diesel', 'nafta', 'gas', 'glp', 'hibrido', 'manual'];
    private const CONDITIONS = ['nuevo', 'usado', 'reacondicionado'];
    private const STATES     = ['disponible', 'reservada', 'vendida', 'mantenimiento', 'consultar'];

    /**
     * Lee y valida un CSV. No escribe nada en la base.
     *
     * @return array{ok:bool,message:string,valid:array<int,array<string,mixed>>,invalid:array<int,array<string,mixed>>,headers:array<int,string>}
     */
    public static function parse(string $filePath, string $type): array
    {
        $columns = $type === 'machine' ? self::MACHINE_COLUMNS : self::PART_COLUMNS;
        $empty   = ['ok' => false, 'message' => '', 'valid' => [], 'invalid' => [], 'headers' => $columns];

        if (!is_readable($filePath)) {
            $empty['message'] = 'No se pudo leer el archivo.';
            return $empty;
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            $empty['message'] = 'No se pudo abrir el archivo.';
            return $empty;
        }

        // Detección del separador a partir de la primera línea
        $firstLine = (string) fgets($handle);
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;

        // La plantilla arranca con una línea "sep=;" para que Excel abra
        // bien las columnas. Si está, se usa ese separador y se saltea.
        $skipSepLine = false;
        if (preg_match('/^sep=(.)\s*$/i', rtrim($firstLine, "\r\n"), $m)) {
            $separator   = $m[1];
            $skipSepLine = true;
        } else {
            $separator = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        }

        rewind($handle);

        if ($skipSepLine) {
            fgets($handle); // descartar la línea "sep=;"
        }

        $header = fgetcsv($handle, 0, $separator);
        if ($header === false) {
            fclose($handle);
            $empty['message'] = 'El archivo está vacío.';
            return $empty;
        }

        $header = array_map(
            static fn ($h): string => slugify(str_replace(' ', '_', trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)))),
            $header
        );
        // slugify convierte guiones bajos en guiones: se normaliza de vuelta
        $header = array_map(static fn (string $h): string => str_replace('-', '_', $h), $header);

        $valid   = [];
        $invalid = [];
        $line    = 1;
        $seen    = [];

        while (($row = fgetcsv($handle, 0, $separator)) !== false) {
            $line++;

            if (count(array_filter($row, static fn ($v): bool => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $data = [];
            foreach ($header as $i => $name) {
                $data[$name] = isset($row[$i]) ? trim((string) $row[$i]) : '';
            }

            $result = $type === 'machine' ? self::validateMachine($data, $seen) : self::validatePart($data, $seen);

            if ($result['errors'] === []) {
                $seen[] = strtoupper($result['data']['code']);
                $valid[] = ['line' => $line, 'data' => $result['data'], 'raw' => $data];
            } else {
                $invalid[] = ['line' => $line, 'errors' => $result['errors'], 'raw' => $data];
            }

            if (count($valid) + count($invalid) > 5000) {
                break; // límite de seguridad
            }
        }

        fclose($handle);

        return [
            'ok'      => true,
            'message' => sprintf('%d fila(s) correcta(s), %d con error.', count($valid), count($invalid)),
            'valid'   => $valid,
            'invalid' => $invalid,
            'headers' => $columns,
        ];
    }

    /**
     * @param array<string,string> $data
     * @param array<int,string>    $seen
     * @return array{data:array<string,mixed>,errors:array<int,string>}
     */
    private static function validateMachine(array $data, array $seen): array
    {
        $errors = [];

        $code = strtoupper(trim($data['codigo'] ?? ''));
        $name = trim($data['nombre'] ?? '');

        if ($code === '') {
            $errors[] = 'Falta el código.';
        } elseif (in_array($code, $seen, true)) {
            $errors[] = 'El código está repetido dentro del archivo.';
        }

        if ($name === '') {
            $errors[] = 'Falta el nombre.';
        }

        $fuel = slugify($data['combustible'] ?? '');
        $fuel = str_replace(['diesel', 'electrico'], ['diesel', 'electrico'], $fuel);
        if ($fuel !== '' && !in_array($fuel, self::FUELS, true)) {
            $errors[] = 'Combustible inválido (' . $data['combustible'] . '). Válidos: ' . implode(', ', self::FUELS);
        }

        $condition = slugify($data['condicion'] ?? '') ?: 'usado';
        if (!in_array($condition, self::CONDITIONS, true)) {
            $errors[] = 'Condición inválida. Válidas: ' . implode(', ', self::CONDITIONS);
        }

        $state = slugify($data['estado'] ?? '') ?: 'disponible';
        if (!in_array($state, self::STATES, true)) {
            $errors[] = 'Estado inválido. Válidos: ' . implode(', ', self::STATES);
        }

        $cost   = normalize_decimal($data['costo'] ?? '0');
        $profit = normalize_decimal($data['ganancia'] ?? '0');
        $price  = normalize_decimal($data['precio'] ?? '0');

        if ($cost < 0 || $price < 0) {
            $errors[] = 'Los importes no pueden ser negativos.';
        }

        $year = (int) preg_replace('/\D/', '', $data['anio'] ?? '');
        if ($year !== 0 && ($year < 1950 || $year > (int) date('Y') + 1)) {
            $errors[] = 'Año fuera de rango (' . $data['anio'] . ').';
        }

        return [
            'errors' => $errors,
            'data'   => [
                'code'        => $code,
                'name'        => $name,
                'brand'       => trim($data['marca'] ?? ''),
                'model'       => trim($data['modelo'] ?? ''),
                'category'    => trim($data['categoria'] ?? ''),
                'year'        => $year ?: null,
                'hours'       => (int) preg_replace('/\D/', '', $data['horas'] ?? '') ?: null,
                'fuel'        => $fuel !== '' ? $fuel : null,
                'capacity_kg' => normalize_decimal($data['capacidad_kg'] ?? '0') ?: null,
                'lift_height_mm' => (int) normalize_decimal($data['altura_mm'] ?? '0') ?: null,
                'condition'   => $condition,
                'location'    => trim($data['ubicacion'] ?? ''),
                'cost'        => $cost,
                'profit'      => $profit,
                'price'       => $price,
                'state'       => $state,
                'description' => trim($data['descripcion'] ?? ''),
            ],
        ];
    }

    /**
     * @param array<string,string> $data
     * @param array<int,string>    $seen
     * @return array{data:array<string,mixed>,errors:array<int,string>}
     */
    private static function validatePart(array $data, array $seen): array
    {
        $errors = [];

        $code = strtoupper(trim($data['codigo'] ?? ''));
        $name = trim($data['nombre'] ?? '');

        if ($code === '') {
            $errors[] = 'Falta el código.';
        } elseif (in_array($code, $seen, true)) {
            $errors[] = 'El código está repetido dentro del archivo.';
        }
        if ($name === '') {
            $errors[] = 'Falta el nombre.';
        }

        $cost  = normalize_decimal($data['costo'] ?? '0');
        $price = normalize_decimal($data['precio'] ?? '0');

        if ($cost < 0 || $price < 0) {
            $errors[] = 'Los importes no pueden ser negativos.';
        }

        return [
            'errors' => $errors,
            'data'   => [
                'code'              => $code,
                'name'              => $name,
                'brand'             => trim($data['marca'] ?? ''),
                'category'          => trim($data['categoria'] ?? ''),
                'oem_code'          => trim($data['codigo_oem'] ?? ''),
                'manufacturer_code' => trim($data['codigo_fabricante'] ?? ''),
                'manufacturer'      => trim($data['fabricante'] ?? ''),
                'cost'              => $cost,
                'profit'            => normalize_decimal($data['ganancia'] ?? '0'),
                'price'             => $price,
                'compatibility'     => trim($data['compatibilidad'] ?? ''),
                'description'       => trim($data['descripcion'] ?? ''),
            ],
        ];
    }

    /**
     * Inserta o actualiza las filas válidas.
     *
     * @param array<int,array<string,mixed>> $rows Salida de parse()['valid']
     * @return array{created:int,updated:int,errors:array<int,string>}
     */
    public static function run(array $rows, string $type, bool $updateExisting = true): array
    {
        $productModel = new Product();
        $machineModel = new Machine();
        $partModel    = new SparePart();

        $created = 0;
        $updated = 0;
        $errors  = [];

        foreach ($rows as $row) {
            $data = $row['data'];

            try {
                Database::beginTransaction();

                $existing = Database::selectOne(
                    'SELECT * FROM products WHERE code = :code LIMIT 1',
                    ['code' => $data['code']]
                );

                if ($existing !== null && !$updateExisting) {
                    Database::rollBack();
                    $errors[] = 'Fila ' . $row['line'] . ': el código ' . $data['code'] . ' ya existe (se omitió).';
                    continue;
                }

                $brandId    = $data['brand'] !== '' ? self::findOrCreateBrand($data['brand']) : null;
                $categoryId = $data['category'] !== '' ? self::findOrCreateCategory($data['category'], $type) : null;

                $prices = PriceService::calculate(
                    (float) $data['cost'],
                    (float) $data['profit'],
                    (float) $data['price'] > 0 ? (float) $data['price'] : null
                );

                $payload = [
                    'type'             => $type,
                    'code'             => $data['code'],
                    'name'             => $data['name'],
                    'brand_id'         => $brandId,
                    'category_id'      => $categoryId,
                    'description'      => $data['description'] !== '' ? $data['description'] : null,
                    'short_description'=> $data['description'] !== '' ? str_limit($data['description'], 200) : null,
                    'cost_price'       => $prices['cost_price'],
                    'profit_percent'   => $prices['profit_percent'],
                    'profit_amount'    => $prices['profit_amount'],
                    'final_price'      => $prices['final_price'],
                    'price_updated_at' => date('Y-m-d H:i:s'),
                    'updated_by'       => Auth::id(),
                ];

                if ($type === 'machine') {
                    $payload['availability'] = $data['state'];
                } else {
                    $payload['availability'] = 'disponible';
                }

                if ($existing !== null) {
                    $productId = (int) $existing['id'];
                    $productModel->updateById($productId, $payload);
                    $updated++;
                } else {
                    $payload['slug']       = $productModel->uniqueSlug($data['name'] . '-' . $data['code']);
                    $payload['created_by'] = Auth::id();
                    $payload['active']     = 1;
                    $productId = $productModel->create($payload);
                    $created++;
                }

                if ($type === 'machine') {
                    $machineModel->save($productId, [
                        'model'          => $data['model'] !== '' ? $data['model'] : null,
                        'year'           => $data['year'],
                        'hours'          => $data['hours'],
                        'fuel'           => $data['fuel'],
                        'capacity_kg'    => $data['capacity_kg'],
                        'lift_height_mm' => $data['lift_height_mm'],
                        'condition_type' => $data['condition'],
                        'location'       => $data['location'] !== '' ? $data['location'] : null,
                    ]);
                } else {
                    $partModel->save($productId, [
                        'oem_code'          => $data['oem_code'] !== '' ? $data['oem_code'] : null,
                        'manufacturer_code' => $data['manufacturer_code'] !== '' ? $data['manufacturer_code'] : null,
                        'manufacturer'      => $data['manufacturer'] !== '' ? $data['manufacturer'] : null,
                    ]);

                    // "Toyota 8FG25|Hyster H2.5" → compatibilidades
                    if ($data['compatibility'] !== '') {
                        Database::delete('spare_part_compatibility', 'spare_part_id = :id', ['id' => $productId]);

                        foreach (preg_split('/[|;]/', $data['compatibility']) ?: [] as $entry) {
                            $entry = trim($entry);
                            if ($entry === '') {
                                continue;
                            }
                            $parts   = preg_split('/\s+/', $entry, 2) ?: [];
                            $brand   = count($parts) > 1 ? self::findOrCreateBrand($parts[0]) : null;
                            $model   = count($parts) > 1 ? $parts[1] : $entry;

                            $partModel->addCompatibility($productId, $brand, $model);
                        }
                    }
                }

                Database::commit();
            } catch (\Throwable $e) {
                Database::rollBack();
                $errors[] = 'Fila ' . $row['line'] . ': ' . $e->getMessage();
            }
        }

        AuditService::log(
            'import',
            'data',
            null,
            null,
            sprintf('Importación de %s: %d creados, %d actualizados', $type === 'machine' ? 'maquinaria' : 'repuestos', $created, $updated)
        );

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    private static function findOrCreateBrand(string $name): int
    {
        $slug = slugify($name);

        $id = Database::scalar('SELECT id FROM brands WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
        if ($id !== null) {
            return (int) $id;
        }

        return Database::insert('brands', [
            'name'   => mb_substr($name, 0, 120),
            'slug'   => $slug,
            'active' => 1,
        ]);
    }

    private static function findOrCreateCategory(string $name, string $type): int
    {
        $slug = slugify($name);

        $id = Database::scalar(
            'SELECT id FROM categories WHERE slug = :slug AND type = :type LIMIT 1',
            ['slug' => $slug, 'type' => $type]
        );

        if ($id !== null) {
            return (int) $id;
        }

        return Database::insert('categories', [
            'type'   => $type,
            'name'   => mb_substr($name, 0, 120),
            'slug'   => $slug,
            'active' => 1,
        ]);
    }

    /** CSV de ejemplo listo para descargar. */
    public static function template(string $type): string
    {
        $columns = $type === 'machine' ? self::MACHINE_COLUMNS : self::PART_COLUMNS;

        $example = $type === 'machine'
            ? ['AE-100', 'Autoelevador Toyota 8FG25 2.500 kg', 'Toyota', '8FG25', 'Autoelevadores', '2018', '6200',
               'gas', '2500', '4700', 'usado', 'Depósito Central', '16000000', '25', '20000000', 'disponible',
               'Equipo revisado con garantía de 6 meses.']
            : ['FIL-100', 'Filtro de aceite Toyota serie 8', 'Toyota', 'Filtros', '15601-U2100-71', 'W68/3',
               'Toyota', '12000', '60', '19200', 'Toyota 8FG25|Toyota 8FD30',
               'Filtro de flujo total con válvula antirretorno.'];

        // "sep=;" en la primera línea: Excel la usa para separar en columnas
        // (sin esto, según la configuración regional, mete todo en la columna A).
        $csv = "\xEF\xBB\xBF" . "sep=;\r\n"
            . implode(';', $columns) . "\r\n"
            . implode(';', $example) . "\r\n";

        return $csv;
    }
}
