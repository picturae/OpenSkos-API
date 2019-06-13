<?php

declare(strict_types=1);

namespace App\Set;

use App\Ontology\OpenSkos;
use App\Ontology\Dc;
use App\Ontology\DcTerms;
use App\Rdf\Iri;
use App\Rdf\Literal;
use App\Rdf\TripleSet;

final class Set extends TripleSet
{
    const allow_oai = 'allow_oai';
    const code = 'code';
    const conceptBaseUri = 'conceptBaseUri';
    const oai_baseURL = 'oai_baseURL';
    const tenant = 'tenant';
    const webpage = 'webpage';
    const description = 'description';
    const license = 'license';
    const publisher = 'publisher';
    const title = 'title';

    /**
     * @var string[]
     */
    private static $mapping = [
        self::tenant => OpenSkos::TENANT,
        self::code => OpenSkos::CODE,
        self::allow_oai => OpenSkos::ALLOW_OAI,
        self::conceptBaseUri => OpenSkos::CONCEPTBASEURI,
        self::oai_baseURL => OpenSkos::OAI_BASEURL,
        self::webpage => OpenSkos::WEBPAGE,
        self::description => DcTerms::DESCRIPTION,
        self::license => DcTerms::LICENSE,
        self::publisher => DcTerms::PUBLISHER,
        self::title => DC::TITLE,
    ];

    public function __construct(Iri $subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string[]
     */
    public static function getMapping(): array
    {
        return self::$mapping;
    }

    /**
     * @return string
     */
    public function getLevel2Predicate()
    {
        return self::tenant;
    }


    /**
     * @return Literal|null
     */
    public function getCode(): ?Literal
    {
        return $this->properties[self::code] ?? null;
    }

    /**
     * @return Literal|null
     */
    public function getWebsite(): ?Literal
    {
        return $this->properties[self::website] ?? null;
    }
}
