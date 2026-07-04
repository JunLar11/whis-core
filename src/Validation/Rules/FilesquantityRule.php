<?php

namespace Whis\Validation\Rules;

use Whis\Storage\File;

class FilesquantityRule implements ValidationRule
{
    private ?int $min;
    private ?int $max;

    public function __construct(string|int|null $min = null, string|int|null $max = null)
    {
        $this->min = $this->parseLimit($min);
        $this->max = $this->parseLimit($max);
    }

    public function message(): string
    {
        if ($this->min !== null && $this->max !== null) {
            return "The file quantity must be between {$this->min} and {$this->max}.";
        }

        if ($this->min !== null) {
            return "The file quantity must be at least {$this->min}.";
        }

        if ($this->max !== null) {
            return "The file quantity must be at most {$this->max}.";
        }

        return "The file quantity is invalid.";
    }

    public function isValid($field, $data): bool
    {
        if (!array_key_exists($field, $data)) {
            return $this->min === null;
        }

        $count = $this->countFiles($data[$field]);

        if ($this->min !== null && $count < $this->min) {
            return false;
        }

        if ($this->max !== null && $count > $this->max) {
            return false;
        }

        return true;
    }

    private function countFiles(mixed $value): int
    {
        if ($value === null || $value === '' || $value === []) {
            return 0;
        }

        if ($value instanceof File) {
            return $this->shouldCountFile($value) ? 1 : 0;
        }

        if (is_array($value)) {
            $count = 0;

            foreach ($value as $item) {
                if ($item instanceof File) {
                    if ($this->shouldCountFile($item)) {
                        $count++;
                    }

                    continue;
                }

                if (is_array($item)) {
                    $count += $this->countFiles($item);
                    continue;
                }

                if (is_string($item) && trim($item) !== '') {
                    $count++;
                }
            }

            return $count;
        }

        return 0;
    }

    private function shouldCountFile(File $file): bool
    {
        /*
         * Si no se seleccionó archivo, no cuenta.
         * Si hubo otro error de subida, sí cuenta como archivo seleccionado;
         * luego filesize/filetype/upload rules pueden marcarlo inválido.
         */
        if (method_exists($file, 'error')) {
            return $file->error() !== UPLOAD_ERR_NO_FILE;
        }

        return true;
    }

    private function parseLimit(string|int|null $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || strtolower($value) === 'null' || $value === '*') {
            return null;
        }

        return max(0, (int) $value);
    }
}