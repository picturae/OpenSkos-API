<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Ontology\OpenSkos;
use App\Rdf\Literal;
use App\Rdf\Iri;
use App\Rest\ListResponse;
use EasyRdf_Graph;
use EasyRdf_Literal_Boolean;
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
    public function normalize($object, $format = null, array $context = [])
    {
        return $object->getDocs();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof ListResponse;
    }
}
