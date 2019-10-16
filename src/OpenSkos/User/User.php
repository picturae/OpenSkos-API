<?php

declare(strict_types=1);

namespace App\OpenSkos\User;

use App\Ontology\DcTerms;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\VCard;
use App\Rdf\AbstractRdfDocument;
use App\Annotation\Document\Table;

/**
 * @Table("user")
 */
final class User extends AbstractRdfDocument
{
    const type = 'type';
    const name = 'name';
    const uuid = 'uuid';
    const email = 'email';
    const tenant = 'tenant';
    const code = 'code';
    const role = 'role';
    const enableSkosXl = 'enableSkosXl';

    const dateSubmitted = 'dateSubmitted';

    /**
     * @var string[]
     */
    protected static $ignoreFields = [
        self::type,
    ];

    /**
     * @var string[]
     */
    protected static $mapping = [
        self::type => Rdf::TYPE,
        self::name => Openskos::NAME,
        self::uuid => OpenSkos::UUID,
        self::email => VCard::EMAIL,
        self::tenant => Openskos::TENANT,
        self::code => Openskos::CODE,
        self::dateSubmitted => DcTerms::DATESUBMITTED,
        self::role => OpenSkos::ROLE,
        self::enableSkosXl => OpenSkos::ENABLESKOSXL,
    ];

    /**
     * @var array
     */
    protected static $uniqueFields = [
        'email' => self::email,
        'tenant' => self::code,
    ];
}
