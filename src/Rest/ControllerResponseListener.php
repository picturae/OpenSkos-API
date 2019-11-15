<?php

declare(strict_types=1);

namespace App\Rest;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Serializer\SerializerInterface;

final class ControllerResponseListener
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * Catch controller responses if they not native Response type.
     */
    public function onKernelView(GetResponseForControllerResultEvent $event): void
    {
        $res = $event->getControllerResult();

        if ($res instanceof ScalarResponse) {
            $triples = $res->doc()->triples();
        } elseif ($res instanceof ListResponse) {
            $triples = (function () use ($res): \Generator {
                foreach ($res->getDocs() as $doc) {
                    foreach ($doc->triples() as $triple) {
                        yield $triple;
                    }
                }
            })();
        } elseif ($res instanceof DirectGraphResponse) {
            $triples = $res->getGraph();
        } else {
            return;
        }

        $content      = $this->serializer->serialize($triples, $res->format()->name());
        $httpResponse = new Response(
            $content,
            Response::HTTP_OK,
            [
                'Content-Type' => $res->format()->contentTypeString(),
            ]
        );

        $event->setResponse($httpResponse);
    }
}
