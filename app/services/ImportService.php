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
        // Identificación y clasificación
        'codigo', 'nombre', 'marca', 'modelo', 'categoria', 'numero_serie',
        'condicion', 'estado', 'ubicacion',
        // Precio
        'moneda', 'costo', 'ganancia', 'precio', 'precio_oferta', 'mostrar_precio',
        // Ficha técnica
        'anio', 'horas', 'combustible', 'capacidad_kg', 'altura_mm', 'altura_plegada_mm',
        'motor', 'potencia_hp', 'transmision', 'peso_kg', 'largo_mm', 'ancho_mm',
        'radio_giro_mm', 'bateria', 'voltaje', 'tipo_mastil', 'tipo_rueda',
        'tamano_una', 'garantia',
        // Contenido y etiquetas
        'resumen', 'descripcion', 'destacado', 'es_nuevo',
    ];

    /** Columnas de la plantilla de repuestos. */
    public const PART_COLUMNS = [
        'codigo', 'nombre', 'marca', 'categoria', 'fabricante',
        'codigo_oem', 'codigo_fabricante', 'origen', 'unidad', 'peso_kg',
        'moneda', 'costo', 'ganancia', 'precio', 'precio_oferta', 'mostrar_precio',
        'estado', 'destacado', 'es_nuevo', 'compatibilidad', 'resumen', 'descripcion',
    ];

    private const FUELS      = ['electrico', 'diesel', 'nafta', 'gas', 'glp', 'hibrido', 'manual'];
    private const CONDITIONS = ['nuevo', 'usado', 'reacondicionado'];
    private const STATES     = ['disponible', 'reservada', 'vendida', 'mantenimiento', 'consultar'];
    private const ORIGINS    = ['original', 'alternativo', 'remanufacturado'];

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

    /** "si"/"no"/"x"/"1"/"0"/"" → 1 | 0 | null (null = la columna vino vacía, no se toca). */
    private static function parseBool(?string $value): ?int
    {
        $v = mb_strtolower(trim((string) $value));
        if ($v === '') {
            return null;
        }
        if (in_array($v, ['si', 'sí', 'x', '1', 'true', 'verdadero', 'yes', 'y'], true)) {
            return 1;
        }
        if (in_array($v, ['no', '0', 'false', 'falso', 'n'], true)) {
            return 0;
        }
        return null;
    }

    /** Texto de moneda → 'ARS' | 'USD' | null (vacío) | '' (inválido). */
    private static function parseCurrency(string $value): ?string
    {
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }
        if ($raw === '$') {
            return 'ARS';
        }
        $v = slugify($raw);
        if ($v === 'ars' || str_starts_with($v, 'peso')) {
            return 'ARS';
        }
        if ($v === 'usd' || $v === 'us' || str_starts_with($v, 'dolar') || str_starts_with($v, 'u-s')) {
            return 'USD';
        }
        return '';
    }

    /** Texto de origen del repuesto → enum de spare_parts | null (vacío) | '' (inválido). */
    private static function parseOrigin(string $value): ?string
    {
        $v = slugify($value);
        if ($v === '') {
            return null;
        }
        $map = [
            'original' => 'original', 'oem' => 'original', 'genuino' => 'original', 'genuina' => 'original',
            'alternativo' => 'alternativo', 'alternativa' => 'alternativo', 'aftermarket' => 'alternativo', 'generico' => 'alternativo',
            'remanufacturado' => 'remanufacturado', 'reman' => 'remanufacturado', 'reacondicionado' => 'remanufacturado',
        ];
        return $map[$v] ?? '';
    }

    /**
     * @param array<string,string> $data
     * @param array<int,string>    $seen
     * @return array{data:array<string,mixed>,errors:array<int,string>}
     */
    private static function validateMachine(array $data, array $seen): array
    {
        $errors = [];

        $txt = static function (string $k, int $max = 160) use ($data): ?string {
            $t = trim($data[$k] ?? '');
            return $t !== '' ? mb_substr($t, 0, $max) : null;
        };
        $dec = static function (string $k) use ($data): ?float {
            $t = trim($data[$k] ?? '');
            if ($t === '') {
                return null;
            }
            $n = normalize_decimal($t);
            return $n > 0 ? round($n, 3) : null;
        };
        $int = static function (string $k) use ($dec): ?int {
            $n = $dec($k);
            return $n === null ? null : (int) round($n);
        };

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

        $currency = self::parseCurrency($data['moneda'] ?? '');
        if ($currency === '') {
            $errors[] = 'Moneda inválida (' . $data['moneda'] . '). Poné ARS o USD.';
            $currency = null;
        }

        $cost   = normalize_decimal($data['costo'] ?? '0');
        $profit = normalize_decimal($data['ganancia'] ?? '0');
        $price  = normalize_decimal($data['precio'] ?? '0');
        $offer  = trim($data['precio_oferta'] ?? '') !== '' ? normalize_decimal($data['precio_oferta']) : null;

        if ($cost < 0 || $price < 0 || ($offer !== null && $offer < 0)) {
            $errors[] = 'Los importes no pueden ser negativos.';
        }
        if ($offer !== null && $offer > 0 && $price > 0 && $offer >= $price) {
            $errors[] = 'El precio de oferta tiene que ser menor al precio.';
        }

        $year = (int) preg_replace('/\D/', '', $data['anio'] ?? '');
        if ($year !== 0 && ($year < 1950 || $year > (int) date('Y') + 1)) {
            $errors[] = 'Año fuera de rango (' . $data['anio'] . ').';
        }

        return [
            'errors' => $errors,
            'data'   => [
                'code'             => $code,
                'name'             => $name,
                'brand'            => trim($data['marca'] ?? ''),
                'model'            => $txt('modelo', 120),
                'category'         => trim($data['categoria'] ?? ''),
                'serial_number'    => $txt('numero_serie', 80),
                'year'             => $year ?: null,
                'hours'            => (int) preg_replace('/\D/', '', $data['horas'] ?? '') ?: null,
                'fuel'             => $fuel !== '' ? $fuel : null,
                'capacity_kg'      => $dec('capacidad_kg'),
                'lift_height_mm'   => $int('altura_mm'),
                'closed_height_mm' => $int('altura_plegada_mm'),
                'engine'           => $txt('motor', 120),
                'power_hp'         => $dec('potencia_hp'),
                'transmission'     => $txt('transmision', 120),
                'weight_kg'        => $dec('peso_kg'),
                'length_mm'        => $int('largo_mm'),
                'width_mm'         => $int('ancho_mm'),
                'turn_radius_mm'   => $int('radio_giro_mm'),
                'battery'          => $txt('bateria', 120),
                'voltage'          => $txt('voltaje', 40),
                'mast_type'        => $txt('tipo_mastil', 80),
                'tire_type'        => $txt('tipo_rueda', 80),
                'fork_size'        => $txt('tamano_una', 120),
                'warranty'         => $txt('garantia', 160),
                'condition'        => $condition,
                'location'         => $txt('ubicacion', 160),
                'currency'         => $currency,
                'cost'             => $cost,
                'profit'           => $profit,
                'price'            => $price,
                'offer_price'      => $offer,
                'price_visible'    => self::parseBool($data['mostrar_precio'] ?? ''),
                'featured'         => self::parseBool($data['destacado'] ?? ''),
                'is_new'           => self::parseBool($data['es_nuevo'] ?? ''),
                'state'            => $state,
                'summary'          => $txt('resumen', 200),
                'description'      => trim($data['descripcion'] ?? ''),
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

        $txt = static function (string $k, int $max = 160) use ($data): ?string {
            $t = trim($data[$k] ?? '');
            return $t !== '' ? mb_substr($t, 0, $max) : null;
        };

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

        $currency = self::parseCurrency($data['moneda'] ?? '');
        if ($currency === '') {
            $errors[] = 'Moneda inválida (' . $data['moneda'] . '). Poné ARS o USD.';
            $currency = null;
        }

        $origin = self::parseOrigin($data['origen'] ?? '');
        if ($origin === '') {
            $errors[] = 'Origen inválido (' . $data['origen'] . '). Válidos: ' . implode(', ', self::ORIGINS);
            $origin = null;
        }

        $state = trim($data['estado'] ?? '') !== '' ? slugify($data['estado']) : null;
        if ($state !== null && !in_array($state, self::STATES, true)) {
            $errors[] = 'Estado inválido. Válidos: ' . implode(', ', self::STATES);
            $state = null;
        }

        $cost  = normalize_decimal($data['costo'] ?? '0');
        $price = normalize_decimal($data['precio'] ?? '0');
        $offer = trim($data['precio_oferta'] ?? '') !== '' ? normalize_decimal($data['precio_oferta']) : null;

        if ($cost < 0 || $price < 0 || ($offer !== null && $offer < 0)) {
            $errors[] = 'Los importes no pueden ser negativos.';
        }
        if ($offer !== null && $offer > 0 && $price > 0 && $offer >= $price) {
            $errors[] = 'El precio de oferta tiene que ser menor al precio.';
        }

        $weight = trim($data['peso_kg'] ?? '') !== '' ? normalize_decimal($data['peso_kg']) : null;

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
                'origin'            => $origin,
                'unit'             => $txt('unidad', 20),
                'weight_kg'        => $weight !== null && $weight > 0 ? round($weight, 3) : null,
                'currency'          => $currency,
                'cost'              => $cost,
                'profit'            => normalize_decimal($data['ganancia'] ?? '0'),
                'price'             => $price,
                'offer_price'       => $offer,
                'price_visible'     => self::parseBool($data['mostrar_precio'] ?? ''),
                'featured'          => self::parseBool($data['destacado'] ?? ''),
                'is_new'            => self::parseBool($data['es_nuevo'] ?? ''),
                'state'             => $state,
                'compatibility'     => trim($data['compatibilidad'] ?? ''),
                'summary'           => $txt('resumen', 200),
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
                    'cost_price'       => $prices['cost_price'],
                    'profit_percent'   => $prices['profit_percent'],
                    'profit_amount'    => $prices['profit_amount'],
                    'final_price'      => $prices['final_price'],
                    'price_updated_at' => date('Y-m-d H:i:s'),
                    'updated_by'       => Auth::id(),
                ];

                // Texto: sólo se pisa si el archivo trae algo.
                if ($data['description'] !== '') {
                    $payload['description'] = $data['description'];
                }
                $summary = $data['summary'];
                if ($summary === null && $data['description'] !== '') {
                    $summary = str_limit($data['description'], 200);
                }
                if ($summary !== null) {
                    $payload['short_description'] = $summary;
                }

                // Comercial: sólo se toca lo que venga con valor en la fila.
                if ($data['currency'] !== null) {
                    $payload['currency'] = $data['currency'];
                }
                if ($data['offer_price'] !== null) {
                    $payload['offer_price'] = $data['offer_price'];
                    $payload['is_offer']    = $data['offer_price'] > 0 ? 1 : 0;
                }
                if ($data['price_visible'] !== null) {
                    $payload['price_visible'] = $data['price_visible'];
                }
                if ($data['featured'] !== null) {
                    $payload['featured'] = $data['featured'];
                }
                if ($data['is_new'] !== null) {
                    $payload['is_new'] = $data['is_new'];
                }

                if ($type === 'machine') {
                    $payload['availability'] = $data['state'];
                } elseif ($data['state'] !== null) {
                    $payload['availability'] = $data['state'];
                } elseif ($existing === null) {
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
                    $tech = [
                        'model'            => $data['model'],
                        'year'             => $data['year'],
                        'hours'            => $data['hours'],
                        'fuel'             => $data['fuel'],
                        'engine'           => $data['engine'],
                        'power_hp'         => $data['power_hp'],
                        'transmission'     => $data['transmission'],
                        'capacity_kg'      => $data['capacity_kg'],
                        'lift_height_mm'   => $data['lift_height_mm'],
                        'closed_height_mm' => $data['closed_height_mm'],
                        'weight_kg'        => $data['weight_kg'],
                        'length_mm'        => $data['length_mm'],
                        'width_mm'         => $data['width_mm'],
                        'turn_radius_mm'   => $data['turn_radius_mm'],
                        'battery'          => $data['battery'],
                        'voltage'          => $data['voltage'],
                        'mast_type'        => $data['mast_type'],
                        'tire_type'        => $data['tire_type'],
                        'fork_size'        => $data['fork_size'],
                        'serial_number'    => $data['serial_number'],
                        'warranty'         => $data['warranty'],
                        'location'         => $data['location'],
                    ];
                    // Sólo se guardan los campos técnicos que vinieron con valor
                    // (así reimportar para tocar el precio no borra la ficha técnica).
                    $tech = array_filter($tech, static fn ($v): bool => $v !== null && $v !== '');
                    $tech['condition_type'] = $data['condition'];
                    $machineModel->save($productId, $tech);
                } else {
                    $sp = [
                        'oem_code'          => $data['oem_code'] !== '' ? $data['oem_code'] : null,
                        'manufacturer_code' => $data['manufacturer_code'] !== '' ? $data['manufacturer_code'] : null,
                        'manufacturer'      => $data['manufacturer'] !== '' ? $data['manufacturer'] : null,
                    ];
                    if ($data['origin'] !== null) {
                        $sp['origin'] = $data['origin'];
                    }
                    if ($data['unit'] !== null) {
                        $sp['unit'] = $data['unit'];
                    }
                    if ($data['weight_kg'] !== null) {
                        $sp['weight_kg'] = $data['weight_kg'];
                    }
                    $partModel->save($productId, $sp);

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
            ? [
                'AE-100', 'Autoelevador Toyota 8FG25 2.500 kg', 'Toyota', '8FG25', 'Autoelevadores', 'SN-8FG25-001',
                'usado', 'disponible', 'Depósito Central',
                'USD', '16000', '25', '20000', '', 'si',
                '2018', '6200', 'gas', '2500', '4700', '2100',
                'Toyota 4Y 2.5L nafta/gas', '52', 'Automática (Powershift)', '3800', '3690', '1150',
                '2200', 'Plomo-ácido', '48V', 'Triple / Full free', 'Neumática', '1070 x 122 x 40 mm', '6 meses',
                'Autoelevador a gas 2.500 kg, torre triple, revisado.',
                'Equipo revisado con garantía de 6 meses. Motor original, cubiertas nuevas.',
                'no', 'no',
            ]
            : [
                'FIL-100', 'Filtro de aceite Toyota serie 8', 'Toyota', 'Filtros', 'Toyota',
                '15601-U2100-71', 'W68/3', 'original', 'unidad', '0.35',
                'ARS', '12000', '60', '19200', '', 'si',
                'disponible', 'no', 'no',
                'Toyota 8FG25|Toyota 8FD30',
                'Filtro de aceite original Toyota para la serie 8.',
                'Filtro de flujo total con válvula antirretorno.',
            ];

        // "sep=;" en la primera línea: Excel la usa para separar en columnas
        // (sin esto, según la configuración regional, mete todo en la columna A).
        $csv = "\xEF\xBB\xBF" . "sep=;\r\n"
            . implode(';', $columns) . "\r\n"
            . implode(';', $example) . "\r\n";

        return $csv;
    }
}
