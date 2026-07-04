<?php

namespace Whis\Validation\Rules;

use Whis\Storage\File;

class FiletypeRule implements ValidationRule
{
    private array $allowedTypes;

    public function __construct(string|array $filetype)
    {
        if (is_string($filetype)) {
            $filetype = str_replace('|', '/', $filetype);
            $filetype = explode('/', $filetype);
        }

        $this->allowedTypes = array_values(array_filter(array_map(
            fn ($type) => strtolower(trim((string) $type)),
            $filetype
        )));
    }

    public function message(): string
    {
        return "Must be of type " . implode(", ", $this->allowedTypes);
    }

    public function isValid($field, $data): bool
    {
        /*
         * Si el campo no existe o no hay archivo, esta regla no debe fallar.
         * La obligatoriedad la controla required o filesquantity.
         */
        if (!array_key_exists($field, $data)) {
            return true;
        }

        $value = $data[$field];

        if ($value === null || $value === '' || $value === []) {
            return true;
        }

        if ($value instanceof File) {
            return $this->matchesFile($value);
        }

        /*
         * CLAVE:
         * files[] llega como array de File.
         */
        if (is_array($value)) {
            foreach ($value as $file) {
                if ($file instanceof File) {
                    if (!$this->matchesFile($file)) {
                        return false;
                    }

                    continue;
                }

                if (is_string($file)) {
                    if (!$this->matchesString($file)) {
                        return false;
                    }

                    continue;
                }

                /*
                 * Ignoramos valores vacíos.
                 */
                if ($file === null || $file === '') {
                    continue;
                }

                return false;
            }

            return true;
        }

        if (is_string($value)) {
            return $this->matchesString($value);
        }

        return false;
    }

    private function matchesFile(File $file): bool
    {
        /*
         * Si PHP marcó error de subida, que falle.
         * Así otras reglas también pueden reportar el problema.
         */
        if ($file->hasUploadError()) {
            return false;
        }

        $mime = strtolower((string) $file->getAttribute('type'));
        $originalName = strtolower($file->originalName());
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        foreach ($this->allowedTypes as $type) {
            $type = strtolower($type);

            /*
             * Ejemplo:
             * archivo.pdf
             * type permitido: pdf
             */
            if ($type === $extension) {
                return true;
            }

            /*
             * Ejemplo:
             * MIME real: application/pdf
             * type permitido: application/pdf
             */
            if ($type === $mime) {
                return true;
            }

            /*
             * Ejemplo:
             * MIME real: application/pdf
             * type permitido: pdf
             */
            if ($type !== '' && str_contains($mime, $type)) {
                return true;
            }

            if ($type === 'jpg' && $mime === 'image/jpeg') {
                return true;
            }

            if ($type === 'jpeg' && $extension === 'jpg') {
                return true;
            }
        }

        return false;
    }

    private function matchesString(string $value): bool
    {
        $value = strtolower($value);
        $extension = strtolower((string) pathinfo($value, PATHINFO_EXTENSION));

        foreach ($this->allowedTypes as $type) {
            $type = strtolower($type);

            if ($type === $extension) {
                return true;
            }

            if ($type !== '' && str_contains($value, $type)) {
                return true;
            }
        }

        return false;
    }
}