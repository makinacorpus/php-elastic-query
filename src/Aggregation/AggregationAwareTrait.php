<?php

namespace MakinaCorpus\ElasticSearch\Aggregation;

trait AggregationAwareTrait
{
    /**
     * @var Aggregation[]
     */
    private $aggregations = [];

    /**
     * Add sub-aggregation
     *
     * @param Aggregation $aggregation
     *
     * @return $this
     */
    final public function addAggregation(Aggregation $aggregation)
    {
        $name = $aggregation->getName();

        if (isset($this->aggregations[$name])) {
            throw new \InvalidArgumentException(sprintf("Sub-aggregation with name %s is already set", $name));
        }

        $this->aggregations[$name] = $aggregation;

        return $this;
    }

    /**
     * Get sub-aggregations
     *
     * @return Aggregation[]
     */
    final public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * Has this object aggregations
     *
     * @return boolean
     */
    final public function hasAggregations()
    {
        return !empty($this->aggregations);
    }
}
