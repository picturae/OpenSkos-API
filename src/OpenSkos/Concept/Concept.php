<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept;

use App\Annotation\Document;
use App\Annotation\Error;
use App\Database\Doctrine;
use App\Ontology\Context;
use App\Ontology\Dc;
use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\Skos;
use App\Ontology\SkosXl;
use App\OpenSkos\ConceptScheme\ConceptSchemeRepository;
use App\OpenSkos\RelationType\RelationType;
use App\Rdf\AbstractRdfDocument;
use App\Rdf\Iri;
use App\Rdf\Triple;
use App\Rdf\VocabularyAwareResource;
use App\Repository\AbstractRepository;

/**
 * @Document\Type(Skos::CONCEPT)
 */
final class Concept extends AbstractRdfDocument
{
    /**
     * All possible statuses.
     */
    const STATUS_CANDIDATE     = 'candidate';
    const STATUS_APPROVED      = 'approved';
    const STATUS_REDIRECTED    = 'redirected';
    const STATUS_NOT_COMPLIANT = 'not_compliant';
    const STATUS_REJECTED      = 'rejected';
    const STATUS_OBSOLETE      = 'obsolete';
    const STATUS_DELETED       = 'deleted';
    const STATUS_EXPIRED       = 'expired';

    const type      = 'type';
    const set       = 'set';
    const uuid      = 'uuid';
    const notation  = 'notation';
    const status    = 'status';
    const tenant    = 'tenant';
    const inScheme  = 'inScheme';
    const publisher = 'publisher';
    const title     = 'title';
    const example   = 'example';

    const contributor   = 'contributor';
    const dateApproved  = 'dateApproved';
    const deletedBy     = 'deletedBy';
    const dateDeleted   = 'dateDeleted';
    const creator       = 'creator';
    const modified      = 'modified';
    const modifiedBy    = 'modifiedBy';
    const datesubmitted = 'datesubmitted';

    const prefLabel     = 'prefLabel';
    const altLabel      = 'altLabel';
    const hiddenLabel   = 'hiddenLabel';
    const XlPrefLabel   = 'XlPrefLabel';
    const XlAltLabel    = 'XlAltLabel';
    const XlHiddenLabel = 'XlHiddenLabel';

    const editorialNote = 'editorialNote';
    const Note          = 'Note';
    const note          = 'note';
    const historyNote   = 'historyNote';
    const scopeNote     = 'scopeNote';
    const changeNote    = 'changeNote';

    const toBeChecked  = 'toBeChecked';
    const dateAccepted = 'dateAccepted';
    const acceptedBy   = 'acceptedBy';

    const definition         = 'definition';
    const related            = 'related';
    const topConceptOf       = 'topConceptOf';
    const broader            = 'broader';
    const broadMatch         = 'broadMatch';
    const broaderMatch       = 'broaderMatch';
    const broaderTransitive  = 'broaderTransitive';
    const narrower           = 'narrower';
    const narrowMatch        = 'narrowMatch';
    const narrowerTransitive = 'narrowerTransitive';

    const relatedMatch = 'relatedMatch';

