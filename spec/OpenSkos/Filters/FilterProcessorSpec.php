<?php

namespace spec\App\OpenSkos\Filters;

use App\Exception\ApiException;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\OpenSkos\Filters\FilterProcessor;
use Doctrine\DBAL\Connection;
use PhpSpec\ObjectBehavior;

class FilterProcessorSpec extends ObjectBehavior
{
    public function it_is_initializable(Connection $connection)
    {
        $this->beConstructedWith($connection);
        $this->shouldHaveType(FilterProcessor::class);
    }

    public function it_recognises_valid_set_filter_types(Connection $connection)
    {
        $this->beConstructedWith($connection);
        //Only tenant codes accepted for sets
        $filter_list = [
            'code',
            'http://tenant/a',
        ];

        $to_apply = [FilterProcessor::ENTITY_INSTITUTION => true];

        $this->buildInstitutionFilters($filter_list, $to_apply)->shouldBe(
            [
                ['predicate' => OpenSkos::TENANT, 'value' => 'code', 'type' => FilterProcessor::TYPE_STRING, 'entity' => FilterProcessor::ENTITY_INSTITUTION],
                ['predicate' => DcTerms::PUBLISHER, 'value' => 'http://tenant/a', 'type' => FilterProcessor::TYPE_URI, 'entity' => FilterProcessor::ENTITY_INSTITUTION],
            ]
        );
    }

    public function it_rejects_invalid_set_filter_types(Connection $connection)
    {
        $this->beConstructedWith($connection);
        //Sets can't filter uuids
        $filter_list = [
            '92d6e19e-c424-4bdb-8cac-0738ae9fe88e',
        ];
        $to_apply = [FilterProcessor::ENTITY_INSTITUTION => true];
        $this->shouldThrow(ApiException::class)
            ->during('buildInstitutionFilters', ['filter_list' => $filter_list, 'to_apply' => $to_apply]);
    }
}
