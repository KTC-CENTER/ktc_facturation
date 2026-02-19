<?php

namespace App\Service;

class NumberToWordsService
{
    private array $units = [
        '', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
        'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'
    ];

    private array $tens = [
        '', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'
    ];

    public function convert(float $number, string $currency = 'FCFA'): string
    {
        if ($number == 0) {
            return 'zéro ' . $currency;
        }

        $number = abs($number);
        $intPart = (int) floor($number);
        $decPart = (int) round(($number - $intPart) * 100);

        $result = $this->convertToWords($intPart);

        if ($decPart > 0) {
            $result .= ' ' . $currency . ' et ' . $this->convertToWords($decPart) . ' centimes';
        } else {
            $result .= ' ' . $currency;
        }

        return ucfirst($result);
    }

    private function convertToWords(int $number): string
    {
        if ($number < 20) {
            return $this->units[$number];
        }

        if ($number < 100) {
            return $this->convertTens($number);
        }

        if ($number < 1000) {
            return $this->convertHundreds($number);
        }

        if ($number < 1000000) {
            return $this->convertThousands($number);
        }

        if ($number < 1000000000) {
            return $this->convertMillions($number);
        }

        return $this->convertBillions($number);
    }

    private function convertTens(int $number): string
    {
        $ten = (int) floor($number / 10);
        $unit = $number % 10;

        // Cas spéciaux pour 70-79 et 90-99
        if ($ten == 7 || $ten == 9) {
            $unit += 10;
        }

        $result = $this->tens[$ten];

        if ($unit == 0) {
            // 80 -> quatre-vingts (avec s)
            if ($ten == 8) {
                $result .= 's';
            }
            return $result;
        }

        // Liaison avec "et" pour 21, 31, 41, 51, 61, 71
        if ($unit == 1 && $ten != 8 && $ten != 9) {
            $result .= ' et ';
        } elseif ($ten == 7 && $unit == 11) {
            $result .= ' et ';
        } else {
            $result .= '-';
        }

        $result .= $this->units[$unit];

        return $result;
    }

    private function convertHundreds(int $number): string
    {
        $hundred = (int) floor($number / 100);
        $rest = $number % 100;

        $result = '';

        if ($hundred == 1) {
            $result = 'cent';
        } else {
            $result = $this->units[$hundred] . ' cent';
            // Accord de "cents" si pas de suite
            if ($rest == 0) {
                $result .= 's';
            }
        }

        if ($rest > 0) {
            $result .= ' ' . $this->convertToWords($rest);
        }

        return $result;
    }

    private function convertThousands(int $number): string
    {
        $thousand = (int) floor($number / 1000);
        $rest = $number % 1000;

        $result = '';

        if ($thousand == 1) {
            $result = 'mille';
        } else {
            $result = $this->convertToWords($thousand) . ' mille';
        }

        if ($rest > 0) {
            $result .= ' ' . $this->convertToWords($rest);
        }

        return $result;
    }

    private function convertMillions(int $number): string
    {
        $million = (int) floor($number / 1000000);
        $rest = $number % 1000000;

        $result = $this->convertToWords($million) . ' million';
        if ($million > 1) {
            $result .= 's';
        }

        if ($rest > 0) {
            $result .= ' ' . $this->convertToWords($rest);
        }

        return $result;
    }

    private function convertBillions(int $number): string
    {
        $billion = (int) floor($number / 1000000000);
        $rest = $number % 1000000000;

        $result = $this->convertToWords($billion) . ' milliard';
        if ($billion > 1) {
            $result .= 's';
        }

        if ($rest > 0) {
            $result .= ' ' . $this->convertToWords($rest);
        }

        return $result;
    }
}
