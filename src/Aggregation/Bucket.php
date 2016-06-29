<?php

namespace MakinaCorpus\ElasticSearch\Aggregation;

use MakinaCorpus\ElasticSearch\PartialResponseTrait;

/**
 * Represents a bucket in aggregation response
 */
class Bucket
{
    use PartialResponseTrait;

    /**
     * Get bucket key
     *
     * @return string
     */
    final public function getKey()
    {
        return $this->get('key');
    }

    /**
     * Get document count from within this bucket
     *
     * @return int
     */
    final public function getDocCount()
    {
        return $this->get('doc_count');
    }
}
