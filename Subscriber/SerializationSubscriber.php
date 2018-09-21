<?php

namespace Wizards\RestBundle\Subscriber;

use League\Fractal\Resource\Collection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use WizardsRest\CollectionManager;
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

    private $collectionManager;

    /**
     * @var DiactorosFactory
     */
    private $psrFactory;

    public function __construct(Serializer $serializer, Provider $provider, CollectionManager $collectionManager = null)
    {
        $this->provider = $provider;
        $this->serializer = $serializer;
        $this->collectionManager = $collectionManager;
        $this->psrFactory = new DiactorosFactory();
    }


    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $this->psrFactory->createRequest($event->getRequest());
        $resource = $this->provider->transform($event->getControllerResult(), $request);

        // Add pagination if resource is a collection
        if ($resource instanceof Collection) {
            
            //$resource->setPaginator($this->collectionManager->getPaginationAdapter($request));
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
