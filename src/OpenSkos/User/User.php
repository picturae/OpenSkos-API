<?php

declare(strict_types=1);

namespace App\OpenSkos\User;

use App\Annotation\Document;
use App\Ontology\DcTerms;
use App\Ontology\Foaf;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Rdf\AbstractRdfDocument;

/**
 * @Document\Table("user")
 * @Document\Type(Foaf::PERSON)
 * @Document\UUID(User::uri)
 */
final class User extends AbstractRdfDocument
{
    const type         = 'type';
    const name         = 'name';
    const email        = 'email';
    const tenant       = 'tenant';
    const role         = 'role';
    const enableSkosXl = 'enableSkosXl';
    const usertype     = 'usertype';
    const apikey       = 'apikey';
    const uri          = 'uri';
    const uuid         = 'uuid';

    const dateSubmitted = 'dateSubmitted';

    /**
     * @var string[]
     */
    protected static $mapping = [
        self::type          => Rdf::TYPE,
        self::name          => Foaf::NAME,
        self::email         => Foaf::MBOX,
        self::tenant        => Openskos::TENANT,
        self::dateSubmitted => DcTerms::DATE_SUBMITTED,
        self::role          => OpenSkos::ROLE,
        self::enableSkosXl  => OpenSkos::ENABLESKOSXL,
        self::usertype      => OpenSkos::USERTYPE,
        self::apikey        => OpenSkos::APIKEY,
        self::uuid          => OpenSkos::UUID,
    ];

    /**
     * @var array
     */
    protected static $uniqueFields = [
        'uri' => self::uri,
    ];

    protected static $columnAlias = [
        'type' => self::usertype,
    ];
}
