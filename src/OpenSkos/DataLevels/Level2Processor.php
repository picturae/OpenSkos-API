<?php

declare(strict_types=1);

namespace App\OpenSkos\DataLevels;

use App\OpenSkos\Concept\Concept;
use App\OpenSkos\Label\Label;
use App\OpenSkos\Label\LabelRepository;
use App\Rdf\Iri;
use App\Rdf\VocabularyAwareResource;

final class Level2Processor
{
    private function crossLinkEntities(array $entities, array $to_process): array
    {
        $crosslink = [];

        foreach ($entities as $position => $tripleList) {
            foreach ($tripleList->triples() as $triplesKey => $triple) {
                if ($triple instanceof Label) {
                    continue;
                }

                foreach ($to_process as $labelKey => $predicate) {
                    if ($triple->getPredicate()->getUri() == $predicate) {
                        //Cross-link the object id's, so we can subsitute later

                        $objectUri = $triple->getObject()->getUri();
                        $co_ordinate = sprintf('%d,%d,%s', $position, $triplesKey, $predicate);
                        if (!isset($crosslink[$objectUri])) {
                            $crosslink[$objectUri] = [];
                        }
                        $crosslink[$objectUri][] = $co_ordinate;
                    }
                }
            }
        }

        return $crosslink;
    }

    public function AddLevel2Data(LabelRepository $repository, array &$entities): void
    {
        /*
         * This function accepts an array of OpenSkos entities, and injects level 2 data onto them
         * See: https://github.com/picturae/API/blob/develop/doc/OpenSKOS-API.md#concepts
         *
         * For now, most levels are on hold, so we have just focused on adding XL labels to Concepts
         *
         * Jena performance is too slow to walk through the list of entities, call up each data level one-by-one,
         *   and then add it to that entity. Instead we get all of the entities in a single query, fetch them, and
         *   add them in after that.
         *
         * We break this up into the following steps
         *
         * 1) Find all the URI's of entities we have to recall. Record which position(s) in the entity array it has to
         *      placed in.
         * 2) Fetch all entities from step 1 in a single query
         * 3) Walk through the query results, and use the data from step 1 to place them into the entity store.
         */

        /*
         * 1) Find all the URI's of entities we have to recall. Record which position(s) in the entity array it has to
         *      placed in.
        */

        $to_enrich = Concept::getXlPredicates(); //Like I said, just XL for now

        $crossLinks = $this->crossLinkEntities($entities, $to_enrich);

        $iris = array_keys($crossLinks);
        $enrichmentTriples = $repository->findManyByIriList($iris);

        $this->populateData($entities, $crossLinks, $enrichmentTriples);
    }

    /**
     * @param array $entities
     * @param array $crossLinks
     * @param array $enrichmentTriples
     */
    private function populateData(array $entities, array $crossLinks, array $enrichmentTriples): void
    {
        foreach ($crossLinks as $object => $co_ordinates_groups) {
            foreach ($co_ordinates_groups as $co_ordinates) {
                list($outer, $inner, $predicate) = preg_split('/,/', $co_ordinates);

                /**
                 * @var Concept
                 */
                $currentRecord = &$entities[$outer];

                /**
                 * @var VocabularyAwareResource
                 */
                $currentRecordSubject = $currentRecord->getResource()->iri();

                $replacementRecord = $enrichmentTriples[$object];
                $replacementRecord->setType(new Iri($predicate));
                $replacementRecord->setSubject($currentRecordSubject);

                $currentRecordResources = $currentRecord->getResource();
                $currentRecordResources->replaceTriple($inner, $replacementRecord);

                //I think in 99% of usage cases, while we're just replacing labels, it's less work to index in this loop
                // than process it all and then loop $entities again. We dont cross-link many XL labels
                $currentRecordResources->reIndexTripleStore();
            }
        }
    }
}