    /**
     * @var string[]
     */
    protected static $mapping = [
        self::type      => Rdf::TYPE,
        self::set       => OpenSkos::SET,
        self::uuid      => OpenSkos::UUID,
        self::notation  => Skos::NOTATION,
        self::status    => OpenSkos::STATUS,
        self::tenant    => OpenSkos::TENANT,
        self::inScheme  => Skos::IN_SCHEME,
        self::publisher => DcTerms::PUBLISHER,
        self::title     => DcTerms::TITLE,
        self::example   => Skos::EXAMPLE,

        self::contributor   => Dc::CONTRIBUTOR,
        self::dateApproved  => DcTerms::DATE_APPROVED,
        self::deletedBy     => OpenSkos::DELETED_BY,
        self::dateDeleted   => OpenSkos::DATE_DELETED,
        self::creator       => Dc::CREATOR,
        self::modified      => DcTerms::MODIFIED,
        self::modifiedBy    => OpenSkos::MODIFIED_BY,
        self::datesubmitted => DcTerms::DATE_SUBMITTED,

        self::prefLabel     => Skos::PREF_LABEL,
        self::altLabel      => Skos::ALT_LABEL,
        self::hiddenLabel   => Skos::HIDDEN_LABEL,
        self::XlPrefLabel   => SkosXl::PREF_LABEL,
        self::XlAltLabel    => SkosXl::ALT_LABEL,
        self::XlHiddenLabel => SkosXl::HIDDEN_LABEL,

        self::editorialNote => Skos::EDITORIAL_NOTE,
        self::Note          => Skos::NOTE,
        self::note          => Skos::NOTE,              /*Both lc and capitalized found in data ???? */
        self::historyNote   => Skos::HISTORY_NOTE,
        self::scopeNote     => Skos::SCOPE_NOTE,
        self::changeNote    => Skos::CHANGE_NOTE,

        self::toBeChecked  => OpenSkos::TO_BE_CHECKED,
        self::dateAccepted => DcTerms::DATE_ACCEPTED,
        self::acceptedBy   => OpenSkos::ACCEPTED_BY,

        self::definition   => Skos::DEFINITION,
        self::topConceptOf => Skos::TOP_CONCEPT_OF,
        self::related      => Skos::RELATED,
        self::relatedMatch => Skos::RELATED_MATCH,

        self::broader           => Skos::BROADER,
        self::broadMatch        => Skos::BROAD_MATCH,
        self::broaderMatch      => Skos::BROADER_MATCH,  /* I'm not certain this should be here, but I've found it lurking in Jena */
        self::broaderTransitive => Skos::BROADER_TRANSITIVE,

        self::narrower           => Skos::NARROWER,
        self::narrowMatch        => Skos::NARROW_MATCH,
        self::narrowerTransitive => Skos::NARROWER_TRANSITIVE,
    ];

    protected static $xlPredicates = [
        self::XlPrefLabel   => SkosXl::PREF_LABEL,
        self::XlAltLabel    => SkosXl::ALT_LABEL,
        self::XlHiddenLabel => SkosXl::HIDDEN_LABEL,
    ];

    protected static $nonXlPredicates = [
        self::prefLabel   => Skos::PREF_LABEL,
        self::altLabel    => Skos::ALT_LABEL,
        self::hiddenLabel => Skos::HIDDEN_LABEL,
    ];

