<?php
namespace App\Core;

class Calculator
{
    private $constants = ['pi' => M_PI, 'e' => M_E];

    public function compute($input)
    {
        $expr = $this->sanitize($input);
        
        if (!$expr) {
            return ['ok' => false, 'error' => 'Nothing to calculate'];
        }

        $bracketCheck = $this->checkBrackets($expr);
        if ($bracketCheck !== true) {
            return ['ok' => false, 'error' => $bracketCheck];
        }

        $expr = $this->fixBrackets($expr);
        $expr = $this->insertConstants($expr);
        $expr = $this->handleTrig($expr);
        $expr = $this->prepareExpression($expr);

        if ($expr === 'OVERFLOW') {
            return ['ok' => true, 'value' => '∞'];
        }

        $validation = $this->validateExpr($expr);
        if ($validation !== true) {
            return ['ok' => false, 'error' => $validation];
        }

        return $this->execute($expr);
    }

    private function sanitize($input)
    {
        $s = strtolower(trim($input));
        $s = str_replace(' ', '', $s);
        return preg_replace('/[^0-9+\-*\/\^%().!a-z]/', '', $s);
    }

    private function checkBrackets($expr)
    {
        $open = substr_count($expr, '(');
        $close = substr_count($expr, ')');

        if ($close > $open) {
            return 'Extra ) bracket - remove ' . ($close - $open);
        }
        
        if (strpos($expr, '()') !== false) {
            return 'Empty brackets found';
        }

        if (preg_match('/[+\-*\/]{2,}/', $expr)) {
            return 'Operators next to each other';
        }

        return true;
    }

    private function fixBrackets($expr)
    {
        $open = substr_count($expr, '(');
        $close = substr_count($expr, ')');
        $missing = $open - $close;

        if ($missing > 0 && $this->hasAmbiguity($expr)) {
            return $expr; // let it fail naturally, user needs to fix
        }
        
        return $expr . str_repeat(')', $missing);
    }

    private function hasAmbiguity($expr)
    {
        $depth = 0;
        $len = strlen($expr);
        
        for ($i = 0; $i < $len; $i++) {
            $c = $expr[$i];
            if ($c === '(') $depth++;
            elseif ($c === ')') $depth--;
            elseif ($depth > 0 && ($c === '+' || $c === '-')) {
                if ($i > 0 && (ctype_digit($expr[$i-1]) || $expr[$i-1] === ')')) {
                    return true;
                }
            }
        }
        return false;
    }

    private function insertConstants($expr)
    {
        foreach ($this->constants as $name => $val) {
            $expr = preg_replace('/\b'.$name.'\b/', $val, $expr);
        }
        return $expr;
    }

    private function handleTrig($expr)
    {
        $direct = ['sin', 'cos', 'tan'];
        $inverse = ['asin', 'acos', 'atan'];

        foreach ($direct as $fn) {
            $expr = $this->wrapTrigDegrees($expr, $fn);
        }
        foreach ($inverse as $fn) {
            $expr = $this->wrapTrigInverse($expr, $fn);
        }

        return $expr;
    }

    private function wrapTrigDegrees($expr, $fn)
    {
        $offset = 0;
        while (($pos = strpos($expr, $fn.'(', $offset)) !== false) {
            $start = $pos + strlen($fn);
            $end = $this->findClosingParen($expr, $start);
            if ($end === false) break;

            $inner = substr($expr, $start + 1, $end - $start - 1);
            $replacement = $fn . '(deg2rad(' . $inner . '))';
            $expr = substr($expr, 0, $pos) . $replacement . substr($expr, $end + 1);
            $offset = $pos + strlen($replacement);
        }
        return $expr;
    }

    private function wrapTrigInverse($expr, $fn)
    {
        $offset = 0;
        while (($pos = strpos($expr, $fn.'(', $offset)) !== false) {
            $start = $pos + strlen($fn);
            $end = $this->findClosingParen($expr, $start);
            if ($end === false) break;

            $inner = substr($expr, $start + 1, $end - $start - 1);
            $replacement = 'rad2deg(' . $fn . '(' . $inner . '))';
            $expr = substr($expr, 0, $pos) . $replacement . substr($expr, $end + 1);
            $offset = $pos + strlen($replacement);
        }
        return $expr;
    }

    private function findClosingParen($str, $openPos)
    {
        $depth = 0;
        $len = strlen($str);
        
        for ($i = $openPos; $i < $len; $i++) {
            if ($str[$i] === '(') $depth++;
            elseif ($str[$i] === ')') {
                $depth--;
                if ($depth === 0) return $i;
            }
        }
        return false;
    }

    private function prepareExpression($expr)
    {
        $expr = preg_replace('/\bln\(/', 'log(', $expr);
        $expr = preg_replace('/\blog\(/', 'log10(', $expr);

        // factorial
        while (preg_match('/(\d+)!/', $expr, $m)) {
            $n = (int)$m[1];
            if ($n > 170) return 'OVERFLOW';
            $expr = str_replace($m[0], $this->fact($n), $expr);
        }

        // powers
        $expr = preg_replace_callback('/(\d+\.?\d*)\^(\d+\.?\d*)/', function($m) {
            return 'pow('.$m[1].','.$m[2].')';
        }, $expr);

        // implicit multiplication - but don't break function names like deg2rad
        $expr = preg_replace('/(\d)\(/', '$1*(', $expr);
        $expr = preg_replace('/(\))(\d)/', '$1*$2', $expr);
        $expr = preg_replace('/(\))(\()/', '$1*(', $expr);
        $expr = preg_replace('/(\))(sin|cos|tan|asin|acos|atan|sqrt|log|pow|rad2deg|deg2rad)/', '$1*$2', $expr);

        return $expr;
    }

    private function fact($n)
    {
        if ($n < 0) return 0;
        if ($n < 2) return 1;
        $r = 1;
        for ($i = 2; $i <= $n; $i++) $r *= $i;
        return $r;
    }

    private function validateExpr($expr)
    {
        if (preg_match('/sqrt\s*\(\s*-/', $expr)) {
            return 'Square root of negative not allowed';
        }
        if (preg_match('/\/\s*0([^.0-9]|$)/', $expr)) {
            return 'Division by zero';
        }
        if (preg_match('/log10?\s*\(\s*0\s*\)/', $expr)) {
            return 'Log of zero undefined';
        }
        if (preg_match('/log10?\s*\(\s*-/', $expr)) {
            return 'Log of negative undefined';
        }
        return true;
    }

    private function execute($expr)
    {
        set_error_handler(function($s, $m) {
            throw new \Exception($m);
        });

        try {
            $result = @eval('return ' . $expr . ';');
        } catch (\ParseError $e) {
            restore_error_handler();
            return ['ok' => false, 'error' => 'Invalid syntax'];
        } catch (\Exception $e) {
            restore_error_handler();
            $msg = $e->getMessage();
            if (stripos($msg, 'division') !== false) {
                return ['ok' => false, 'error' => 'Division by zero'];
            }
            return ['ok' => false, 'error' => 'Calculation error'];
        }

        restore_error_handler();

        if ($result === null) {
            return ['ok' => false, 'error' => 'Could not compute'];
        }

        return ['ok' => true, 'value' => $this->formatNum($result)];
    }

    private function formatNum($num)
    {
        if (is_nan($num)) return 'NaN';
        if (is_infinite($num)) return $num > 0 ? '∞' : '-∞';
        if (abs($num) < 1e-14) return '0';
        if (abs($num - round($num)) < 1e-9) return (string)(int)round($num);
        return (string)round($num, 10);
    }
}
