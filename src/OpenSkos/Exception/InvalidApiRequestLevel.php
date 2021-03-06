<?php

declare(strict_types=1);

namespace App\OpenSkos\Exception;

final class InvalidApiRequestLevel extends \InvalidArgumentException
{
    public function __construct(int $level)
    {
        parent::__construct("Invalid level $level. Valid range is 1 to 4");
    }
}