    /*
     * Which fields can be used for projection.
     * Labels defined at: https://github.com/picturae/API/blob/develop/doc/OpenSKOS-API.md#concepts.
     */
    protected static $acceptable_fields = [
        'uri'   => '',        //IN the specs, but it's actually the Triple subject, and has to be sent
        'label' => '?',     //Unclear what is meant
        'type'  => Rdf::TYPE,

        //Labels
        'prefLabel'   => Skos::PREF_LABEL,
        'altLabel'    => Skos::ALT_LABEL,
        'hiddenLabel' => Skos::HIDDEN_LABEL,

        'prefLabelXl'   => SkosXl::PREF_LABEL,
        'altLabelXl'    => SkosXl::ALT_LABEL,
        'hiddenLabelXl' => SkosXl::HIDDEN_LABEL,

        //Notes
        'note'          => Skos::NOTE,
        'Note'          => Skos::NOTE,
        'example'       => Skos::EXAMPLE,
        'changeNote'    => Skos::CHANGE_NOTE,
        'historyNote'   => Skos::HISTORY_NOTE,
        'scopeNote'     => Skos::SCOPE_NOTE,
        'editorialNote' => Skos::EDITORIAL_NOTE,
        'notation'      => Skos::NOTATION,

        //Relations
        'definition'         => Skos::DEFINITION,
        'broader'            => Skos::BROADER,
        'narrower'           => Skos::NARROWER,
        'related'            => Skos::RELATED,
        'broaderTransitive'  => Skos::BROADER_TRANSITIVE,
        'narrowerTransitive' => Skos::NARROWER_TRANSITIVE,

        'mappingRelation' => '?',

        //Matches
        'closeMatch'   => Skos::CLOSE_MATCH,
        'exactMatch'   => Skos::EXACT_MATCH,
        'broadMatch'   => Skos::BROAD_MATCH,
        'narrowMatch'  => Skos::NARROW_MATCH,
        'relatedMatch' => Skos::RELATED_MATCH,

        //Mappings
        'openskos:inCollection' => OpenSkos::IN_SKOS_COLLECTION,
        'openskos:set'          => OpenSkos::SET,
        'openskos:institution'  => OpenSkos::TENANT,

        //Dates
        'dateSubmitted'    => DcTerms::DATE_SUBMITTED,
        'modified'         => DcTerms::MODIFIED,
        'dateAccepted'     => DcTerms::DATE_ACCEPTED,
        'openskos:deleted' => OpenSkos::DATE_DELETED,

        //Users
        'creator'             => Dc::CREATOR,
        'openskos:modifiedBy' => OpenSkos::MODIFIED_BY,
        'openskos:acceptedBy' => OpenSkos::ACCEPTED_BY,
        'openskos:deletedBy'  => OpenSkos::DELETED_BY,

        //Other Stuff
        'openskos:status' => OpenSkos::STATUS,
        'inScheme'        => Skos::IN_SCHEME,
        'topConceptOf'    => Skos::TOP_CONCEPT_OF,
        'skosxl'          => '?',
    ];

    protected static $updateFields = [
        OpenSkos::SET,
        OpenSkos::STATUS,
        Skos::IN_SCHEME,
        DcTerms::TITLE,
        Skos::EXAMPLE,
        Dc::CONTRIBUTOR,
        DcTerms::DATE_APPROVED,
        OpenSkos::DELETED_BY,
        OpenSkos::DATE_DELETED,
        DcTerms::MODIFIED,
        OpenSkos::MODIFIED_BY,
        DcTerms::DATE_SUBMITTED,
        Skos::PREF_LABEL,
        Skos::ALT_LABEL,
        Skos::HIDDEN_LABEL,
        SkosXl::PREF_LABEL,
        SkosXl::ALT_LABEL,
        SkosXl::HIDDEN_LABEL,
        Skos::EDITORIAL_NOTE,
        Skos::NOTE,
        Skos::HISTORY_NOTE,
        Skos::SCOPE_NOTE,
        Skos::CHANGE_NOTE,
        OpenSkos::TO_BE_CHECKED,
        DcTerms::DATE_ACCEPTED,
        OpenSkos::ACCEPTED_BY,
        Skos::DEFINITION,
        Skos::TOP_CONCEPT_OF,
        Skos::RELATED,
        Skos::RELATED_MATCH,
        Skos::BROADER,
        Skos::BROAD_MATCH,
        Skos::BROADER_MATCH,
        Skos::BROADER_TRANSITIVE,
        Skos::NARROWER,
        Skos::NARROW_MATCH,
        Skos::NARROWER_TRANSITIVE,
    ];

    /*
     * XL alternatives for acceptable fields
     */
    protected static $acceptable_fields_to_xl = [
        'prefLabel'   => 'prefLabelXl',
        'altLabel'    => 'altLabelXl',
        'hiddenLabel' => 'hiddenLabelXl',
    ];

    /*
     * Which fields are language sensitive. Subset of $acceptable_fields
     */
    public static $language_sensitive = [
        //Labels
        'label', 'prefLabel', 'altLabel', 'hiddenLabel',
        //Notes
        'note', 'definition', 'example', 'changeNote', 'historyNote', 'scopeNote', 'editorialNote',
    ];

