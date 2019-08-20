<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept;

use App\Ontology\Dc;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\Skos;
use App\Ontology\SkosXl;
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

    /*
    <http://www.w3.org/2004/02/skos/core#prefLabel>
    <http://www.w3.org/2004/02/skos/core#inScheme>
    <http://www.w3.org/2004/02/skos/core#notation>
    <http://www.w3.org/2008/05/skos-xl#prefLabel>
    
    <http://purl.org/dc/terms/publisher>
    <http://www.w3.org/2004/02/skos/core#editorialNote>
    <http://www.w3.org/2004/02/skos/core#changeNote>
    <http://openskos.org/xmlns#toBeChecked>
    <http://purl.org/dc/terms/dateAccepted>
    <http://www.w3.org/2004/02/skos/core#hiddenLabel>
    <http://openskos.org/xmlns#acceptedBy>
    <http://www.w3.org/2008/05/skos-xl#hiddenLabel>
    <http://www.w3.org/2004/02/skos/core#scopeNote>
    <http://www.w3.org/2004/02/skos/core#related>
    <http://purl.org/dc/terms/title>
    <http://www.w3.org/2004/02/skos/core#altLabel>
    <http://www.w3.org/2008/05/skos-xl#altLabel>
    <http://www.w3.org/2004/02/skos/core#historyNote>
    <http://purl.org/dc/terms/creator>
    <http://www.w3.org/2004/02/skos/core#note>
    <http://purl.org/dc/elements/1.1/contributor>
    <http://www.w3.org/2004/02/skos/core#topConceptOf>
    <http://www.w3.org/2004/02/skos/core#broadMatch>
    <http://www.w3.org/2004/02/skos/core#narrowerTransitive>
    <http://www.w3.org/2004/02/skos/core#narrower>
    <http://www.w3.org/2004/02/skos/core#broaderMatch>
    <http://www.w3.org/2004/02/skos/core#broaderTransitive>
    <http://www.w3.org/2004/02/skos/core#broader>
    <http://www.w3.org/2004/02/skos/core#narrowMatch>
    <http://www.w3.org/2004/02/skos/core#example>
    <http://openskos.org/xmlns#dateDeleted>
    <http://www.w3.org/2004/02/skos/core#definition>
    <http://purl.org/dc/terms/contributor>
    <http://openskos.org/xmlns#deletedBy>
    <http://www.w3.org/2004/02/skos/core#relatedMatch>
    <http://openskos.org/xmlns#notation>
    <http://purl.org/dc/terms/dateApproved>
    <http://www.w3.org/2004/02/skos/core#Note>
    */

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
}
