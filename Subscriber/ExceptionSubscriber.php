<?php

namespace Wizards\RestBundle\Subscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;
use Wizards\RestBundle\Exception\MultiPartHttpException;
use Wizards\RestBundle\Services\FormatOptions;
use WizardsRest\Exception\HttpException;
use WizardsRest\Serializer;

/**
 * When an exception happen, this subscriber helps to serialize it the rest way.
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FormatOptions
     */
    private $formatOptions;

    public function __construct(LoggerInterface $logger, FormatOptions $formatOptions)
    {
        $this->logger = $logger;
        $this->formatOptions = $formatOptions;
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
        $response->setContent('Internal Server Error');

        if ($exception instanceof HttpExceptionInterface || $exception instanceof HttpException) {
            $content = $this->getErrorResponseContent($exception);

            $response->setStatusCode($exception->getStatusCode());

            if ($content !== null) {
                $response->setContent($content);
            }
        }

        $response->headers->replace($this->formatOptions->getFormatSpecificHeaders());

        $event->setResponse($response);
    }

    /**
     * @param HttpExceptionInterface|HttpException $exception
     *
     * @return null|string
     */
    private function getErrorResponseContent($exception): ?string
    {
        $errorMessages = $this->getErrorMessages($exception);

        // If the error has no specific text, use the common text for this code
        if (!$errorMessages[0] && isset(Response::$statusTexts[$exception->getStatusCode()])) {
            $errorMessages = [Response::$statusTexts[$exception->getStatusCode()]];
        }

        if (Serializer::SPEC_JSONAPI === $this->formatOptions->getSpecification()) {
            $errorMessages = \array_map(
                function ($error) {
                    return ['detail' => $error];
                },
                $errorMessages
            );
        }

        $encodedContent = \json_encode(['errors' => $errorMessages]);

        if ($encodedContent === false) {
            return null;
        }

        return $encodedContent;
    }

    private function getErrorMessages($exception): array
    {
        if ($exception instanceof MultiPartHttpException) {
            return $exception->getMessageList();
        }

        return [$exception->getMessage()];
    }
}
