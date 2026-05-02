<?php

namespace App\Support;

class DocumentNumber
{
    public static function generate(string $prefix): string
    {
        return sprintf(
            '%s-%s-%s',
            strtoupper($prefix),
            now()->format('YmdHis'),
            strtoupper(bin2hex(random_bytes(3)))
        );
    }
}
