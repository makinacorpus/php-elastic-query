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
     * @var string
     */
    private $fulltextParameterName = self::PARAM_FULLTEXT_QUERY;

    /**
     * @var float
     */
    private $fulltextRoaming = self::DEFAULT_ROAMING;

    /**
     * @var string
     */
    private $fulltextField = null;

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
     * Default constructor
     *
     * @param Client $client
     */
    public function __construct(Client $client = null)
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
     * Set fulltext query field name
     *
     * @param string $field
     *
     * @return $this
     */
    public function setFulltextField($field)
    {
        $this->fulltextField = $field;

        return $this;
    }

    /**
     * Get fulltext query field name
     *
     * @return string
     */
    public function getFulltextField()
    {
        return $this->fulltextField;
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
    private function getQueryParam(array $request, $name, $default = null)
    {
        if (array_key_exists($name, $request)) {
            return $request[$name];
        }

        return $default;
    }

    /**
     * Get page number from request
     *
     * @param array $request
     *
     * @return int
     */
    private function getPageFromRequest(array $request = [])
    {
        return $this->getQueryParam($request, $this->pageParameterName, 0) + $this->pageDelta;
    }

    /**
     * Prepare current search using the incomming query
     *
     * @param Query $query
     *   User fixed query
     * @param mixed[] $request
     *   Incomming request
     */
    private function prepare(Query $query, array $request = [])
    {
        // Pagination handling
        $this->setPage($this->getPageFromRequest($request));

        // Only process query when there is a value, in order to avoid sending
        // an empty query string to ElasticSearch, its API is so weird that we
        // probably would end up with exceptions
        $value = $this->getQueryParam($request, $this->fulltextParameterName);
        if ($value) {
            $query->getQuery()->matchTerm($this->fulltextField, $value, null, $this->fulltextRoaming);
        }
    }

    /**
     * Alias of the ::execute() method which does not involves Elastic Search
     *
     * Usage is primarily for testing, but it's valid to use as it is public API
     *
     * @param Query $query
     *   User fixed query
     * @param mixed[] $request
     *   Incomming request
     *
     * @return mixed[]
     */
    public function toArray(Query $query, array $request = [])
    {
        if (!$this->index) {
            throw new \RuntimeException("You must set an index");
        }

        // This must be set before the query is being rendered, since some
        // aggregations might alter the query filter; this is not an Elastic
        // Search feature, but that's where we actually do implement the
        // facet logic
        foreach ($query->getAggregations() as $aggregation) {
            $aggregation->apply($query, $request);
        }

        $this->prepare($query, $request);

        $body = $query->toArray();

        $data = [
            'index' => $this->index,
            'type'  => 'node',
            'body'  => $body,
        ];

        if (!empty($this->limit)) {
            $data['size'] = (int)$this->limit;
            if (!empty($this->page)) {
                $data['from'] = max([0, $this->page - 1]) * $this->limit;
            }
        }

        return $data;
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
        return new Response($this, $this->client->search($this->toArray($query, $request)));
    }
}
