<?php

declare(strict_types=1);

namespace App\OpenSkos\Relation\Controller;

use App\Annotation\Error;
use App\Exception\ApiException;
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
use App\Rest\DirectGraphResponse;
use EasyRdf_Graph as Graph;
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
        $this->whitelist  = array_filter(array_merge(
            [Rdf::TYPE],
            RelationType::vocabularyFields(),
            [OpenSkos::TENANT],
            [OpenSkos::UUID],
            [SkosXl::LABEL_RELATION]
        ), function ($uri) {
            return (bool) Context::decodeUri($uri);
        });
    }

    /**
     * @return Triple[]
     */
    public static function toTriples(?string $iri, ?array $data): array
    {
        $result = [];

        if (is_null($data) || is_null($iri)) {
            return [];
        }

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
     * @throws ApiException
     *
     * @Error(code="relation-getall-missing-subject-and-object",
     *        status=400,
     *        description="Neither subject or object was given, either is required",
     *        fields={"uuid"}
     * )
     * @Error(code="relation-getall-subject-not-found",
     *        status=404,
     *        description="No subject could be found with the given id",
     *        fields={"subject"}
     * )
     * @Error(code="relation-getall-object-not-found",
     *        status=404,
     *        description="No object could be found with the given id",
     *        fields={"object"}
     * )
     */
    public function getRelations(
        ApiRequest $apiRequest,
        ApiFilter $apiFilter,
        JenaRepository $jenaRepository
    ): DirectGraphResponse {
        $whitelist = $this->whitelist;
        $subject   = $apiRequest->getParameter('subject');
        $object    = $apiRequest->getParameter('object');
        $triples   = [];
        if (is_null($subject) && is_null($object)) {
            throw new ApiException('relation-getall-missing-subject-and-object');
        }

        /* * * * * * * * * * * *\
         * SUBJECT DATA START  *
        \* * * * * * * * * * * */

        $subjectData    = null;
        if ($subject) {
            // Attempt with UUID field
            if (is_null($subjectData) && ApiFilter::isUuid($subject)) {
                $subjectData = $jenaRepository->getByUuid(new InternalResourceId($subject));
            }

            // Direct by iri
            if (is_null($subjectData) && filter_var($subject, FILTER_VALIDATE_URL)) {
                $subjectData = $jenaRepository->findByIri(new Iri($subject));
            }

            // Check if requested data was actually found
            if (is_null($subjectData)) {
                throw new ApiException('relation-getall-subject-not-found', [
                    'subject' => $subject,
                ]);
            }

            // Separate the ID
            $subjectIri = $subjectData['_id'];
            unset($subjectData['_id']);

            // Transform raw data into triples
            $triples = static::toTriples($subjectIri, $subjectData);

            // Remove non-whitelisted fields
            $triples = array_filter(array_map(function (Triple $triple) use ($whitelist) {
                $field = $triple->getPredicate()->getUri();

                return in_array($field, $whitelist, true) ? $triple : null;
            }, $triples));
        }

        /* * * * * * * * * * *\
         * OBJECT DATA START *
        \* * * * * * * * * * */

        $objectData = null;
        if ($object) {
            $triples = array_merge(
                $triples,
                $jenaRepository->findSubjectForObject(new Iri($object))
            );
        }

        /* * * * * * * *\
         * GRAPH START *
        \* * * * * * * */

        $tripleString = implode(" .\n", $triples).' .';
        $graph        = new Graph();
        $graph->parse($tripleString, 'ntriples');

        return new DirectGraphResponse(
            $graph,
            $apiRequest->getFormat()
        );
    }
}
