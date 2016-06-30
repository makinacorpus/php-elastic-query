<?php

namespace MakinaCorpus\ElasticSearch;

/**
 * Represents a response document
 */
final class Document
{
    private $index;
    private $type;
    private $id;
    private $score = 1;
    private $source = [];

    public function __construct(array $body = [])
    {
        if (isset($body['_index'])) {
            $this->index = $body['_index'];
        }
        if (isset($body['_type'])) {
            $this->type = $body['_type'];
        }
        if (isset($body['_id'])) {
            $this->id = $body['_id'];
        }
        if (isset($body['_score'])) {
            $this->score = $body['_score'];
        }
        if (isset($body['_source'])) {
            $this->source = $body['_source'];
        }
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getSourceFieldNames()
    {
        return array_keys($this->source);
    }

    public function get($name)
    {
        if (!isset($this->source[$name])) {
            return null;
        }

        return $this->source[$name];
    }
}
