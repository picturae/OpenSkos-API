<?php declare(strict_types=1);

namespace App\Institution;

use App\Rdf\Triple;

final class Institution {

    /**
     * @var Triple[]
     */
    private $triples = [];

    private function __construct() {}

    /**
     * @return string[]
     */
    public function properties() : array
    {
        $res = [];
        foreach ($this->triples as $triple) {
            $res[$triple->getPredicate()] = $triple->getObject();
        }

        return $res;
    }

    /**
     * @param Triple[] $triples
     */
    public static function fromTriples(array $triples) : self
    {
        $obj = new self();
        $obj->triples = $triples;

        return $obj;
    }
}