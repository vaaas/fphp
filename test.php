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
        case 'NULL':
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

Test('map_object', function() {
    expect_deep_equal(
        FP::make_object('a', 2, 'b', 3),
        FP::map_object(fn($x) => [$x[0], $x[1]+1])(FP::make_object('a', 1, 'b', 2))
    );
});

Test('map_dictionary', function() {
    expect_deep_equal(
        ['a' => 2, 'b' => 3],
        FP::map_dictionary(fn($x) => [$x[0], $x[1]+1])(['a' => 1, 'b' => 2])
    );
});

Test('filter_object', function() {
    expect_deep_equal(
        FP::make_object('a', 1),
        FP::filter_object(fn($x) => $x[1] !== 2)(FP::make_object('a', 1, 'b', 2, 'c', 2))
    );
});

Test('filter_dictionary', function() {
    expect_deep_equal(
        ['a' => 1],
        FP::filter_dictionary(fn($x) => $x[1] !== 2)(['a' => 1, 'b' => 2, 'c' => 2])
    );
});

Test('map_array', function() {
    expect_deep_equal(
        ['1', '2', '3'],
        FP::map_array(fn($x) => "{$x}")([1,2,3])
    );
});

Test('filter_array', function() {
    expect_deep_equal(
        [1,3,5,7],
        FP::filter_array(fn($x) => $x % 2 === 1)([1,2,3,4,5,6,7])
    );
});

Test('array_push', function() {
    expect_deep_equal(
        [1,2,3],
        FP::array_push(3)([1,2])
    );
});

Test('not', function() {
    expect_deep_equal(false, FP::not(true));
    expect_deep_equal(true, FP::not(false));
});

Test('and', function() {
    expect_deep_equal(2, FP::and(2)(1));
    expect_deep_equal(0, FP::and(6)(0));
});

Test('or', function() {
    expect_deep_equal('yo', FP::or('yo')(0));
    expect_deep_equal(1, FP::or('yo')(1));
});

Test('first', function() {
    expect_deep_equal(1, FP::first([1,2]));
});

Test('second', function() {
    expect_deep_equal(2, FP::second([1,2]));
});

Test('last', function() {
    expect_deep_equal('memes', FP::last([1,2,3,4,'memes']));
});

Test('head', function() {
    expect_deep_equal([1,2], FP::head([1,2,3]));
});

Test('tail', function() {
    expect_deep_equal([2,3], FP::tail([1,2,3]));
});

Test('construct', function() {
    expect_deep_equal(
        ['0', '1', '2'] ,
        FP::construct(fn($i) => "{$i}", 3));
});

Test('array', function() {
    $test = function() {
        yield 1;
        yield 2;
        yield 3;
    };
    expect_deep_equal([1,2,3], FP::array($test()));
});

Test('join', function() {
    $test = function() {
        yield 1;
        yield 2;
        yield 3;
    };
    expect_deep_equal('123', FP::join('')($test()));
});

Test('sort', function() {
    expect_deep_equal([1,2,3], FP::sort(fn($a, $b) => $a < $b ? -1 : 1)([3,2,1]));
});

Test('reverse', function() {
    expect_deep_equal([3,2,1], FP::reverse([1,2,3]));
    expect_deep_equal('cba', FP::reverse('abc'));
});

Test('dict', function() {
    expect_deep_equal([ 'medium' => 'blue', 'large' => 'red' ],
        FP::dict(fn($x) => $x['size'], fn($x) => $x['colour'])
            ([
                [ 'size' => 'medium', 'colour' => 'blue' ],
                [ 'size' => 'large', 'colour' => 'red' ]
            ]));
});

Test('obj', function() {
    expect_deep_equal(
        FP::make_object('medium', 'blue', 'large', 'red'),
        FP::obj(fn($x) => $x['size'], fn($x) => $x['colour'])
            ([
                [ 'size' => 'medium', 'colour' => 'blue' ],
                [ 'size' => 'large', 'colour' => 'red' ]
            ]));
});

Test('swap', function() {
    expect_deep_equal(
        [2, 1],
        FP::swap([1, 2], 0, 1)
    );
});

Test('group', function() {
    $in = [
        [ 'size' => 'm', 'colour' => 'blue' ],
        [ 'size' => 'm', 'colour' => 'red' ],
        [ 'size' => 'l', 'colour' => 'blue' ]
    ];

    $out = [
        'm' => [
            [ 'size' => 'm', 'colour' => 'blue' ],
            [ 'size' => 'm', 'colour' => 'red' ],
        ],
        'l' => [
            [ 'size' => 'l', 'colour' => 'blue' ]
        ],
    ];

    expect_deep_equal(
        $out,
        FP::group(fn($x) => $x, fn($x) => $x['size'])($in)
    );
});


