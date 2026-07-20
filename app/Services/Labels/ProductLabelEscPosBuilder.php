<?php

namespace App\Services\Labels;

use App\Models\Product;
use InvalidArgumentException;

class ProductLabelEscPosBuilder
{
    /**
     * Misma impresora que SaleTicketEscPosBuilder (Inkspire, clon OCPP-58H):
     * 58 mm de papel, ~384 puntos de ancho. El firmware del clon deforma
     * los códigos nativos "GS k" (salen rayados / ilegibles), así que el
     * símbolo se dibuja en software y se manda como bitmap "GS v 0".
     */
    private const WIDTH = 32;

    private const BOLD = 8;

    private const BOLD_TALL = 24;

    private const ESC = "\x1B";

    private const GS = "\x1D";

    /** Ancho máximo del bitmap del código (puntos). Deja márgenes en 384. */
    private const BARCODE_MAX_DOTS = 360;

    /** Alto del símbolo en puntos. */
    private const BARCODE_HEIGHT = 72;

    /** Puntos por módulo del código (grosor de cada barra). */
    private const MODULE_DOTS = 2;

    private const CODE39_CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. $/+%';

    /**
     * Patrones CODE39 (9 bits: 1=barra ancha/espacio ancho, 0=angosto).
     * Formato: bwbwbwbwb (barra-espacio alternado).
     */
    private const CODE39_PATTERNS = [
        '0' => '000110100', '1' => '100100001', '2' => '001100001', '3' => '101100000',
        '4' => '000110001', '5' => '100110000', '6' => '001110000', '7' => '000100101',
        '8' => '100100100', '9' => '001100100', 'A' => '100001001', 'B' => '001001001',
        'C' => '101001000', 'D' => '000011001', 'E' => '100011000', 'F' => '001011000',
        'G' => '000001101', 'H' => '100001100', 'I' => '001001100', 'J' => '000011100',
        'K' => '100000011', 'L' => '001000011', 'M' => '101000010', 'N' => '000010011',
        'O' => '100010010', 'P' => '001010010', 'Q' => '000000111', 'R' => '100000110',
        'S' => '001000110', 'T' => '000010110', 'U' => '110000001', 'V' => '011000001',
        'W' => '111000000', 'X' => '010010001', 'Y' => '110010000', 'Z' => '011010000',
        '-' => '010000101', '.' => '110000100', ' ' => '011000100', '$' => '010101000',
        '/' => '010100010', '+' => '010001010', '%' => '000101010', '*' => '010010100',
    ];

    /** EAN left-hand odd (A) encodings. */
    private const EAN_LEFT_A = [
        '0' => '0001101', '1' => '0011001', '2' => '0010011', '3' => '0111101',
        '4' => '0100011', '5' => '0110001', '6' => '0101111', '7' => '0111011',
        '8' => '0110111', '9' => '0001011',
    ];

    /** EAN left-hand even (G) encodings. */
    private const EAN_LEFT_G = [
        '0' => '0100111', '1' => '0110011', '2' => '0011011', '3' => '0100001',
        '4' => '0011101', '5' => '0111001', '6' => '0000101', '7' => '0010001',
        '8' => '0001001', '9' => '0010111',
    ];

    /** EAN right-hand (C) encodings. */
    private const EAN_RIGHT = [
        '0' => '1110010', '1' => '1100110', '2' => '1101100', '3' => '1000010',
        '4' => '1011100', '5' => '1001110', '6' => '1010000', '7' => '1000100',
        '8' => '1001000', '9' => '1110100',
    ];

    /** Primer dígito EAN-13 → paridad de los 6 dígitos izquierdos (A=impar, G=par). */
    private const EAN13_PARITY = [
        '0' => 'AAAAAA', '1' => 'AABABB', '2' => 'AABBAB', '3' => 'AABBBA',
        '4' => 'ABAABB', '5' => 'ABBAAB', '6' => 'ABBBAA', '7' => 'ABABAB',
        '8' => 'ABABBA', '9' => 'ABBABA',
    ];

    public function build(Product $product, int $copies = 1): string
    {
        if (blank($product->barcode)) {
            throw new InvalidArgumentException("El producto \"{$product->name}\" no tiene código de barras asignado.");
        }

        $copies = max(1, $copies);
        $label = $this->buildOne($product);

        return str_repeat($label, $copies);
    }

    /**
     * @param  iterable<array{0: Product, 1: int}>  $items
     */
    public function buildMany(iterable $items): string
    {
        $out = '';

        foreach ($items as [$product, $copies]) {
            $out .= $this->build($product, $copies);
        }

        return $out;
    }

