<?php

namespace MakinaCorpus\ElasticSearch;

trait PartialResponseTrait
{
    protected $body;

    /**
     * Default constructor
     *
     * @param array $body
     *   Raw aggregation response body
     */
    public function __construct(array $body)
    {
        $this->body = $body;
    }

    /**
     * Get raw aggregation body response
     *
     * @return mixed[]
     */
    final public function getBody()
    {
        return $this->body;
    }

    /**
     * Get an arbitrary value in the response body
     *
     * @param string $name
     *
     * @return mixed
     */
    final public function get($name)
    {
        if (!isset($this->body[$name])) {
            return null;
        }

        return $this->body[$name];
    }
}
