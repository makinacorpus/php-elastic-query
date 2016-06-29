<?php

namespace MakinaCorpus\ElasticSearch\Tests;

use MakinaCorpus\ElasticSearch\Aggregation\GenericAggregation;
use MakinaCorpus\ElasticSearch\Sort;
use MakinaCorpus\ElasticSearch\Query;

class SortTest extends \PHPUnit_Framework_TestCase
{
    public function testSortBuild()
    {
        $sort = new Sort('some');
        $this->assertSame([], $sort->toArray());

        $sort = new Sort('some', Sort::ORDER_DESC);
        $this->assertSame([
            'order' => 'desc',
        ], $sort->toArray());

        $sort = new Sort('some', null, Sort::MODE_MAX);
        $this->assertSame([
            'mode' => 'max',
        ], $sort->toArray());

        $sort = new Sort('some', null, null, Sort::MISSING_LAST);
        $this->assertSame([
            'missing' => '_last',
        ], $sort->toArray());

        $sort = new Sort('some', Sort::ORDER_ASC, Sort::MODE_MAX, Sort::MISSING_FIRST);
        $this->assertSame([
            'order' => 'asc',
            'mode' => 'max',
            'missing' => '_first',
        ], $sort->toArray());
    }

    public function testSortBuildInQuery()
    {
        $query = new Query();

        $this->assertSame([], $query->toArray());

        $query->addSort('foo');
        $this->assertSame([
            'sort' => [
                'foo',
            ],
        ], $query->toArray());

        $query->addSort('bar', Sort::ORDER_DESC);
        $this->assertSame([
            'sort' => [
                'foo',
                [
                    'bar' => [
                        'order' => 'desc',
                    ],
                ],
            ],
        ], $query->toArray());

        $query = new Query();
        $query->addSort('c', null, Sort::MODE_MAX);
        $query->addSort('d', Sort::ORDER_ASC, Sort::MODE_MAX, Sort::MISSING_FIRST);
        $this->assertSame([
            'sort' => [
                [
                    'c' => [
                        'mode' => 'max',
                    ],
                ],
                [
                    'd' => [
                        'order' => 'asc',
                        'mode' => 'max',
                        'missing' => '_first',
                    ],
                ],
            ],
        ], $query->toArray());
    }
}
