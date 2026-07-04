<?php

function snake_case(string $string): string
{
    $snake_cased = [];
    $skip        = [' ', '-', '_', '.', '/', '\\', '|', ':', ';', '@', '&', '=', '+', '$', '#', '!', '?', '*', '^', '~', '`', '(', ')', '[', ']', '{', '}', '<', '>', ','];
    $i           = 0;

    while ($i < strlen($string)) {
        $last      = (count($snake_cased) > 0) ? $snake_cased[count($snake_cased) - 1] : null;
        $character = $string[$i++];
        if (ctype_upper($character)) {
            if ($last != '_') {
                $snake_cased[] = '_';
            }
            $snake_cased[] = strtolower($character);
        } elseif (ctype_lower($character)) {
            $snake_cased[] = $character;
        } elseif (in_array($character, $skip)) {
            if ($last != '_') {
                $snake_cased[] = '_';
            }
            while ($i < strlen($string) && in_array($string[$i], $skip)) {
                $i++;
            }
        }
    }

    if ($snake_cased[0] == '_') {
        $snake_cased[0] = '';
    }
    if ($snake_cased[count($snake_cased) - 1] == '_') {
        $snake_cased[count($snake_cased) - 1] = '';
    }
    return implode($snake_cased);
}
function uuid2bin($uuid)
{
    return hex2bin(str_replace('-', '', $uuid));
}

function bin2uuid($value)
{
    $string = bin2hex($value);

    return preg_replace('/([0-9a-f]{8})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{12})/', '$1-$2-$3-$4-$5', $string);
}

function base64_url_encode($input)
{
    return strtr(base64_encode($input), '+/=', '._-');
}

function base64_url_decode($input)
{
    return base64_decode(strtr($input, '._-', '+/='));
}

function string_contains(string | array | null $haystack = null, ?array $needles = null, bool $returnNeedle = false): bool | string
{
    if ($haystack === null || $needles === null || empty($needles)) {
        return false;
    }

    if (is_array($haystack)) {
        foreach ($haystack as $value) {
            if (! is_string($value) && ! is_array($value)) {
                $value = (string) $value;
            }

            $found = string_contains($value, $needles, $returnNeedle);

            if ($found !== false) {
                return $found;
            }
        }

        return false;
    }

    foreach ($needles as $needle) {
        $needle = (string) $needle;

        if ($needle === '') {
            continue;
        }

        if (strpos($haystack, $needle) !== false) {
            return $returnNeedle ? $needle : true;
        }
    }

    return false;
}
