<?php

declare(strict_types=1);

namespace App\Rest\ArgumentResolver;

use App\Annotation\Error;
use App\Annotation\ErrorInherit;
use App\Database\Doctrine;
use App\Exception\ApiException;
use App\Ontology\OpenSkos;
use App\OpenSkos\ApiRequest;
use App\OpenSkos\Institution\Institution;
use App\OpenSkos\Institution\InstitutionRepository;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Set\SetRepository;
use App\Rdf\Format\RdfFormat;
use App\Rdf\Format\RdfFormatFactory;
use App\Rdf\Format\UnknownFormatException;
use App\Rdf\Iri;
use App\Security\Authentication;
use EasyRdf_Format as Format;
use EasyRdf_Graph as Graph;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ApiRequestResolver implements ArgumentValueResolverInterface
{
    /**
     * @var RdfFormatFactory
     */
    private $formatFactory;

    /**
     * @var Doctrine|null
     */
    private $connection;

    /**
     * @var InstitutionRepository|null
     */
    private $institutionRepository;

    /**
     * @var SetRepository|null
     */
    private $setRepository;

    public function __construct(
        RdfFormatFactory $formatFactory,
        ?Doctrine $connection,
        InstitutionRepository $institutionRepository = null,
        SetRepository $setRepository = null
    ) {
        $this->formatFactory         = $formatFactory;
        $this->connection            = $connection;
        $this->institutionRepository = $institutionRepository;
        $this->setRepository         = $setRepository;
    }

    /**
     * @param array|null $headers
     *
     * @Error(code="apirequest-format-invalid",
     *        status=406,
     *        description="The requested data format is invalid or not supported",
     *        fields={"requested", "supported"}
     * )
     *
     * @throws ApiException
     */
    private function resolveFormat(?string $formatName, $headers = null): ?RdfFormat
    {
        if (null === $formatName) {
            // Attempt using the accept header
            if (!is_null($headers)) {
                // Build accept list
                $accepts = [];
                foreach ($headers as $list) {
                    $list = str_getcsv($list);
                    foreach ($list as $entry) {
                        if (false !== strpos($entry, ';')) {
                            $entry = explode(';', $entry)[0];
                        }
                        array_push($accepts, $entry);
                    }
                }

                // Attempt using the mimetype
                foreach ($accepts as $mime) {
                    $format = $this->formatFactory->createFromMime($mime);
                    if (!is_null($format)) {
                        return $format;
                    }
                }
            }

            return null;
        }

        try {
            return $this->formatFactory->createFromName($formatName);
        } catch (UnknownFormatException $e) {
            throw new ApiException('apirequest-format-invalid', [
                'requested' => $formatName,
                'supported' => array_keys($this->formatFactory->formats()),
            ]);
        }
    }

    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return ApiRequest::class === $argument->getType();
    }

    /**
     * @throws ApiException
     *
     * @Error(code="apirequestresolver-institution-not-found",
     *        status=404,
     *        description="An institution filter was given but could not be found",
     *        fields={"given"}
     * )
     *
     * @ErrorInherit(class=InstitutionRepository::class, method="__construct")
     * @ErrorInherit(class=InstitutionRepository::class, method="findOneBy"  )
     * @ErrorInherit(class=InstitutionRepository::class, method="get"        )
     * @ErrorInherit(class=InstitutionRepository::class, method="getByUuid"  )
     * @ErrorInherit(class=InternalResourceId::class   , method="__construct")
     * @ErrorInherit(class=Iri::class                  , method="__construct")
     */
    public function resolveInstitutions(array $institutions, bool $throw = true): array
    {
        /** @var InstitutionRepository $institutionRepository */
        $institutionRepository = $this->institutionRepository;

        return array_map(function ($institutionIdentifier) use ($institutionRepository) {
            // Check if the identifier is a url
            $institution = $institutionRepository->get(
                new Iri($institutionIdentifier),
            );
            if ($institution) {
                return $institution;
            }

            // Check if the identifier is a openskos:uuid
            $institution = $institutionRepository->getByUuid(
                new InternalResourceId($institutionIdentifier),
            );
            if ($institution) {
                return $institution;
            }

            // Check if the identifier is a openskos:code
            $institution = $institutionRepository->findOneBy(
                new Iri(OpenSkos::CODE),
                new InternalResourceId($institutionIdentifier),
            );
            if ($institution) {
                return $institution;
            }

            // None of the available options was able to fetch the institution
            throw new ApiException('apirequestresolver-institution-not-found', [
                'given' => $institutionIdentifier,
            ]);
        }, $institutions);
    }

    /**
     * @throws ApiException
     *
     * @Error(code="apirequestresolver-set-not-found",
     *        status=404,
     *        description="A set filter was given but could not be found",
     *        fields={"given"}
     * )
     *
     * @ErrorInherit(class=SetRepository::class     , method="__construct")
     * @ErrorInherit(class=SetRepository::class     , method="findOneBy"  )
     * @ErrorInherit(class=SetRepository::class     , method="get"        )
     * @ErrorInherit(class=SetRepository::class     , method="getByUuid"  )
     * @ErrorInherit(class=InternalResourceId::class, method="__construct")
     * @ErrorInherit(class=Iri::class               , method="__construct")
     */
    public function resolveSets(array $sets): array
    {
        /** @var SetRepository $setRepository */
        $setRepository = $this->setRepository;

        return array_map(function ($setIdentifier) use ($setRepository) {
            // Check if the identifier is a url
            $institution = $setRepository->get(
                new Iri($setIdentifier),
            );
            if ($institution) {
                return $institution;
            }

            // Check if the identifier is a openskos:uuid
            $institution = $setRepository->getByUuid(
                new InternalResourceId($setIdentifier),
            );
            if ($institution) {
                return $institution;
            }

            // Check if the identifier is a openskos:code
            $institution = $setRepository->findOneBy(
                new Iri(OpenSkos::CODE),
                new InternalResourceId($setIdentifier),
            );
            if ($institution) {
                return $institution;
            }

            // None of the available options was able to fetch the institution
            throw new ApiException('apirequestresolver-set-not-found', [
                'given' => $setIdentifier,
            ]);
        }, $sets);
    }

    /**
     * @return \Generator
     *
     * @throws HttpException
     *
     * @ErrorInherit(class=ApiRequestResolver::class, method="resolveFormat"      )
     * @ErrorInherit(class=ApiRequestResolver::class, method="resolveInstitutions")
     * @ErrorInherit(class=ApiRequestResolver::class, method="resolveSets"        )
     * @ErrorInherit(class=Graph::class             , method="__construct"        )
     * @ErrorInherit(class=Graph::class             , method="parse"              )
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        $allParameters = $request->query->all();

        $institutions = $request->query->get('institutions', '');
        $institutions = preg_split('/\s*,\s*/', $institutions, -1, PREG_SPLIT_NO_EMPTY);
        $institutions = $this->resolveInstitutions($institutions);

        $sets = $request->query->get('sets', '');
        $sets = preg_split('/\s*,\s*/', $sets, -1, PREG_SPLIT_NO_EMPTY);
        $sets = $this->resolveSets($sets);

        //B.Hillier. The specs from Menzo ask for a 'foreign uri' as a parameter. I have no idea how this is stored
        // at Meertens. For now it just searches on the same field as the 'native' uri
        $foreignUri = $request->query->get('uri', null);

        $formatName = $request->query->get('format');
        if (is_null($formatName)) {
            $formatName = $request->attributes->get('format');
            if (is_string($formatName) && (!strlen($formatName))) {
                $formatName = null;
            }
        }

        // Detect format of request body
        $graph = new Graph();
        $types = $request->headers->get('content-type', null, false);
        if (is_string($types)) {
            $types = [$types];
        }
        if (is_null($types)) {
            $types = [];
        }
        if (is_array($types)) {
            array_push($types, 'application/rdf+json');
        }

        $givenFormat = $this->resolveFormat(null, [implode(',', $types)]);

        // Parse body into graph
        // No error needs to be thrown here: empty data will throw a BadRequest later
        $content = $request->getContent();
        if (!is_null($givenFormat) && is_string($content) && strlen($content)) {
            $graph->parse($content, $givenFormat->easyRdfName());
        }

        yield new ApiRequest(
            $allParameters,
            $this->resolveFormat($formatName, $request->headers->all()['accept'] ?? null),
            $request->query->getInt('level', 1),
            $request->query->getInt('limit', 100),
            $request->query->getInt('offset', 0),
            $institutions,
            $sets,
            $foreignUri,
            new Authentication($request),
            $graph
        );
    }
}
