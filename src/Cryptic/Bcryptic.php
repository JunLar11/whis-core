<?php

namespace Whis\Cryptic;

class Bcryptic implements Hasher
{
    public function hash(string $input): string
    {
        return password_hash($input, PASSWORD_BCRYPT);
    }

    public function verify(string $input, string $hashed): bool
    {
        return password_verify($input, $hashed);
    }
}
