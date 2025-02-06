<?php

/**
 * @author Aaron Francis <aaron@tryhardstudios.com>
 *
 * @link https://aaronfrancis.com
 * @link https://x.com/aarondfrancis
 */

namespace SoloTerm\Solo\Support;

use Exception;

class BaseConverter
{
    public static string $characters = '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~';

    public static function toString(int $integer)
    {
        if ($integer < 0) {
            throw new Exception('Invalid input. Integer must be non-negative.');
        }

        $result = '';
        $base = strlen(static::$characters);

        while (true) {
            $whole = (int) ($integer / $base);

            if ($whole >= 1) {
                $result .= static::$characters[$whole];
                $integer -= $whole * $base;
            } else {
                $result .= static::$characters[$integer];
                break;
            }
        }

        return $result;
    }

    public static function toInt(string $string)
    {
        $string = strrev($string);
        $base = strlen(static::$characters);
        $int = 0;
        foreach (str_split($string) as $mult => $char) {
            $value = strpos(static::$characters, $char);

            if ($mult > 0) {
                $value *= ($mult * $base);
            }

            $int += $value;
        }

        return $int;
    }
}
