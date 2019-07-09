<?php

namespace spec\App\OpenSkos\Filters;

use App\OpenSkos\Filters\FilterProcessor;
use PhpSpec\ObjectBehavior;
use App\Ontology\OpenSkos;
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
            'anothercode',
        ];

        $this->buildInstitutionFilters($filter_list)->shouldBe(
            [
                ['predicate' => OpenSkos::TENANT, 'value' => 'code'],
                ['predicate' => OpenSkos::TENANT, 'value' => 'anothercode'],
            ]
        );
    }

    public function it_rejects_invalid_set_filter_types()
    {
        //Sets can't filter uuids or urls
        $filter_list = [
            'http://tenant/92d6e19e-c424-4bdb-8cac-0738ae9fe88e',
        ];

        $this->shouldThrow(BadRequestHttpException::class)
            ->during('buildInstitutionFilters', ['filter_list' => $filter_list]);

        $filter_list = [
            '92d6e19e-c424-4bdb-8cac-0738ae9fe88e',
        ];
        $this->shouldThrow(BadRequestHttpException::class)
            ->during('buildInstitutionFilters', ['filter_list' => $filter_list]);
    }
}
