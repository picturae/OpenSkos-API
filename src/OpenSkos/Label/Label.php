<?php

declare(strict_types=1);

namespace App\OpenSkos\Label;

use App\Annotation\Document;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\SkosXl;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Iri;
use App\Rdf\RdfResource;
use App\Rdf\Triple;
use App\Rdf\VocabularyAwareResource;

/**
 * @Document\Type(SkosXl::LABEL)
 */
final class Label extends AbstractRdfDocument implements RdfResource
{
    const type        = 'type';
    const modified    = 'modified';
    const literalForm = 'literalForm';

    /**
     * @var string[]
     */
    protected static $mapping = [
        self::type        => Rdf::TYPE,
        self::modified    => DcTerms::MODIFIED,
        self::literalForm => SkosXl::LITERAL_FORM,
    ];

    /**
     * @var Iri|null
     */
    protected $type;

    /**
     * @var Iri|null
     */
    protected $subject;

    /**
     * @var Iri
     */
    protected $childSubject;

    /**
     * Which fields can be used for projection.
     * Labels defined at: https://github.com/picturae/API/blob/develop/doc/OpenSKOS-API.md#concepts.
     *
     * @var array
     */
    private static $acceptable_fields = [
        'uri'         => '',        //IN the specs, but it's actually the Triple subject, and has to be sent
        'literalForm' => SkosXl::LITERAL_FORM,
        /* 'isPrefLabelOf' => */
        /* 'isAltLabelOf' => */
        /* 'isHiddenLabelOf' => */
        'labelRelation' => SkosXl::LABEL_RELATION,
        'set'           => OpenSkos::SET,
        /* 'institution' => OpenSkos::INSTITUTION, */
        'tenant' => OpenSkos::TENANT,
        'status' => OpenSkos::STATUS,

        /* 'alive' => */
        /* 'deleted' => */
        /* 'dates' => */
        /* 'modified' => */
        /* 'dateSubmitted' => */
        /* 'users' => */
        /* 'creator' => */
        /* 'modifiedBy' => */
        /* 'acceptedBy' => */
        /* 'deletedBy' => */
    ];

    /**
     * Meta projection parameters.
     *
     * @var array
     */
    public static $meta_groups = [
        'all'     => [],
        'skosxl'  => [], //@todo. We're also doing this in levels for everything. Why project here too? What do we want to do with this?
        'default' => ['uri' => ['lang' => ''], 'literalForm' => ['lang' => '']],
    ];

    /**
     * When we create a label, the terms 'subject', 'predicate', 'object' can get mixed up, because the object of the parent is
     * also the subject of the label elements.
     *
     * We allow the parent subject and predicate to be null because
     * 1) In theory labels can exist in isolation.
     * 2) The way the repositories are structured means the code the fetches label elements is unaware of which parent
     *      it's fetching for. Indeed: it can be fetched for several parents
     *
     * Label constructor.
     */
    public function __construct(
        Iri $childSubject,
        ?VocabularyAwareResource $resource = null,
        ?Iri $parentPredicate = null,
        ?Iri $parentSubject = null
    ) {
        $this->childSubject = $childSubject;
        $this->type         = $parentPredicate;
        $this->subject      = $parentSubject;
        parent::__construct($childSubject, $resource);
    }

    public function getType(): ?Iri
    {
        return $this->type;
    }

    public function setType(?Iri $type): void
    {
        $this->type = $type;
    }

    public function getSubject(): ?Iri
    {
        return $this->subject;
    }

    /**
     * @param Iri $subject|null
     */
    public function setSubject(Iri $subject): void
    {
        $this->subject = $subject;
    }

    public function getChildSubject(): Iri
    {
        return $this->childSubject;
    }

    public function setChildSubject(Iri $childSubject): void
    {
        $this->childSubject = $childSubject;
    }

    public static function getAcceptableFields(): array
    {
        return self::$acceptable_fields;
    }

    public static function getMetaGroups(): array
    {
        return self::$meta_groups;
    }
}
