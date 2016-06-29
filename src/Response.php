<?php

namespace MakinaCorpus\ElasticSearch;

use Elasticsearch\Client;

class Response
{
    use ResponseTrait;

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

        $this->parseHits($body);
    }
}
