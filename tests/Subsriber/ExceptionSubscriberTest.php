<?php

namespace WizardsTest\ObjectManager;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
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
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $exception = new HttpException(404);
        $event = new ExceptionEvent($kernel, $request, 42, $exception);
        $response = new Response(
            '{"errors":[{"detail":"Not Found"}]}',
            404,
            $formatOptions->getFormatSpecificHeaders()
        );

        $subscriber = new ExceptionSubscriber($logger, $formatOptions);
        $subscriber->onKernelException($event);
        $this->assertEquals($response, $event->getResponse());
    }

    public function testFormatJsonApi400()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log');
        $formatOptions = new FormatOptions('jsonapi');
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $exception = new MultiPartHttpException(400, ['one', 'two']);
        $event = new ExceptionEvent($kernel, $request, 42, $exception);
        $response = new Response(
            '{"errors":[{"detail":"one"},{"detail":"two"}]}',
            400,
            $formatOptions->getFormatSpecificHeaders()
        );

        $subscriber = new ExceptionSubscriber($logger, $formatOptions);
        $subscriber->onKernelException($event);
        $this->assertEquals($response, $event->getResponse());
    }

    public function testObfuscateError()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $formatOptions = new FormatOptions('jsonapi');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $exception = new \RuntimeException('internal problems');
        $event = new ExceptionEvent($kernel, $request, 42, $exception);

        $response = new Response(
            'Internal Server Error',
            500,
            $formatOptions->getFormatSpecificHeaders()
        );

        $subscriber = new ExceptionSubscriber($logger, $formatOptions);
        $subscriber->onKernelException($event);
        $this->assertEquals($response, $event->getResponse());
    }
}
