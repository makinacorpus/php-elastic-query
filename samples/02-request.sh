curl -XGET localhost:9200/private/_search -d '{
    "query": {
        "constant_score": {
            "filter": {
                "fquery": {
                    "query": {
                        "query_string": {
                            "query": "(type:(page OR news OR event) status:1)"
                        }
                    },
                    "_cache": true
                }
            }
        }
    },
    "aggs": {
        "terms": {
            "terms": {
                "field": "type"
            },
            "aggs": {
                "type_top_hits": {
                    "top_hits": {
                        "_source": {
                            "include": [
                                "_id",
                                "title"
                            ]
                        },
                        "size": 3
                    }
                }
            }
        },
        "type_count": {
            "value_count": {
                "field": "type"
            }
        }
    }
}'
