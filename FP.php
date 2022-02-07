<?php
declare(strict_types=1);
namespace FPHP;

class FP {
    static function K($x): callable {
        return function() use ($x) { return $x; };
    }

    static function T($a): callable {
        return function ($b) use ($a) {
            return $b($a);
        };
    }

    static function spread(callable $f) {
        return function ($x) use ($f) {
            return $f(...$x);
        };
    }

    static function unspread(callable $f) {
        return function (...$x) use ($f) {
            return $f($x);
        };
    }

    static function tap(callable $f) {
        return function ($x) use ($f) {
            $f($x);
            return $x;
        };
    }

    static function pipe($x, callable ...$fs) {
        foreach($fs as $f)
            $x = $f($x);
        return $x;
    }

    static function arrow(callable ...$fs): callable {
        return function($x) use ($fs) {
            foreach($fs as $f)
                $x = $f($x);
            return $x;
        };
    }

    static function by(callable $f): callable {
        return function($a, $b) use ($f) {
            $xa = $f($a);
            $xb = $f($b);
            if ($xa === $xb) return 0;
            else if ($xa < $xb) return -1;
            else return 1;
        };
    }

    static function do_nothing() {
        return FP::K(false);
    }

    static function get(...$ks): callable {
        return function($x) use ($ks) {
            foreach ($ks as $k) {
                $type = gettype($x);
                if ($type === 'array') $x = $x[$k];
                else if ($type === 'object') $x = $x->$k;
                else break;
            }
            return $x;
        };
    }

    static function set($k): callable {
        return function($v) use ($k): callable {
            return FP::tap(function($o) use ($k, $v) {
                $type = gettype($o);
                if ($type === 'array') $x = $x[$k] = $v;
                else if ($type === 'object') $x = $x->$k = $v;
            });
        };
    }

    static function get_from($x): callable {
        return function($k) use ($x) {
            switch (gettype($x)) {
                case 'array': return $x[$k];
                case 'object': return $x->$k;
                default: return null;
            }
        };
    }

    static function keys($x): array {
        switch (gettype($x)) {
            case 'array': return array_keys($x);
            case 'object': return get_object_vars($x);
            default: return [];
        }
    }

    static function entries(iterable $x): array {
        $r = [];
        foreach ($x as $k => $v)
            array_push($r, [$k, $v]);
        return $r;
    }

    static function change(callable $f, ...$ks): callable {
        return FP::tap(function($x) use ($f, $ks) {
            if (count($ks) === 0)
                $ks = FP::keys($x);
            switch (gettype($x)) {
                case 'array':
                    foreach ($ks as $k) $x[$k] = $f($x[$k]);
                    break;
                case 'object':
                    foreach ($ks as $k) $x->$k = $f($x->$k);
                    break;
            }
        });
    }

    static function update($a, ...$ks): callable {
        return FP::tap(function($b) use ($a, $ks) {
            if (count($ks) === 0) $ks = FP::keys($a);
            switch (gettype($b)) {
                case 'array':
                    foreach ($ks as $k) $b[$k] = $a[$k];
                    break;
                case 'object':
                    foreach ($ks as $k) $b[$k] = $a[$k];
                    break;
            }
        });
    }

    static function alist_to_dictionary(iterable $xs): array {
        $r = [];
        foreach ($xs as $x) $r[$x[0]] = $x[1];
        return $r;
    }

    static function alist_to_object(iterable $xs): object {
        $r = new \stdclass();
        foreach ($xs as $x) {
            $k = $x[0];
            $v = $x[1];
            $r->$k = $v;
        }
        return $r;
    }

    static function map_object(callable $f): callable {
        return function(object $x) use ($f): object {
            return FP::alist_to_object(array_map($f, FP::entries($x)));
        };
    }

    static function map_dictionary(callable $f): callable {
        return function (array $x) use ($f): array {
            return FP::alist_to_dictionary(array_map($f, FP::entries($x)));
        };
    }

    static function filter_object(callable $f): callable {
        return function (object $x) use ($f): object {
            return FP::alist_to_object(array_filter(FP::entries($x), $f));
        };
    }

    static function filter_dictionary(callable $f): callable {
        return function (array $x) use ($f): array {
            return FP::alist_to_dictionary(array_filter(FP::entries($x), $f));
        };
    }

    static function map_array(callable $f): callable {
        return function (array $x) use ($f): array {
            return array_map($f, $x);
        };
    }

    static function filter_array(callable $f): callable {
        return function (array $x) use ($f): array {
            return array_filter($f, $x);
        };
    }

