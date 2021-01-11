<?php

namespace Wizards\RestBundle\Subscriber;

use League\Fractal\Resource\ResourceAbstract;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Wizards\RestBundle\Services\FormatOptions;
use Wizards\RestBundle\Services\ResourceProvider;
use WizardsRest\RestView;
use WizardsRest\Serializer;

/**
 * Serializes a controller output to a configured format response.
 */
class SerializationSubscriber implements EventSubscriberInterface
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var PsrHttpFactory
     */
    private $psrFactory;
    /**
     * @var FormatOptions
     */
    private $optionsFormatter;

    /**
     * @var ResourceProvider
     */
    private $resourceProvider;

    public function __construct(
        Serializer $serializer,
        ResourceProvider $resourceProvider,
        FormatOptions $optionsFormatter
    ) {
        $this->serializer = $serializer;
        $psr17Factory = new Psr17Factory();
        $this->psrFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $this->resourceProvider = $resourceProvider;
        $this->optionsFormatter = $optionsFormatter;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => 'onKernelView',
            KernelEvents::CONTROLLER => 'onKernelController'
        ];
    }

    /**
     * Catches returns from Controllers, and serialize their content.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $serializedResponse = $this->serializer->serialize(
            $this->getResource($event),
            $this->optionsFormatter->getSpecification(),
            $this->optionsFormatter->getFormat()
        );

        // This nasty condition can be used in case another output format than json is configured
        // that is not supported by the library yet
        if (is_array($serializedResponse)) {
            $serializedResponse = print_r($serializedResponse, true);
        }

        $result = $event->getControllerResult();

        $event->setResponse(new Response(
            $serializedResponse,
            $result instanceof RestView ? $result->getCode() : 200,
            $this->optionsFormatter->getFormatSpecificHeaders()
        ));
    }

    /**
     * Stores a link to a controller. Useful to read its annotations.
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $this->resourceProvider->setController($controller);
        }
    }

    /**
     * Transforms a entity or a collection to a Fractal resource.
     * If it is a collection, paginate it.
     */
    private function getResource(ViewEvent $event): ResourceAbstract
    {
        $request = $this->psrFactory->createRequest($event->getRequest());
        $result = $event->getControllerResult();

        if ($result instanceof RestView) {
            $result = $result->getContent();
        }

        return $this->resourceProvider->getResource($result, $request);
    }
}
