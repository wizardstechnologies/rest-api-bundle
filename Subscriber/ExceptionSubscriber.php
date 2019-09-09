<?php

namespace Wizards\RestBundle\Subscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Wizards\RestBundle\Exception\MultiPartHttpException;
use WizardsRest\Exception\HttpException;

/**
 * Serializes a controller output to a configured format response.
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(KernelInterface $kernel, LoggerInterface $logger)
    {
        $this->kernel = $kernel;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        $this->logger->log('error', $exception->getMessage());

        $response = new Response();
        $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);

        if ($this->kernel->isDebug()) {
            $response->setContent(\json_encode(['errors' => $this->getErrorBody($exception)]));
        }

        if ($exception instanceof HttpExceptionInterface || $exception instanceof HttpException) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace(['content-type' => 'application/vnd.api+json']);
        }

        $event->setResponse($response);
    }

    private function getErrorBody($exception): array
    {
        if ($exception instanceof MultiPartHttpException) {
            return \array_map(
                function ($error) {
                    return ['detail' => $error];
                },
                $exception->getMessageList()
            );
        }

        return [['detail' => $exception->getMessage()]];
    }
}
