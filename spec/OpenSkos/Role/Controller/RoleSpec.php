<?php

namespace spec\App\OpenSkos\Role\Controller;

use App\OpenSkos\ApiRequest;
use App\Rdf\Format\RdfFormatFactory;
use App\Rest\DirectGraphResponse;
use EasyRdf_Graph as Graph;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Serializer\SerializerInterface;

class RoleSpec extends ObjectBehavior
{
    protected static function buildApiRequest()
    {
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
        return new ApiRequest(
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
    }

    public function it_returns_a_direct_graph(
        SerializerInterface $serializer
    ) {
        $apiRequest = self::buildApiRequest();

        // Initialize the controller we're testing
        $this->beConstructedWith($serializer);

        // Run the method we're testing
        $result = $this->role($apiRequest);

        $result->shouldBeAnInstanceOf(DirectGraphResponse::class);
        $result->getGraph()->shouldBeAnInstanceOf(Graph::class);
    }
}
