<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution;

use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\VCard;
use App\Rdf\VocabularyAwareResource;
use App\Rdf\Iri;
use App\Rdf\RdfResource;
use App\Rdf\Triple;

final class Institution implements RdfResource
{
    const code = 'code';
    const name = 'name';
    const organisationUnit = 'organisationUnit';
    const email = 'email';
    const website = 'website';
    const streetAddress = 'streetAddress';
    const locality = 'locality';
    const postalCode = 'postalCode';
    const countryName = 'countryName';
    const enableStatusesSystem = 'enableStatusesSystem';
    const enableSkosXl = 'enableSkosXl';
    const type = 'type';
    const disableSearchInOtherTenants = 'disableSearchInOtherTenants';

    /**
     * @var string[]
     */
    private static $mapping = [
        self::code => OpenSkos::CODE,
        self::name => OpenSkos::NAME,
        self::disableSearchInOtherTenants => OpenSkos::DISABLESEARCHINOTERTENANTS,
        self::organisationUnit => VCard::ORGUNIT,
        self::email => VCard::EMAIL,
        self::website => OpenSkos::WEBPAGE,
        self::streetAddress => VCard::ADR,
        self::locality => VCard::LOCALITY,
        self::postalCode => VCard::PCODE,
        self::countryName => VCard::COUNTRY,
        self::enableStatusesSystem => OpenSkos::ENABLESTATUSSESSYSTEMS,
        self::enableSkosXl => OpenSkos::ENABLESKOSXL,
        self::type => Rdf::TYPE,
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
     * @return Institution
     */
    public static function createEmpty(Iri $subject): self
    {
        return new self($subject);
    }

    /**
     * @param Iri      $subject
     * @param Triple[] $triples
     *
     * @return Institution
     */
    public static function fromTriples(Iri $subject, array $triples): self
    {
        $resource = VocabularyAwareResource::fromTriples($subject, $triples, self::$mapping);

        return new self($subject, $resource);
    }
}
