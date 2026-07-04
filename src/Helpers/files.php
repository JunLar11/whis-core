<?php

function return_bytes(?string $val = null): int
{
    if ($val === null || trim($val) === '') {
        return 0;
    }

    $val    = trim($val);
    $last   = strtolower(substr($val, -1));
    $number = (float) $val;

    return match ($last) {
        'g'     => (int) ($number * 1024 * 1024 * 1024),
        'm'     => (int) ($number * 1024 * 1024),
        'k'     => (int) ($number * 1024),
        default => (int) $number,
    };
}
