<?php
declare(strict_types=1);

namespace App\Core;

class I18n
{
    private static string $locale = 'en';
    private static array $strings = [];
    private static bool $loaded = false;

    public static function init(): void
    {
        if (self::$loaded) {
            return;
        }

        Session::start();
        $storedLocale = Session::get('locale');
        if ($storedLocale && in_array($storedLocale, ['en', 'bn'], true)) {
            self::$locale = $storedLocale;
        } else {
            self::$locale = (string) Config::get('app.locale', 'en');
        }

        self::loadStrings();
        self::$loaded = true;
    }

    public static function setLocale(string $locale): void
    {
        if (in_array($locale, ['en', 'bn'], true)) {
            self::$locale = $locale;
            Session::set('locale', $locale);
            self::loadStrings();
        }
    }

    public static function getLocale(): string
    {
        self::init();
        return self::$locale;
    }

    private static function loadStrings(): void
    {
        $langFile = dirname(__DIR__) . '/Lang/' . self::$locale . '.php';
        if (file_exists($langFile)) {
            self::$strings = require $langFile;
        } else {
            self::$strings = [];
        }
    }

    public static function get(string $key, array $replace = []): string
    {
        self::init();

        $text = self::$strings[$key] ?? $key;

        foreach ($replace as $placeholder => $value) {
            $text = str_replace(':' . $placeholder, (string)$value, $text);
        }

        return $text;
    }

    /**
     * Converts ASCII digits 0-9 to Bangla digits ০-৯
     */
    public static function toBanglaDigits(string|int|float $input): string
    {
        $banglaDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($englishDigits, $banglaDigits, (string)$input);
    }

    /**
     * Format Indian / South Asian numbering system (lakh, crore)
     * e.g., 150000.50 -> 1,50,000.50
     */
    public static function formatIndianNumber(float|int $amount, int $decimals = 2): string
    {
        $isNegative = $amount < 0;
        $amount = abs($amount);
        $formatted = number_format($amount, $decimals, '.', '');
        $parts = explode('.', $formatted);
        $num = $parts[0];
        $dec = isset($parts[1]) ? '.' . $parts[1] : '';

        if (strlen($num) > 3) {
            $lastThree = substr($num, -3);
            $rest = substr($num, 0, -3);
            $restChunks = [];
            while (strlen($rest) > 2) {
                array_unshift($restChunks, substr($rest, -2));
                $rest = substr($rest, 0, -2);
            }
            if (strlen($rest) > 0) {
                array_unshift($restChunks, $rest);
            }
            $num = implode(',', $restChunks) . ',' . $lastThree;
        }

        return ($isNegative ? '-' : '') . $num . $dec;
    }

    /**
     * Format money stored in minor units (paisa)
     * 100 paisa = 1 BDT
     */
    public static function formatMoney(
        int|float $amountInMinorUnits,
        string $currency = 'BDT',
        ?string $format = null,
        ?bool $useBanglaDigits = null
    ): string {
        self::init();

        $format = $format ?? Config::get('app.number_format', 'indian');
        $useBangla = $useBanglaDigits ?? (self::$locale === 'bn');

        $majorUnits = $amountInMinorUnits / 100.0;

        if ($format === 'indian') {
            $formattedNumber = self::formatIndianNumber($majorUnits, 2);
        } else {
            $formattedNumber = number_format($majorUnits, 2, '.', ',');
        }

        if ($useBangla) {
            $formattedNumber = self::toBanglaDigits($formattedNumber);
            $symbol = ($currency === 'BDT') ? '৳' : $currency;
            return $symbol . ' ' . $formattedNumber;
        }

        $symbol = ($currency === 'BDT') ? '৳' : $currency;
        return $symbol . ' ' . $formattedNumber;
    }
}