    private function buildOne(Product $product): string
    {
        $rawCode = trim((string) $product->barcode);
        $price = number_format((float) $product->sale_price, 2, ',', '.');

        $label = self::ESC.'@';

        $label .= self::ESC.'!'.chr(self::BOLD);
        foreach ($this->wrap($this->sanitizeText($product->name)) as $line) {
            $label .= $line."\n";
        }
        $label .= self::ESC.'!'.chr(0);

        $label .= self::ESC.'!'.chr(self::BOLD_TALL);
        $label .= $this->centered('$ '.$price);
        $label .= self::ESC.'!'.chr(0);

        $label .= "\n";
        $label .= $this->barcodeBitmap($rawCode);
        $label .= $this->centered($this->sanitizeText($rawCode));
        $label .= "\n";

        $label .= self::GS.'V'.chr(0);

        return $label;
    }

    /**
     * Dibuja el código como bitmap monocromo (GS v 0). Evita el comando
     * nativo GS k, que en este firmware sale rayado / ilegible.
     */
    private function barcodeBitmap(string $rawCode): string
    {
        $modules = $this->modulesFor($rawCode);
        $rowBits = $this->modulesToRow($modules);
        $widthDots = count($rowBits);
        $widthBytes = (int) ceil($widthDots / 8);
        $height = self::BARCODE_HEIGHT;

        // Centrar el bitmap en el ancho útil del papel (~384 dots).
        $paperDots = 384;
        $leftPadDots = max(0, intdiv($paperDots - $widthDots, 2));
        $totalDots = $leftPadDots + $widthDots;
        $totalBytes = (int) ceil($totalDots / 8);

        $row = array_fill(0, $totalBytes * 8, 0);
        for ($i = 0; $i < $widthDots; $i++) {
            $row[$leftPadDots + $i] = $rowBits[$i];
        }

        $rowBytes = '';
        for ($b = 0; $b < $totalBytes; $b++) {
            $byte = 0;
            for ($bit = 0; $bit < 8; $bit++) {
                if ($row[$b * 8 + $bit]) {
                    $byte |= 0x80 >> $bit;
                }
            }
            $rowBytes .= chr($byte);
        }

        // GS v 0 m xL xH yL yH [data]
        $out = self::GS.'v0'.chr(0);
        $out .= chr($totalBytes & 0xFF).chr(($totalBytes >> 8) & 0xFF);
        $out .= chr($height & 0xFF).chr(($height >> 8) & 0xFF);
        $out .= str_repeat($rowBytes, $height);

        return $out."\n";
    }

    /**
     * @return list<int> 1 = barra negra, 0 = espacio blanco (módulos lógicos)
     */
    private function modulesFor(string $rawCode): array
    {
        $digits = preg_replace('/\D+/', '', $rawCode) ?? '';

        if (strlen($digits) === 13 && $this->isValidEan13($digits)) {
            return $this->ean13Modules($digits);
        }

        if (strlen($digits) === 12) {
            return $this->ean13Modules($digits.$this->ean13CheckDigit($digits));
        }

        if (strlen($digits) === 8 && ctype_digit($digits)) {
            return $this->ean8Modules($digits);
        }

        return $this->code39Modules($this->sanitizeCode39($rawCode));
    }

    /**
     * @param  list<int>  $modules
     * @return list<int>
     */
    private function modulesToRow(array $modules): array
    {
        $quiet = 10; // módulos de silencio a cada lado
        $scaled = [];

        for ($i = 0; $i < $quiet; $i++) {
            for ($d = 0; $d < self::MODULE_DOTS; $d++) {
                $scaled[] = 0;
            }
        }

        foreach ($modules as $module) {
            for ($d = 0; $d < self::MODULE_DOTS; $d++) {
                $scaled[] = $module ? 1 : 0;
            }
        }

        for ($i = 0; $i < $quiet; $i++) {
            for ($d = 0; $d < self::MODULE_DOTS; $d++) {
                $scaled[] = 0;
            }
        }

        // Si aún no entra, achicar módulos a 1 punto.
        if (count($scaled) > self::BARCODE_MAX_DOTS && self::MODULE_DOTS > 1) {
            $scaled = [];
            for ($i = 0; $i < $quiet; $i++) {
                $scaled[] = 0;
            }
            foreach ($modules as $module) {
                $scaled[] = $module ? 1 : 0;
            }
            for ($i = 0; $i < $quiet; $i++) {
                $scaled[] = 0;
            }
        }

        return $scaled;
    }

