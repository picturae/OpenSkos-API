<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Rest\ListResponse;
use EasyRdf_Graph;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ListResponseNormalizer implements NormalizerInterface
{
    /**
     * EasyRdf is used an an intermediate format between the TripleStore and its serialised formats.
     */

    /**
     * @var ObjectNormalizer
     */
    private $normalizer;

    /**
     * ListResponseNormalizer constructor.
     *
     * @param ObjectNormalizer $normalizer
     */
    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($list_object, $format = null, array $context = [])
    {
        // A listing of artist
        //$uri = sprintf("%s%s", self::RKD_RECORDSEARCH, $searchQuery);

        $graph = new EasyRdf_Graph('http://openskos.org');

        $OSEntityCollection = $list_object->getDocs();

        foreach ($OSEntityCollection as $osEntity) {
            $subject = $osEntity->getSubject()->getUri();

            $entity = $graph->resource($subject, 'rdf:Description');

            $mapping = $osEntity->getMapping();
            $literals = $osEntity->getLiterals();
            foreach ($mapping as $key => $property) {
                if (isset($literals[$key])) {
                    $entity->addLiteral($property, $literals[$key]->getValue(), $literals[$key]->getLanguage());
                }
            }
        }

        return $graph;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof ListResponse;
    }
}
