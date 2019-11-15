<?php

declare(strict_types=1);

/**
 * OpenSKOS.
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 *
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

namespace OpenSkos2\Solr;

/*
use OpenSkos2\Rdf\Uri;
use Solarium\Client;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\ResourceCollection;
use Solarium\QueryType\Update\Query\Query as UpdateQuery;
*/

use App\OpenSkos\Exception\InvalidApiRequest;
use Solarium\Core\Client\Client;

class SolrResourceRepository
{
    /**
     * @var Client
     */
    protected $solr;

    /**
     * Use that if inserting a large amount of resources.
     * Call commit at the end.
     *
     * @var bool
     */

    //TODO: Implement for full CRUD
    //protected $isNoCommitMode = false;

    /**
     * Use that if inserting a large amount of resources.
     * Call commit at the end.
     *
     * @return bool
     */
    //TODO: Implement for full CRUD
    //public function getIsNoCommitMode()
    //{
    //   return $this->isNoCommitMode;
    //}

    /**
     * Use that if inserting a large amount of resources.
     * Call commit at the end.
     *
     * @param bool
     */
    //TODO: Implement for full CRUD
    //public function setIsNoCommitMode($isNoCommitMode)
    //{
    //$this->isNoCommitMode = $isNoCommitMode;
    //}

    public function __construct(Client $solr)
    {
        $this->solr = $solr;
    }

    /**
     * @param \OpenSkos2\Rdf\Resource $resource
     *
     * @throws \Exception
     */
    //TODO: Implement for full CRUD
    /*
    public function insert(Resource $resource)
    {
        $update = $this->solr->createUpdate();

        $this->addResourceToUpdate($resource, $update);

        if (!$this->getIsNoCommitMode()) {
            $update->addCommit(true);
        }

        $this->updateWithRetries($update);
    }
    */

    /**
     * @param \OpenSkos2\Rdf\ResourceCollection $resourceCollection
     *
     * @throws ResourceAlreadyExistsException
     */
    //TODO: Implement for full CRUD
    /*
    public function insertCollection(ResourceCollection $resourceCollection)
    {
        $update = $this->solr->createUpdate();

        foreach ($resourceCollection as $resource) {
            $this->addResourceToUpdate($resource, $update);
        }

        if (!$this->getIsNoCommitMode()) {
            $update->addCommit(true);
        }

        $this->updateWithRetries($update);
    }
    */

    /**
     * Adds the rdf resource to update.
     *
     * @param \Solarium\QueryType\Update\Query\Query $update
     */
    //TODO: Implement for full CRUD
    /*
    protected function addResourceToUpdate(Resource $resource, UpdateQuery $update)
    {
        $doc = $update->createDocument();
        $convert = new \OpenSkos2\Solr\Document($resource, $doc);
        $resourceDoc = $convert->getDocument();

        $update->addDocument($resourceDoc);
    }
    */

    /**
     * Sometimes solr update fails with timeout. So we update with retrying...
     *
     * @param \Solarium\QueryType\Update\Query\Query $update
     *
     * @throws \Exception
     */
    //TODO: Implement for full CRUD
    /*
    protected function updateWithRetries(UpdateQuery $update)
    {
        $result = $this->solr->update($update);
        return;
        // Sometimes solr update fails with timeout.
        $exception = null;
        $tries = 0;
        $maxTries = 3;
        do {
            try {
                $exception = null;
                $result = $this->solr->update($update);
            } catch (\Solarium\Exception\HttpException $exception) {
                $tries ++;
            }
        } while ($exception !== null && $tries < $maxTries);

        if ($exception !== null) {
            throw new \Exception($exception->getBody());
        }
    }
    */

    /**
     * @param resource $uri
     */
    //TODO: Implement for full CRUD
    /*
    public function delete(Uri $uri)
    {
        // delete resource in solr
        $update = $this->solr->createUpdate();
        $update->addDeleteById($uri->getUri());

        if (!$this->getIsNoCommitMode()) {
            $update->addCommit(true);
        }

        $this->solr->update($update);
    }
    */

