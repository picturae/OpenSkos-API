<?php

/**
 * OpenSKOS.
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 *
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

namespace App\Ontology;

use App\Annotation\Error;
use App\Exception\ApiException;
use App\Rdf\Iri;
use App\Rdf\Literal\Literal;

final class OpenSkos
{
    const NAME_SPACE = 'http://openskos.org/xmlns#';
    const DATATYPE = 'http://openskos.org/xmlns#datatype';
    const TENANT = 'http://openskos.org/xmlns#tenant';
    const STATUS = 'http://openskos.org/xmlns#status';
    const TO_BE_CHECKED = 'http://openskos.org/xmlns#toBeChecked';
    const DATE_DELETED = 'http://openskos.org/xmlns#dateDeleted';
    const DELETED_BY = 'http://openskos.org/xmlns#deletedBy';
    const ACCEPTED_BY = 'http://openskos.org/xmlns#acceptedBy';
    const MODIFIED_BY = 'http://openskos.org/xmlns#modifiedBy';
    const UUID = 'http://openskos.org/xmlns#uuid';
    const SET = 'http://openskos.org/xmlns#set';
    const ROLE = 'http://openskos.org/xmlns#role';
    const IN_SKOS_COLLECTION = 'http://openskos.org/xmlns#inSkosCollection';
    const CODE = 'http://openskos.org/xmlns#code';
    const NAME = 'http://openskos.org/xmlns#name';
    const DISABLE_SEARCH_IN_OTHER_TENANTS = 'http://openskos.org/xmlns#disableSearchInOtherTenants';
    const ENABLE_STATUSSES_SYSTEM = 'http://openskos.org/xmlns#enableStatussesSystem';
    const ALLOW_OAI = 'http://openskos.org/xmlns#allow_oai';
    const OAI_BASE_URL = 'http://openskos.org/xmlns#oai_baseURL';
    const CONCEPT_BASE_URI = 'http://openskos.org/xmlns#conceptBaseUri';
    const LICENCE_URL = 'http://openskos.org/xmlns#licenceURL';
    const WEBPAGE = 'http://openskos.org/xmlns#webpage';
    const ENABLESKOSXL = 'http://openskos.org/xmlns#enableskosxl';
    const NOTATIONUNIQUEPERTENANT = 'http://openskos.org/xmlns#notationuniquepertenant';
    const NOTATIONAUTOGENERATED = 'http://openskos.org/xmlns#notationautogenerated';
    const USERTYPE = 'http://openskos.org/xmlns#usertype';
    const APIKEY = 'http://openskos.org/xmlns#apikey';
    const IS_REPLACED_BY = 'http://openskos.org/xmlns#isReplacedBy';
    const REPLACES = 'http://openskos.org/xmlns#replaces';
    const IN_COLLECTION = 'http://openskos.org/xmlns#inCollection';
    const IN_SET = 'http://openskos.org/xmlns#inSet';
    const ERROR = 'http://openskos.org/xmlns#error';

    const STATUS_CANDIDATE = 'candidate';
    const STATUS_APPROVED = 'approved';
    const STATUS_REDIRECTED = 'redirected';
    const STATUS_NOT_COMPLIANT = 'not_compliant';
    const STATUS_REJECTED = 'rejected';
    const STATUS_OBSOLETE = 'obsolete';
    const STATUS_DELETED = 'deleted';

    const STATUSES = [
        self::STATUS_CANDIDATE,
        self::STATUS_APPROVED,
        self::STATUS_REDIRECTED,
        self::STATUS_NOT_COMPLIANT,
        self::STATUS_REJECTED,
        self::STATUS_OBSOLETE,
        self::STATUS_DELETED,
    ];

    /**
     * Returns the first encountered error for tenant.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="openskos-tenant-validate-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the tenant predicate has a different type than 'http://www.w3.org/2001/XMLSchema#string'"
     *     )
     */
    public function validateTenant($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#string' !== $property->typeIri()->getUri()) {
                throw new ApiException('openskos-tenant-validate-literal-type', [
                    'expected' => 'http://www.w3.org/2001/XMLSchema#string',
                    'actual' => $property->typeIri()->getUri(),
                ]);
            }
        }
        if (is_null($value)) {
            return null;
        }

        return null;
    }

    /**
     * Returns the first encountered error for uuid.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="openskos-uuid-validate-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the uuid predicate has a different type than 'http://www.w3.org/2001/XMLSchema#string'"
     *     )
     * @Error(code="openskos-uuid-validate-regex",
     *        status=422,
     *        fields={"regex","value"},
     *        description="The object for the uuid predicate did not match the configured regex"
     *     )
     */
    public function validateUuid($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#string' !== $property->typeIri()->getUri()) {
                throw new ApiException('openskos-uuid-validate-literal-type', [
                    'expected' => 'http://www.w3.org/2001/XMLSchema#string',
                    'actual' => $property->typeIri()->getUri(),
                ]);
            }
        }
        if (is_null($value)) {
            return null;
        }

        $regex = '/[0-9a-f]{8}\\-[0-9a-f]{4}\\-[0-9a-f]{4}\\-[0-9a-f]{4}\\-[0-9a-f]{12}/i';
        if (!preg_match($regex, $value)) {
            throw new ApiException('openskos-uuid-validate-regex', [
                'regex' => $regex,
                'value' => $value,
            ]);
        }

        return null;
    }

    /**
     * Returns the first encountered error for disableSearchInOtherTenants.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
     * @Error(code="openskos-disablesearchinothertenants-validate-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the disablesearchinothertenants predicate has a different type than 'http://www.w3.org/2001/XMLSchema#boolean'"
     *     )
     */
    public function validateDisableSearchInOtherTenants($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();

            if ('http://www.w3.org/2001/XMLSchema#boolean' !== $property->typeIri()->getUri()) {
                throw new ApiException('openskos-disablesearchinothertenants-validate-literal-type', [
                    'expected' => 'http://www.w3.org/2001/XMLSchema#boolean',
                    'actual' => $property->typeIri()->getUri(),
                ]);
            }
        }
        if (is_null($value)) {
            return null;
        }

        return null;
    }

    public static function vocabulary(): \EasyRdf_Graph
    {
        \EasyRdf_Namespace::set('dc', Dc::NAME_SPACE);
        \EasyRdf_Namespace::set('dcmi', Dcmi::NAME_SPACE);
        \EasyRdf_Namespace::set('dcterms', DcTerms::NAME_SPACE);
        \EasyRdf_Namespace::set('foaf', Foaf::NAME_SPACE);
        \EasyRdf_Namespace::set('http', Http::NAME_SPACE);
        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);
        \EasyRdf_Namespace::set('org', Org::NAME_SPACE);
        \EasyRdf_Namespace::set('owl', Owl::NAME_SPACE);
        \EasyRdf_Namespace::set('rdf', Rdf::NAME_SPACE);
        \EasyRdf_Namespace::set('rdfs', Rdfs::NAME_SPACE);
        \EasyRdf_Namespace::set('skos', Skos::NAME_SPACE);
        \EasyRdf_Namespace::set('skosxl', SkosXl::NAME_SPACE);
        \EasyRdf_Namespace::set('vcard', VCard::NAME_SPACE);
        \EasyRdf_Namespace::set('xsd', Xsd::NAME_SPACE);

        // Define graph structure
        $graph = new \EasyRdf_Graph('openskos.org');

        // Intro
        $openskos = $graph->resource('http://openskos.org/xmlns#');
        $openskos->setType('owl:Ontology');
        $openskos->addLiteral('dc:title', 'OpenSkos vocabulary');

        $datatype = $graph->resource('openskos:datatype');
        $datatype->setType('rdf:Property');
        $datatype->addLiteral('openskos:datatype', 'literal');
        $datatype->addResource('rdf:type', 'owl:ObjectProperty');
        $datatype->addLiteral('rdfs:comment', 'The type of data that\'s supposed to go in the described field');

        $tenant = $graph->resource('openskos:tenant');
        $tenant->setType('rdf:Property');
        $tenant->addLiteral('openskos:datatype', 'literal');
        $tenant->addResource('rdf:type', 'owl:ObjectProperty');
        $tenant->addLiteral('rdfs:comment', 'A reference to an org:FormalOrganization by it\'s openskos:code field');

        $status = $graph->resource('openskos:status');
        $status->setType('rdf:Property');
        $status->addLiteral('openskos:datatype', 'literal');
        $status->addResource('rdf:type', 'owl:ObjectProperty');

        $toBeChecked = $graph->resource('openskos:toBeChecked');
        $toBeChecked->setType('rdf:Property');
        $toBeChecked->addLiteral('openskos:datatype', 'literal');
        $toBeChecked->addResource('rdf:type', 'owl:ObjectProperty');

        $dateDeleted = $graph->resource('openskos:dateDeleted');
        $dateDeleted->setType('rdf:Property');
        $dateDeleted->addLiteral('openskos:datatype', 'literal');
        $dateDeleted->addResource('rdf:type', 'owl:ObjectProperty');

        $deletedBy = $graph->resource('openskos:deletedBy');
        $deletedBy->setType('rdf:Property');
        $deletedBy->addLiteral('openskos:datatype', 'resource');
        $deletedBy->addResource('rdf:type', 'owl:ObjectProperty');

        $acceptedBy = $graph->resource('openskos:acceptedBy');
        $acceptedBy->setType('rdf:Property');
        $acceptedBy->addLiteral('openskos:datatype', 'resource');
        $acceptedBy->addResource('rdf:type', 'owl:ObjectProperty');

        $modifiedBy = $graph->resource('openskos:modifiedBy');
        $modifiedBy->setType('rdf:Property');
        $modifiedBy->addLiteral('openskos:datatype', 'resource');
        $modifiedBy->addResource('rdf:type', 'owl:ObjectProperty');

        $uuid = $graph->resource('openskos:uuid');
        $uuid->setType('rdf:Property');
        $uuid->addLiteral('openskos:datatype', 'literal');
        $uuid->addResource('rdf:type', 'owl:ObjectProperty');

        $set = $graph->resource('openskos:set');
        $set->setType('rdf:Property');
        $set->addLiteral('openskos:datatype', 'resource');
        $set->addResource('rdf:type', 'owl:ObjectProperty');

        $role = $graph->resource('openskos:role');
        $role->setType('rdf:Property');
        $role->addLiteral('openskos:datatype', 'literal');
        $role->addResource('rdf:type', 'owl:ObjectProperty');

        $inSkosCollection = $graph->resource('openskos:inSkosCollection');
        $inSkosCollection->setType('rdf:Property');
        $inSkosCollection->addLiteral('openskos:datatype', 'literal');
        $inSkosCollection->addResource('rdf:type', 'owl:ObjectProperty');

        $code = $graph->resource('openskos:code');
        $code->setType('rdf:Property');
        $code->addLiteral('openskos:datatype', 'literal');
        $code->addResource('rdf:type', 'owl:ObjectProperty');
        $code->addLiteral('rdfs:comment', 'Short unique identifier for an org:FormalOrganization');

        $name = $graph->resource('openskos:name');
        $name->setType('rdf:Property');
        $name->addLiteral('openskos:datatype', 'literal');
        $name->addResource('rdf:type', 'owl:ObjectProperty');

        $disableSearchInOtherTenants = $graph->resource('openskos:disableSearchInOtherTenants');
        $disableSearchInOtherTenants->setType('rdf:Property');
        $disableSearchInOtherTenants->addLiteral('openskos:datatype', 'literal');
        $disableSearchInOtherTenants->addResource('rdf:type', 'owl:ObjectProperty');

        $enableStatussesSystem = $graph->resource('openskos:enableStatussesSystem');
        $enableStatussesSystem->setType('rdf:Property');
        $enableStatussesSystem->addLiteral('openskos:datatype', 'literal');
        $enableStatussesSystem->addResource('rdf:type', 'owl:ObjectProperty');

        $allow_oai = $graph->resource('openskos:allow_oai');
        $allow_oai->setType('rdf:Property');
        $allow_oai->addLiteral('openskos:datatype', 'literal');
        $allow_oai->addResource('rdf:type', 'owl:ObjectProperty');

        $oai_baseURL = $graph->resource('openskos:oai_baseURL');
        $oai_baseURL->setType('rdf:Property');
        $oai_baseURL->addLiteral('openskos:datatype', 'literal');
        $oai_baseURL->addResource('rdf:type', 'owl:ObjectProperty');

        $conceptBaseUri = $graph->resource('openskos:conceptBaseUri');
        $conceptBaseUri->setType('rdf:Property');
        $conceptBaseUri->addLiteral('openskos:datatype', 'literal');
        $conceptBaseUri->addResource('rdf:type', 'owl:ObjectProperty');

        $licenceURL = $graph->resource('openskos:licenceURL');
        $licenceURL->setType('rdf:Property');
        $licenceURL->addLiteral('openskos:datatype', 'literal');
        $licenceURL->addResource('rdf:type', 'owl:ObjectProperty');

        $webpage = $graph->resource('openskos:webpage');
        $webpage->setType('rdf:Property');
        $webpage->addLiteral('openskos:datatype', 'literal');
        $webpage->addResource('rdf:type', 'owl:ObjectProperty');

        $enableskosxl = $graph->resource('openskos:enableskosxl');
        $enableskosxl->setType('rdf:Property');
        $enableskosxl->addLiteral('openskos:datatype', 'literal');
        $enableskosxl->addResource('rdf:type', 'owl:ObjectProperty');

        $notationuniquepertenant = $graph->resource('openskos:notationuniquepertenant');
        $notationuniquepertenant->setType('rdf:Property');
        $notationuniquepertenant->addLiteral('openskos:datatype', 'literal');
        $notationuniquepertenant->addResource('rdf:type', 'owl:ObjectProperty');

        $notationautogenerated = $graph->resource('openskos:notationautogenerated');
        $notationautogenerated->setType('rdf:Property');
        $notationautogenerated->addLiteral('openskos:datatype', 'literal');
        $notationautogenerated->addResource('rdf:type', 'owl:ObjectProperty');

        $usertype = $graph->resource('openskos:usertype');
        $usertype->setType('rdf:Property');
        $usertype->addLiteral('openskos:datatype', 'literal');
        $usertype->addResource('rdf:type', 'owl:ObjectProperty');

        $apikey = $graph->resource('openskos:apikey');
        $apikey->setType('rdf:Property');
        $apikey->addLiteral('openskos:datatype', 'literal');
        $apikey->addResource('rdf:type', 'owl:ObjectProperty');

        $isReplacedBy = $graph->resource('openskos:isReplacedBy');
        $isReplacedBy->setType('rdf:Property');
        $isReplacedBy->addLiteral('openskos:datatype', 'resource');
        $isReplacedBy->addResource('rdf:type', 'owl:ObjectProperty');

        $replaces = $graph->resource('openskos:replaces');
        $replaces->setType('rdf:Property');
        $replaces->addLiteral('openskos:datatype', 'resource');
        $replaces->addResource('rdf:type', 'owl:ObjectProperty');

        $inCollection = $graph->resource('openskos:inCollection');
        $inCollection->setType('rdf:Property');
        $inCollection->addLiteral('openskos:datatype', 'resource');
        $inCollection->addResource('rdf:type', 'owl:ObjectProperty');

        $inSet = $graph->resource('openskos:inSet');
        $inSet->setType('rdf:Property');
        $inSet->addLiteral('openskos:datatype', 'resource');
        $inSet->addResource('rdf:type', 'owl:ObjectProperty');

        $error = $graph->resource('openskos:error');
        $error->setType('rdfs:Class');
        $error->addLiteral('openskos:datatype', 'class');

        return $graph;
    }
}
