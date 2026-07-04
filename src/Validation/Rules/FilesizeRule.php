<?php

namespace Whis\Validation\Rules;

use Whis\Storage\File;

class FilesizeRule implements ValidationRule
{
    private int $lessThanBytes;

    public function __construct(string|int|float $lessThan)
    {
        $this->lessThanBytes = $this->toBytes($lessThan);
    }

    public function message(): string
    {
        return "The filesize must be less than {$this->lessThanBytes} bytes.";
    }

    public function isValid($field, $data): bool
    {
        if (!array_key_exists($field, $data)) {
            return true;
        }

        $value = $data[$field];

        if ($value === null || $value === '' || $value === []) {
            return true;
        }

        return $this->isValidValue($value);
    }

    private function isValidValue(mixed $value): bool
    {
        if ($value instanceof File) {
            return $this->isValidFile($value);
        }

        if (is_array($value)) {
            /*
             * Caso tipo $_FILES individual:
             * ['size' => 12345]
             */
            if (isset($value['size']) && is_numeric($value['size'])) {
                return (int) $value['size'] <= $this->lessThanBytes;
            }

            /*
             * Caso array de archivos:
             * [File, File, File]
             */
            foreach ($value as $item) {
                if (!$this->isValidValue($item)) {
                    return false;
                }
            }

            return true;
        }

        if (is_numeric($value)) {
            return (int) $value <= $this->lessThanBytes;
        }

        return false;
    }

    private function isValidFile(File $file): bool
    {
        /*
         * Si PHP reportó error de subida, lo marcamos inválido.
         * Ejemplo: UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_PARTIAL, etc.
         */
        if (method_exists($file, 'hasUploadError') && $file->hasUploadError()) {
            return false;
        }

        return $file->size() <= $this->lessThanBytes;
    }

    private function toBytes(string|int|float $value): int
    {
        if (is_int($value) || is_float($value)) {
            return (int) $value;
        }

        $value = trim(strtolower($value));

        if (preg_match('/^(\d+(?:\.\d+)?)(kb|k|mb|m|gb|g)?$/', $value, $matches) !== 1) {
            return (int) $value;
        }

        $number = (float) $matches[1];
        $unit = $matches[2] ?? '';

        return match ($unit) {
            'g', 'gb' => (int) ($number * 1024 * 1024 * 1024),
            'm', 'mb' => (int) ($number * 1024 * 1024),
            'k', 'kb' => (int) ($number * 1024),
            default => (int) $number,
        };
    }
}