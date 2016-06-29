<?php

namespace MakinaCorpus\ElasticSearch\Tests;

use MakinaCorpus\ElasticSearch\Aggregation\GenericAggregation;
use MakinaCorpus\ElasticSearch\ArrayUtil;

class ArrayUtilTest extends \PHPUnit_Framework_TestCase
{
    public function testMerge()
    {
        $this->assertSame(
            [],
            ArrayUtil::merge(
                [],
                []
            )
        );

        $this->assertSame(
            ['a'],
            ArrayUtil::merge(
                ['a'],
                []
            )
        );

        $this->assertSame(
            [
                0 => ['c' => false],
                '137' => 12,
                'touloulou' => [
                    'a' => 12,
                    'b' => 24,
                    'd' => 78,
                    'e' => 1,
                ],
                1 => 'b',
                2 => '12',
            ],
            ArrayUtil::merge(
                [
                    'b',
                    '137' => 12,
                    'touloulou' => [
                        'a' => 12,
                        'b' => 24,
                        'd' => 137,
                    ],
                ],
                [
                    ['c' => false],
                    'b',
                    '12',
                    'touloulou' => [
                        'b' => 24,
                        'd' => 78,
                        'e' => 1,
                    ],
                ]
            )
        );
    }
}
