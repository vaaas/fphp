<?php
declare(strict_types=1);
require "FP.php";

use FPHP\FP;

function Test(string $name, callable $f) {
    try {
        $f();
    } catch (\throwable $e) {
        error_log('test failed for ' . $name);
        error_log($e->getMessage());
        error_log($e->getTraceAsString());
    }
}

function deep_equal($a, $b) {
    if (gettype($a) !== gettype($b)) return false;
    switch (gettype($a)) {
        case 'boolean':
        case 'integer':
        case 'string':
        case 'double':
            return $a === $b;

        case 'array':
            foreach ($a as $k => $v) {
                if (!(array_key_exists($k, $b))) return false;
                if (!(deep_equal($v, $b[$k]))) return false;
            }
            return true;

        case 'object':
            if (is_iterable($a) && is_iterable($b)) {
                return deep_equal(iterator_to_array($a), iterator_to_array($b));
            } else {
                foreach ($a as $k => $v) {
                    if (!(property_exists($b, $k))) return false;
                    else if (!(deep_equal($v, $b->$k))) return false;
                }
                return true;
            }
    }
}

function expect_deep_equal($a, $b) {
    if (deep_equal($a, $b)) return true;
    else throw new \Exception('expected deep equality');
}

Test('make_object', function() {
    $x = new \stdclass;
    $x->a = 1;
    $x->b = 2;
    expect_deep_equal($x, FP::make_object('a', 1, 'b', 2));
});

Test('K', function() {
    expect_deep_equal(1, FP::K(1)());
    expect_deep_equal(1, FP::K(1)(2));
    expect_deep_equal(1, FP::K(1)('test'));
});

Test('T', function() {
    expect_deep_equal(2, FP::T(1)(fn($x) => $x + 1));
});

Test('spread', function() {
    $add = fn($a, $b) => $a + $b;
    expect_deep_equal(5, FP::spread($add)([2, 3]));
});

Test('unspread', function() {
    $add = fn($x) => $x[0] + $x[1];
    expect_deep_equal(5, FP::unspread($add)(2, 3));
});

Test('tap', function() {
    $xs = [1, 2];
    expect_deep_equal([1,2,3], FP::tap(fn(&$x) => array_push($x, 3))($xs));
});

Test('pipe', function() {
    expect_deep_equal(10,
        FP::pipe(
            1,
            fn($x) => $x + 1,
            fn($x) => $x * 2,
            fn($x) => $x * 10,
            fn($x) => $x - 30,
        ));
});

Test('arrow', function() {
    expect_deep_equal(10,
        FP::arrow(
            fn($x) => $x + 1,
            fn($x) => $x * 2,
            fn($x) => $x * 10,
            fn($x) => $x - 30)(1));
});

Test('by', function() {
    $a = [ 'value' => 1 ];
    $b = [ 'value' => 2 ];
    expect_deep_equal(-1, FP::by(fn($x) => $x['value'])($a, $b));
    expect_deep_equal(1, FP::by(fn($x) => $x['value'])($b, $a));
    expect_deep_equal(0, FP::by(fn($x) => $x['value'])($a, $a));
});

Test('do_nothing', function() {
    expect_deep_equal(false, FP::do_nothing());
    expect_deep_equal(false, FP::do_nothing(1));
    expect_deep_equal(false, FP::do_nothing(true));
    expect_deep_equal(false, FP::do_nothing([]));
});

Test('get', function() {
    expect_deep_equal(1, FP::get(1)([0, 1, 2]));
    expect_deep_equal(1, FP::get('value', 1, 'okay')(['value' => [0, ['okay' => 1 ]]]));
});

Test('set', function() {
    expect_deep_equal([1,2,3], FP::set(2)(3)([1,2]));
});

Test('get_from', function() {
    $x = FP::make_object('test', 3);
    expect_deep_equal(3, FP::get_from($x)('test'));
});

Test('keys', function() {
    $x = FP::make_object('a', 1, 'b', 2);
    $y = [ 'a' => 1, 'b' => 2 ];
    expect_deep_equal(['a', 'b'], FP::array(FP::keys($x)));
    expect_deep_equal(['a', 'b'], FP::array(FP::keys($y)));
});

Test('entries', function() {
    $x = FP::make_object('a', 1, 'b', 2);
    $y = [ 'a' => 1, 'b' => 2 ];
    expect_deep_equal([['a', 1], ['b', 2]], FP::array(FP::entries($x)));
    expect_deep_equal([['a', 1], ['b', 2]], FP::array(FP::entries($y)));
});

Test('change', function() {
    expect_deep_equal(
        ['a' => 2, 'b' => 3, 'c' => 3],
        FP::change(fn($x) => $x + 1, 'a', 'b')(['a' => 1, 'b' => 2, 'c' => 3])
    );
});

Test('update', function() {
    expect_deep_equal(
        ['a' => 2, 'b' => 2, 'c' => 3],
        FP::update(['a' => 2, 'b' => 2, 'c' => 666], 'a', 'b')(['a' => 1, 'c' => 3])
    );
});

Test('alist_to_dictionary', function() {
    expect_deep_equal(
        ['a' => 1, 'b' => 2],
        FP::alist_to_dictionary([['a', 1], ['b', 2]])
    );
});

Test('alist_to_object', function() {
    $x = FP::make_object('a', 1, 'b', 2);
    expect_deep_equal(
        $x,
        FP::alist_to_object([['a', 1], ['b', 2]])
    );
});

echo "Tests completed\n";
