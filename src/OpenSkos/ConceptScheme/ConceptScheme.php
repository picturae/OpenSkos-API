<?php

declare(strict_types=1);

namespace App\OpenSkos\ConceptScheme;

use App\Ontology\Dc;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\Skos;
use App\Rdf\VocabularyAwareResource;
use App\Rdf\Iri;
use App\Rdf\RdfResource;
use App\Rdf\Triple;

final class ConceptScheme implements RdfResource
{
    const type = 'type';
    const datesubmitted = 'datesubmitted';
    const set = 'set';
    const uuid = 'uuid';
    const creator = 'creator';
    const tenant = 'tenant';
    const modified = 'modified';
    const title = 'title';
    const description = 'description';
    const hasTopConcept = 'hasTopConcept';
    const status = 'status';
    const dateDeleted = 'dateDeleted';
    const deletedBy = 'deletedBy';
    const modifiedBy = 'modifiedBy';

    /**
     * @var string[]
     */
    private static $mapping = [
        self::type => Rdf::TYPE,
        self::datesubmitted => DcTerms::DATESUBMITTED,
        self::set => OpenSkos::SET,
        self::uuid => OpenSkos::UUID,
        self::creator => Dc::CREATOR,
        self::tenant => OpenSkos::TENANT,
        self::modified => DcTerms::MODIFIED,
        self::title => DcTerms::TITLE,
        self::description => DcTerms::DESCRIPTION,
        self::hasTopConcept => Skos::HASTOPCONCEPT,
        self::status => OpenSkos::STATUS,
        self::dateDeleted => OpenSkos::DATE_DELETED,
        self::deletedBy => OpenSkos::DELETEDBY,
        self::modifiedBy => OpenSkos::MODIFIEDBY,
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
     * @return ConceptScheme
     */
    public static function createEmpty(Iri $subject): self
    {
        return new self($subject);
    }

    /**
     * @param Iri      $subject
     * @param Triple[] $triples
     *
     * @return ConceptScheme
     */
    public static function fromTriples(Iri $subject, array $triples): self
    {
        $resource = VocabularyAwareResource::fromTriples($subject, $triples, self::$mapping);

        return new self($subject, $resource);
    }
}
