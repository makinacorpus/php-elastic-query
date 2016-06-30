<?php

namespace MakinaCorpus\ElasticSearch\Tests;

use MakinaCorpus\ElasticSearch\Aggregation\GenericAggregation;
use MakinaCorpus\ElasticSearch\Query;
use MakinaCorpus\ElasticSearch\QueryRunner;
use MakinaCorpus\ElasticSearch\Response;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Builds generic aggregations and ensuire the body is correct
     */
    public function testCustomTopHitBasedSearch()
    {
        $query = new Query();

        $allowedTypes = [
            'page'  => "Page",
            'news'  => "News",
            'event' => "Event",
        ];

        $topHits = new GenericAggregation('type_top_hits', 'top_hits');
        $topHits->setBody([
            '_source' => [
                'include' => ['_id', 'title'],
            ],
            'size' => 3,
        ]);

        $terms = new GenericAggregation('terms', 'terms');
        $terms->setIsBucketAggregation(true);
        $terms->setBody(['field' => 'type']);
        $terms->addAggregation($topHits);

        $counts = new GenericAggregation('type_count', 'value_count');
        $counts->setBody(['field' => 'type']);
        $counts->setApplyOnPostFilter(true);
        $counts->setApplyCallback(function (Query $query, array $request = []) use ($allowedTypes) {
            // This aggregation is post filter, no need to recheck
            if (isset($request['type'])) {
                $query->getPostFilter()->matchTerm('type', $request['type']);
            }
        });

        // Filter the base query upon the allowed types
        $query
            ->addAggregation($terms)
            ->addAggregation($counts)
            ->getFilter()
            ->matchTermCollection('type', array_keys($allowedTypes))
            ->matchTerm('status', 1)
        ;

        // Consider that request is something that comes from heaven (in another
        // words, unsafe user input as HTTP request parameters)
        $request = [];

        $runner = (new QueryRunner())
            ->setIndex('my_content')
            ->setFulltextField('text_combined')
            ->setFulltextParameterName('q')
            ->setPageParameter('page')
            // Note that the offset (from in elastic language) 
            ->setLimit(20)
        ;

        // And we did run this test using a real database, so here is the
        // response!
        $raw = json_decode(file_get_contents(__DIR__ . '/../../samples/02-response.json'), true);

        $response = new Response($runner, $query, $raw);

        $this->assertSame(625, $response->getDocCount());
        $this->assertCount(10, $response->getHits());

        $documents = $response->getHits();

        /** @var \MakinaCorpus\ElasticSearch\Document $document */

        // Test the first, for fun
        $document = array_shift($documents);
        $this->assertSame('private', $document->getIndex());
        $this->assertSame('node', $document->getType());
        $this->assertSame(1, $document->getScore());
        $this->assertSame('5', $document->getId());
        $this->assertSame('2016-05-30T17:11:45+0000', $document->get('created'));
        $this->assertSame('15', $document->get('owner'));
        $this->assertSame(false, $document->get('is_flagged'));

        // Test the second, to be sure
        $document = array_shift($documents);
        $this->assertSame('6', $document->getId());
        $this->assertSame('2016-06-02T20:02:26+0000', $document->get('updated'));
        $this->assertSame('15', $document->get('owner'));
        $this->assertSame(true, $document->get('is_global'));

        // Test the last, to close the deal
        $document = array_pop($documents);
        $this->assertSame('15', $document->getId());
        $this->assertSame('2016-05-31T14:31:01+0000', $document->get('updated'));
        $this->assertSame('6', $document->get('owner'));
        $this->assertSame('example - Montagne', $document->get('title'));
    }
}
