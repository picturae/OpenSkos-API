<?php

declare(strict_types=1);

namespace App\Exception\Listener;

use App\Exception\ApiException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ExceptionListener
{
    /**
     * @var bool
     */
    private $debug = false;

    public function __construct(
        ParameterBagInterface $params
    ) {
        /* $this->debug = !!$params->get('kernel.debug'); */
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        // No json response in debug mode
        if ($this->debug) {
            return;
        }

        // Fetch the thrown exception
        $exception = $event->getException();

        // Generic HttpException by symfony
        if ($exception instanceof HttpException) {
            $response = new Response();
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'code'    => $exception->getCode() ?: 500,
                'message' => $exception->getMessage(),
            ], JSON_PRETTY_PRINT));
            $event->setResponse($response);

            return;
        }

        // Don't handle exceptions outside of ApiException
        if (!($exception instanceof ApiException)) {
            return;
        }

        /** @var ApiException $exception */
        $errorCode             = $exception->errorCode;
        $errorConfig           = $exception->config;
        $errorConfig['fields'] = $errorConfig['fields'] ?? [];
        $errorData             = $exception->data;

        // Basic JSON response
        $response = new Response();
        $response->setStatusCode($exception->status);
        $response->headers->set('Content-Type', 'application/json');

        // Handle 401
        if (401 == $response->getStatusCode()) {
            $response->headers->set('WWW-Authenticate', 'Basic realm="'.($errorConfig['realm'] ?? 'OpenSkos').'"');
        }

        // Base response data
        $responseData = array_merge($exception->data, [
            'status'      => $exception->status,
            'code'        => $errorCode,
            'description' => $exception->description,
        ]);

        // Encode & respond
        $response->setContent(json_encode(array_filter($responseData), JSON_PRETTY_PRINT));
        $event->setResponse($response);

        return;
    }
}
