<?php

declare(strict_types=1);

namespace App\OpenSkos\ConceptScheme;

use App\Annotation\Document;
use App\Ontology\Dc;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\Skos;
use App\Rdf\AbstractRdfDocument;

/**
 * @Document\Type(Skos::CONCEPT_SCHEME)
 */
final class ConceptScheme extends AbstractRdfDocument
{
    const type          = 'type';
    const datesubmitted = 'datesubmitted';
    const set           = 'set';
    const uuid          = 'uuid';
    const creator       = 'creator';
    const tenant        = 'tenant';
    const modified      = 'modified';
    const title         = 'title';
    const description   = 'description';
    const hasTopConcept = 'hasTopConcept';
    const status        = 'status';
    const dateDeleted   = 'dateDeleted';
    const deletedBy     = 'deletedBy';
    const modifiedBy    = 'modifiedBy';

    /**
     * @var string[]
     */
    protected static $mapping = [
        self::type          => Rdf::TYPE,
        self::datesubmitted => DcTerms::DATE_SUBMITTED,
        self::set           => OpenSkos::SET,
        self::uuid          => OpenSkos::UUID,
        self::creator       => Dc::CREATOR,
        self::tenant        => OpenSkos::TENANT,
        self::modified      => DcTerms::MODIFIED,
        self::title         => DcTerms::TITLE,
        self::description   => DcTerms::DESCRIPTION,
        self::hasTopConcept => Skos::HAS_TOP_CONCEPT,
        self::status        => OpenSkos::STATUS,
        self::dateDeleted   => OpenSkos::DATE_DELETED,
        self::deletedBy     => OpenSkos::DELETED_BY,
        self::modifiedBy    => OpenSkos::MODIFIED_BY,
    ];

    protected static $required = [
        DcTerms::TITLE,
        OpenSkos::SET,
        OpenSkos::TENANT,
    ];

    protected static $updateFields = [
        DcTerms::TITLE,
        OpenSkos::MODIFIED_BY,
    ];
}
