<?php

declare(strict_types=1);

namespace App\OpenSkos\User;

use App\Ontology\Dc;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\Skos;
use App\Ontology\SkosXl;
use App\OpenSkos\InternalResourceId;
use App\OpenSkos\Label\Label;
use App\OpenSkos\Label\LabelRepository;
use App\Rdf\VocabularyAwareResource;
use App\Rdf\Iri;
use App\Rdf\RdfResource;
use App\Rdf\Triple;

final class User implements RdfResource
{
    // /**
    //  * All possible statuses.
    //  */
    // const STATUS_CANDIDATE = 'candidate';
    // const STATUS_APPROVED = 'approved';
    // const STATUS_REDIRECTED = 'redirected';
    // const STATUS_NOT_COMPLIANT = 'not_compliant';
    // const STATUS_REJECTED = 'rejected';
    // const STATUS_OBSOLETE = 'obsolete';
    // const STATUS_DELETED = 'deleted';
    // const STATUS_EXPIRED = 'expired';

    const type = 'type';
    const name = 'set';
    const uuid = 'uuid';
    // const notation = 'notation';
    // const status = 'status';
    // const tenant = 'tenant';
    // const inScheme = 'inScheme';
    // const publisher = 'publisher';
    // const title = 'title';
    // const example = 'example';

    // const contributor = 'contributor';
    // const dateApproved = 'dateApproved';
    // const deletedBy = 'deletedBy';
    // const dateDeleted = 'dateDeleted';
    // const creator = 'creator';
    // const modified = 'modified';
    // const modifiedBy = 'modifiedBy';
    // const datesubmitted = 'datesubmitted';

    // const prefLabel = 'prefLabel';
    // const altLabel = 'altLabel';
    // const hiddenLabel = 'hiddenLabel';
    // const XlPrefLabel = 'XlPrefLabel';
    // const XlAltLabel = 'XlAltLabel';
    // const XlHiddenLabel = 'XlHiddenLabel';

    // const editorialNote = 'editorialNote';
    // const Note = 'Note';
    // const note = 'note';
    // const historyNote = 'historyNote';
    // const scopeNote = 'scopeNote';
    // const changeNote = 'changeNote';

    // const toBeChecked = 'toBeChecked';
    // const dateAccepted = 'dateAccepted';
    // const acceptedBy = 'acceptedBy';

    // const definition = 'definition';
    // const related = 'related';
    // const topConceptOf = 'topConceptOf';
    // const broader = 'broader';
    // const broadMatch = 'broadMatch';
    // const broaderMatch = 'broaderMatch';
    // const broaderTransitive = 'broaderTransitive';
    // const narrower = 'narrower';
    // const narrowMatch = 'narrowMatch';
    // const narrowerTransitive = 'narrowerTransitive';

    // const relatedMatch = 'relatedMatch';

    /**
     * @var string[]
     */
    private static $mapping = [
        self::type => Rdf::TYPE,
        self::name => Openskos::NAME,
    //     self::set => OpenSkos::SET,
        self::uuid => OpenSkos::UUID,
    //     self::notation => Skos::NOTATION,
    //     self::status => OpenSkos::STATUS,
    //     self::tenant => OpenSkos::TENANT,
    //     self::inScheme => Skos::INSCHEME,
    //     self::publisher => DcTerms::PUBLISHER,
    //     self::title => DcTerms::TITLE,
    //     self::example => Skos::EXAMPLE,

    //     self::contributor => Dc::CONTRIBUTOR,
    //     self::dateApproved => DcTerms::DATEAPPROVED,
    //     self::deletedBy => OpenSkos::DELETEDBY,
    //     self::dateDeleted => OpenSkos::DATE_DELETED,
    //     self::creator => Dc::CREATOR,
    //     self::modified => DcTerms::MODIFIED,
    //     self::modifiedBy => OpenSkos::MODIFIEDBY,
    //     self::datesubmitted => DcTerms::DATESUBMITTED,

    //     self::prefLabel => Skos::PREFLABEL,
    //     self::altLabel => Skos::ALTLABEL,
    //     self::hiddenLabel => Skos::HIDDENLABEL,
    //     self::XlPrefLabel => SkosXl::PREFLABEL,
    //     self::XlAltLabel => SkosXl::ALTLABEL,
    //     self::XlHiddenLabel => SkosXl::HIDDENLABEL,

    //     self::editorialNote => Skos::EDITORIALNOTE,
    //     self::Note => Skos::NOTE,
    //     self::note => Skos::NOTE,              /*Both lc and capitalized found in data ???? */
    //     self::historyNote => Skos::HISTORYNOTE,
    //     self::scopeNote => Skos::SCOPENOTE,
    //     self::changeNote => Skos::CHANGENOTE,

    //     self::toBeChecked => OpenSkos::TOBECHECKED,
    //     self::dateAccepted => DcTerms::DATEACCEPTED,
    //     self::acceptedBy => OpenSkos::ACCEPTEDBY,

    //     self::definition => Skos::DEFINITION,
    //     self::topConceptOf => Skos::TOPCONCEPTOF,
    //     self::related => Skos::RELATED,
    //     self::relatedMatch => Skos::RELATEDMATCH,

    //     self::broader => Skos::BROADER,
    //     self::broadMatch => Skos::BROADMATCH,
    //     self::broaderMatch => Skos::BROADERMATCH,  /* I'm not certain this should be here, but I've found it lurking in Jena */
    //     self::broaderTransitive => Skos::BROADERTRANSITIVE,

    //     self::narrower => Skos::NARROWER,
    //     self::narrowMatch => Skos::NARROWMATCH,
    //     self::narrowerTransitive => Skos::NARROWERTRANSITIVE,
    ];