    /*
     * Meta projection parameters
     */
    public static $meta_groups = [
        'all'     => [],
        'skosxl'  => [], //@todo. We're also doing this in levels for everything. Why project here too? What do we want to do with this?
        'default' => ['uri' => ['lang' => ''], 'prefLabel' => ['lang' => ''], 'definition' => ['lang' => '']],
    ];

    public function __construct(
        Iri $subject,
        ?VocabularyAwareResource $resource = null,
        ?AbstractRepository $repository = null
    ) {
        parent::__construct($subject, $resource, $repository);

        // Ensure we have a notation
        (function (): void {
            // Only generate if no notation is known yet
            if ($this->getValue(Skos::NOTATION)) {
                return;
            }

            // We need the tenant to generate an id
            if (!$this->getValue(OpenSkos::TENANT)) {
                return;
            }

            // Build our fetching query
            $stmt = Doctrine::getConnection()
                ->createQueryBuilder()
                ->select('*')
                ->from('max_numeric_notation')
                ->where('tenant_code = :tenant')
                ->setParameter(':tenant', $this->getValue(OpenSkos::TENANT))
                ->execute()
            ;

            // We might've receive an affected rows count
            if (is_int($stmt)) {
                return;
            }

            // Notation must be non-null
            $data = $stmt->fetch();
            if (!is_array($data)) {
                return;
            }

            // Increment & store
            $data['max_numeric_notation'] = intval($data['max_numeric_notation']) + 1;
            $this->setValue(new Iri(Skos::NOTATION), ''.$data['max_numeric_notation']);

            // Write incremented number back to db
            $stmt = Doctrine::getConnection()
                ->createQueryBuilder()
                ->update('max_numeric_notation')
                ->set('max_numeric_notation', ''.$data['max_numeric_notation'])
                ->where('tenant_code = :tenant')
                ->setParameter(':tenant', $data['tenant_code'])
                ->execute()
            ;
        })();

        // Generate ID if needed
        (function () use ($repository): void {
            if (is_null($repository)) {
                return;
            }

            // Only generate if the subject starts with '_:'
            // This is default in EasyRdf if no id was given
            $iri = $this->iri()->getUri();
            if ('_:' !== substr($iri, 0, 2)) {
                return;
            }

            // We need a set to generate the ID
            $set = $this->getValue(OpenSkos::SET);
            if (!($set instanceof Iri)) {
                return;
            }

            // Attempt direct fetch
            $notation = $this->getValue(Skos::NOTATION);
            if (is_null($notation)) {
                return;
            }

            // Update subject through the whole resource
            $iri = $set->getUri().'/'.$notation;
            $this->iri()->setUri($iri);
            foreach ($this->triples() as $triple) {
                $triple->setSubject($this->iri());
            }
        })();
    }

