<?php

namespace WizardsTest\ObjectManager;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Wizards\RestBundle\Exception\MultiPartHttpException;
use Wizards\RestBundle\Services\FormatOptions;
use Wizards\RestBundle\Subscriber\ExceptionSubscriber;
use Psr\Log\LoggerInterface;
use WizardsRest\Exception\HttpException;

class ExceptionSubscriberTest extends TestCase
{
    public function testFormatJsonApi404()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log');
        $formatOptions = new FormatOptions('jsonapi');
        $event = $this->createMock(ExceptionEvent::class);
        $exception = $this->createMock(HttpException::class);

        $exception->method('getStatusCode')->willReturn(404);
        $event->method('getThrowable')->willReturn($exception);
        $response = new Response(
            '{"errors":[{"detail":"Not Found"}]}',
            404,
            $formatOptions->getFormatSpecificHeaders()
        );
        $event->expects($this->once())->method('setResponse')->with($response);

        $subscriber = new ExceptionSubscriber($logger, $formatOptions);
        $subscriber->onKernelException($event);
    }

    public function testFormatJsonApi400()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log');
        $formatOptions = new FormatOptions('jsonapi');
        $event = $this->createMock(ExceptionEvent::class);
        $exception = new MultiPartHttpException(400, ['one', 'two']);
        $event->method('getThrowable')->willReturn($exception);
        $response = new Response(
            '{"errors":[{"detail":"one"},{"detail":"two"}]}',
            400,
            $formatOptions->getFormatSpecificHeaders()
        );
        $event->expects($this->once())->method('setResponse')->with($response);

        $subscriber = new ExceptionSubscriber($logger, $formatOptions);
        $subscriber->onKernelException($event);
    }

    public function testObfuscateError()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $formatOptions = new FormatOptions('jsonapi');
        $event = $this->createMock(ExceptionEvent::class);

        $exception = new \RuntimeException('internal problems');

        $event->method('getThrowable')->willReturn($exception);
        $response = new Response(
            'Internal Server Error',
            500,
            $formatOptions->getFormatSpecificHeaders()
        );
        $event->expects($this->once())->method('setResponse')->with($response);

        $subscriber = new ExceptionSubscriber($logger, $formatOptions);
        $subscriber->onKernelException($event);
    }
}
