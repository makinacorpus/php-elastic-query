<?php

namespace MakinaCorpus\ElasticSearch\Tests;

use MakinaCorpus\ElasticSearch\Query;
use MakinaCorpus\ElasticSearch\QueryRunner;
use MakinaCorpus\ElasticSearch\Sort;
use MakinaCorpus\ElasticSearch\Aggregation\GenericAggregation;

/**
 * Tests a few basic use cases based upon real use cases
 */
class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * This one comes from an admin screen we use in some project, you don't
     * have to understand everything, but it's basically what it does in the
     * end and why the API was originally built
     */
    public function testAdminListing()
    {
        $query = new Query();

        // This admin screen will display site content, but only content which
        // is "flagged" (flagged as no meaning here, it's only in order to be
        // iso with the original use case) - also add some other term matching
        // for fun...
        $query
            ->getFilter()
            ->matchTerm('is_flagged', 1)
            ->matchTerm('site_id', 12)
        ;

        // This is a facet based search, so initialize facets
        /*
        $ret[] = $query
            ->createFacet('type', null)
            ->setChoicesMap(node_type_get_names())
            ->setTitle($this->t("Type"))
        ;

        $ret[] = $query
            ->setChoicesCallback(function ($values) {
                if ($accounts = user_load_multiple($values)) {
                    foreach ($accounts as $index => $account) {
                        $accounts[$index] = filter_xss(format_username($account));
                    }
                    return $accounts;
                }
            })
            ->setTitle($this->t("Owner"))
        ;

        $ret[] = $this
            ->getSearch()
            ->createFacet('tags', null)
            ->setChoicesCallback(function ($values) {
                if ($terms = taxonomy_term_load_multiple($values)) {
                    foreach ($terms as $index => $term) {
                        $terms[$index] = check_plain($term->name);
                    }
                    return $terms;
                }
            })
            ->setTitle($this->t("Tags"))
        ;

        $ret[] = $this
            ->getSearch()
            ->createFacet('status', null)
            ->setChoicesMap([0 => $this->t("Unpublished"), 1 => $this->t("Published")])
            ->setExclusive(true)
            ->setTitle($this->t("Status"))
        ;
          */

        // Finalize query
        $query
            ->addSort('updated', Sort::ORDER_DESC)
            ->addSourceFields('_id')
            ->addSourceFields(['_id', 'title', 'created'])
        ;

        // Consider that request is something that comes from heaven (in another
        // words, unsafe user input as HTTP request parameters)
        $request = [
            'q'     => 'I want to break free',
            'tags'  => [27, 32],
            'page'  => 3,
        ];

        $queryArray = (new QueryRunner())
            ->setIndex('my_content')
            ->setFulltextField('text_combined')
            ->setFulltextParameterName('q')
            ->setPageParameter('page')
            // Note that the offset (from in elastic language) 
            ->setLimit(20)
            ->toArray($query, $request)
        ;

        $this->assertSame(
            [
                'index' => 'my_content',
                'type' => 'node',
                'body' => [
                    'query' => [
                        'filtered' => [
                            'query' => [
                                'query_string' => [
                                    'query' => 'text_combined:"I want to break free"~0.8',
                                ],
                            ],
                            'filter' => [
                                'fquery' => [
                                    'query' => [
                                        'query_string' => [
                                            'query' => '(is_flagged:1 site_id:12)',
                                        ],
                                    ],
                                    '_cache' => true,
                                ],
                            ],
                        ],
                    ],
                    'sort' => [
                        0 => [
                            'updated' => [
                                'order' => 'desc',
                            ],
                        ],
                    ],
                ],
                'size' => 20,
                'from' => 60,
            ],
            $queryArray
        );
    }

    /**
     * This one comes from an a specific client need, the ability to search with
     * some facet-like display, but not really, within tagged content with top 3
     * results for each tag in the index
     */
    public function testCustomTopHitBasedSearch()
    {
        $query = new Query();

        $allowedTypes = [
            'page'  => "Page",
            'news'  => "News",
            'event' => "Event",
        ];

        $topHits = new GenericAggregation('type_top_hits', 'top_hits');
        $topHits->setBody([
            '_source' => [
                'include' => ['_id', 'title'],
            ],
            'size' => 3,
        ]);

        $terms = new GenericAggregation('terms', 'terms');
        $terms->setIsBucketAggregation(true);
        $terms->setBody(['field' => 'type']);
        $terms->addAggregation($topHits);

        $counts = new GenericAggregation('type_count', 'value_count');
        $counts->setBody(['field' => 'type']);
        $counts->setApplyOnPostFilter(true);
        $counts->setApplyCallback(function (Query $query, array $request = []) use ($allowedTypes) {
            // This aggregation is post filter, no need to recheck
            if (isset($request['type'])) {
                $query->getPostFilter()->matchTerm('type', $request['type']);
            }
        });

        // Filter the base query upon the allowed types
        $query
            ->addAggregation($terms)
            ->addAggregation($counts)
            ->getFilter()
            ->matchTermCollection('type', array_keys($allowedTypes))
            ->matchTerm('status', 1)
        ;

        // Consider that request is something that comes from heaven (in another
        // words, unsafe user input as HTTP request parameters)
        $request = [];

        $runner = (new QueryRunner())
            ->setIndex('my_content')
            ->setFulltextField('text_combined')
            ->setFulltextParameterName('q')
            ->setPageParameter('page')
            // Note that the offset (from in elastic language) 
            ->setLimit(20)
        ;

        $queryArray = $runner->toArray($query, $request);

        $this->assertSame(
            [
                'index' => 'my_content',
                'type' => 'node',
                'body' => [
                    'query' => [
                        'constant_score' => [
                            'filter' => [
                                'fquery' => [
                                    'query' => [
                                        'query_string' => [
                                            'query' => '(type:(page OR news OR event) status:1)',
                                        ],
                                    ],
                                    '_cache' => true,
                                ],
                            ],
                        ],
                    ],
                    'aggs' => [
                        'terms' => [
                            'terms' => [
                                'field' => 'type',
                            ],
                            'aggs' => [
                                'type_top_hits' => [
                                    'top_hits' => [
                                        '_source' => [
                                            'include' => ['_id', 'title'],
                                        ],
                                        'size' => 3,
                                    ],
                                ],
                            ],
                        ],
                        'type_count' => [
                            'value_count' => [
                                'field' => 'type',
                            ],
                        ],
                    ],
                ],
                'size' => 20,
                'from' => 0,
            ],
            $queryArray
        );

        // I changed my mind
        $request = [
            'type' => 'page',
            'q' => "Oh you...",
        ];

        $queryArray = $runner->toArray($query, $request);

        $this->assertSame(
            [
                'index' => 'my_content',
                'type' => 'node',
                'body' => [
                    'query' => [
                        'filtered' => [
                            'query' => [
                                'query_string' => [
                                    'query' => 'text_combined:"Oh you..."~0.8',
                                ],
                            ],
                            'filter' => [
                                'fquery' => [
                                    'query' => [
                                        'query_string' => [
                                            'query' => '(type:(page OR news OR event) status:1)',
                                        ],
                                    ],
                                    '_cache' => true,
                                ],
                            ],
                        ],
                    ],
                    'post_filter' => [
                        'query_string' => [
                            'query' => 'type:page',
                        ],
                    ],
                    'aggs' => [
                        'terms' => [
                            'terms' => [
                                'field' => 'type',
                            ],
                            'aggs' => [
                                'type_top_hits' => [
                                    'top_hits' => [
                                        '_source' => [
                                            'include' => ['_id', 'title'],
                                        ],
                                        'size' => 3,
                                    ],
                                ],
                            ],
                        ],
                        'type_count' => [
                            'value_count' => [
                                'field' => 'type',
                            ],
                        ],
                    ],
                ],
                'size' => 20,
                'from' => 0,
            ],
            $queryArray
        );
    }
}
