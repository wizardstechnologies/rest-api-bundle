<?php

namespace Wizards\RestBundle\Subscriber;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
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
 * Serializes a Response to JsonApi.
 * Should be configurable !
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

    public function __construct(
        Serializer $serializer,
        Provider $provider,
        PaginatorInterface $paginator
    ) {
        $this->provider = $provider;
        $this->serializer = $serializer;
        $this->paginator = $paginator;
        $this->psrFactory = new DiactorosFactory();
    }

    private function getResource($content, Request $request)
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

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $this->psrFactory->createRequest($event->getRequest());
        $resource = $this->getResource($event->getControllerResult(), $event->getRequest());

        // Add pagination if resource is a collection
        if ($resource instanceof Collection) {
            $resource->setPaginator($this->paginator->getPaginationAdapter($resource, $request));
        }

        $response = new Response(
            $this->serializer->serialize($resource, Serializer::SPEC_JSONAPI, Serializer::FORMAT_JSON),
            200,
            ['Content-Type' => 'application/vnd.api+json']
        );

        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => 'onKernelView'
        ];
    }
}
