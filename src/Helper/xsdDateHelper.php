<?php

declare(strict_types=1);

namespace App\Helper;

final class xsdDateHelper
{
    public function isValidXsdDateTime(string $value): bool
    {
        $result = preg_match('/^-?(?!0{4})(0\d{3}|[1-9]\d{3,})-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d*[1-9])?([-+]\d{2}.\d{2}|Z|)$/', $value);

        return (bool) $result;
    }
}