    static function array_push($x): callable {
        return function (array $xs) use ($x): array {
            array_push($xs, $x);
            return $xs;
        };
    }

    static function not($x): bool {
        return !$x;
    }

    static function and($a): callable {
        return function ($b) use ($a) {
            return $b && $a;
        };
    }

    static function or($a): callable {
        return function ($b) use ($a) {
            return $b || $a;
        };
    }

    static function first(array $x) {
        return $x[0];
    }

    static function second(array $x) {
        return $x[1];
    }

    static function last(array $x) {
        return $x[FP::len($x) - 1];
    }

    static function head(array $x): array {
        return array_slice($x, 0, -1);
    }

    static function tail(array $x): array {
        return array_slice($x, 1);
    }

    static function construct(callable $f, int $n=1): array {
        $x = [];
        for ($i = 0; $i < $n; $i++)
            array_push($x, $f($i, $n, $x));
        return $x;
    }

    static function array(iterable $xs): array {
        $arr = [];
        foreach ($xs as $x) array_push($arr, $x);
        return $arr;
    }

    static function join(string $s): callable {
        return function (iterable $x) use ($s): string {
            switch (gettype($x)) {
                case 'array': return implode($s, $x);
                default: return implode($x, FP::array($x));
            }
        };
    }

    static function sort(callable $f): callable {
        return function (iterable $x) use ($f): array {
            switch (gettype($f)) {
                case 'array': return usort($x, $f);
                default: return usort(FP::array($x), $f);
            }
        };
    }

    static function reverse(iterable $x): array {
        switch (gettype($x)) {
            case 'array': return array_reverse($x);
            case 'string': return (strrev($x));
            default: return array_reverse(FP::array($x));
        }
    }

    static function dict(callable $key, ?callable $val=null): callable {
        return function (iterable $xs) use ($key, $val): array {
            $r = [];
            foreach ($xs as $x)
                $r[$key($x)] = $val ? $val($x) : $x;
            return $r;
        };
    }

    static function obj(callable $key, ?callable $val=null): callable {
        return function (iterable $xs) use ($key, $val): object {
            $r = new \stdClass();
            foreach ($xs as $x) {
                $k = $key($x);
                $r->$k = $val ? $val($x) : $x;
            }
            return $r;
        };
    }

    static function swap(array $x, $a, $b): array {
        $c = $x[$a];
        $x[$a] = $x[$b];
        $x[$b] = $x[$a];
        return $x;
    }

    static function group(callable ...$fs): callable {
        return function ($xs) use ($fs): array {
            if (count($fs) === 0) return $xs;
            else {
                $f = $fs[0];
                $groups = [];
                foreach ($xs as $x) {
                    $g = $f($x);
                    if (!array_key_exists($g, $groups)) $groups[$g] = [];
                    array_push($groups[$g], $x);
                }
                foreach ($groups as $g => $xs)
                    $groups[$g] = FP::group(FP::tail($fs))($xs);
                return $groups;
            }
        };
    }

    static function partition(callable ...$fs): callable {
        $c = count($fs);
        return function (iterable $xs) use ($fs): array {
            $r = FP::construct(fn() => [], $c);
            foreach ($xs as $x) {
                $i = 0;
                foreach ($fs as $f) {
                    if ($f($x)) break;
                    else $i++;
                }
                if ($i < $c) array_push($r[$i], $x);
            }
            return $x;
        };
    }

    static function is($a): callable {
        return function($b) use ($a): bool {
            return $a === $b;
        };
    }

    static function isnt($a): callable {
        return function($b) use ($a): bool {
            return $a !== $b;
        };
    }

    static function like($a): callable {
        return function($b) use ($a): bool {
            return $a == $b;
        };
    }

    static function unlike($a): callable {
        return function($b) use ($a): bool {
            return $a != $b;
        };
    }

    static function defined($x): bool { return $x !== null; }

    static function ifelse(callable $cond, callable $ok, callable $bad): callable {
        return function($x) use ($cond, $ok, $bad) {
            if ($cond($x)) return $ok($x);
            else return $bad($x);
        };
    }

    static function when(callable $cond): callable {
        return fn(callable $ok) => fn($x) => $cond($x) ? $ok($x) : $x;
    }

    static function maybe(callable $good, callable $bad): callable {
        return fn($x) => $x === null ? $bad($x) : $good($x);
    }

    static function nothing(callable $f): callable {
        return fn($x) => $x === null ? $f($x) : $x;
    }

