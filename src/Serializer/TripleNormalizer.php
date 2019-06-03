<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Ontology\OpenSkos;
use App\Ontology\Rdf;
use App\Rdf\Literal;
use App\Rdf\Iri;
use App\Rdf\Triple;
use EasyRdf_Graph;
use EasyRdf_Literal_Boolean;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class TripleNormalizer implements NormalizerInterface
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
     *
     * @param Triple $object
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return [$object->getSubject()->getUri(), $object->getPredicate()->getUri(), (string) $object->getObject()];

        // A listing of artist
        //$uri = sprintf("%s%s", self::RKD_RECORDSEARCH, $searchQuery);

//        $graph = new EasyRdf_Graph('http://openskos.org');
//        \EasyRdf_Namespace::set('openskos', OpenSkos::NAME_SPACE);
//
//        $OSEntityCollection = $listObject->getDocs();
//
//        foreach ($OSEntityCollection as $osEntity) {
//            $subject = $osEntity->getSubject()->getUri();
//
//            $entity = $graph->resource($subject, 'rdf:Description');
//
//            $mapping = $osEntity->getMapping();
//            $properties = $osEntity->getProperties();
//            foreach ($mapping as $key => $property) {
//                if (isset($properties[$key])) {
//                    if ($properties[$key] instanceof Literal) {
//                        if (null === $properties[$key]->getType()) {
//                            $entity->addLiteral(
//                                $property,
//                                $properties[$key]->getValue(),
//                                $properties[$key]->getLanguage(),
//                            );
//                        } elseif (\App\Rdf\Literal::TYPE_BOOL === $properties[$key]->getType()) {
//                            $res = new EasyRdf_Literal_Boolean($properties[$key]->getValue());
//                            $entity->add($property, $res);
//                        } else {
//                            $entity->addLiteral(
//                                $property,
//                                $properties[$key]->getValue(),
//                                $properties[$key]->getLanguage(),
//                            );
//                        }
//                    } elseif ($properties[$key] instanceof Iri) {
//                        $entity->addResource($property, $properties[$key]->getUri());
//                    }
//                }
//            }
//        }
//
//        return $graph;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof Triple;
    }
}
