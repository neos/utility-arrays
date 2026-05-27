<?php

declare(strict_types=1);

namespace Neos\Utility\Arrays\Tests\Unit;

/*
 * This file is part of the Neos.Utility.Arrays package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Exception\InvalidPositionException;
use Neos\Utility\PositionalArraySorter;

/**
 * Tests for the PositionalArraySorter utility class
 */
final class PositionalArraySorterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function toArraySortsNumericKeysIfNoPositionMetaDataIsSet()
    {
        $array = [2 => 'foo', 1 => 'bar', 'z' => 'baz', 'a' => 'quux'];
        $expectedResult = ['z' => 'baz', 'a' => 'quux', 1 => 'bar', 2 => 'foo'];

        $positionalArraySorter = new PositionalArraySorter($array);
        $sortedArray = $positionalArraySorter->toArray();
        self::assertSame($expectedResult, $sortedArray);
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function invalidPositions(): \Iterator
    {
        yield ['subject' => ['foo' => ['position' => 'invalid'], 'first' => []]];
        yield ['subject' => ['foo' => ['position' => 'start123'], 'first' => []]];
        yield ['subject' => ['foo' => ['position' => 'start 12 34'], 'first' => []]];
        yield ['subject' => ['foo' => ['position' => 'after 12 34 56'], 'first' => []]];
        yield ['subject' => ['third' => ['position' => 'before nonexisting'], 'first' => []]];
        yield ['subject' => ['third' => ['position' => 'after nonexisting'], 'first' => []]];
    }

    /**
     * @test
     * @dataProvider invalidPositions
     *
     * @param array $subject
     */
    public function toArrayThrowsExceptionForInvalidPositions(array $subject)
    {
        $this->expectException(InvalidPositionException::class);
        $positionalArraySorter = new PositionalArraySorter($subject);
        $positionalArraySorter->toArray();
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function sampleArrays(): \Iterator
    {
        yield [
            'message' => 'Position end should put element to end',
            'subject' => ['second' => ['__meta' => ['position' => 'end']], 'first' => []],
            'positionPropertyPath' => '__meta.position',
            'expectedArrayKeys' => ['first', 'second']
        ];
        yield [
            'message' => 'Position start should put element to start',
            'subject' => ['second' => [], 'first' => ['__meta' => ['position' => 'start']]],
            'positionPropertyPath' => '__meta.position',
            'expectedArrayKeys' => ['first', 'second']
        ];
        yield [
            'message' => 'Position start should respect priority',
            'subject' => ['second' => ['__meta' => ['position' => 'start 50']], 'first' => ['__meta' => ['position' => 'start 52']]],
            'positionPropertyPath' => '__meta.position',
            'expectedArrayKeys' => ['first', 'second']
        ];
        yield [
            'message' => 'Position end should respect priority',
            'subject' => ['second' => ['__meta' => ['position' => 'end 17']], 'first' => ['__meta' => ['position' => 'end']]],
            'positionPropertyPath' => '__meta.position',
            'expectedArrayKeys' => ['first', 'second']
        ];
        yield [
            'Positional numbers are in the middle',
            'subject' => ['last' => ['__meta' => ['position' => 'end']], 'second' => ['__meta' => ['position' => '17']], 'first' => ['__meta' => ['position' => '5']], 'third' => ['__meta' => ['position' => '18']]],
            'positionPropertyPath' => '__meta.position',
            'expectedArrayKeys' => ['first', 'second', 'third', 'last']
        ];
        yield [
            'message' => 'Position before adds before named element if present',
            'subject' => ['second' => [], 'first' => ['__meta' => ['position' => 'before second']]],
            'positionPropertyPath' => '__meta.position',
            'expectedArrayKeys' => ['first', 'second']
        ];
        yield [
            'message' => 'Position before uses priority when referencing the same element; The higher the priority the closer before the element gets added.',
            'subject' => ['third' => [], 'second' => ['__meta' => ['position' => 'before third']], 'first' => ['__meta' => ['position' => 'before third 12']]],
            'positionPropertyPath' => '__meta.position',
            'expectedArrayKeys' => ['second', 'first', 'third']
        ];
        yield [
            'message' => 'Position before works recursively',
            'subject' => ['third' => [], 'second' => ['__meta' => ['position' => 'before third']], 'first' => ['__meta' => ['position' => 'before second']]],
            'positionPropertyPath' => '__meta.position',
            'expectedArrayKeys' => ['first', 'second', 'third']
        ];
        yield [
            'Position after adds after named element if present',
            'subject' => ['second' => ['__meta' => ['position' => 'after first']], 'first' => []],
            'positionPropertyPath' => '__meta.position',
            'expectedArrayKeys' => ['first', 'second']
        ];
        yield [
            'message' => 'Position after uses priority when referencing the same element; The higher the priority the closer after the element gets added.',
            'subject' => ['third' => ['__meta' => ['position' => 'after first']], 'second' => ['__meta' => ['position' => 'after first 12']], 'first' => []],
            'positionPropertyPath' => '__meta.position',
            'expectedArrayKeys' => ['first', 'second', 'third']
        ];
        yield [
            'message' => 'Position after works recursively',
            'subject' => ['third' => ['__meta' => ['position' => 'after second']], 'second' => ['__meta' => ['position' => 'after first']], 'first' => []],
            'positionPropertyPath' => '__meta.position',
            'expectedArrayKeys' => ['first', 'second', 'third']
        ];
        yield [
            'message' => 'Array keys may contain special characters',
            'subject' => ['thi:rd' => ['position' => 'end'], 'sec.ond' => ['position' => 'before thi:rd'], 'fir-st' => ['position' => 'before sec.ond']],
            'positionPropertyPath' => 'position',
            'expectedArrayKeys' => ['fir-st', 'sec.ond', 'thi:rd']
        ];
    }

    /**
     * @test
     * @dataProvider sampleArrays
     *
     * @param string $message
     * @param array $subject
     * @param string $positionPropertyPath
     * @param array $expectedArrayKeys
     */
    public function toArrayTests($message, array $subject, $positionPropertyPath, array $expectedArrayKeys)
    {
        $positionalArraySorter = new PositionalArraySorter($subject, $positionPropertyPath);
        $result = $positionalArraySorter->toArray();

        self::assertSame($expectedArrayKeys, array_keys($result), $message);
    }

    /**
     * @test
     * @dataProvider sampleArrays
     *
     * @param string $message
     * @param array $subject
     * @param string $positionPropertyPath
     * @param array $expectedArrayKeys
     */
    public function getSortedKeysTests($message, array $subject, $positionPropertyPath, array $expectedArrayKeys)
    {
        $positionalArraySorter = new PositionalArraySorter($subject, $positionPropertyPath);
        $result = $positionalArraySorter->getSortedKeys();

        self::assertSame($expectedArrayKeys, $result, $message);
    }
}
