<?php

namespace spec\App\OpenSkos\Role\Controller;

use App\OpenSkos\ApiRequest;
use App\Rdf\Format\RdfFormatFactory;
use App\Rest\DirectGraphResponse;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Serializer\SerializerInterface;

class RoleSpec extends ObjectBehavior
{
    public function it_returns_a_direct_graph(
        SerializerInterface $serializer
    ) {
        // Define request parameters
        $formatFactory = RdfFormatFactory::loadDefault();
        $format = $formatFactory->createFromName('rdf');
        $allParameters = [];
        $level = 1;
        $limit = 100;
        $offset = 0;
        $institutions = [];
        $sets = [];
        $searchProfile = 0;
        $foreignUri = null;

        // Build the apiRequest
        $apiRequest = new ApiRequest(
            $allParameters,
            $format,
            $level,
            $limit,
            $offset,
            $institutions,
            $sets,
            $searchProfile,
            $foreignUri,
        );

        // Initialize the controller we're testing
        $this->beConstructedWith($serializer);

        // Run the method we're testing
        $result = $this->role($apiRequest);

        $result->shouldBeAnInstanceOf(DirectGraphResponse::class);
    }
}