Test('partition', function() {
    $xs = [ 'yo', 1, [], 'bro', 2 ];
    expect_deep_equal(
        [['yo', 'bro'], [1, 2], [[]]],
        FP::partition(
            fn($x) => gettype($x) === 'string',
            fn($x) => gettype($x) === 'integer',
            fn($x) => gettype($x) === 'array')
            ($xs)
    );
});

Test('is', function() {
    expect_deep_equal(1 === 1, FP::is(1)(1));
    expect_deep_equal('yo' === 'yo', FP::is('yo')('yo'));
    expect_deep_equal([] === [], FP::is([])([]));
});

Test('isnt', function() {
    expect_deep_equal(1 !== 1, FP::isnt(1)(1));
    expect_deep_equal('yo' !== 'yo', FP::isnt('yo')('yo'));
    expect_deep_equal([] !== [], FP::isnt([])([]));
});

Test('like', function() {
    expect_deep_equal(1 == '1', FP::like(1)('1'));
});

Test('unlike', function() {
    expect_deep_equal(1 != '1', FP::unlike(1)('1'));
    expect_deep_equal(2 != '1', FP::unlike(2)('1'));
});

Test('ifelse', function() {
    expect_deep_equal('good', FP::ifelse(fn($x) => $x, FP::K('good'), FP::K('bad'))(1));
    expect_deep_equal('bad', FP::ifelse(fn($x) => $x, FP::K('good'), FP::K('bad'))(0));
});

Test('when', function() {
    expect_deep_equal('good', FP::when(fn($x) => $x, FP::K('good'))(1));
    expect_deep_equal(0, FP::when(fn($x) => $x, FP::K('good'))(0));
});

Test('maybeor', function() {
    expect_deep_equal(2, FP::maybeor(FP::add(1), FP::K(0))(1));
    expect_deep_equal(0, FP::maybeor(FP::add(1), FP::K(0))(null));
});

Test('maybe', function() {
    expect_deep_equal(2, FP::maybe(FP::add(1))(1));
    expect_deep_equal(null, FP::maybe(FP::add(1))(null));
});

Test('nothing', function() {
    expect_deep_equal(384, FP::nothing(FP::K(1))(384));
    expect_deep_equal(1, FP::nothing(FP::K(1))(null));
});

Test('valmap', function() {
    expect_deep_equal(1, FP::valmap('one', 1, 'two', 2)('one'));
    expect_deep_equal(2, FP::valmap('one', 1, 'two', 2)('two'));
    expect_deep_equal('yo', FP::valmap('one', 1, 'two', 2)('yo'));
    expect_deep_equal(3, FP::valmap('one', 1, 'two', 2, 3)('yo'));
});

Test('cond', function() {
    expect_deep_equal(1, FP::cond(FP::is('one'), FP::K(1), FP::is('two'), FP::K(2))('one'));
    expect_deep_equal(2, FP::cond(FP::is('one'), FP::K(1), FP::is('two'), FP::K(2))('two'));
    expect_deep_equal('yo', FP::cond(FP::is('one'), FP::K(1), FP::is('two'), FP::K(2))('yo'));
    expect_deep_equal(3, FP::cond(FP::is('one'), FP::K(1), FP::is('two'), FP::K(2), FP::K(3))('yo'));
});

Test('between', function() {
    expect_deep_equal(true, FP::between(1, 3)(1));
    expect_deep_equal(true, FP::between(1, 3)(2));
    expect_deep_equal(true, FP::between(1, 3)(3));
    expect_deep_equal(false, FP::between(1, 3)(4));
});

Test('gt', function() {
    expect_deep_equal(1 > 2, FP::gt(2)(1));
    expect_deep_equal(2 > 1, FP::gt(1)(2));
});

Test('lt', function() {
    expect_deep_equal(1 < 2, FP::lt(2)(1));
    expect_deep_equal(2 < 1, FP::lt(1)(2));
});

Test('gte', function() {
    expect_deep_equal(1 >= 2, FP::gte(2)(1));
    expect_deep_equal(2 >= 1, FP::gte(1)(2));
    expect_deep_equal(2 >= 2, FP::gte(2)(2));
});

Test('lte', function() {
    expect_deep_equal(1 <= 2, FP::lte(2)(1));
    expect_deep_equal(2 <= 1, FP::lte(1)(2));
    expect_deep_equal(2 <= 2, FP::lte(2)(2));
});

Test('divisible', function() {
    expect_deep_equal((4 % 2) === 0, FP::divisible(2)(4));
    expect_deep_equal((4 % 3) === 0, FP::divisible(3)(4));
});