    /**
     * @function doesMatchingPrefLabelExist
     *
     * @param $value Value of PrefLabel
     *
     * @return bool true if matching label found, else false
     *
     * Notes: The field prefLabel is searched; this will exist even if using XL
     */
    public function doesMatchingPrefLabelExist($value)
    {
        $solrSearchTerm = sprintf('prefLabel:"%s"', $value);

        // Solarium brakes stat results when we have long int, so we use ordering.
        /** @var \Solarium\QueryType\Select\Query\Query */
        $select = $this->solr->createSelect();

        $select->setStart(0)
            ->setRows(1)
            ->setFields(['prefLabel'])
            ->setQuery($solrSearchTerm);

        /** @var \Solarium\QueryType\Select\Result\Result */
        $solrResult = $this->solr->select($select);

        $has_match = ($solrResult->count() > 0);

        return $has_match;
    }

    /**
     * Perform a full text query
     * lucene / solr queries are possible
     * for the available fields see schema.xml.
     *
     * @param string $query
     * @param int    $rows
     * @param int    $start
     * @param int    &$numFound output Total number of found records
     * @param array  $sorts
     *
     * @return array Array of uris
     *
     * @throws InvalidApiRequest
     */
    public function search(
        $query,
        $rows = 20,
        $start = 0,
        &$numFound = 0,
        $sorts = null,
        $filterQueries = null,
        $full_retrieve = false
    ) {
        /** @var \Solarium\QueryType\Select\Query\Query */
        $select = $this->solr->createSelect();
        $select->setStart($start)
            ->setRows($rows)
            ->setFields(['uri', 'prefLabel', 'inScheme', 'scopeNote', 'status'])
            ->setQuery($query);
        if (!empty($sorts)) {
            $select->setSorts($sorts);
        }

        if (!empty($filterQueries)) {
            if (!is_array($filterQueries)) {
                throw new InvalidApiRequest('Filter queries must be array.');
            }

            foreach ($filterQueries as $key => $value) {
                $select->addFilterQuery($select->createFilterQuery($key)->setQuery($value));
            }
        }

        /** @var \Solarium\QueryType\Select\Result\Result */
        $solrResult = $this->solr->select($select);
        $numFound   = intval($solrResult->getNumFound());

        $return_data = [];

        if ($full_retrieve) {
            //Return an array of URI's
            $return_data = $solrResult->getDocuments();
        } else {
            //Return an array of URI's
            foreach ($solrResult as $doc) {
                $return_data[] = $doc->uri;
            }
        }

        return $return_data;
    }

    /**
     * Get the max value of a single value field.
     *
     * @param string $field Get the max value of a single value field
     *
     * @return string|int
     */
    public function getMaxFieldValue($query, $field)
    {
        // Solarium brakes stat results when we have long int, so we use ordering.
        /** @var \Solarium\QueryType\Select\Query\Query */
        $select = $this->solr->createSelect();

        $select
            ->setQuery($query)
            ->setRows(1)
            ->addSort($field, 'desc')
            ->addField($field);

        /** @var \Solarium\QueryType\Select\Result\Result */
        $solrResult = $this->solr->select($select);
        if (count($solrResult->getIterator()) > 0) {
            return $solrResult->getIterator()->current()->{$field};
        } else {
            return 0;
        }
    }

    /*
     * Send a commit request to solr
     * @return \Solarium\QueryType\Update\Result
     */

    //TODO: Implement for full CRUD
    /*
    public function commit()
    {
        $update = $this->solr->createUpdate();
        //$update->addCommit();
        return $this->solr->update($update);
    }
    */

    /*
     * Send a commit request to solr
     * @return \Solarium\QueryType\Update\Result
     */
    //TODO: Implement for full CRUD
    /*
    public function optimize()
    {
        $update = $this->solr->createUpdate();
        $update->addOptimize();
        return $this->solr->update($update);
    }
    */
}
