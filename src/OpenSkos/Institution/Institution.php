<?php

declare(strict_types=1);

namespace App\OpenSkos\Institution;

use App\OpenSkos\Institution\Exception\UnknownProperty;
use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Ontology\VCard;
use App\Rdf\Iri;
use App\Rdf\Literal\Literal;
use App\Rdf\RdfResource;
use App\Rdf\Triple;

final class Institution implements RdfResource
{
    const code = 'code';
    const name = 'name';
    const organisationUnit = 'organisationUnit';
    const email = 'email';
    const website = 'website';
    const streetAddress = 'streetAddress';
    const locality = 'locality';
    const postalCode = 'postalCode';
    const countryName = 'countryName';
    const enableStatusesSystem = 'enableStatusesSystem';
    const enableSkosXl = 'enableSkosXl';
    const type = 'type';
    const disableSearchInOtherTenants = 'disableSearchInOtherTenants';

    /**
     * @var string[]
     */
    private static $mapping = [
        self::code => OpenSkos::CODE,
        self::name => OpenSkos::NAME,
        self::disableSearchInOtherTenants => OpenSkos::DISABLESEARCHINOTERTENANTS,
        self::organisationUnit => VCard::ORGUNIT,
        self::email => VCard::EMAIL,
        self::website => OpenSkos::WEBPAGE,
        self::streetAddress => VCard::ADR,
        self::locality => VCard::LOCALITY,
        self::postalCode => VCard::PCODE,
        self::countryName => VCard::COUNTRY,
        self::enableStatusesSystem => OpenSkos::ENABLESTATUSSESSYSTEMS,
        self::enableSkosXl => OpenSkos::ENABLESKOSXL,
        self::type => Rdf::TYPE,
    ];

    /**
     * @var Iri
     */
    private $subject;

    /**
     * @var array<string,?Literal>
     */
    private $literals;

    /**
     * @var Triple[]
     */
    private $triples = [];

    public function __construct(Iri $subject)
    {
        $this->subject = $subject;

        $this->literals = array_fill_keys(
            array_values(self::$mapping),
            null
        );
    }

    public function addLiteral(Iri $property, Literal $literal): void
    {
        $iri = $property->getUri();
        if (!array_key_exists($iri, $this->literals)) {
            throw new UnknownProperty($property);
        }

        $this->literals[$iri] = $literal;

        $this->triples[] = new Triple(
            $this->subject,
            $property,
            $literal
        );
    }

    public function iri(): Iri
    {
        return $this->subject;
    }

    public function triples(): array
    {
        return $this->triples;
    }

    /**
     * @param Iri      $iri
     * @param Triple[] $triples
     *
     * @return Institution
     */
    public static function fromTriples(Iri $iri, array $triples)
    {
        $iriString = $iri->getUri();
        $obj = new self($iri);
        foreach ($triples as $triple) {
            if ($triple->getSubject()->getUri() !== $iriString) {
                // TODO: Should we skip, log or throw an exception?
                continue;
            }

            $predicateString = $triple->getPredicate()->getUri();
            if (false === array_key_exists($predicateString, $obj->literals)) {
                // TODO: Should we skip, log or throw an exception?
                continue;
            }

            $obj->triples[] = $triple;
            $object = $triple->getObject();
            if ($object instanceof Literal) {
                $obj->literals[$predicateString] = $object;
            }
            //TODO: Add Resources
        }

        return $obj;
    }
}
