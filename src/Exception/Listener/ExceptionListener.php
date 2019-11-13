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

    /**
     * @var array
     */
    private $knownErrors = [];

    public function __construct(
        ParameterBagInterface $params
    ) {
        $this->knownErrors = json_decode(file_get_contents(__DIR__.'/../list.json'), true);
        /* $this->debug = !!$params->get('kernel.debug'); */
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        /* // No fancy formatting in debug mode */
        /* // TODO: this might still be needed */
        /* if ($this->debug) { */
        /*     return; */
        /* } */

        // Fetch the thrown exception
        $exception = $event->getException();

        // Generic HttpException by symfony
        if ($exception instanceof HttpException) {
            $response = new Response();
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]));
            $event->setResponse($response);

            return;
        }

        // Don't handle exceptions outside of ApiException
        if (!($exception instanceof ApiException)) {
            return;
        }

        /** @var ApiException $exception */
        $errorCode = $exception->errorCode;
        $errorConfig = $this->knownErrors[$errorCode] ?? [];
        $errorConfig['fields'] = $errorConfig['fields'] ?? [];
        $errorData = $exception->data;
        $statusCode = $errorConfig['status'] ?? $exception->getCode();

        // Status fallback
        if (!$statusCode) {
            $statusCode = 500;
        }

        // Basic JSON response
        $response = new Response();
        $response->setStatusCode(intval($statusCode));
        $response->headers->set('Content-Type', 'application/json');

        // Handle 401
        if (401 == $response->getStatusCode()) {
            $response->headers->set('WWW-Authenticate', 'Basic realm="'.($errorConfig['realm'] ?? 'OpenSkos').'"');
        }

        // Base response data
        $responseData = [
            'status' => $statusCode,
            'code' => $errorCode,
            'description' => $errorConfig['description'] ?? '',
        ];

        // Include configured fields
        foreach ($errorConfig['fields'] as $field) {
            if (isset($errorData[$field])) {
                $responseData[$field] = $errorData[$field];
            }
        }

        // Encode & respond
        $response->setContent(json_encode(array_filter($responseData)));
        $event->setResponse($response);

        return;
    }
}
