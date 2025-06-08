<?php

declare(strict_types=1);

namespace AhkBin;

class AhkUtils
{
    static function strip($input, $max = 10)
    {
        return substr(preg_replace("/[^A-Fa-f0-9]/", "", $input), 0, $max);
    }
}