    static function something(callable $f): callable {
        return fn($x) => $x === null ? $x : $f($x);
    }

    static function valmap(...$xs): callable {
        return function($x) use ($xs) {
            $len = count($xs);
            $len = $len - $len % 2;
            for ($i = 0; $i < $len; $i += 2)
                if ($xs[$i] === $x) return $xs[$i+1];
            return $len === count($xs) ? $x : FP::last($xs);
        };
    }

    static function cond(callable ...$fs): callable {
        return function($x) use ($fs) {
            $len = count($fs);
            $len = $len - $len % 2;
            for ($i = 0; $i < $len; $i += 2)
                if ($fs[$i]($x)) return $fs[$i+1]($x);
            return $len === count($fs) ? $x : FP::last($fs)($x);
        };
    }

    static function attempt(callable $f) {
        try { return $f(); }
        catch (\Throwable $e) { return $e; }
    }

    static function reject(callable $f, callable $m): callable {
        if ($f($x)) throw $m($x);
        else return $x;
    }

    static function between($low, $high): callable {
        return function ($x): bool {
            return $x >= $low && $x <= high;
        };
    }

    static function gt($n): callable {
        return fn($x): bool => $x > $n;
    }

    static function gte($n): callable {
        return fn($x): bool => $x >= $n;
    }

    static function lt($n): callable {
        return fn($x): bool => $x < $n;
    }

    static function lte($n): callable {
        return fn($x): bool => $x <= $n;
    }

    static function divisible(int $a): callable {
        return function (int $b) use ($a): bool {
            return $b % $a === 0;
        };
    }

    static function add($a): callable {
        return function ($b) use ($a) {
            if ($b === null) return null;
            switch (gettype($a)) {
                case 'NULL': return null;
                case 'integer':
                case 'double':
                    return $a + $b;

                case 'string';
                    return $a . $b;

                case 'array':
                    return array_merge($a, $b);

                case 'object':
                    if (is_iterable($a) && is_iterable($b))
                        return (function() use ($a, $b) { yield from $a; yield from $b; })();
                    else {
                        $r = new \stdclass();
                        foreach ($a as $k => $v) $r->$k = $v;
                        foreach ($b as $k => $v) $r->$k = $v;
                        return $r;
                    }

                default: return null;
            }
        };
    }

    static function addr($a): callable {
        return function($b) use ($a) {
            return FP::add($b)($a);
        };
    }

    static function clamp($x, $min, $max) {
        if ($x < $min) return $min;
        else if ($x > $max) return $max;
        else return $x;
    }

    static function signum($x): int {
        if ($x === 0) return 0;
        else if ($x < 0) return -1;
        else return 1;
    }

    static function inside($xs): callable {
        return function ($x) use ($xs): bool {
            switch(gettype($xs)) {
                case 'array':
                    // TODO: use array_is_list in the future
                    $f = array_search($x, $xs, true);
                    if ($f === false) return false;
                    else return true;

                case 'string':
                    $f = strpos($xs, $x);
                    if ($f === false) return false;
                    else return true;

                case 'object':
                    $f = array_search($x, FP::keys($xs), true);
                    if ($f === false) return false;
                    else return true;
            }
        };
    }

    static function outside($xs): callable {
        return function ($x) use ($xs): bool {
            return !FP::inside($xs)($x);
        };
    }

    static function has($x): callable {
        return function ($xs) use ($x): bool {
            return FP::inside($xs)($x);
        };
    }

    static function hasnt($x): callable {
        return function ($xs) use ($x): bool {
            return !FP::inside($xs)($x);
        };
    }

    static function flatten(int $n): callable {
        return function (iterable $xs) use ($n): iterable {
            foreach ($xs as $x) {
                if ($n > 0 && is_iterable($x)) yield from FP::flatten($n-1)($x);
                else yield $x;
            }
        };
    }

    static function flatten_until(callable $f): callable {
        return function (iterable $xs) use ($f): iterable {
            foreach ($xs as $x) {
                if ($f($x)) yield $x;
                else yield from FP::flatten_until($f)($x);
            }
        };
    }

    static function enumerate(iterable $xs): iterable {
        $i = 0;
        foreach ($xs as $x) {
            yield [$i, $x];
            $i++;
        }
    }

    static function foldl(callable $f): callable {
        return function($i) use ($f): callable {
            return function(iterable $xs) use ($f, $i) {
                $a = $i;
                foreach ($xs as $x) $a = $f($a)($x);
                return $a;
            };
        };
    }

