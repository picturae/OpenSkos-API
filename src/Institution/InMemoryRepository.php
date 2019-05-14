<?php declare(strict_types=1);

namespace App\Institution;

use App\Rdf\Triple;

final class InMemoryRepository implements InstitutionRepository {

    /**
     * @return Institution[]
     */
    function all(): array
    {
        $subjA = "http://test.io/institutions/a";
        $instA = Institution::fromTriples([
            new Triple($subjA, "http://random.predicate/asdas", "Hello Wordl"),
            new Triple($subjA, "http://random.predicate/1231", "http://google.com"),
            new Triple($subjA, "http://random.predicate/4234", "ssui3werwd"),
        ]);

        $subjB = "http://test.io/institutions/b";
        $instB = Institution::fromTriples([
            new Triple($subjB, "http://random.predicate/asdas", "Hello"),
            new Triple($subjB, "http://random.predicate/1231", "http://yandex.ru"),
            new Triple($subjB, "http://random.predicate/4234", "wiqe76t12"),
        ]);


        return [$instA, $instB];
    }

}