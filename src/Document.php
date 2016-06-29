<?php

namespace MakinaCorpus\ElasticSearch;

use Elasticsearch\Client;

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

    public function get($name)
    {
        if (!isset($this->source[$name])) {
            return null;
        }

        return $this->source[$name];
    }
}
