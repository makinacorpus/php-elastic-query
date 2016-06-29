<?php

namespace MakinaCorpus\ElasticSearch\Aggregation;

use MakinaCorpus\ElasticSearch\PartialResponseTrait;

/**
 * Represents a bucket aggregation response
 */
class BucketResponse
{
    use PartialResponseTrait;

    /**
     * Get document count from within this bucket
     *
     * @return NULL|\MakinaCorpus\ElasticSearch\Aggregation\mixed
     */
    final public function getDocCount()
    {
        return $this->get('doc_count');
    }
}
