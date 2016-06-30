<?php

namespace MakinaCorpus\ElasticSearch;

use MakinaCorpus\ElasticSearch\Aggregation\Aggregation;
use MakinaCorpus\ElasticSearch\Aggregation\AggregationResponse;
use MakinaCorpus\ElasticSearch\Aggregation\AggregationResponseInterface;
use MakinaCorpus\ElasticSearch\Aggregation\BucketAggregationResponse;

class Response
{
    use ResponseTrait;

    private $query;
    private $runner;
    private $aggregationIndex = [];
    private $debug = true;

    /**
     * Default constructor
     *
     * @param QueryRunner $runner
     * @param Query $query
     * @param mixed[] $body
     *   Response raw body
     */
    public function __construct(QueryRunner $runner, Query $query, $body)
    {
        $this->runner = $runner;
        $this->query = $query;
        $this->body = $body;

        $this->parseHits($body);
        $this->parseAggregationResults($query, $body);
    }

    /**
     * Recursion for parseAggregationResults()
     *
     * @param AggregationResponseInterface $aggregation
     */
    private function buildAggregationIndex(AggregationResponseInterface $response)
    {
        if ($response instanceof AggregationResponse) {
            foreach ($response->getChilren() as $child) {
                $this->aggregationIndex[$child->getAggregationName()][] = $child;
                $this->buildAggregationIndex($child);
            }
        } else if ($response instanceof BucketAggregationResponse) {
            foreach ($response->getBuckets() as $child) {
                $this->buildAggregationIndex($child);
            }
        }
    }

    /**
     * Parse aggregations results recursively
     *
     * @param Aggregation[] $aggregations
     * @param mixed[] $body
     */
    private function parseAggregationResults(Query $query, $body)
    {
        foreach ($query->getAggregations() as $aggregation) {

            $name = $aggregation->getName();

            if (!isset($body['aggregations'][$name])) {
                if ($this->debug) {
                    throw new \LogicException(sprintf("aggregation '%s' return is not in body", $name));
                }
                continue;
            }

            $response = $aggregation->getResponse($body['aggregations'][$name]);

            $this->aggregationIndex[$name][] = $response;

            $this->buildAggregationIndex($response);
        }
    }
}
