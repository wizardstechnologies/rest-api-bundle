<?php

namespace Wizards\RestBundle\Subscriber;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\ResourceAbstract;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use WizardsRest\CollectionManager;
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
     * @var string
     */
    private $format;

    public function __construct(
        Serializer $serializer,
        Provider $provider,
        PaginatorInterface $paginator,
        string $format
    ) {
        $this->provider = $provider;
        $this->serializer = $serializer;
        $this->paginator = $paginator;
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

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => 'onKernelView'
        ];
    }

    private function getResource($content, Request $request): ResourceAbstract
    {
        // this should come from data_source config
        $transformer = (isset($content['id']) || isset($content[0]['id']))
            ? function ($data) { return $data; }
            : null;

        $resource = $this->provider->transform(
            $content,
            $this->psrFactory->createRequest($request),
            $transformer,
            basename($request->getPathInfo())
        );

        return $resource;
    }

    private function getSpecification(): string
    {
        if ('jsonapi' === $this->format) {
            return Serializer::FORMAT_JSON;
        }

        if ('array' === $this->format) {
            return Serializer::FORMAT_ARRAY;
        }

        return Serializer::FORMAT_JSON;
    }

    private function getFormat(): string
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
