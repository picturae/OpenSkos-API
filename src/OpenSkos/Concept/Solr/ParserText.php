<?php

declare(strict_types=1);

namespace App\OpenSkos\Concept\Solr;

class ParserText
{
    /**
     * Holds the format in which the dates in the options must be.
     *
     * @var string
     */
    const OPTIONS_DATE_FORMAT = 'dd/MM/yyyy';

    /**
     * Holds the regular expression for splitting the search text for search by text.
     *
     * @var string
     */
    const SEARCH_TEXT_SPLIT_REGEX = '/[\s]+/';

    /**
     * Holds an array of the chars which if are contained in the text field it will be considered query.
     *
     * @var array
     */
    private $operatorsDeterminingTextAsQuery = ['AND', 'OR', 'NOT', '&&', '||'];

    /**
     * Holds the regular expression for characters that are part of the solr syntax and needs escaping.
     *
     * @var string
     */
    //!NOTE "\" must be first in the list.
    private $charsToEscape = ['\\', ' ', '+', '-', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', ':'];

    /**
     * Escapes chars that are part of solr syntax.
     *
     * @param string $text
     *
     * @return string
     */
    public function escapeSpecialChars($text)
    {
        foreach ($this->charsToEscape as $char) {
            $text = str_ireplace($char, '\\'.$char, $text);
        }

        return $text;
    }

    /**
     * Fix "@nl" to "_nl".
     *
     * @param string $searchText
     *
     * @return string
     */
    public function replaceLanguageTags($searchText)
    {
        return preg_replace('/([^@]*)@(\w{2}:)/', '$1_$2', $searchText);
    }

    /**
     * Checks the search text for is it a complex query or a simple search text.
     *
     * @param string $searchText
     *
     * @return bool
     */
    public function isSearchTextQuery($searchText)
    {
        $parts = preg_split(self::SEARCH_TEXT_SPLIT_REGEX, $searchText);

        foreach ($parts as $part) {
            if (in_array($part, $this->operatorsDeterminingTextAsQuery, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks the search text if it contains search based on fields.
     *
     * @param string $searchText
     *
     * @return bool
     */
    public function isFieldSearch($searchText)
    {
        return 1 === preg_match('/(^|\s)[^"]*[a-z_]+:"?[^"]*"?(\s|$)/i', $searchText);
    }

    /**
     * Checks if the search text is fully quoted - like "Koomen, Theo".
     *
     * @param string $searchText
     *
     * @return bool
     */
    public function isFullyQuoted($searchText)
    {
        return 1 === preg_match('/^".*"$/', $searchText);
    }

    /**
     * Is the search a wildcard search. Containing ? or *.
     *
     * @param string $searchText
     *
     * @return bool
     */
    public function isWildcardSearch($searchText)
    {
        return false !== stripos($searchText, '*') || false !== stripos($searchText, '?');
    }

    /**
     * Builds query for date period - like created_timestamp:[{startDate} TO {endDate}].
     *
     * @param string $field     the field to search by
     * @param string $startDate Use as start date (it is converted to timestamp)
     * @param string $endDate   Use as end date (it is converted to timestamp)
     *
     * @return string
     */
    public function buildDatePeriodQuery($field, $startDate, $endDate)
    {
        $isStartDateSpecified = !empty($startDate);
        $isEndDateSpecified = !empty($endDate);
        if (!$isStartDateSpecified && !$isEndDateSpecified) {
            return '';
        }
        if ($isStartDateSpecified) {
            $startDate = $this->dateToSolrDate($startDate);
        } else {
            $startDate = '*';
        }
        if ($isEndDateSpecified) {
            $endDate = $this->dateToSolrDate($endDate);
        } else {
            $endDate = '*';
        }

        return $field.':['.$startDate.' TO '.$endDate.']';
    }

    /**
     * Converts the given date into a solr date (ISO 8601).
     *
     * @return string The solr date
     */
    public function dateToSolrDate($date)
    {
        if ($date instanceof \DateTime) {
            $timestamp = $date->getTimestamp();
        } else {
            $timestamp = strtotime($date);
        }

        return gmdate('Y-m-d\TH:i:s.z\Z', $timestamp);
    }
}
