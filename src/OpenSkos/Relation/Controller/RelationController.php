<?php

declare(strict_types=1);

namespace App\OpenSkos\Relation\Controller;

use App\Ontology\Context;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\SkosXl;
use App\OpenSkos\ApiFilter;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Relation\JenaRepository;
use App\OpenSkos\RelationType\RelationType;
use App\Rdf\Iri;
use App\Rdf\RdfTerm;
use App\Rdf\Triple;
use App\Rest\ScalarResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class RelationController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string[]
     */
    private $whitelist = [];

    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
        $this->whitelist  = array_merge(
            [Rdf::TYPE],
            RelationType::vocabularyFields(),
            [OpenSkos::TENANT],
            [OpenSkos::UUID],
            [SkosXl::LABEL_RELATION]
        );
    }

    /**
     * @return Triple[]
     */
    public static function toTriples(string $iri, array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = new Triple(new Iri($iri), new Iri($key), $value);
        }

        return $result;
    }

    /**
     * @param Triple[] $triples
     */
    public static function getType(array $triples): ?RdfTerm
    {
        foreach ($triples as $triple) {
            if (Rdf::TYPE === $triple->getPredicate()->getUri()) {
                return $triple->getObject();
            }
        }

        return null;
    }

    /**
     * @param RdfTerm $type
     */
    public static function getClass(?RdfTerm $type): ?string
    {
        if (is_null($type)) {
            return null;
        }
        $uri    = $type->__toString();
        $tokens = Context::decodeUri($uri);
        if (is_null($tokens)) {
            return null;
        }

        $fullname = implode(':', $tokens);

        return Context::dataclass[$fullname];
    }

    /**
     * @Route(path="/relations.{format?}", methods={"GET"})
     *
     * @throws BadRequestHttpException
     */
    public function getLabels(
        ApiRequest $apiRequest,
        ApiFilter $apiFilter,
        JenaRepository $jenaRepository
    ): ScalarResponse {
        // Fetch which object to fetch relations for
        $object = null;
        $object = $apiRequest->getParameter('subject', $object);
        $object = $apiRequest->getParameter('object', $object);
        if (!$object) {
            throw new BadRequestHttpException('Missing resource ID');
        }

        $rawdata = null;

        // Attempt with UUID field
        if (is_null($rawdata) && ApiFilter::isUuid($object)) {
            $rawdata = $jenaRepository->getByUuid(new InternalResourceId($object));
        }

        // Direct by iri
        if (is_null($rawdata) && filter_var($object, FILTER_VALIDATE_URL)) {
            $rawdata = $jenaRepository->findByIri(new Iri($object));
        }

        // No resource yet = couldn't find it
        if (is_null($rawdata)) {
            throw new NotFoundHttpException("Could not get resource with id: ${object}");
        }

        // Separate the ID
        $iri = $rawdata['_id'];
        unset($rawdata['_id']);

        // Transform raw data into triples
        $triples = static::toTriples($iri, $rawdata);

        // Remove non-whitelisted fields
        $whitelist = $this->whitelist;
        $triples   = array_filter(array_map(function (Triple $triple) use ($whitelist) {
            $field = $triple->getPredicate()->getUri();

            return in_array($field, $whitelist, true) ? $triple : null;
        }, $triples));

        // Fetch the classname for the rdf type
        $type  = static::getType($triples);
        $class = static::getClass($type);
        if (is_null($class)) {
            throw new BadRequestHttpException("Could not get class for resource type: ${type}");
        }

        // Build the resource and return it
        $resource = $class::fromTriples(new Iri($iri), $triples);

        return new ScalarResponse(
            $resource,
            $apiRequest->getFormat()
        );
    }
}
