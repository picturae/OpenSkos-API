<?php

declare(strict_types=1);

namespace App\OpenSkos\Relation\Controller;

use App\Annotation\Error;
use App\Annotation\OA;
use App\EasyRdf\TripleFactory;
use App\Exception\ApiException;
use App\Ontology\Context;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\SkosXl;
use App\OpenSkos\ApiFilter;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Concept\Concept;
use App\OpenSkos\Concept\ConceptRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Relation\JenaRepository;
use App\OpenSkos\RelationType\RelationType;
use App\OpenSkos\SkosResourceRepository;
use App\Rdf\Iri;
use App\Rdf\Literal\DatetimeLiteral;
use App\Rdf\Literal\Literal;
use App\Rdf\RdfTerm;
use App\Rdf\Triple;
use App\Rest\DirectGraphResponse;
use App\Rest\ListResponse;
use App\Rest\ScalarResponse;
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
     * @OA\Summary("Retreive all (filtered) relations for a subject or object")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="format",
     *     in="path",
     *     example="json",
     *     enum={"json", "ttl", "n-triples"},
     *   ),
     *   @OA\Schema\StringLiteral(
     *     name="subject",
     *     in="query",
     *     example="1911",
     *   ),
     *   @OA\Schema\StringLiteral(
     *     name="object",
     *     in="query",
     *     example="1337",
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Concept::class),
     *     ),
     *   }),
     * )
     * @OA\Response(
     *   code="400",
     *   content=@OA\Content\Json(properties={
     *     @OA\Schema\ObjectLiteral(class=Error::class),
     *   }),
     * )
     * @OA\Response(
     *   code="404",
     *   content=@OA\Content\Json(properties={
     *     @OA\Schema\ObjectLiteral(class=Error::class),
     *   }),
     * )
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
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Concept::class),
     *     ),
     *   }),
     * )
     * @OA\Response(
     *   code="400",
     *   content=@OA\Content\Json(properties={
     *     @OA\Schema\ObjectLiteral(class=Error::class),
     *   }),
     * )
     * @OA\Response(
     *   code="403",
     *   content=@OA\Content\Json(properties={
     *     @OA\Schema\ObjectLiteral(class=Error::class),
     *   }),
     * )
     * @OA\Response(
     *   code="404",
     *   content=@OA\Content\Json(properties={
     *     @OA\Schema\ObjectLiteral(class=Error::class),
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
     * @OA\Summary("Update one or more relations (FULL rewrite)")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="format",
     *     in="path",
     *     example="json",
     *     enum={"json", "ttl", "n-triples"},
     *   ),
     * })
     * @OA\Response(
     *   code="400",
     *   content=@OA\Content\Json(properties={
     *     @OA\Schema\ObjectLiteral(class=Error::class),
     *   }),
     * )
     *
     * @throws ApiException
     *
     * @OA\Summary("Update an existing relation")
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

    /**
     * @Route(path="/relations.{format?}", methods={"DELETE"})
     *
     * @OA\Summary("Delete one or more relations")
     * @OA\Request(parameters={
     *   @OA\Schema\StringLiteral(
     *     name="format",
     *     in="path",
     *     example="json",
     *     enum={"json", "ttl", "n-triples"},
     *   ),
     *   @OA\Schema\StringLiteral(
     *     name="object",
     *     in="query",
     *   ),
     *   @OA\Schema\StringLiteral(
     *     name="predicate",
     *     in="query",
     *   ),
     *   @OA\Schema\StringLiteral(
     *     name="subject",
     *     in="query",
     *   ),
     * })
     * @OA\Response(
     *   code="200",
     *   content=@OA\Content\Rdf(properties={
     *     @OA\Schema\ObjectLiteral(name="@context"),
     *     @OA\Schema\ArrayLiteral(
     *       name="@graph",
     *       items=@OA\Schema\ObjectLiteral(class=Concept::class),
     *     ),
     *   }),
     * )
     * @OA\Response(
     *   code="400",
     *   content=@OA\Content\Json(properties={
     *     @OA\Schema\ObjectLiteral(class=Error::class),
     *   }),
     * )
     * @OA\Response(
     *   code="403",
     *   content=@OA\Content\Json(properties={
     *     @OA\Schema\ObjectLiteral(class=Error::class),
     *   }),
     * )
     * @OA\Response(
     *   code="404",
     *   content=@OA\Content\Json(properties={
     *     @OA\Schema\ObjectLiteral(class=Error::class),
     *   }),
     * )
     *
     * @Error(code="semantic-relation-delete-subject-not-found",
     *        status=404,
     *        description="The subject to delete a relation for could not be found",
     *        fields={"subject"}
     * )
     * @Error(code="semantic-relation-delete-object-not-found",
     *        status=404,
     *        description="The object to delete a relation for could not be found",
     *        fields={"object"}
     * )
     * @Error(code="semantic-relation-delete-missing-param-subject",
     *        status=400,
     *        description="Missing the 'subject' parameter"
     * )
     * @Error(code="semantic-relation-delete-missing-param-predicate",
     *        status=400,
     *        description="Missing the 'predicate' parameter"
     * )
     * @Error(code="semantic-relation-delete-missing-param-object",
     *        status=400,
     *        description="Missing the 'object' parameter"
     * )
     * @Error(code="semantic-relation-delete-corrupt-param-predicate",
     *        status=400,
     *        description="The given predicate is not valid or not allowed"
     * )
     *
     * @throws ApiException
     */
    public function deleteRelation(
        ApiRequest $apiRequest,
        ConceptRepository $conceptRepository,
        string $finalArg = null
    ): ScalarResponse {
        // Client permissions
        $auth = $apiRequest->getAuthentication();
        $auth->requireAdministrator();
        $user = $auth->getUser();

        // Fetch params
        $objectIri  = $apiRequest->getParameter('object');
        $predicate  = $apiRequest->getParameter('predicate');
        $subjectIri = $apiRequest->getParameter('subject');
        if (is_null($subjectIri)) {
            throw new ApiException('semantic-relation-delete-missing-param-subject');
        }
        if (is_null($predicate)) {
            throw new ApiException('semantic-relation-delete-missing-param-predicate');
        }
        if (is_null($objectIri)) {
            throw new ApiException('semantic-relation-delete-missing-param-object');
        }

        // Resolve predicate
        $allowedPredicates = RelationType::semanticFields();
        $predicate         = Context::fullUri($predicate);
        if (is_null($predicate) || !in_array($predicate, $allowedPredicates, true)) {
            throw new ApiException('semantic-relation-delete-corrupt-param-predicate');
        }

        // Resolve concepts
        $object  = $conceptRepository->findByIri(new Iri($objectIri));
        $subject = $conceptRepository->findByIri(new Iri($subjectIri));
        if (is_null($object)) {
            throw new ApiException('semantic-relation-delete-object-not-found', [
                'object' => $objectIri,
            ]);
        }
        if (is_null($subject)) {
            throw new ApiException('semantic-relation-delete-subject-not-found', [
                'subject' => $subjectIri,
            ]);
        }

        /* // Fetch relevant relations */
        $relations = array_filter($subject->getProperty($predicate) ?? [], function (Iri $relation) use ($objectIri) {
            return $relation->getUri() == $objectIri;
        });

        // Remove all relevenat relations
        foreach ($relations as $relation) {
            $subject->getResource()->removeTriple($predicate, $relation);
        }

        // Mark as updated
        $subject->setValue(new Iri(DcTerms::MODIFIED), new DatetimeLiteral(new \Datetime()));
        if (!is_null($user)) {
            $subject->setValue(new Iri(OpenSkos::MODIFIED_BY), $user->iri());
        }

        // Save the updates
        $conceptRepository->deleteIndex($subject->iri());
        $errors = $subject->update();
        if (is_array($errors)) {
            throw new ApiException($errors[0]);
        }

        // Return what's left
        return new ScalarResponse(
            $subject,
            $apiRequest->getFormat()
        );
    }
}