    /**
     * @return list<int>
     */
    private function ean13Modules(string $code): array
    {
        $first = $code[0];
        $parity = self::EAN13_PARITY[$first];
        $left = substr($code, 1, 6);
        $right = substr($code, 7, 6);

        $modules = $this->bitsToModules('101'); // guard

        for ($i = 0; $i < 6; $i++) {
            $digit = $left[$i];
            $pattern = $parity[$i] === 'A'
                ? self::EAN_LEFT_A[$digit]
                : self::EAN_LEFT_G[$digit];
            $modules = array_merge($modules, $this->bitsToModules($pattern));
        }

        $modules = array_merge($modules, $this->bitsToModules('01010')); // center

        for ($i = 0; $i < 6; $i++) {
            $modules = array_merge($modules, $this->bitsToModules(self::EAN_RIGHT[$right[$i]]));
        }

        return array_merge($modules, $this->bitsToModules('101'));
    }

    /**
     * @return list<int>
     */
    private function ean8Modules(string $code): array
    {
        $left = substr($code, 0, 4);
        $right = substr($code, 4, 4);

        $modules = $this->bitsToModules('101');

        for ($i = 0; $i < 4; $i++) {
            $modules = array_merge($modules, $this->bitsToModules(self::EAN_LEFT_A[$left[$i]]));
        }

        $modules = array_merge($modules, $this->bitsToModules('01010'));

        for ($i = 0; $i < 4; $i++) {
            $modules = array_merge($modules, $this->bitsToModules(self::EAN_RIGHT[$right[$i]]));
        }

        return array_merge($modules, $this->bitsToModules('101'));
    }

    /**
     * @return list<int>
     */
    private function code39Modules(string $code): array
    {
        $payload = '*'.$code.'*';
        $modules = [];

        $chars = str_split($payload);
        foreach ($chars as $index => $char) {
            $pattern = self::CODE39_PATTERNS[$char] ?? null;
            if ($pattern === null) {
                continue;
            }

            // CODE39: 9 elementos barra/espacio; anchos 1 o 3 módulos.
            for ($i = 0; $i < 9; $i++) {
                $isBar = $i % 2 === 0;
                $wide = $pattern[$i] === '1';
                $width = $wide ? 3 : 1;
                for ($w = 0; $w < $width; $w++) {
                    $modules[] = $isBar ? 1 : 0;
                }
            }

            // Separador inter-caracter (1 módulo blanco), salvo al final.
            if ($index < count($chars) - 1) {
                $modules[] = 0;
            }
        }

        return $modules;
    }

    /**
     * @return list<int>
     */
    private function bitsToModules(string $bits): array
    {
        $modules = [];
        $len = strlen($bits);
        for ($i = 0; $i < $len; $i++) {
            $modules[] = $bits[$i] === '1' ? 1 : 0;
        }

        return $modules;
    }

    private function isValidEan13(string $code): bool
    {
        if (! ctype_digit($code) || strlen($code) !== 13) {
            return false;
        }

        return $code[12] === $this->ean13CheckDigit(substr($code, 0, 12));
    }

    private function ean13CheckDigit(string $twelve): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $n = (int) $twelve[$i];
            $sum += ($i % 2 === 0) ? $n : $n * 3;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }

    private function sanitizeCode39(string $code): string
    {
        $code = strtoupper($this->sanitizeText($code));
        $filtered = '';

        for ($i = 0; $i < mb_strlen($code); $i++) {
            $char = mb_substr($code, $i, 1);
            if (str_contains(self::CODE39_CHARS, $char)) {
                $filtered .= $char;
            }
        }

        if ($filtered === '') {
            throw new InvalidArgumentException('El código de barras no tiene caracteres válidos para imprimir.');
        }

        // CODE39 es ancho: en 58 mm más de ~12 caracteres se vuelve ilegible.
        if (strlen($filtered) > 12) {
            $filtered = substr($filtered, 0, 12);
        }

        return $filtered;
    }

    private function sanitizeText(string $text): string
    {
        static $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U',
            '¡' => '', '¿' => '',
        ];

        return strtr($text, $map);
    }

    /**
     * @return list<string>
     */
    private function wrap(string $text): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            while (mb_strlen($word) > self::WIDTH) {
                $lines[] = mb_substr($word, 0, self::WIDTH);
                $word = mb_substr($word, self::WIDTH);
            }

            $candidate = $current === '' ? $word : "{$current} {$word}";

            if (mb_strlen($candidate) > self::WIDTH) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines === [] ? [''] : $lines;
    }

    private function centered(string $text): string
    {
        $out = '';

        foreach ($this->wrap($text) as $line) {
            $padding = max(0, intdiv(self::WIDTH - mb_strlen($line), 2));
            $out .= str_repeat(' ', $padding).$line."\n";
        }

        return $out;
    }
}