Test('add', function() {
    expect_deep_equal(4, FP::add(2)(2));
    expect_deep_equal('hello', FP::add('hel')('lo'));
    expect_deep_equal([1,2,3], FP::add([1,2])([3]));
    expect_deep_equal(['a' => 1, 'b' => 2], FP::add(['b' => 2])(['a' => 1]));
    expect_deep_equal(null, FP::add(3)(null));
    expect_deep_equal(FP::make_object('a', 1, 'b', 2), FP::add(FP::make_object('b', 2))(FP::make_object('a', 1)));
});

Test('addr', function() {
    expect_deep_equal(4, FP::addr(2)(2));
    expect_deep_equal('lohel', FP::addr('hel')('lo'));
    expect_deep_equal([3,1,2], FP::addr([1,2])([3]));
    expect_deep_equal(['a' => 1, 'b' => 2], FP::addr(['b' => 2])(['a' => 1]));
    expect_deep_equal(null, FP::addr(3)(null));
    expect_deep_equal(FP::make_object('a', 1, 'b', 2), FP::addr(FP::make_object('b', 2))(FP::make_object('a', 1)));
});

Test('clamp', function() {
    expect_deep_equal(2, FP::clamp(1, 3)(2));
    expect_deep_equal(3, FP::clamp(1, 3)(3));
    expect_deep_equal(3, FP::clamp(1, 3)(4));
    expect_deep_equal(1, FP::clamp(1, 3)(-394));
});

Test('signum', function() {
    expect_deep_equal(0, FP::signum(0));
    expect_deep_equal(-1, FP::signum(-1));
    expect_deep_equal(1, FP::signum(1));
    expect_deep_equal(1, FP::signum(14839));
    expect_deep_equal(-1, FP::signum(-14389));
});

Test('inside', function() {
    expect_deep_equal(true, FP::inside([1,2,3])(1));
    expect_deep_equal(false, FP::inside([1,2,3])(4));
    // expect_deep_equal(true, FP::inside(['a' => 1, 'b' => 2])('b'));
    expect_deep_equal(true, FP::inside(FP::make_object('a', 1, 'b', 2))('b'));
    expect_deep_equal(true, FP::inside('zab')('b'));
});

Test('outside', function() {
    expect_deep_equal(false, FP::outside([1,2,3])(1));
    expect_deep_equal(true, FP::outside([1,2,3])(4));
    // expect_deep_equal(true, FP::outside(['a' => 1, 'b' => 2])('b'));
    expect_deep_equal(false, FP::outside(FP::make_object('a', 1, 'b', 2))('b'));
    expect_deep_equal(false, FP::outside('zab')('b'));
});

Test('has', function() {
    expect_deep_equal(true, FP::has(1)([1,2,3]));
    expect_deep_equal(false, FP::has(4)([1,2,3]));
    // expect_deep_equal(true, FP::has(['a' => 1, 'b' => 2])('b'));
    expect_deep_equal(true, FP::has('b')(FP::make_object('a', 1, 'b', 2)));
    expect_deep_equal(true, FP::has('b')('zab'));
});

Test('hasnt', function() {
    expect_deep_equal(false, FP::hasnt(1)([1,2,3]));
    expect_deep_equal(true, FP::hasnt(4)([1,2,3]));
    // expect_deep_equal(true, FP::hasnt(['a' => 1, 'b' => 2])('b'));
    expect_deep_equal(false, FP::hasnt('b')(FP::make_object('a', 1, 'b', 2)));
    expect_deep_equal(false, FP::hasnt('b')('zab'));
});

Test('flatten', function() {
    expect_deep_equal([1,2,3,4], FP::array(FP::flatten(2)([ [ [ 1, 2, 3 ] ], [ [ 4 ] ] ])));
});

Test('flatten_until', function() {
    expect_deep_equal([1,2,3,4], FP::array(FP::flatten_until(fn($x) => gettype($x) === 'integer')([ [ [ 1, 2, 3 ] ], [ [ 4 ] ] ])));
});

Test('enumerate', function() {
    expect_deep_equal([ [ 0, 'a'], [ 1, 'b' ], [ 2, 'c' ] ], FP::array(FP::enumerate(['a', 'b', 'c'])));
});

Test('foldl', function() {
    expect_deep_equal(6, FP::foldl([FP::class, 'add'], 0)([1,2,3]));
    expect_deep_equal('abc', FP::foldl([FP::class, 'add'], '')(['a', 'b', 'c']));
});

Test('foldr', function() {
    expect_deep_equal(6, FP::foldr([FP::class, 'add'], 0)([1,2,3]));
    expect_deep_equal('cba', FP::foldr([FP::class, 'add'], '')(['a', 'b', 'c']));
});

