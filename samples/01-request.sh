curl -XGET localhost:9200/private/_search -d '{
    "aggs": {
        "identifiers" : {
            "histogram" : {
                "field" : "id",
                "interval" : 10
            },
            "aggs" : {
                "top_type": {
                    "terms": {
                        "field": "type",
                        "size": 3
                    },
                    "aggs": {
                        "site_stat" : { "stats" : { "field" : "grade" } },
                        "type_tag_hits": {
                            "top_hits": {
                                "_source": {
                                    "include": [
                                        "title"
                                    ]
                                },
                                "size" : 3
                            }
                        }
                    }
                }
            }
        }
    }
}'
