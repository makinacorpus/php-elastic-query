<?php

namespace MakinaCorpus\ElasticSearch\Tests;

use MakinaCorpus\ElasticSearch\Aggregation\GenericAggregation;
use MakinaCorpus\ElasticSearch\Query;
use MakinaCorpus\ElasticSearch\Aggregation\BucketAggregationResponse;

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
            'query' => ['match_all' => []],
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
        // @todo
    }

    public function testGenericResponseBuckets()
    {
        // For example, https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-daterange-aggregation.html
        $raw = <<<EOT
{
    "aggregations": {
        "range": {
            "buckets": [
                {
                    "to": 1.3437792E+12,
                    "to_as_string": "08-2012",
                    "doc_count": 7
                },
                {
                    "from": 1.3437792E+12,
                    "from_as_string": "08-2012",
                    "doc_count": 2
                }
            ]
        }
    }
}
EOT;
        $raw = json_decode($raw, true)['aggregations']['range'];
        $aggregation = new GenericAggregation("range", "date_range");
        $aggregation->setIsBucketAggregation(true);
        $response = $aggregation->getResponse($raw);

        if (!$response instanceof BucketAggregationResponse) {
            $this->fail();
        }

        $this->assertTrue($response->hasBuckets());
        $this->assertCount(2, $response->getBuckets());

        $buckets = $response->getBuckets();
        $this->assertSame(7, $buckets[0]->get('doc_count'));
        $this->assertSame(2, $buckets[1]->get('doc_count'));

        // Another fun one, https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-top-hits-aggregation.html
        $raw = <<<EOT
{
  "aggregations": {
    "top-tags": {
       "buckets": [
          {
             "key": "windows-7",
             "doc_count": 25365,
             "top_tags_hits": {
                "hits": {
                   "total": 25365,
                   "max_score": 1,
                   "hits": [
                      {
                         "_index": "stack",
                         "_type": "question",
                         "_id": "602679",
                         "_score": 1,
                         "_source": {
                            "title": "Windows port opening"
                         },
                         "sort": [
                            1370143231177
                         ]
                      }
                   ]
                }
             }
          },
          {
             "key": "linux",
             "doc_count": 18342,
             "top_tags_hits": {
                "hits": {
                   "total": 18342,
                   "max_score": 1,
                   "hits": [
                      {
                         "_index": "stack",
                         "_type": "question",
                         "_id": "602672",
                         "_score": 1,
                         "_source": {
                            "title": "Ubuntu RFID Screensaver lock-unlock"
                         },
                         "sort": [
                            1370143379747
                         ]
                      }
                   ]
                }
             }
          },
          {
             "key": "windows",
             "doc_count": 18119,
             "top_tags_hits": {
                "hits": {
                   "total": 18119,
                   "max_score": 1,
                   "hits": [
                      {
                         "_index": "stack",
                         "_type": "question",
                         "_id": "602678",
                         "_score": 1,
                         "_source": {
                            "title": "If I change my computers date / time, what could be affected?"
                         },
                         "sort": [
                            1370142868283
                         ]
                      }
                   ]
                }
             }
          }
       ]
    }
  }
}
EOT;
        $raw = json_decode($raw, true)['aggregations']['top-tags'];

        $topHitsAggregation = new GenericAggregation("top_tags_hits", "top_hits");
        $topHitsAggregation->setIsBucketAggregation(false);

        $termAggregation = new GenericAggregation("top-tags", "terms");
        $termAggregation->addAggregation($topHitsAggregation);
        $termAggregation->setIsBucketAggregation(true);

        // The main aggregation

        $response = $termAggregation->getResponse($raw);

        if (!$response instanceof BucketAggregationResponse) {
            $this->fail();
        }

        $this->assertTrue($response->hasBuckets());
        $this->assertCount(3, $response->getBuckets());

        $buckets = $response->getBuckets();

        $this->assertSame(25365, $buckets[0]->get('doc_count'));
        $this->assertSame(18342, $buckets[1]->get('doc_count'));
        $this->assertSame(18119, $buckets[2]->get('doc_count'));

        $this->assertSame("windows-7", $buckets[0]->get('key'));
        $this->assertSame("linux", $buckets[1]->get('key'));
        $this->assertSame("windows", $buckets[2]->get('key'));

        // Since the other is a sub aggregation, we should find the results
        // inside each bucket

        // @todo
    }
}
