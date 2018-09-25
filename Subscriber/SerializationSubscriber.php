<?php

namespace Wizards\RestBundle\Subscriber;

use Doctrine\Common\Annotations\Reader;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\ResourceAbstract;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use WizardsRest\Annotation\Type;
use WizardsRest\Paginator\PaginatorInterface;
use WizardsRest\Provider;
use WizardsRest\Serializer;

/**
 * Serializes a controller output to a configured format response.
 */
class SerializationSubscriber implements EventSubscriberInterface
{
    /**
     * @var Provider
     */
    private $provider;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * @var DiactorosFactory
     */
    private $psrFactory;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var string
     */
    private $format;

    /**
     * @var callable
     */
    private $controller;

    public function __construct(
        Serializer $serializer,
        Provider $provider,
        PaginatorInterface $paginator,
        Reader $reader,
        string $format
    ) {
        $this->provider = $provider;
        $this->serializer = $serializer;
        $this->paginator = $paginator;
        $this->reader = $reader;
        $this->psrFactory = new DiactorosFactory();
        $this->format = $format;
    }

    public function onKernelView(GetResponseForControllerResultEvent $event): void
    {
        $request = $this->psrFactory->createRequest($event->getRequest());
        $resource = $this->getResource($event->getControllerResult(), $event->getRequest());

        // Add pagination if resource is a collection
        if ($resource instanceof Collection) {
            $resource->setPaginator($this->paginator->getPaginationAdapter($resource, $request));
        }

        $response = new Response(
            $this->serializer->serialize($resource, $this->getSpecification(), $this->getFormat()),
            200,
            $this->getFormatSpecificHeaders()
        );

        $event->setResponse($response);
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $this->controller = $event->getController();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => 'onKernelView',
            KernelEvents::CONTROLLER => 'onKernelController'
        ];
    }

    private function getResource($content, Request $request): ResourceAbstract
    {
        return $this->provider->transform(
            $content,
            $this->psrFactory->createRequest($request),
            $this->getTransformer($content),
            $this->getResourceName()
        );
    }

    private function getResourceName()
    {
        $resourceTypeAnnotation = $this->reader->getClassAnnotation(
            new \ReflectionClass($this->controller),
            Type::class
        );

        if (null !== $resourceTypeAnnotation) {
            return $resourceTypeAnnotation->getType();
        }

        return null;
    }

    private function getTransformer($content)
    {
        return is_array($content) && (isset($content['id']) || isset($content[0]['id']))
            ? function ($data) { return $data; }
            : null;
    }

    private function getFormat(): string
    {
        if ('jsonapi' === $this->format) {
            return Serializer::FORMAT_JSON;
        }

        if ('array' === $this->format) {
            return Serializer::FORMAT_ARRAY;
        }

        return Serializer::FORMAT_JSON;
    }

    private function getSpecification(): string
    {
        if ('jsonapi' === $this->format) {
            return Serializer::SPEC_JSONAPI;
        }

        return Serializer::SPEC_ARRAY;
    }

    private function getFormatSpecificHeaders(): array
    {
        if ('jsonapi' === $this->format) {
            return ['Content-Type' => 'application/vnd.api+json'];
        }

        return [];
    }
}
