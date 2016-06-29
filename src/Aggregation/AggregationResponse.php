<?php

namespace MakinaCorpus\ElasticSearch\Aggregation;

use MakinaCorpus\ElasticSearch\PartialResponseTrait;

/**
 * Represents a bucket aggregation response
 */
class AggregationResponse
{
    use PartialResponseTrait;

    /**
     * @var string
     */
    private $aggregationName;

    /**
     * @var Bucket[]
     */
    private $buckets;

    /**
     * Default constructor
     *
     * @param string $aggregationName
     * @param array $body
     */
    public function __construct($aggregationName, array $body)
    {
        $this->body = $body;
        $this->aggregationName = $aggregationName;

        $this->buckets = $this->parseBuckets($body, $aggregationName);
    }

    /**
     * Parse bucktes from given content
     *
     * @param mixed[] $body
     * @param string $aggregationName
     *
     * @return Bucket[]
     */
    protected function parseBuckets($body, $aggregationName)
    {
        $ret = [];

        if (empty($body['buckets'])) {
            return $ret;
        }

        foreach ($body['buckets'] as $bucket) {
            $ret[] = new Bucket($bucket);
        }

        return $ret;
    }

    /**
     * Is there any buckets in this response
     *
     * @return boolean
     */
    final public function hasBuckets()
    {
        return !empty($this->buckets);
    }

    /**
     * Get all buckets
     *
     * @return Bucket[]
     */
    final public function getBuckets()
    {
        return $this->buckets;
    }
}
