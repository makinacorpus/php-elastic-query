<?php

namespace MakinaCorpus\ElasticSearch;

final class ArrayUtil
{
    final static public function merge(array $a, array $b)
    {
        foreach ($b as $k => $v) {
            if (isset($a[$k]) && is_array($a[$k]) && is_array($b[$k])) {
                $a[$k] = self::merge($a[$k], $b[$k]);
            } else {
                $a[$k] = $b[$k];
            }
        }

        return $a;
    }
}
