<?php

declare(strict_types=1);

namespace App\OpenSkos\Relation\Controller;

use App\Annotation\Error;
use App\Annotation\OA;
use App\EasyRdf\TripleFactory;
use App\Exception\ApiException;
use App\Ontology\Context;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\SkosXl;
use App\OpenSkos\ApiFilter;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Concept\ConceptRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Relation\JenaRepository;
use App\OpenSkos\RelationType\RelationType;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\Iri;
use App\Rdf\Literal\Literal;
use App\Rdf\RdfTerm;
use App\Rdf\Triple;
use App\Rest\DirectGraphResponse;
use App\Rest\ListResponse;
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

    /**
     * @Route(path="/relations.{format?}", methods={"POST"})
     *
     * @OA\Summary("Create one or more semantic relations")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="format",
     *     in="path",
     *     example="json",
     *     enum={"json", "ttl", "n-triples"},
     *   ),
     *   @OA\Schema\ObjectLiteral(name="@context",in="body"),
     *   @OA\Schema\ArrayLiteral(
     *     name="@graph",
     *     in="body",
     *   ),
     * })
     *
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\JsonRdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=SkosConcept::class),
     *     ),
     *   }),
     * )
     *
     * @Error(code="semantic-relation-create-subject-concept-not-found",
     *        status=404,
     *        description="The subject concept to create a semantic relation for could not be found",
     *        fields={"iri"}
     * )
     * @Error(code="semantic-relation-create-object-concept-not-found",
     *        status=404,
     *        description="The target concept to create a semantic relation for could not be found",
     *        fields={"iri"}
     * )
     * @Error(code="semantic-relation-create-predicate-not-allowed",
     *        status=400,
     *        description="The given predicate is not a valid relation to be created",
     *        fields={"predicate"}
     * )
     * @Error(code="semantic-relation-create-literal-not-allowed",
     *        status=400,
     *        description="Semantic relations with a literal reference are not allowed",
     *        fields={"predicate","reference"}
     * )
     * @Error(code="semantic-relation-create-unknown-reference",
     *        status=400,
     *        description="An IRI reference was expected, received an unknown type",
     *        fields={"type"}
     * )
     *
     * @throws ApiException
     */
    public function postRelation(
        ApiRequest $apiRequest,
        ConceptRepository $conceptRepository,
        string $finalArg = null
    ): ListResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();

        // Load data from request
        $graph   = $apiRequest->getGraph();
        $triples = TripleFactory::triplesFromGraph($graph);
        $entries = array_map(function ($tripleGroup) {
            return ['triples' => $tripleGroup];
        }, SkosResourceRepository::groupTriples($triples));

        // List of non-semantic fields
        $nonSemantic = [
            Rdf::TYPE,
            OpenSkos::TENANT,
            OpenSkos::UUID,
        ];

        // Check if all given triples are valid
        $allowed = RelationType::semanticFields();
        foreach ($triples as $triple) {
            $subject = $triple->getSubject()->getUri();

            // Ignored or used to find subjects to update
            if (in_array($triple->getPredicate()->getUri(), $nonSemantic, true)) {
                continue;
            }

            // All semantic relations require iri objects
            $iri = $triple->getObject();
            if ($iri instanceof Literal) {
                throw new ApiException('semantic-relation-create-literal-not-allowed', [
                    'predicate' => $triple->getPredicate()->getUri(),
                    'reference' => $iri->value(),
                ]);
            }

            // No iri = error
            if (!($iri instanceof Iri)) {
                $type = gettype($iri);
                if ('object' === $type) {
                    $type .= '('.get_class($iri).')';
                }
                throw new ApiException('semantic-relation-create-unknown-reference', [
                    'type' => $type,
                ]);
            }

            $target = $conceptRepository->findByIri($iri);
            if (is_null($target)) {
                throw new ApiException('semantic-relation-create-object-concept-not-found', [
                    'iri' => $iri->getUri(),
                ]);
            }

            // Allowed relations
            if (in_array($triple->getPredicate()->getUri(), $allowed, true)) {
                continue;
            }

            // All others are rejected
            throw new ApiException('semantic-relation-create-predicate-not-allowed', [
                'predicate' => $triple->getPredicate()->getUri(),
            ]);
        }

        // Fetch concepts for all
        foreach ($entries as $iri => $entry) {
            $entries[$iri]['concept'] = $conceptRepository->findByIri(new Iri($iri));
            if (is_null($entries[$iri]['concept'])) {
                throw new ApiException('semantic-relation-create-subject-concept-not-found', [
                    'iri' => $iri,
                ]);
            }
        }

        // Start writing data
        foreach ($entries as $iri => $entry) {
            // Fetch concept
            $concept = $entry['concept'] ?? null;
            if (is_null($concept)) {
                continue;
            }
            // Add new relations
            foreach ($entry['triples'] as $triple) {
                $concept->addProperty($triple->getPredicate(), $triple->getObject());
            }
            // Save
            $errors = $concept->update();
            if ($errors) {
                throw new ApiException($errors[0]);
            }
        }

        return new ListResponse(
            array_map(function ($entry) {
                return $entry['concept'] ?? null;
            }, $entries),
            count($entries),
            0,
            $apiRequest->getFormat()
        );
    }

    /**
     * @Route(path="/relations.{format?}", methods={"PUT"})
     *
     * @throws ApiException
     *
     * @Error(code="relation-update-bad-request",
     *        status=400,
     *        description="Updating a relation is not allowed/possible"
     * )
     */
    public function putRelation(
        ApiRequest $apiRequest
    ): void {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();

        throw new ApiException('relation-update-bad-request');
    }
}