    static function foldr(callable $f): callable {
        return function($i) use ($f): callable {
            return function(iterable $xs) use ($f, $i) {
                $a = $i;
                foreach ($xs as $x) $a = $f($x)($a);
                return $a;
            };
        };
    }

    static function scanl(callable $f): callable {
        return function($i) use ($f): callable {
            return function(iterable $xs) use ($f, $i): iterable {
                $a = $i;
                foreach ($xs as $x) $a = $f($x)($a);
                yield $a;
            };
        };
    }

    static function scanr(callable $f): callable {
        return function($i) use ($f): callable {
            return function(iterable $xs) use ($f, $i): iterable {
                $a = $i;
                foreach ($xs as $x) $a = $f($x)($a);
                yield $a;
            };
        };
    }

    static function map(callable $f): callable {
        return function(iterable $xs) use ($f): iterable {
            foreach ($xs as $i=>$x)
                yield $f($x, $i, $xs);
        };
    }

    static function filter(callable $f): callable {
        return function(iterable $xs) use ($f): iterable {
            foreach ($xs as $i=>$x)
                if ($f($x, $i, $xs)) yield $x;
        };
    }

    static function find(callable $f): callable {
        return function(iterable $xs) use ($f) {
            foreach ($xs as $x) if ($f($x)) return $x;
            return null;
        };
    }

    static function find_index(callable $f): callable {
        return function(iterable $xs) use ($f) {
            $i = 0;
            foreach ($xs as $x) {
                if ($f($x)) return $i;
                else $i++;
            }
            return null;
        };
    }

    static function every(callable $f): callable {
        return function (iterable $xs) use ($f): bool {
            foreach ($xs as $x)
                if (!$f($x)) return false;
            return true;
        };
    }

    static function some(callable $f): callable {
        return function (iterable $xs) use ($f): bool {
            foreach ($xs as $x)
                if ($f($x)) return true;
            return false;
        };
    }

    static function seq(int $start, int $end): iterable {
        $i = $start;
        while ($i <= $end) {
            yield $i;
            $i++;
        }
    }

    static function each(callable $f): callable {
        return function(iterable $xs) use ($f): iterable {
            foreach ($xs as $i=>$x)
                $f($x, $i, $xs);
            return $xs;
        };
    }

    static function apply(array $fs): callable {
        return function ($x) use ($fs) {
            $r = [];
            foreach ($fs as $f) array_push($r, $f($x));
            return $r;
        };
    }

    static function limit(int $n): callable {
        return function (iterable $xs): iterable {
            $i = 0;
            foreach ($xs as $x) {
                yield $x;
                $i++;
                if ($i === $n) break;
            }
        };
    }

    static function sum(iterable $xs): int {
        return FP::foldr(fn($a) => fn($b) => $a+$b)(0);
    }

    static function len($x): int {
        switch (gettype($x)) {
            case 'array': return count($x);
            case 'string': return strlen($x);
            case 'object':
                $c = 0;
                foreach ($x as $v) $c++;
                return $c;
            default: return 0;
        }
    }

    static function empty($x): bool {
        return FP::len($x) === 0;
    }

    static function nonempty($x): bool {
        return FP::len($x) > 0;
    }

    static function average(iterable $x): int {
        return FP::sum($x) / FP::len($x);
    }

    static function batch(int $size=100): callable {
        return function(iterable $xs) use ($size): iterable {
            $batch = [];
            $c = 0;
            foreach ($xs as $x) {
                array_push($batch, $x);
                $c++;
                if ($c === $size) {
                    yield $batch;
                    $batch = [];
                    $c = 0;
                }
            }
            if ($c > 0) yield $batch;
        };
    }

    static function count(iterable $xs): array {
        $r = [];
        foreach ($xs as $x) {
            if (!(array_key_exists($x, $r))) $r[$x] = 0;
            $r[$x]++;
        }
        return $r;
    }

    static function plist_to_alist(iterable $xs): iterable {
        $last = null;
        foreach ($xs as $x) {
            if ($last) {
                yield [$last, $x];
                $last = null;
            } else $last = $x;
        }
    }

    static function split(string $d): callable {
        return function (string $x) use ($d): array {
            return explode($d, $x);
        };
    }

    static function starts_with(string $prefix): callable {
        return function (string $x): bool {
            return str_starts_with($x, $prefix);
        };
    }

    static function ends_with(string $prefix): callable {
        return function (string $x): bool {
            return str_ends_with($x, $prefix);
        };
    }
}
