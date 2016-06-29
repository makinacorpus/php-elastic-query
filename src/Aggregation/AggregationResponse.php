<?php

namespace MakinaCorpus\ElasticSearch\Aggregation;

use MakinaCorpus\ElasticSearch\PartialResponseTrait;

/**
 * Represents a noon-bucket aggregation response
 */
class AggregationResponse extends AbstractAggregationResponse
{
    use PartialResponseTrait;

    private $children = [];

    /**
     * Default constructor
     *
     * @param array $body
     */
    public function __construct(Aggregation $aggregation, array $body)
    {
        parent::__construct($aggregation->getName());

        $this->body = $body;

        if ($aggregation->hasAggregations()) {
            foreach ($aggregation->getAggregations() as $child) {

                $name = $child->getName();

                if (!isset($body[$name])) {
                    throw new \InvalidArgumentException(sprintf("given response body is missing the '%s' sub-aggregation response", $name));
                }

                $this->children[] = $child->getResponse($body[$name]);
            }
        }
    }

    /**
     * Is there any children in this response
     *
     * @return boolean
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }

    /**
     * Get the children aggregation responses
     *
     * @return AggregationResponseInterface
     */
    public function getChilren()
    {
        return $this->children;
    }
}
