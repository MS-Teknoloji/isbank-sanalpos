<?php

namespace GrisePet\IsbankSanalpos\Models;

class Currency
{
    public const TRY = 'TRY';
    public const TL = 'TL';
    public const USD = 'USD';
    public const EUR = 'EUR';
    public const GBP = 'GBP';
    public const JPY = 'JPY';
    public const RUB = 'RUB';

    public const CODE_TRY = '949';
    public const CODE_USD = '840';
    public const CODE_EUR = '978';
    public const CODE_GBP = '826';
    public const CODE_JPY = '392';
    public const CODE_RUB = '643';

    public static function getNumericCode(string $currency): string
    {
        return match (strtoupper($currency)) {
            self::TRY, self::TL => self::CODE_TRY,
            self::USD => self::CODE_USD,
            self::EUR => self::CODE_EUR,
            self::GBP => self::CODE_GBP,
            self::JPY => self::CODE_JPY,
            self::RUB => self::CODE_RUB,
            default => self::CODE_TRY,
        };
    }
}