Test('scanl', function() {
    expect_deep_equal([1, 3, 6],
        FP::array(
            FP::scanl([FP::class, 'add'], 0)([1,2,3])));
    expect_deep_equal(['a', 'ab', 'abc'],
        FP::array(
            FP::scanl([FP::class, 'add'], '')(['a', 'b', 'c'])));
});

Test('scanr', function() {
    expect_deep_equal([1, 3, 6],
        FP::array(
            FP::scanr([FP::class, 'add'], 0)([1,2,3])));
    expect_deep_equal(['a', 'ba', 'cba'],
        FP::array(
            FP::scanr([FP::class, 'add'], '')(['a', 'b', 'c'])));
});

Test('map', function() {
    expect_deep_equal([1,2,3],
        FP::array(
            FP::map(FP::add(1))([0,1,2])));
});

Test('filter', function() {
    expect_deep_equal([0, 2],
        FP::array(
            FP::filter(FP::divisible(2))([0,1,2,3])));
});

Test('find', function() {
    expect_deep_equal('yo',
        FP::find(FP::is('yo'))(['bro', 'yo']));
    expect_deep_equal(null,
        FP::find(FP::is(1))(['bro', 'yo']));
});

Test('find_index', function() {
    expect_deep_equal(1,
        FP::find_index(FP::is('yo'))(['bro', 'yo']));
    expect_deep_equal(null,
        FP::find_index(FP::is(1))(['bro', 'yo']));
});

Test('every', function() {
    expect_deep_equal(true,
        FP::every(fn($x) => gettype($x) === 'string')(['a', 'b', 'c']));
    expect_deep_equal(false,
        FP::every(fn($x) => gettype($x) === 'string')(['a', 'b', 2]));
});

Test('some', function() {
    expect_deep_equal(true,
        FP::some(fn($x) => gettype($x) === 'string')(['a', 1, 2]));
    expect_deep_equal(false,
        FP::some(fn($x) => gettype($x) === 'string')([0, 1, 2]));
});

Test('seq', function() {
    expect_deep_equal([0,1,2], FP::array(FP::seq(0, 2)));
});

Test('each', function() {
    expect_deep_equal([0,1,2], FP::each(fn($x) => $x)([0,1,2]));
});

Test('apply', function() {
    expect_deep_equal(
        [2, '1', 10],
        FP::apply([FP::add(1), fn($x) => "{$x}", FP::mult(10)])(1));
});

Test('limit', function() {
    $test = function() {
        $i = 1;
        while (true) {
            yield $i;
            $i++;
        }
    };

    expect_deep_equal(
        [1, 2, 3],
        FP::array(FP::limit(3)($test())));
});

Test('sum', function() {
    expect_deep_equal(6, FP::sum([1,2,3]));
});

Test('len', function() {
    expect_deep_equal(3, FP::len('abc'));
    expect_deep_equal(10, FP::len([0,1,2,3,4,5,6,7,8,9]));
    expect_deep_equal(2, FP::len(FP::make_object('a', 1, 'b', 2)));
});

Test('empty', function() {
    expect_deep_equal(true, FP::empty(''));
    expect_deep_equal(true, FP::empty([]));
    expect_deep_equal(true, FP::empty(new \stdclass));
    expect_deep_equal(false, FP::empty([1]));
});

Test('nonempty', function() {
    expect_deep_equal(false, FP::nonempty(''));
    expect_deep_equal(false, FP::nonempty([]));
    expect_deep_equal(false, FP::nonempty(new \stdclass));
    expect_deep_equal(true, FP::nonempty([1]));
});

Test('average', function() {
    expect_deep_equal(2, FP::average([1,2,3]));
});

Test('count', function() {
    expect_deep_equal(
        ['a' => 2, 'b' => 1],
        FP::count(['a', 'b', 'a']));
});

Test('plist_to_alist', function() {
    expect_deep_equal(
        [['a', 1], ['b', 2]],
        FP::array(FP::plist_to_alist(['a', 1, 'b', 2])));
});

Test('split', function() {
    expect_deep_equal(['a', 'b', 'c'], FP::split(' ')('a b c'));
});

Test('starts_with', function() {
    expect_deep_equal(true, FP::starts_with('www.')('www.google.com'));
    expect_deep_equal(false, FP::starts_with('test.')('www.google.com'));
});

Test('distinct', function() {
    expect_deep_equal([0, 1], FP::distinct([0,0,0,0,1,1,0,0,1]));
});

Test('slice', function() {
    expect_deep_equal([1,2,3], FP::slice(1,4)([0,1,2,3,4]));
});

echo "Tests completed\n";
