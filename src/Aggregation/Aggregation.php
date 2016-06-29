<?php

namespace MakinaCorpus\ElasticSearch\Aggregation;

use MakinaCorpus\ElasticSearch\Query;

/**
 * Represents an aggregation
 *
 * An aggregation is almost an immutable object, only sub-aggregations can be
 * added.
 *
 * If you plan to use aggregations as facets, you must implement the apply()
 * method on your custom aggregations.
 */
abstract class Aggregation
{
    use AggregationAwareTrait;

    private $name;
    private $type;
    private $applyOnPostFilter = false;

    protected $body = [];
    protected $meta = [];

    /**
     * Default constructor
     *
     * @param string $name
     * @param string $type
     */
    public function __construct($name, $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Get aggregation name
     *
     * @return string
     */
    final public function getName()
    {
        return $this->name;
    }

    /**
     * Get aggregation type
     *
     * @return string
     */
    final public function getType()
    {
        return $this->type;
    }

    /**
     * Should this aggregation, when used as a facet, be applied on post filter
     *
     * @return boolean
     */
    final public function shouldApplyOnPostFilter()
    {
        return $this->applyOnPostFilter;
    }

    /**
     * Mark this aggregation as applyable on post filter
     *
     * @param boolean $toggle
     * 
     * @return $this
     */
    final public function applyOnPostFilter($toggle = true)
    {
        $this->applyOnPostFilter = (bool)$toggle;

        return $this;
    }

    /**
     * Format body
     *
     * @return mixed[]
     */
    protected function formatBody()
    {
        return $this->body;
    }

    /**
     * Format meta
     *
     * @return mixed[]
     */
    protected function formatMeta()
    {
        return $this->meta;
    }

    /**
     * If aggregation should behave like a facet, apply the incomming request
     *
     * @param Query $query
     * @param array $request
     */
    public function apply(Query $query, array $request = [])
    {
    }

    /**
     * Build response from the given raw response data
     *
     * @param array $data
     *
     * @return AggregationResponse
     */
    public function getResponse(array $data)
    {
        return new AggregationResponse($this->getName(), $data);
    }

    /**
     * Build aggregation body for query
     *
     * Returns current aggregation as an array suitable for the official Elastic
     * Search PHP API, if you need to override this method behavior, please see
     * the formatBody() and formatMeta() methods.
     *
     * @return mixed[]
     */
    final public function toArray()
    {
        $body = [
            $this->type => $this->formatBody(),
        ];

        if ($this->meta) {
            $body['meta'] = $this->formatMeta();
        }

        if ($this->hasAggregations()) {
            foreach ($this->getAggregations() as $aggregation) {
                $body['aggs'][$aggregation->getName()] = $aggregation->toArray();
            }
        }

        return $body;
    }
}
