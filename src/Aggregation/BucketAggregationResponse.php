<?php

namespace MakinaCorpus\ElasticSearch\Aggregation;

use MakinaCorpus\ElasticSearch\PartialResponseTrait;

/**
 * Represents a bucket aggregation response
 */
class BucketAggregationResponse extends AbstractAggregationResponse
{
    private $buckets;

    /**
     * Default constructor
     *
     * @param array $body
     */
    public function __construct(Aggregation $aggregation, array $body)
    {
        parent::__construct($aggregation->getName());

        if (!isset($body['buckets'])) {
            throw new \InvalidArgumentException("given response body is not a response bucket");
        }

        foreach ($body['buckets'] as $data) {
            $this->buckets[] = new AggregationResponse($aggregation, $data);
        }
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
     * @return AggregationResponseInterface[]
     */
    final public function getBuckets()
    {
        return $this->buckets;
    }
}
