<?php
/**
 * ARCHIVO: core/Validator.php
 * ---------------------------------------------------------------------
 * Validación del lado del servidor. La validación de JavaScript es sólo
 * una comodidad para el usuario: la que decide es ésta.
 *
 * Reglas soportadas:
 *   required, optional, string, email, url, numeric, integer, decimal,
 *   boolean, date, min:n, max:n, between:a,b, in:a,b,c, regex:/.../,
 *   unique:tabla,columna[,idIgnorado], exists:tabla,columna,
 *   confirmed, same:campo, different:campo, slug, phone
 */

declare(strict_types=1);

namespace Core;

final class Validator
{
    /** @var array<string,mixed> */
    private array $data;
    /** @var array<string,string> */
    private array $rules;
    /** @var array<string,string> */
    private array $labels;
    /** @var array<string,string> */
    private array $errors = [];
    /** @var array<string,mixed> */
    private array $validated = [];

    /**
     * @param array<string,mixed>  $data
     * @param array<string,string> $rules
     * @param array<string,string> $labels
     */
    public function __construct(array $data, array $rules, array $labels = [])
    {
        $this->data   = $data;
        $this->rules  = $rules;
        $this->labels = $labels;
        $this->run();
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<string,mixed> */
    public function validated(): array
    {
        return $this->validated;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $rules = array_filter(array_map('trim', explode('|', $ruleString)));

            $required = in_array('required', $rules, true);
            $isEmpty  = $value === null || $value === '' || (is_array($value) && $value === []);

            if ($isEmpty) {
                if ($required) {
                    $this->addError($field, 'El campo %s es obligatorio.');
                    continue;
                }
                // Campo opcional vacío: se guarda como null y no se sigue validando
                $this->validated[$field] = $value === '' ? null : $value;
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'required' || $rule === 'optional') {
                    continue;
                }

                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

                if (!$this->applyRule((string) $name, $field, $value, $parameter)) {
                    break; // un error por campo alcanza
                }
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    private function applyRule(string $rule, string $field, mixed $value, ?string $parameter): bool
    {
        switch ($rule) {
            case 'string':
                if (!is_string($value)) {
                    return $this->addError($field, 'El campo %s debe ser texto.');
                }
                break;

            case 'email':
                if (!filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                    return $this->addError($field, 'El campo %s debe ser un email válido.');
                }
                break;

            case 'url':
                if (!filter_var((string) $value, FILTER_VALIDATE_URL)) {
                    return $this->addError($field, 'El campo %s debe ser una URL válida.');
                }
                break;

            case 'numeric':
            case 'decimal':
                if (!is_numeric((string) $value)) {
                    return $this->addError($field, 'El campo %s debe ser numérico.');
                }
                break;

            case 'integer':
                if (filter_var((string) $value, FILTER_VALIDATE_INT) === false) {
                    return $this->addError($field, 'El campo %s debe ser un número entero.');
                }
                break;

            case 'boolean':
                if (!in_array((string) $value, ['0', '1', 'true', 'false', 'on', 'off'], true)) {
                    return $this->addError($field, 'El campo %s debe ser verdadero o falso.');
                }
                break;

            case 'date':
                if (strtotime((string) $value) === false) {
                    return $this->addError($field, 'El campo %s debe ser una fecha válida.');
                }
                break;

            case 'min':
                $min = (float) $parameter;
                if (is_numeric((string) $value) && !is_string($value)) {
                    if ((float) $value < $min) {
                        return $this->addError($field, 'El campo %s debe ser mayor o igual a ' . $parameter . '.');
                    }
                } elseif (mb_strlen((string) $value) < $min) {
                    return $this->addError($field, 'El campo %s debe tener al menos ' . $parameter . ' caracteres.');
                }
                break;

            case 'max':
                $max = (float) $parameter;
                if (is_numeric((string) $value) && !is_string($value)) {
                    if ((float) $value > $max) {
                        return $this->addError($field, 'El campo %s debe ser menor o igual a ' . $parameter . '.');
                    }
                } elseif (mb_strlen((string) $value) > $max) {
                    return $this->addError($field, 'El campo %s no puede superar los ' . $parameter . ' caracteres.');
                }
                break;

            case 'between':
                [$min, $max] = array_pad(explode(',', (string) $parameter), 2, '0');
                $number = (float) $value;
                if ($number < (float) $min || $number > (float) $max) {
                    return $this->addError($field, 'El campo %s debe estar entre ' . $min . ' y ' . $max . '.');
                }
                break;

            case 'in':
                $options = explode(',', (string) $parameter);
                if (!in_array((string) $value, $options, true)) {
                    return $this->addError($field, 'El valor de %s no es válido.');
                }
                break;

            case 'regex':
                if (!preg_match((string) $parameter, (string) $value)) {
                    return $this->addError($field, 'El formato del campo %s no es válido.');
                }
                break;

            case 'slug':
                if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value)) {
                    return $this->addError($field, 'El campo %s sólo admite minúsculas, números y guiones.');
                }
                break;

            case 'phone':
                if (!preg_match('/^[0-9+()\s.-]{6,25}$/', (string) $value)) {
                    return $this->addError($field, 'El campo %s no parece un teléfono válido.');
                }
                break;

            case 'confirmed':
                if (($this->data[$field . '_confirmation'] ?? null) !== $value) {
                    return $this->addError($field, 'La confirmación de %s no coincide.');
                }
                break;

            case 'same':
                if (($this->data[(string) $parameter] ?? null) !== $value) {
                    return $this->addError($field, 'El campo %s no coincide.');
                }
                break;

            case 'different':
                if (($this->data[(string) $parameter] ?? null) === $value) {
                    return $this->addError($field, 'El campo %s debe ser distinto.');
                }
                break;

            case 'unique':
                [$table, $column, $ignoreId] = array_pad(explode(',', (string) $parameter), 3, null);
                if (!$this->safeIdentifier((string) $table) || !$this->safeIdentifier((string) $column)) {
                    return $this->addError($field, 'Validación mal configurada para %s.');
                }
                $sql    = sprintf('SELECT COUNT(*) FROM `%s` WHERE `%s` = :value', $table, $column);
                $params = ['value' => $value];
                if ($ignoreId !== null && $ignoreId !== '') {
                    $sql .= ' AND id <> :ignore';
                    $params['ignore'] = (int) $ignoreId;
                }
                if ((int) Database::scalar($sql, $params) > 0) {
                    return $this->addError($field, 'Ya existe un registro con ese %s.');
                }
                break;

            case 'exists':
                [$table, $column] = array_pad(explode(',', (string) $parameter), 2, 'id');
                if (!$this->safeIdentifier((string) $table) || !$this->safeIdentifier((string) $column)) {
                    return $this->addError($field, 'Validación mal configurada para %s.');
                }
                $found = (int) Database::scalar(
                    sprintf('SELECT COUNT(*) FROM `%s` WHERE `%s` = :value', $table, $column),
                    ['value' => $value]
                );
                if ($found === 0) {
                    return $this->addError($field, 'El valor seleccionado en %s no existe.');
                }
                break;
        }

        return true;
    }

    private function safeIdentifier(string $identifier): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $identifier);
    }

    private function addError(string $field, string $template): bool
    {
        if (!isset($this->errors[$field])) {
            $label = $this->labels[$field] ?? str_replace('_', ' ', $field);
            $this->errors[$field] = sprintf($template, '"' . $label . '"');
        }
        return false;
    }
}
