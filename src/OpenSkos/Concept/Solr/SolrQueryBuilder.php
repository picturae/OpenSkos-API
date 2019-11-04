<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept\Solr;

use App\OpenSkos\Concept\Concept;
use Solarium\Core\Query\Helper as QueryHelper;

final class SolrQueryBuilder
{
    //Taken from the Openskos Editor
    const BOOST_PREFLABEL = 40;
    const BOOST_ALTLABEL = 20;
    const BOOST_HIDDENLABEL = 10;

    /**
     * @param $selection
     */
    public function processSearchExpression(string $searchText, $selection): string
    {
        /*
         * Adapted from the openskos 1 function Autocomplete->search();
         */

        $helper = new QueryHelper();
        $parser = new ParserText();

        // Empty query and query for all is replaced with *
        $searchText = trim($searchText);
        if (empty($searchText) || '*:*' == $searchText) {
            $searchText = '*';
        }

        // In all other cases - start parsing the query
        if ('*' != $searchText) {
            $searchText = $parser->replaceLanguageTags($searchText);

            if ($parser->isSearchTextQuery($searchText) || $parser->isFieldSearch($searchText)) {
                // Custom user query, he has to escape and do everything.
                $searchText = '('.$searchText.')';
            } else {
                if ($parser->isFullyQuoted($searchText)) {
                    $searchText = $searchText;
                } elseif ($parser->isWildcardSearch($searchText)) {
                    // do not escape wildcard search with the new tokenizer
                    // $searchText = $helper->escapePhrase($searchText);
                } else {
                    $searchText = $helper->escapePhrase($searchText);
                }
            }
        }

        $searchTextPlain = preg_replace('#\ #', '\\ ', $searchText);
        $prefix = 'a_';

        /*
         * This is currently not specified in the open-api docs, but I've added it as an undocumented feature copied from old openskos
         */
        //Meertens: the feature wholeworld  works only  when labels and/or properties are given as request parameters
        if (isset($selection['wholeword'])) {
            if ($selection['wholeword']) {
                $prefix = 't_';
            }
        }

        // @TODO Better to use edismax qf
        $searchTextQueries = [];
        // labels
        if (0 !== count($selection)) {
            foreach ($selection['labels'] as $label) {
                // boost important labels
                $boost = '';
                $searchText = $searchTextPlain;
                if ('prefLabel' === $label['type']) {
                    $boost = '^'.self::BOOST_PREFLABEL;
                }
                if ('altLabel' === $label['type']) {
                    $boost = '^'.self::BOOST_ALTLABEL;
                }
                if ($label['type'] === 'hiddenLabel'.self::BOOST_HIDDENLABEL) {
                    $boost = '^';
                }

                if (!empty($label['lang'])) {
                    $searchTextQueries[] = $prefix.$label['type'].'_'.$label['lang'].':'.$searchText.$boost;
                } else {
                    $searchTextQueries[] = $prefix.$label['type'].':'.$searchText.$boost;
                }
            }
            $searchText = $searchTextPlain;
        }

        /*
         * The following are in the Zend version of OpenSkos, but not requested in the specs
         *
         * - notes
         * - searchUri
         * - status (implemented in filters)
         * - tenants (now 'institutions', and implemented in filters)
         * - collections (now 'sets', and implemented in filters)
         * - skosCollection (specified in filters, but not possible with current OpenSkos solr. Predicate also not specified)
         * - toBeChecked
         * - topConcepts
         * - orphanedConcepts
         */

        //Properties are currently not mentioned in the specs
        // notes

        // search notation
        if (!empty($selection['notation'])) {
            $searchTextQueries[] = 's_notation:'.$searchText;
        }

        if (empty($searchTextQueries)) {
            $solrQuery = $searchText;
        } else {
            $solrQuery = '('.implode(' OR ', $searchTextQueries).')';
        }

        // @TODO Use filter queries
        $optionsQueries = [];

        /* //status */
        /* if (false === strpos($searchText, 'status')) { // We dont add status query if it is in the query already. */
        /*     if (!empty($options['status'])) { */
        /*         $optionsQueries[] = '(' */
        /*             .'s_status:(' */
        /*             .implode(' OR ', array_map([$helper, 'escapePhrase'], $options['status'])) */
        /*             .'))'; */
        /*     } else { */
        /*         $optionsQueries[] = '-s_status:'.Concept::STATUS_DELETED; */
        /*     } */
        /* } */

        /* // combine */
        /* if (!empty($optionsQueries)) { */
        /*     $optionsQuery = implode(' AND ', $optionsQueries); */
        /*     if (empty($solrQuery)) { */
        /*         $solrQuery = $optionsQuery; */
        /*     } else { */
        /*         // a possible bug in solr version */
        /*         if ('-s_status:deleted' != trim($optionsQuery)) { */
        /*             $solrQuery .= ' AND ('.$optionsQuery.')'; */
        /*         } else { */
        /*             $solrQuery .= ' AND -s_status:deleted'; */
        /*         } */
        /*     } */
        /* } */

        /* //@todo: Sort parameters */
        /* if (!empty($options['sorts'])) { */
        /*     $sorts = $options['sorts']; */
        /* } else { */
        /*     $sorts = null; */
        /* } */

        return $solrQuery;
    }
}
