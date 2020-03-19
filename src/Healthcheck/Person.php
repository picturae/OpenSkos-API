<?php

declare(strict_types=1);

namespace App\Healthcheck;

use App\Annotation\Document;
use App\Ontology\Foaf;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Rdf\AbstractRdfDocument;

/**
 * @Document\Type(Foaf::PERSON)
 * @Document\UUID(Person::uri)
 */
final class Person extends AbstractRdfDocument
{
    const type = 'type';
    const name = 'name';
    const uri  = 'uri';
    const uuid = 'uuid';

    /**
     * @var string[]
     */
    protected static $mapping = [
        self::name => Foaf::NAME,
        self::type => Rdf::TYPE,
        self::uuid => OpenSkos::UUID,
    ];
}
