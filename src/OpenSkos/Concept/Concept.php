<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept;

use App\Ontology\Dc;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\Skos;
use App\Ontology\SkosXl;
use App\OpenSkos\Label\Label;
use App\OpenSkos\Label\LabelRepository;
use App\Rdf\VocabularyAwareResource;
use App\Rdf\Iri;
use App\Rdf\RdfResource;
use App\Rdf\Triple;

final class Concept implements RdfResource
{
    const type = 'type';
    const set = 'set';
    const uuid = 'uuid';
    const notation = 'notation';
    const status = 'status';
    const tenant = 'tenant';
    const inScheme = 'inScheme';
    const publisher = 'publisher';
    const title = 'title';
    const example = 'example';

    const contributor = 'contributor';
    const dateApproved = 'dateApproved';
    const deletedBy = 'deletedBy';
    const dateDeleted = 'dateDeleted';
    const creator = 'creator';
    const modified = 'modified';
    const modifiedBy = 'modifiedBy';
    const datesubmitted = 'datesubmitted';

    const prefLabel = 'prefLabel';
    const altLabel = 'altLabel';
    const hiddenLabel = 'hiddenLabel';
    const XlPrefLabel = 'XlPrefLabel';
    const XlAltLabel = 'XlAltLabel';
    const XlHiddenLabel = 'XlHiddenLabel';

    const editorialNote = 'editorialNote';
    const Note = 'Note';
    const note = 'note';
    const historyNote = 'historyNote';
    const scopeNote = 'scopeNote';
    const changeNote = 'changeNote';

    const toBeChecked = 'toBeChecked';
    const dateAccepted = 'dateAccepted';
    const acceptedBy = 'acceptedBy';

    const definition = 'definition';
    const related = 'related';
    const topConceptOf = 'topConceptOf';
    const broader = 'broader';
    const broadMatch = 'broadMatch';
    const broaderMatch = 'broaderMatch';
    const broaderTransitive = 'broaderTransitive';
    const narrower = 'narrower';
    const narrowMatch = 'narrowMatch';
    const narrowerTransitive = 'narrowerTransitive';

    const relatedMatch = 'relatedMatch';

    /**
     * @var string[]
     */
    private static $mapping = [
        self::type => Rdf::TYPE,
        self::set => OpenSkos::SET,
        self::uuid => OpenSkos::UUID,
        self::notation => Skos::NOTATION,
        self::status => OpenSkos::STATUS,
        self::tenant => OpenSkos::TENANT,
        self::inScheme => Skos::INSCHEME,
        self::publisher => DcTerms::PUBLISHER,
        self::title => DcTerms::TITLE,
        self::example => Skos::EXAMPLE,

        self::contributor => Dc::CONTRIBUTOR,
        self::dateApproved => DcTerms::DATEAPPROVED,
        self::deletedBy => OpenSkos::DELETEDBY,
        self::dateDeleted => OpenSkos::DATE_DELETED,
        self::creator => Dc::CREATOR,
        self::modified => DcTerms::MODIFIED,
        self::modifiedBy => OpenSkos::MODIFIEDBY,
        self::datesubmitted => DcTerms::DATESUBMITTED,

        self::prefLabel => Skos::PREFLABEL,
        self::altLabel => Skos::ALTLABEL,
        self::hiddenLabel => Skos::HIDDENLABEL,
        self::XlPrefLabel => SkosXl::PREFLABEL,
        self::XlAltLabel => SkosXl::ALTLABEL,
        self::XlHiddenLabel => SkosXl::HIDDENLABEL,

        self::editorialNote => Skos::EDITORIALNOTE,
        self::Note => Skos::NOTE,
        self::note => Skos::NOTE,              /*Both lc and capitalized found in data ???? */
        self::historyNote => Skos::HISTORYNOTE,
        self::scopeNote => Skos::SCOPENOTE,
        self::changeNote => Skos::CHANGENOTE,

        self::toBeChecked => OpenSkos::TOBECHECKED,
        self::dateAccepted => DcTerms::DATEACCEPTED,
        self::acceptedBy => OpenSkos::ACCEPTEDBY,

        self::definition => Skos::DEFINITION,
        self::topConceptOf => Skos::TOPCONCEPTOF,
        self::related => Skos::RELATED,
        self::relatedMatch => Skos::RELATEDMATCH,

        self::broader => Skos::BROADER,
        self::broadMatch => Skos::BROADMATCH,
        self::broaderMatch => Skos::BROADERMATCH,  /* I'm not certain this should be here, but I've found it lurking in Jena */
        self::broaderTransitive => Skos::BROADERTRANSITIVE,

        self::narrower => Skos::NARROWER,
        self::narrowMatch => Skos::NARROWMATCH,
        self::narrowerTransitive => Skos::NARROWERTRANSITIVE,
    ];


    private static $xlPredicates = [
        self::XlPrefLabel => SkosXl::PREFLABEL,
        self::XlAltLabel => SkosXl::ALTLABEL,
        self::XlHiddenLabel => SkosXl::HIDDENLABEL,
    ];

    private static $nonXlPredicates = [
        self::prefLabel => Skos::PREFLABEL,
        self::altLabel => Skos::ALTLABEL,
        self::hiddenLabel => Skos::HIDDENLABEL
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
     * @return Triple[]
     */
    public function properties(): array
    {
        return $this->resource->properties();
    }

    /**
     * @param string $property
     * @return array|null
     */
    public function getProperty(string $property): ?array
    {
        return $this->resource->getProperty($property);
    }

    /**
     * @param Iri $subject
     *
     * @return Concept
     */
    public static function createEmpty(Iri $subject): self
    {
        return new self($subject);
    }

    /**
     * @param Iri      $subject
     * @param Triple[] $triples
     *
     * @return Concept
     */
    public static function fromTriples(Iri $subject, array $triples): self
    {
        $resource = VocabularyAwareResource::fromTriples($subject, $triples, self::$mapping);

        return new self($subject, $resource);
    }


    /**
     * Loads the XL labels and replaces the default URI value with the full resource
     * @param LabelRepository $labelRepository
     */
    public function loadFullXlLabels(LabelRepository $labelRepository)
    {
        $tripleList = $this->triples();
        foreach ($tripleList as $triplesKey => $triple){

            if ($triple instanceof Label) {
                continue;
            }

            foreach ($this::$xlPredicates as $key => $xlLabelPredicate) {
                if($triple->getPredicate()->getUri() == $xlLabelPredicate){
                    $xlLabel = $triple->getObject();

                    $fullLabel = $labelRepository->findByIri($xlLabel);
                    $predicate = $triple->getPredicate();
                    $fullLabel->setType($predicate);
                    $this->resource->replaceTriple($triplesKey, $fullLabel);
                }
            }

        }
        $this->resource->reIndexTripleStore();
    }
}
