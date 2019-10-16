<?php

declare(strict_types=1);

namespace App\OpenSkos\User;

use App\Ontology\DcTerms;
use App\Ontology\Foaf;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\VCard;
use App\Rdf\AbstractRdfDocument;
use App\Annotation\Document;

/**
 * @Document\Table("user")
 * @Document\Type(Foaf::PERSON)
 * @Document\UUID(User::uuid)
 */
final class User extends AbstractRdfDocument
{
    const type = 'type';
    const name = 'name';
    const uuid = 'id';
    const email = 'email';
    const tenant = 'tenant';
    const role = 'role';
    const enableSkosXl = 'enableSkosXl';
    const userType = 'userType';

    const dateSubmitted = 'dateSubmitted';

    /**
     * @var string[]
     */
    protected static $mapping = [
        self::type => Rdf::TYPE,
        self::name => Openskos::NAME,
        self::uuid => OpenSkos::UUID,
        self::email => VCard::EMAIL,
        self::tenant => Openskos::TENANT,
        self::dateSubmitted => DcTerms::DATESUBMITTED,
        self::role => OpenSkos::ROLE,
        self::enableSkosXl => OpenSkos::ENABLESKOSXL,
        self::userType => OpenSkos::USERTYPE,
    ];

    /**
     * @var array
     */
    protected static $uniqueFields = [
        'email' => self::email,
        'tenant' => self::tenant,
    ];

    protected static $columnAlias = [
        'type' => self::userType,
    ];
}
