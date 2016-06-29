<?php

namespace MakinaCorpus\ElasticSearch\Tests;

use MakinaCorpus\ElasticSearch\Aggregation\GenericAggregation;
use MakinaCorpus\ElasticSearch\Query;

class AggregationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Builds generic aggregations and ensuire the body is correct
     */
    public function testGenericBuild()
    {
        $body = [
            'some' => 'value',
            'and' => ['other' => 'value'],
        ];

        // Pass the body using the setter
        $aggregation = new GenericAggregation('some_agg', 'some_type');
        $aggregation->setBody($body);
        $aggregation->setMeta(['some' => 'meta']);

        $this->assertSame('some_agg', $aggregation->getName());
        $this->assertSame('some_type', $aggregation->getType());

        $this->assertSame([
            'some_type' => [
                'some' => 'value',
                'and' => ['other' => 'value'],
            ],
            'meta' => [
                'some' => 'meta',
            ],
        ], $aggregation->toArray());

        // Pass the body directly into the constructor
        $aggregation = new GenericAggregation('some_agg', 'some_type', $body);

        $this->assertSame([
            'some_type' => [
                'some' => 'value',
                'and' => ['other' => 'value'],
            ],
        ], $aggregation->toArray());
    }

    /**
     * Adds a few sub-aggregation, ensure consitency and body is correct
     */
    public function testSubAggregationBuild()
    {
        $aggregation = new GenericAggregation('a', 'type_a');
        $aggregation->setBody(['a']);

        $aggregation->addAggregation(new GenericAggregation('b', 'type_b', ['value_b' => 'foo']));
        $aggregation->addAggregation(new GenericAggregation('c', 'type_c', ['value_c' => 'bar']));

        try {
            $aggregation->addAggregation(new GenericAggregation('b', 'type_b', ['value_b' => 'foo']));
            $this->fail();
        } catch (\Exception $e) {}

        $this->assertSame([
            'type_a' => [
                'a',
            ],
            'aggs' => [
                'b' => [
                    'type_b' => [
                        'value_b' => 'foo',
                    ],
                ],
                'c' => [
                    'type_c' => [
                        'value_c' => 'bar',
                    ],
                ],
            ],
        ], $aggregation->toArray());
    }

    public function testSubAggregationBuildInQuery()
    {
        $aggregation = new GenericAggregation('a', 'type_a');
        $aggregation->setBody(['pouet']);

        $aggregation->addAggregation(new GenericAggregation('b', 'type_b', ['value_b' => 'foo']));
        $aggregation->addAggregation(new GenericAggregation('c', 'type_c', ['value_c' => 'bar']));

        try {
            $aggregation->addAggregation(new GenericAggregation('b', 'type_b', ['value_b' => 'foo']));
            $this->fail();
        } catch (\Exception $e) {}

        $query = new Query();
        $query->addAggregation($aggregation);

        $this->assertSame([
            'aggs' => [
                'a' => [
                    'type_a' => [
                        'pouet',
                    ],
                    'aggs' => [
                        'b' => [
                            'type_b' => [
                                'value_b' => 'foo',
                            ],
                        ],
                        'c' => [
                            'type_c' => [
                                'value_c' => 'bar',
                            ],
                        ],
                    ],
                ],
            ],
        ], $query->toArray());
    }

    /**
     * Provide a stupid raw result and ensure result is correct
     */
    public function testGenericResponse()
    {
        
    }
}