    // private static $xlPredicates = [
    //     self::XlPrefLabel => SkosXl::PREFLABEL,
    //     self::XlAltLabel => SkosXl::ALTLABEL,
    //     self::XlHiddenLabel => SkosXl::HIDDENLABEL,
    // ];

    // private static $nonXlPredicates = [
    //     self::prefLabel => Skos::PREFLABEL,
    //     self::altLabel => Skos::ALTLABEL,
    //     self::hiddenLabel => Skos::HIDDENLABEL,
    // ];

    // /*
    //  * Which fields can be used for projection.
    //  * Labels defined at: https://github.com/picturae/API/blob/develop/doc/OpenSKOS-API.md#concepts.
    //  */
    // private static $acceptable_fields = [
    //     'uri' => '',        //IN the specs, but it's actually the Triple subject, and has to be sent
    //     'label' => '?',     //Unclear what is meant
    //     'type' => Rdf::TYPE,

    //     //Labels
    //     'prefLabel' => Skos::PREFLABEL,
    //     'altLabel' => Skos::ALTLABEL,
    //     'hiddenLabel' => Skos::HIDDENLABEL,

    //     'prefLabelXl' => SkosXl::PREFLABEL,
    //     'altLabelXl' => SkosXl::ALTLABEL,
    //     'hiddenLabelXl' => SkosXl::HIDDENLABEL,

    //     //Notes
    //     'note' => Skos::NOTE,
    //     'Note' => Skos::NOTE,
    //     'example' => Skos::EXAMPLE,
    //     'changeNote' => Skos::CHANGENOTE,
    //     'historyNote' => Skos::HISTORYNOTE,
    //     'scopeNote' => Skos::SCOPENOTE,
    //     'editorialNote' => Skos::EDITORIALNOTE,
    //     'notation' => Skos::NOTATION,

    //     //Relations
    //     'definition' => Skos::DEFINITION,
    //     'broader' => Skos::BROADER,
    //     'narrower' => Skos::NARROWER,
    //     'related' => Skos::RELATED,
    //     'broaderTransitive' => Skos::BROADERTRANSITIVE,
    //     'narrowerTransitive' => Skos::NARROWERTRANSITIVE,

    //     'mappingRelation' => '?',

    //     //Matches
    //     'closeMatch' => Skos::CLOSEMATCH,
    //     'exactMatch' => Skos::EXACTMATCH,
    //     'broadMatch' => Skos::BROADMATCH,
    //     'narrowMatch' => Skos::NARROWMATCH,
    //     'relatedMatch' => Skos::RELATEDMATCH,

    //     //Mappings
    //     'openskos:inCollection' => OpenSkos::INSKOSCOLLECTION,
    //     'openskos:set' => OpenSkos::SET,
    //     'openskos:institution' => OpenSkos::TENANT,

    //     //Dates
    //     'dateSubmitted' => DcTerms::DATESUBMITTED,
    //     'modified' => DcTerms::MODIFIED,
    //     'dateAccepted' => DcTerms::DATEACCEPTED,
    //     'openskos:deleted' => OpenSkos::DATE_DELETED,

    //     //Users
    //     'creator' => Dc::CREATOR,
    //     'openskos:modifiedBy' => OpenSkos::MODIFIEDBY,
    //     'openskos:acceptedBy' => OpenSkos::ACCEPTEDBY,
    //     'openskos:deletedBy' => OpenSkos::DELETEDBY,

