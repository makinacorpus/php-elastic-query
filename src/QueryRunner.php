<?php

namespace MakinaCorpus\ElasticSearch;

use Elasticsearch\Client;

/**
 * Object reponsible for applying user input and execute query
 */
class QueryRunner
{
    /**
     * Default fulltext query parameter name
     */
    const PARAM_FULLTEXT_QUERY = 's';

    /**
     * Page parameter name
     */
    const PARAM_PAGE = 'page';

    /**
     * Default fulltext search romain and fuziness value
     */
    const DEFAULT_ROAMING = 0.8;

    /**
     * Default page limit
     */
    const DEFAULT_LIMIT = 100;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $index;

    /**
     * @var int
     */
    private $limit = self::DEFAULT_LIMIT;

    /**
     * @var int
     */
    private $page = 1;

    /**
     * @var string[]
     */
    private $fields = [];

    /**
     * @var string
     */
    private $fulltextParameterName = self::PARAM_FULLTEXT_QUERY;

    /**
     * @var string
     */
    private $pageParameterName = self::PARAM_PAGE;

    /**
     * Drupal paging start with 0, Elastic search one starts with 1
     *
     * @var int
     */
    private $pageDelta = 1;

    /**
     * @var float
     */
    private $fulltextRoaming = self::DEFAULT_ROAMING;

    /**
     * Default constructor
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Set limit
     *
     * @param int $limit
     *   Positive integer or null if no limit
     *
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get current limit
     *
     * @return int
     *   Positive integer or null if no limit
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Set page
     *
     * @param int $page
     *   Positive integer or null or 1 for first page
     *
     * @return $this
     */
    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get current page
     *
     * @return int
     *   Positive integer or null if no limit
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set index
     *
     * @param string $index
     *
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Set returned fields
     *
     * @param string[]
     *
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Add field to returned fields
     *
     * @param string $field
     *
     * @return $this
     */
    public function addField($field)
    {
        if (!in_array($field, $this->fields)) {
            $this->fields[] = $field;
        }

        return $this;
    }

    /**
     * Set fulltext query parameter name
     *
     * @param string $parameterName
     *
     * @return $this
     */
    public function setFulltextParameterName($parameterName)
    {
        $this->fulltextParameterName = $parameterName;

        return $this;
    }

    /**
     * Get fulltext query parameter name
     *
     * @return string
     */
    public function getFulltextParameterName()
    {
        return $this->fulltextParameterName;
    }

    /**
     * Set fulltext roaming and fuziness value, should be between 0 and 1
     *
     * @param float $value
     *
     * @return $this
     */
    public function setFulltextRoaming($value)
    {
        $this->fulltextRoaming = (float)$value;

        return $this;
    }

    /**
     * Set page delta
     *
     * @param int $value
     *
     * @return $this
     */
    public function setPageDelta($value)
    {
        $this->pageDelta = (int)$value;

        return $this;
    }

    /**
     * Set page parameter name
     *
     * @param string $parameterName
     *
     * @return $this
     */
    public function setPageParameter($parameterName)
    {
        $this->pageParameterName = (string)$parameterName;

        return $this;
    }

    /**
     * Get query parameter from given array
     *
     * @param array $query
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    private function getQueryParam($query, $name, $default = null)
    {
        if (array_key_exists($name, $query)) {
            return $query[$name];
        }

        return $default;
    }

    /**
     * Build aggregations query data
     *
     * @return string[]
     */
    private function buildAggQueryData($query)
    {
        $ret = [];

        foreach ($this->aggregations as $agg) {
            $additions = $agg->buildQueryData($this, $query);
            if ($additions) {
                $ret = array_merge($ret, $additions);
            }
        }

        return $ret;
    }

    /**
     * Prepare current search using the incomming query
     *
     * @param string[] $query
     */
    private function prepare($query)
    {
        foreach ($this->aggregations as $agg) {
            $agg->prepareQuery($this, $query);
        }

        // Handle paging
        $this->setPage(
            $this->getQueryParam($query, $this->pageParameterName, 0)
                + $this->pageDelta
        );

        // Only process query when there is a value, in order to avoid sending
        // an empty query string to ElasticSearch, its API is so weird that we
        // probably would end up with exceptions
        $value = $this->getQueryParam($query, $this->fulltextParameterName);
        if ($value) {
            $this
                ->getQuery()
                ->matchTerm(
                    'combined',
                    $value,
                    null,
                    $this->fulltextRoaming
                )
            ;
        }
    }

    /**
     * Execute query
     *
     * @param Query $query
     *   User fixed query
     * @param mixed[] $request
     *   Incomming request
     *
     * @return Response
     */
    public function execute(Query $query, array $request = [])
    {
        if (!$this->index) {
            throw new \RuntimeException("You must set an index");
        }

        $this->prepare($query);

        // This must be set before filter since filter query will be altered by
        // the applied aggregations
        $aggs = $this->buildAggQueryData($query);

// HERE WAS QUERY

/*
        if ($this->fields) {
            $body['fields'] = $this->fields;
        }*/

        $data = [
            'index' => $this->index,
            'type'  => 'node',
            'body'  => $body,
        ];

        if (!empty($this->limit)) {
            $data['size'] = $this->limit;
            if (!empty($this->page)) {
                $data['from'] = max([0, $this->page - 1]) * $this->limit;
            }
        }

        return new Response($this, $this->client->search($data));
    }
}
