<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Rest\ListResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ListResponseNormalizer implements NormalizerInterface
{
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
        //$objectNormalizer = new ObjectNormalizer();

        $data = $this->normalizer->normalize($list_object, $format, $context);

        /*
                // Here, add, edit, or delete some data:
                $data['href']['self'] = $this->router->generate('topic_show', [
                    'id' => $topic->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                return $data;
        */
        return $data;

        /*
        return json_encode($data);

        return var_export($list_object);
        */
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof ListResponse;
    }
}
