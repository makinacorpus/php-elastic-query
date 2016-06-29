<?php

namespace MakinaCorpus\ElasticSearch\Aggregation;

/**
 * Represents a bucket aggregation response
 */
interface AggregationResponseInterface
{
    /**
     * Get aggregation name
     *
     * @return string
     */
    public function getAggregationName();
}
