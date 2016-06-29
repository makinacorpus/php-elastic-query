<?php

namespace MakinaCorpus\ElasticSearch\Aggregation;

/**
 * Represents a bucket aggregation response
 */
abstract class AbstractAggregationResponse implements AggregationResponseInterface
{
    private $aggregationName;

    public function __construct($aggregationName)
    {
        $this->aggregationName = $aggregationName;
    }

    /**
     * {@inheritdoc}
     */
    final public function getAggregationName()
    {
        return $this->aggregationName;
    }
}
