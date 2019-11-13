<?php

declare(strict_types=1);

namespace App\OpenSkos\Set;

use App\Annotation\Document;
use App\Annotation\Error;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Literal\Literal;

/**
 * @Document\Type(OpenSkos::SET)
 */
final class Set extends AbstractRdfDocument
{
    const allow_oai = 'allow_oai';
    const code = 'code';
    const conceptBaseUri = 'conceptBaseUri';
    const oai_baseURL = 'oai_baseURL';
    const tenant = 'tenant';
    const webpage = 'webpage';
    const description = 'description';
    const license = 'license';
    const publisher = 'publisher';
    const title = 'title';
    const type = 'type';
    const uuid = 'uuid';

    /**
     * @var string[]
     */
    protected static $mapping = [
        self::tenant => OpenSkos::TENANT,
        self::code => OpenSkos::CODE,
        self::allow_oai => OpenSkos::ALLOW_OAI,
        self::conceptBaseUri => OpenSkos::CONCEPT_BASE_URI,
        self::oai_baseURL => OpenSkos::OAI_BASE_URL,
        self::webpage => OpenSkos::WEBPAGE,
        self::description => DcTerms::DESCRIPTION,
        self::license => DcTerms::LICENSE,
        self::publisher => DcTerms::PUBLISHER,
        self::title => DcTerms::TITLE,
        self::type => Rdf::TYPE,
        self::uuid => OpenSkos::UUID,
    ];

    protected static $required = [
        OpenSkos::CODE,
        OpenSkos::CONCEPT_BASE_URI,
        OpenSkos::TENANT,
    ];

    /**
     * Extends the regular error checking.
     *
     * @Error(code="set-base-uri-does-not-match-code",
     *        status=422,
     *        description="The given openskos:code does not match the end of the given openskos:conceptBaseUri",
     *        fields={"code","base-uri"}
     * )
     */
    public function errors(string $errorPrefix = null): array
    {
        $errorPrefix = $errorPrefix ?? 'set';

        // Regular checking
        $errors = parent::errors($errorPrefix);
        if (count($errors)) {
            return $errors;
        }

        // Fetch baseUri & code
        /** @var Literal $baseUri */
        $baseUri = $this->getValue(OpenSkos::CONCEPT_BASE_URI);
        $baseUri = $baseUri->value();
        /** @var Literal $code */
        $code = $this->getValue(OpenSkos::CODE);
        $code = $code->value();

        // The last part of the iri and the code must match
        $id = explode('/', $baseUri);
        $id = array_pop($id);
        if ($id !== $code) {
            return [[
                'code' => $errorPrefix.'-base-uri-does-not-match-code',
                'data' => [
                    'code' => $code,
                    'base-uri' => $id,
                ],
            ]];
        }

        return [];
    }
}
