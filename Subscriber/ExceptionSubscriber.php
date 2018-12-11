<?php

namespace Wizards\RestBundle\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Wizards\RestBundle\Exception\MultiPartHttpException;
use WizardsRest\Exception\HttpException;

/**
 * Serializes a controller output to a configured format response.
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $response = new Response();
        $response->setContent(json_encode(['errors' => $this->getErrorBody($exception)]));

        if ($exception instanceof HttpExceptionInterface || $exception instanceof HttpException) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace(['content-type' => 'application/vnd.api+json']);
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $event->setResponse($response);
    }

    /**
     * Formats the error body.
     *
     * @param $exception
     *
     * @return array
     */
    private function getErrorBody($exception)
    {
        if ($exception instanceof MultiPartHttpException) {
            return array_map(
                function ($error) {
                    return ['detail' => $error];
                },
                $exception->getMessageList()
            );
        }

        return [['detail' => $exception->getMessage()]];
    }
}
