<?php

namespace spec\App\OpenSkos\Filters;

use App\OpenSkos\Filters\FilterProcessor;
use PhpSpec\ObjectBehavior;
use App\Ontology\OpenSkos;
use App\Ontology\DcTerms;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FilterProcessorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(FilterProcessor::class);
    }

    public function it_recognises_valid_set_filter_types()
    {
        //Only tenant codes accepted for sets
        $filter_list = [
            'code',
            'http://tenant/a',
        ];

        $this->buildInstitutionFilters($filter_list)->shouldBe(
            [
                ['predicate' => OpenSkos::TENANT, 'value' => 'code', 'type' => FilterProcessor::TYPE_STRING],
                ['predicate' => DcTerms::PUBLISHER, 'value' => 'http://tenant/a', 'type' => FilterProcessor::TYPE_URI],
            ]
        );
    }

    public function it_rejects_invalid_set_filter_types()
    {
        //Sets can't filter uuids
        $filter_list = [
            '92d6e19e-c424-4bdb-8cac-0738ae9fe88e',
        ];
        $this->shouldThrow(BadRequestHttpException::class)
            ->during('buildInstitutionFilters', ['filter_list' => $filter_list]);
    }
}
