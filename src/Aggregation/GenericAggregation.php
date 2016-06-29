<?php

namespace MakinaCorpus\ElasticSearch\Aggregation;

use MakinaCorpus\ElasticSearch\Query;

/**
 * If the aggregation you need is not implemented by this API and you don't
 * need to provide a specific implementation, you may use this one.
 */
class GenericAggregation extends Aggregation
{
    /**
     * @var callable
     */
    private $applyCallback;

    /**
     * Default constructor
     *
     * @param string $name
     * @param string $type
     */
    public function __construct($name, $type, array $body = [], array $meta = [])
    {
        parent::__construct($name, $type);

        $this->setBody($body);
        $this->setMeta($meta);
    }

    /**
     * Arbitrarily set body
     *
     * @param mixed[] $body
     *
     * @return $this
     */
    public function setBody(array $body = [])
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Arbitrarily set meta
     *
     * @param mixed[] $meta
     *
     * @return $this
     */
    public function setMeta(array $meta = [])
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Set apply callback, see Aggregation::apply() for documentation
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function setApplyCallback(callable $callback)
    {
        $this->applyCallback = $callback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Query $query, array $request = [])
    {
        if ($this->applyCallback) {
            return call_user_func($this->applyCallback, $query, $request);
        }
    }
}
