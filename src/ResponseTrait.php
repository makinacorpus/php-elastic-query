<?php

namespace MakinaCorpus\ElasticSearch;

trait ResponseTrait
{
    private $docCount = null;
    private $maxScore = null;
    private $documents = [];
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

        $this->parseHits($body);
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

    /**
     * Parse hits from response
     *
     * @return Document[]
     */
    final protected function parseHits($body)
    {
        if (isset($body['hits']['total'])) {
            $this->docCount = (int)$body['hits']['total'];
        }
        if (isset($body['hits']['max_score'])) {
            $this->maxScore = (int)$body['hits']['max_score'];
        }

        if (isset($body['hits']['hits'])) {
            foreach ($body['hits']['hits'] as $document) {
                $this->documents[] = new Document($document);
            }
        }
    }

    /**
     * Get total doc count
     *
     * @return int
     */
    final public function getDocCount()
    {
        if (null === $this->docCount) {
            return $this->get('doc_count');
        }

        return $this->docCount;
    }

    /**
     * Get max score
     *
     * @return int
     */
    final public function getMaxScore()
    {
        return $this->maxScore;
    }

    /**
     * Is there any hits in this response
     *
     * @return boolean
     */
    final public function hasHits()
    {
        return !empty($this->documents);
    }

    /**
     * Get all hits
     *
     * @return Document[]
     */
    final public function getHits()
    {
        return $this->documents;
    }
}