    /**
     * @Error(code="concept-related-concept-not-found",
     *        status=404,
     *        description="The referenced concept for the relation could not be found",
     *        fields={"iri", "relation"}
     * )
     * @Error(code="concept-related-concept-scheme-not-found",
     *        status=404,
     *        description="The referenced concept scheme for the relation could not be found",
     *        fields={"iri", "relation"}
     * )
     * @Error(code="concept-missing-concept-scheme",
     *        status=400,
     *        description="No concept scheme is registered for concept"
     * )
     * @Error(code="concept-semantic-relation-concept-scheme-mismatch",
     *        status=400,
     *        description="Semantic relations are not possible between concepts with mismatchiing schemes",
     *        fields={"ours", "referenced"}
     * )
     */
    public function errors(string $errorPrefix = null): array
    {
        // Generic rules first
        $errors = parent::errors($errorPrefix);
        if (is_null($this->repository)) {
            return $errors;
        }

        // Fetch fields to check
        $relationFields = RelationType::vocabularyFields();
        $conceptSchemes = $this->getProperty(Skos::IN_SCHEME) ?? [];
        if (!count($conceptSchemes)) {
            array_push($errors, [
                'code' => 'concept-missing-concept-scheme',
            ]);

            return $errors;
        }

        foreach ($relationFields as $relationField) {
            $relations = $this->getProperty($relationField) ?? [];
            if (!count($relations)) {
                continue;
            }

            switch ($relationField) {
                case OpenSkos::IN_COLLECTION:
                case OpenSkos::IN_SET:
                case Skos::TOP_CONCEPT_OF:
                    // TODO: CHECK IF ?? EXISTS
                    break;

                case Skos::IN_SCHEME:

                    // Fetch the concept scheme repository
                    $conceptSchemeRepository = ConceptSchemeRepository::instance();
                    if (is_null($conceptSchemeRepository)) {
                        continue;
                    }

                    // Check if all concept schemes actually exist
                    foreach ($relations as $relation) {
                        $relatedConceptScheme = $conceptSchemeRepository->findByIri($relation);

                        // The related concept must be found
                        if (is_null($relatedConceptScheme)) {
                            array_push($errors, [
                                'code'     => 'concept-related-concept-scheme-not-found',
                                'iri'      => $relation->getUri(),
                                'relation' => $relationField,
                            ]);
                            continue;
                        }
                    }

                    break;
                default:
                    $schemeMatch = 'match' !== substr(strtolower($relationField), -5);

                    foreach ($relations as $relation) {
                        $relatedConcept = $this->repository->findByIri($relation);

                        // The related concept must be found
                        if (is_null($relatedConcept)) {
                            array_push($errors, [
                                'code'     => 'concept-related-concept-not-found',
                                'iri'      => $relation->getUri(),
                                'relation' => $relationField,
                            ]);
                            continue;
                        }

                        // Concept Schemes may have to match
                        if ($schemeMatch) {
                            $found        = false;
                            $foundSchemes = $relatedConcept->getProperty(Skos::IN_SCHEME) ?? [];
                            foreach ($foundSchemes as $foundScheme) {
                                foreach ($conceptSchemes as $scheme) {
                                    if ($scheme->getUri() == $foundScheme->getUri()) {
                                        $found = true;
                                        break 2;
                                    }
                                }
                            }
                            if (!$found) {
                                array_push($errors, [
                                    'code'       => 'concept-semantic-relation-concept-scheme-mismatch',
                                    'ours'       => explode("\n", implode("\n", $conceptSchemes)),
                                    'referenced' => explode("\n", implode("\n", $foundSchemes)),
                                ]);
                            }
                        }
                    }
                    break;
            }
        }

        return $errors;
    }

    public function isOrphan(): bool
    {
        $relationTypes = RelationType::vocabularyFields();
        foreach ($relationTypes as $relationType) {
            $relation = $this->getProperty($relationType);
            if (!count($relation ?? [])) {
                continue;
            }

            // Ignored relations
            $decodedType = implode(':', Context::decodeUri($relationType) ?? []);
            if (in_array($decodedType, [
                'skos:inScheme',
                'skos:topConceptOf',
                'openskos:isReplacedBy',
                'openskos:inSet',
            ], true)) {
                continue;
            }

            // Found relation is not ignored
            // We're not an orphan
            return false;
        }

        // No known relations found
        // We're an orphan
        return true;
    }

    public static function getAcceptableFields(): array
    {
        return self::$acceptable_fields;
    }

    /**
     * @return string[]
     */
    public static function getMapping(): array
    {
        return static::$mapping;
    }

    public static function getXlPredicates(): array
    {
        return static::$xlPredicates;
    }

    public static function getNonXlPredicates(): array
    {
        return static::$nonXlPredicates;
    }

    public static function getLanguageSensitive(): array
    {
        return static::$language_sensitive;
    }

    public static function getMetaGroups(): array
    {
        return static::$meta_groups;
    }

    public static function getAcceptableFieldsToXl(): array
    {
        return static::$acceptable_fields_to_xl;
    }
}
