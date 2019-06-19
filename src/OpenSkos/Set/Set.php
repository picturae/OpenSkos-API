<?php

declare(strict_types=1);

namespace App\OpenSkos\Set;

use App\Ontology\Dc;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\Rdf\Iri;
use App\Rdf\RdfResource;
use App\Rdf\Triple;
use App\Rdf\VocabularyAwareResource;

final class Set implements RdfResource
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

    /**
     * @var string[]
     */
    private static $mapping = [
        self::tenant => OpenSkos::TENANT,
        self::code => OpenSkos::CODE,
        self::allow_oai => OpenSkos::ALLOW_OAI,
        self::conceptBaseUri => OpenSkos::CONCEPTBASEURI,
        self::oai_baseURL => OpenSkos::OAI_BASEURL,
        self::webpage => OpenSkos::WEBPAGE,
        self::description => DcTerms::DESCRIPTION,
        self::license => DcTerms::LICENSE,
        self::publisher => DcTerms::PUBLISHER,
        self::title => DC::TITLE,
    ];

    /**
     * @var VocabularyAwareResource
     */
    private $resource;

    private function __construct(
        Iri $subject,
        ?VocabularyAwareResource $resource = null
    ) {
        if (null === $resource) {
            $this->resource = new VocabularyAwareResource($subject, array_flip(self::$mapping));
        } else {
            $this->resource = $resource;
        }
    }

    public function iri(): Iri
    {
        return $this->resource->iri();
    }

    /**
     * @return Triple[]
     */
    public function triples(): array
    {
        return $this->resource->triples();
    }

    /**
     * @param Iri $subject
     *
     * @return self
     */
    public static function createEmpty(Iri $subject): self
    {
        return new self($subject);
    }

    /**
     * @param Iri      $subject
     * @param Triple[] $triples
     *
     * @return self
     */
    public static function fromTriples(Iri $subject, array $triples): self
    {
        $resource = VocabularyAwareResource::fromTriples($subject, $triples, self::$mapping);

        return new self($subject, $resource);
    }
}
