<?php

namespace MakinaCorpus\ElasticSearch;

use MakinaCorpus\ElasticSearch\Aggregation\Aggregation;
use MakinaCorpus\ElasticSearch\Aggregation\AggregationAwareTrait;
use MakinaCorpus\Lucene\Query as LuceneQuery;

/**
 * Represents a search query
 *
 * This class will bring you an extensive ES search support, but will never
 * implement it 100% for all the use-case you can't use, you are still allowed
 * to extend it as expected:
 *
 * @code
 *   $query = new Query();
 *   // ... do whatever with the query
 *   // at execute() call, you may pass an arbitrary array of options to merge:
 *   
 * @code
 *
 * @todo
 *   - query, filter, post_filter
 *   - scroll
 *      https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-scroll.html
 *   - highlighting
 *      https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
 *   - fields ? (deprecated)
 *      https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-fields.html
 *   - _source
 *      https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-source-filtering.html
 */
class Query
{
    use AggregationAwareTrait;

    /**
     * @var LuceneQuery
     */
    private $query;

    /**
     * @var LuceneQuery
     */
    private $filter;

    /**
     * @var LuceneQuery
     */
    private $postFilter;

    /**
     * @var Sort[]
     */
    private $sorts = [];

    /**
     * @var string[]
     */
    private $sourceFields = [];

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->query      = new LuceneQuery();
        $this->filter     = new LuceneQuery();
        $this->postFilter = new LuceneQuery();
    }

    /**
     * Get query
     *
     * @return LuceneQuery
     */
    final public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get filter query
     *
     * @return LuceneQuery
     */
    final public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Get post-filter query
     *
     * Using a post-filter query allows you to apply and fetch result for
     * aggregations that are being run *before* this filter is applied to the
     * response, this way, you may aggregate stuff and get the results that
     * ignore the actual filtered query
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/guide/current/_post_filter.html
     *
     * @return LuceneQuery
     */
    final public function getPostFilter()
    {
        return $this->postFilter;
    }

    /**
     * Add sort
     *
     * @param string $field
     *   Field name to sort on
     * @param string $order
     *   One of the Sort::ORDER_* constants
     * @param string $mode
     *   One of the Sort::MODE_* constants
     * @param string $missing
     *   One of the Sort::MISSING_* constants
     *
     * @return $this
     */
    public function addSort($field = Sort::FIELD_SCORE, $order = null, $mode = null, $missing = null)
    {
        $this->sorts[] = new Sort($field, $order, $mode, $missing);

        return $this;
    }

    /**
     * Add field to returned fields
     *
     * @param string|string[] $fields
     *
     * @return $this
     */
    public function addSourceFields($fields)
    {
        if (!is_array($this->sourceFields)) {
            $this->sourceFields = [];
        }

        if (!is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($fields as $field) {
            if (!in_array($field, $this->sourceFields)) {
                $this->sourceFields[] = $field;
            }
        }

        return $this;
    }

    /**
     * Disable source fetch
     *
     * If you call setSourceFields() after this, _source will be enabled back
     *
     * @return $this
     */
    public function disableSource()
    {
        $this->sourceFields = false;

        return $this;
    }

    /**
     * Build sort clause
     *
     * @return mixed[]
     */
    private function applySorts()
    {
        $body = [];

        foreach ($this->sorts as $sort) {
            $array = $sort->toArray();

            if (empty($array)) {
                // Giving a simple string is allowed
                $body[] = $sort->getField();
            } else {
                $body[][$sort->getField()] = $array;
            }
        }

        return $body;
    }

    /**
     * Build aggregation clause
     *
     * @return mixed[]
     */
    private function applyAggregations()
    {
        $body = [];

        foreach ($this->getAggregations() as $aggregation) {
            $body[$aggregation->getName()] = $aggregation->toArray();
        }

        return $body;
    }

    /**
     * Execute current query and return response
     *
     * @param mixed $overrides
     *   This array will be recursively merged with the raw body array, please
     *   not that any conflicting key will be squashed using the function
     *   parameter
     *
     * @return mixed[]
     */
    public function toArray(array $overrides = [])
    {
        $body = [];

        if ($this->filter->isEmpty()) {
            if ($this->query->isEmpty()) {
                $body = [
                    'query' => [
                        'match_all' => []
                    ],
                ];
            } else {
                $body = [
                    'query' => [
                        'query_string' => [
                            'query' => (string)$this->query,
                        ]
                    ],
                ];
            }
        } else {
            if ($this->query->isEmpty()) {
                $body = [
                    'query' => [
                        'constant_score' => [
                            'filter' => [
                                'fquery' => [
                                    'query' => [
                                        'query_string' => [
                                            'query' => (string)$this->filter
                                        ],
                                    ],
                                    // @todo Without this ElasticSearch seems to
                                    // throw exceptions...
                                    '_cache' => true,
                                ],
                            ],
                        ],
                    ],
                ];
            } else {
                $body = [
                    'query' => [
                        'filtered' => [
                            'query'  => [
                               'query_string' => [
                                   'query' => (string)$this->query
                               ],
                            ],
                            'filter' => [
                                'fquery' => [
                                    'query' => [
                                        'query_string' => [
                                            'query' => (string)$this->filter
                                        ],
                                    ],
                                    // @todo Without this ElasticSearch seems to
                                    // throw exceptions...
                                    '_cache' => true,
                                ],
                            ],
                        ],
                    ],
                ];
            }
        }

        if (!$this->postFilter->isEmpty()) {
            $body['post_filter']['query_string']['query'] = (string)$this->postFilter;
        }

        if ($this->sorts) {
            $body['sort'] = $this->applySorts();
        }

        if ($this->hasAggregations()) {
            $body['aggs'] = $this->applyAggregations();
        }

        if ($overrides) {
            $body = ArrayUtil::merge($body, $overrides);
        }

        return $body;
    }
}
