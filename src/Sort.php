<?php

namespace MakinaCorpus\ElasticSearch;

final class Sort
{
    const MODE_MIN      = 'min';
    const MODE_MAX      = 'max';
    const MODE_AVG      = 'avg';
    const MODE_SUM      = 'sum';
    const MODE_MED      = 'median';

    const ORDER_ASC     = 'asc';
    const ORDER_DESC    = 'desc';

    const MISSING_FIRST = '_first';
    const MISSING_LAST  = '_last';

    const FIELD_SCORE   = '_score';

    private $field;
    private $order;
    private $mode;
    private $missing;

    /**
     * Default constructor
     *
     * @param string $field
     *   Field name to sort on
     * @param string $order
     *   One of the Sort::ORDER_* constants
     * @param string $mode
     *   One of the Sort::MODE_* constants
     * @param string $missing
     *   One of the Sort::MISSING_* constants
     */
    public function __construct($field = self::FIELD_SCORE, $order = null, $mode = null, $missing = null)
    {
        $this->field = $field;
        $this->order = $order;
        $this->mode = $mode;
        $this->missing = $missing;
    }

    /**
     * Get sort field
     *
     * @return mixed
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Get sort order
     *
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set sort mode for array or multi-valued fields
     *
     * @param $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * Get sort mode for array or multi-valued fields
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set missing field behavior
     *
     * @param $missing
     */
    public function setMissing($missing)
    {
        $this->missing = $missing;
    }

    /**
     * Get missing field behavior.
     *
     * @return string
     */
    public function getMissing()
    {
        return $this->missing;
    }

    /**
     * Get sort structure
     *
     * @return array
     */
    public function toArray()
    {
        $body = [];

        if ($this->order) {
            $body['order'] = $this->order;
        }
        if ($this->mode) {
            $body['mode'] = $this->mode;
        }
        if ($this->missing) {
            $body['missing'] = $this->missing;
        }

        return $body;
    }
}
