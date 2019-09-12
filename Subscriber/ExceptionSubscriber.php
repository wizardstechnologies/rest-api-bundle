<?php

namespace Wizards\RestBundle\Subscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;
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

        if ($this->kernel->isDebug()
            || (
                ($exception instanceof HttpExceptionInterface || $exception instanceof HttpException)
                && $exception->getStatusCode() !== 500
            )
        ) {
            $content = $this->getErrorResponseContent($exception);

            if ($content !== null) {
                $response->setContent($content);
            }
        }

        if ($exception instanceof HttpExceptionInterface || $exception instanceof HttpException) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace(['content-type' => 'application/vnd.api+json']);
        }

        $event->setResponse($response);
    }

    private function getErrorResponseContent(Throwable $exception): ?string
    {
        $errorMessages = $this->getErrorMessages($exception);

        $errors = \array_map(
            function ($error) {
                return ['detail' => $error];
            },
            $errorMessages
        );

        $encodedContent = \json_encode(['errors' => $errors]);

        if ($encodedContent === false) {
            return null;
        }

        return $encodedContent;
    }

    private function getErrorMessages(Throwable $exception): array
    {
        if ($exception instanceof MultiPartHttpException) {
            return $exception->getMessageList();
        }

        return [$exception->getMessage()];
    }
}