    //     //Other Stuff
    //     'openskos:status' => OpenSkos::STATUS,
    //     'inScheme' => Skos::INSCHEME,
    //     'topConceptOf' => Skos::TOPCONCEPTOF,
    //     'skosxl' => '?',
    // ];

    // /*
    //  * XL alternatives for acceptable fields
    //  */
    // private static $acceptable_fields_to_xl = [
    //     'prefLabel' => 'prefLabelXl',
    //     'altLabel' => 'altLabelXl',
    //     'hiddenLabel' => 'hiddenLabelXl',
    // ];

    // /*
    //  * Which fields are language sensitive. Subset of $acceptable_fields
    //  */
    // public static $language_sensitive = [
    //     //Labels
    //     'label', 'prefLabel', 'altLabel', 'hiddenLabel',
    //     //Notes
    //     'note', 'definition', 'example', 'changeNote', 'historyNote', 'scopeNote', 'editorialNote',
    // ];

    // /*
    //  * Meta projection parameters
    //  */
    // public static $meta_groups = [
    //     'all' => [],
    //     'skosxl' => [], //@todo. We're also doing this in levels for everything. Why project here too? What do we want to do with this?
    //     'default' => ['uri' => ['lang' => ''], 'prefLabel' => ['lang' => ''], 'definition' => ['lang' => '']],
    // ];

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

    // /**
    //  * @return array
    //  */
    // public static function getAcceptableFields(): array
    // {
    //     return self::$acceptable_fields;
    // }

    /**
     * @return string[]
     */
    public static function getMapping(): array
    {
        return self::$mapping;
    }

    // /**
    //  * @return array
    //  */
    // public static function getXlPredicates(): array
    // {
    //     return self::$xlPredicates;
    // }

    // /**
    //  * @return array
    //  */
    // public static function getNonXlPredicates(): array
    // {
    //     return self::$nonXlPredicates;
    // }

    // /**
    //  * @return array
    //  */
    // public static function getLanguageSensitive(): array
    // {
    //     return self::$language_sensitive;
    // }

    // /**
    //  * @return array
    //  */
    // public static function getMetaGroups(): array
    // {
    //     return self::$meta_groups;
    // }

    // /**
    //  * @return array
    //  */
    // public static function getAcceptableFieldsToXl(): array
    // {
    //     return self::$acceptable_fields_to_xl;
    // }

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

    // /**
    //  * @return Triple[]
    //  */
    // public function properties(): ?array
    // {
    //     return $this->resource->properties();
    // }

    // /**
    //  * @param string $property
    //  *
    //  * @return array|null
    //  */
    // public function getProperty(string $property): ?array
    // {
    //     return $this->resource->getProperty($property);
    // }

    // /**
    //  * @param Iri $subject
    //  *
    //  * @return Concept
    //  */
    // public static function createEmpty(Iri $subject): self
    // {
    //     return new self($subject);
    // }

    /**
     * @param Iri      $subject
     * @param Triple[] $triples
     *
     * @return User
     */
    public static function fromTriples(Iri $subject, array $triples): self
    {
        $resource = VocabularyAwareResource::fromTriples($subject, $triples, self::$mapping);

        return new self($subject, $resource);
    }

    /**
     * Loads the XL labels and replaces the default URI value with the full resource.
     *
     * @param LabelRepository $labelRepository
     */
    public function loadFullXlLabels(LabelRepository $labelRepository)
    {
        $tripleList = $this->triples();
        foreach ($tripleList as $triplesKey => $triple) {
            if ($triple instanceof Label) {
                continue;
            }

            foreach ($this::$xlPredicates as $key => $xlLabelPredicate) {
                if ($triple->getPredicate()->getUri() == $xlLabelPredicate) {
                    /**
                     * @var Iri
                     */
                    $xlLabel = $triple->getObject();

                    $fullLabel = $labelRepository->findByIri($xlLabel);
                    if (isset($fullLabel)) {
                        $subject = $triple->getSubject();
                        $fullLabel->setSubject($subject);
                        $predicate = $triple->getPredicate();
                        $fullLabel->setType($predicate);
                        $this->resource->replaceTriple($triplesKey, $fullLabel);
                    }
                }
            }
        }
        $this->resource->reIndexTripleStore();
    }

    /**
     * We are returning by reference, to quickly enable the data-levels functionality.
     *   Otherwise, a lot of extra hoops have to be jumped through just to add a data level.
     *
     * @return VocabularyAwareResource
     */
    public function &getResource(): VocabularyAwareResource
    {
        return $this->resource;
    }
}
