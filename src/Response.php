<?php

namespace MakinaCorpus\ElasticSearch;

use Elasticsearch\Client;

class Response
{
    private $body;
    private $query;

    /**
     * Default constructor
     *
     * @param Query $query
     * @param mixed[] $body
     *   Response raw body
     */
    public function __construct(Query $query, $body)
    {
        $this->query = $query;
        $this->body = $body;
    }
}
