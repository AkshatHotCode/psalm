<?php
namespace Psalm\Tests;

use function array_values;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Type;

class TypeCombinationTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @dataProvider providerTestValidTypeCombination
     *
     * @param string $expected
     * @param non-empty-list<string> $types
     *
     */
    public function testValidTypeCombination($expected, $types): void
    {
        $converted_types = [];

        foreach ($types as $type) {
            $converted_type = self::getAtomic($type);
            $converted_type->from_docblock = true;
            $converted_types[] = $converted_type;
        }

        $this->assertSame(
            $expected,
            TypeCombiner::combine($converted_types)->getId()
        );
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'multipleValuedArray' => [
                '<?php
                    class A {}
                    class B {}
                    $var = [];
                    $var[] = new A();
                    $var[] = new B();',
            ],
            'preventLiteralAndClassString' => [
                '<?php
                    /**
                     * @param "array"|class-string $type_name
                     */
                    function foo(string $type_name) : bool {
                        return $type_name === "array";
                    }',
            ],
        ];
    }

    /**
     * @return array<string,array{string,non-empty-list<string>}>
     */
    public function providerTestValidTypeCombination(): array
    {
        return [
            'intOrString' => [
                'int|string',
                [
                    'int',
                    'string',
                ],
            ],
            'mixedOrNull' => [
                'mixed|null',
                [
                    'mixed',
                    'null',
                ],
            ],
            'mixedOrEmpty' => [
                'mixed',
                [
                    'empty',
                    'mixed',
                ],
            ],
            'mixedOrObject' => [
                'mixed|object',
                [
                    'mixed',
                    'object',
                ],
            ],
            'mixedOrEmptyArray' => [
                'array<empty, empty>|mixed',
                [
                    'mixed',
                    'array<empty, empty>',
                ],
            ],
            'falseTrueToBool' => [
                'bool',
                [
                    'false',
                    'true',
                ],
            ],
            'trueFalseToBool' => [
                'bool',
                [
                    'true',
                    'false',
                ],
            ],
            'trueBoolToBool' => [
                'bool',
                [
                    'true',
                    'bool',
                ],
            ],
            'boolTrueToBool' => [
                'bool',
                [
                    'bool',
                    'true',
                ],
            ],
            'intOrTrueOrFalseToBool' => [
                'bool|int',
                [
                    'int',
                    'false',
                    'true',
                ],
            ],
            'intOrBoolOrTrueToBool' => [
                'bool|int',
                [
                    'int',
                    'bool',
                    'true',
                ],
            ],
            'intOrTrueOrBoolToBool' => [
                'bool|int',
                [
                    'int',
                    'true',
                    'bool',
                ],
            ],
            'arrayOfIntOrString' => [
                'array<array-key, int|string>',
                [
                    'array<int>',
                    'array<string>',
                ],
            ],
            'arrayOfIntOrAlsoString' => [
                'array<array-key, int>|string',
                [
                    'array<int>',
                    'string',
                ],
            ],
            'emptyArrays' => [
                'array<empty, empty>',
                [
                    'array<empty,empty>',
                    'array<empty,empty>',
                ],
            ],
            'arrayStringOrEmptyArray' => [
                'array<array-key, string>',
                [
                    'array<empty>',
                    'array<string>',
                ],
            ],
            'arrayMixedOrString' => [
                'array<array-key, mixed|string>',
                [
                    'array<mixed>',
                    'array<string>',
                ],
            ],
            'arrayMixedOrStringKeys' => [
                'array<array-key, string>',
                [
                    'array<int|string,string>',
                    'array<mixed,string>',
                ],
            ],
            'arrayMixedOrEmpty' => [
                'array<array-key, mixed>',
                [
                    'array<empty>',
                    'array<mixed>',
                ],
            ],
            'arrayBigCombination' => [
                'array<array-key, float|int|string>',
                [
                    'array<int|float>',
                    'array<string>',
                ],
            ],
            'arrayTraversableToIterable' => [
                'iterable<array-key|mixed, mixed>',
                [
                    'array',
                    'Traversable',
                ],
            ],
            'arrayIterableToIterable' => [
                'iterable<mixed, mixed>',
                [
                    'array',
                    'iterable',
                ],
            ],
            'iterableArrayToIterable' => [
                'iterable<mixed, mixed>',
                [
                    'iterable',
                    'array',
                ],
            ],
            'traversableIterableToIterable' => [
                'iterable<mixed, mixed>',
                [
                    'Traversable',
                    'iterable',
                ],
            ],
            'iterableTraversableToIterable' => [
                'iterable<mixed, mixed>',
                [
                    'iterable',
                    'Traversable',
                ],
            ],
            'arrayTraversableToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'array<int, string>',
                    'Traversable<int, bool>',
                ],
            ],
            'arrayIterableToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'array<int, string>',
                    'iterable<int, bool>',
                ],
            ],
            'iterableArrayToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'iterable<int, string>',
                    'array<int, bool>',
                ],
            ],
            'traversableIterableToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'Traversable<int, string>',
                    'iterable<int, bool>',
                ],
            ],
            'iterableTraversableToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'iterable<int, string>',
                    'Traversable<int, bool>',
                ],
            ],
            'arrayObjectAndParamsWithEmptyArray' => [
                'ArrayObject<int, string>|array<empty, empty>',
                [
                    'ArrayObject<int, string>',
                    'array<empty, empty>',
                ],
            ],
            'emptyArrayWithArrayObjectAndParams' => [
                'ArrayObject<int, string>|array<empty, empty>',
                [
                    'array<empty, empty>',
                    'ArrayObject<int, string>',
                ],
            ],
            'falseDestruction' => [
                'bool',
                [
                    'false',
                    'bool',
                ],
            ],
            'onlyFalse' => [
                'false',
                [
                    'false',
                ],
            ],
            'onlyTrue' => [
                'true',
                [
                    'true',
                ],
            ],
            'falseFalseDestruction' => [
                'false',
                [
                    'false',
                    'false',
                ],
            ],
            'aAndAOfB' => [
                'A|A<B>',
                [
                    'A',
                    'A<B>',
                ],
            ],
            'combineObjectType1' => [
                'array{a?: int, b?: string}',
                [
                    'array{a: int}',
                    'array{b: string}',
                ],
            ],
            'combineObjectType2' => [
                'array{a: int|string, b?: string}',
                [
                    'array{a: int}',
                    'array{a: string,b: string}',
                ],
            ],
            'combineObjectTypeWithIntKeyedArray' => [
                'array<"a"|int, int|string>',
                [
                    'array{a: int}',
                    'array<int, string>',
                ],
            ],
            'combineNestedObjectTypeWithTKeyedArrayIntKeyedArray' => [
                'array{a: array<"a"|int, int|string>}',
                [
                    'array{a: array{a: int}}',
                    'array{a: array<int, string>}',
                ],
            ],
            'combineIntKeyedObjectTypeWithNestedIntKeyedArray' => [
                'array<int, array<"a"|int, int|string>>',
                [
                    'array<int, array{a:int}>',
                    'array<int, array<int, string>>',
                ],
            ],
            'combineNestedObjectTypeWithNestedIntKeyedArray' => [
                'array<"a"|int, array<"a"|int, int|string>>',
                [
                    'array{a: array{a: int}}',
                    'array<int, array<int, string>>',
                ],
            ],
            'combinePossiblyUndefinedKeys' => [
                'array{a: bool, b?: mixed, d?: mixed}',
                [
                    'array{a: false, b: mixed}',
                    'array{a: true, d: mixed}',
                    'array{a: true, d: mixed}',
                ],
            ],
            'combinePossiblyUndefinedKeysAndString' => [
                'array{a: string, b?: int}|string',
                [
                    'array{a: string, b?: int}',
                    'string',
                ],
            ],
            'combineMixedArrayWithTKeyedArray' => [
                'array<array-key, mixed>',
                [
                    'array{a: int}',
                    'array',
                ],
            ],
            'traversableAorB' => [
                'Traversable<mixed, A|B>',
                [
                    'Traversable<A>',
                    'Traversable<B>',
                ],
            ],
            'iterableAorB' => [
                'iterable<mixed, A|B>',
                [
                    'iterable<A>',
                    'iterable<B>',
                ],
            ],
            'FooAorB' => [
                'Foo<A>|Foo<B>',
                [
                    'Foo<A>',
                    'Foo<B>',
                ],
            ],
            'traversableOfMixed' => [
                'Traversable<mixed, mixed>',
                [
                    'Traversable',
                    'Traversable<mixed, mixed>',
                ],
            ],
            'traversableAndIterator' => [
                'Traversable&Iterator',
                [
                    'Traversable&Iterator',
                    'Traversable&Iterator',
                ],
            ],
            'traversableOfMixedAndIterator' => [
                'Traversable<mixed, mixed>&Iterator',
                [
                    'Traversable<mixed, mixed>&Iterator',
                    'Traversable<mixed, mixed>&Iterator',
                ],
            ],
            'objectLikePlusArrayEqualsArray' => [
                'array<"a"|"b"|"c", 1|2|3>',
                [
                    'array<"a"|"b"|"c", 1|2|3>',
                    'array{a: 1|2, b: 2|3, c: 1|3}',
                ],
            ],
            'combineClosures' => [
                'Closure(A):void|Closure(B):void',
                [
                    'Closure(A):void',
                    'Closure(B):void',
                ],
            ],
            'combineClassStringWithString' => [
                'string',
                [
                    'class-string',
                    'string',
                ],
            ],
            'combineClassStringWithFalse' => [
                'class-string|false',
                [
                    'class-string',
                    'false',
                ],
            ],
            'combineRefinedClassStringWithString' => [
                'string',
                [
                    'class-string<Exception>',
                    'string',
                ],
            ],
            'combineRefinedClassStrings' => [
                'class-string<Exception>|class-string<Iterator>',
                [
                    'class-string<Exception>',
                    'class-string<Iterator>',
                ],
            ],
            'combineClassStringsWithLiteral' => [
                'class-string',
                [
                    'class-string',
                    'Exception::class',
                ],
            ],
            'combineCallableAndCallableString' => [
                'callable',
                [
                    'callable',
                    'callable-string',
                ],
            ],
            'combineCallableStringAndCallable' => [
                'callable',
                [
                    'callable-string',
                    'callable'
                ],
            ],
            'combineCallableAndCallableObject' => [
                'callable',
                [
                    'callable',
                    'callable-object',
                ],
            ],
            'combineCallableObjectAndCallable' => [
                'callable',
                [
                    'callable-object',
                    'callable'
                ],
            ],
            'combineCallableAndCallableArray' => [
                'callable',
                [
                    'callable',
                    'callable-array',
                ],
            ],
            'combineCallableArrayAndCallable' => [
                'callable',
                [
                    'callable-array',
                    'callable'
                ],
            ],
            'combineCallableArrayAndArray' => [
                'array<array-key, mixed>',
                [
                    'callable-array{class-string, string}',
                    'array',
                ],
            ],
            'combineGenericArrayAndMixedArray' => [
                'array<array-key, int|mixed>',
                [
                    'array<string, int>',
                    'array<array-key, mixed>',
                ],
            ],
            'combineTKeyedArrayAndArray' => [
                'array<array-key, mixed>',
                [
                    'array{hello: int}',
                    'array<array-key, mixed>',
                ],
            ],
            'combineTKeyedArrayAndNestedArray' => [
                'array<array-key, mixed>',
                [
                    'array{hello: array{goodbye: int}}',
                    'array<array-key, mixed>',
                ],
            ],
            'combineNumericStringWithLiteralString' => [
                'numeric-string',
                [
                    'numeric-string',
                    '"1"',
                ],
            ],
            'combineLiteralStringWithNumericString' => [
                'numeric-string',
                [
                    '"1"',
                    'numeric-string',
                ],
            ],
            'combineNonEmptyListWithTKeyedArrayList' => [
                'array{0: null|string}<int, string>',
                [
                    'non-empty-list<string>',
                    'array{null}'
                ],
            ],
            'combineZeroAndPositiveInt' => [
                '0|positive-int',
                [
                    '0',
                    'positive-int',
                ],
            ],
            'combinePositiveIntAndZero' => [
                '0|positive-int',
                [
                    'positive-int',
                    '0',
                ],
            ],
            'combinePositiveIntAndMinusOne' => [
                'int',
                [
                    'positive-int',
                    '-1',
                ],
            ],
            'combinePositiveIntZeroAndMinusOne' => [
                'int',
                [
                    '0',
                    'positive-int',
                    '-1',
                ],
            ],
            'combineMinusOneAndPositiveInt' => [
                'int',
                [
                    '-1',
                    'positive-int',
                ],
            ],
            'combineZeroMinusOneAndPositiveInt' => [
                'int',
                [
                    '0',
                    '-1',
                    'positive-int',
                ],
            ],
            'combineZeroOneAndPositiveInt' => [
                '0|positive-int',
                [
                    '0',
                    '1',
                    'positive-int',
                ],
            ],
            'combinePositiveIntOneAndZero' => [
                '0|positive-int',
                [
                    'positive-int',
                    '1',
                    '0',
                ],
            ],
            'combinePositiveInts' => [
                'positive-int',
                [
                    'positive-int',
                    'positive-int',
                ],
            ],
            'combineNonEmptyArrayAndKeyedArray' => [
                'array<int, int>',
                [
                    'non-empty-array<int, int>',
                    'array{0?:int}',
                ]
            ],
            'combineNonEmptyStringAndLiteral' => [
                'non-empty-string',
                [
                    'non-empty-string',
                    '"foo"',
                ]
            ],
            'combineLiteralAndNonEmptyString' => [
                'non-empty-string',
                [
                    '"foo"',
                    'non-empty-string'
                ]
            ],
            'combineNonFalsyNonEmptyString' => [
                'non-empty-string',
                [
                    'non-falsy-string',
                    'non-empty-string'
                ]
            ],
            'combineNonEmptyNonFalsyString' => [
                'non-empty-string',
                [
                    'non-empty-string',
                    'non-falsy-string'
                ]
            ],
        ];
    }

    /**
     * @param  string $string
     *
     */
    private static function getAtomic($string): Type\Atomic
    {
        return array_values(Type::parseString($string)->getAtomicTypes())[0];
    }
}
