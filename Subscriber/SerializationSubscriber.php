<?php

namespace Wizards\RestBundle\Subscriber;

use League\Fractal\Resource\ResourceAbstract;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Wizards\RestBundle\Services\FormatOptions;
use Wizards\RestBundle\Services\ResourceProvider;
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
     * @var DiactorosFactory
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
        $this->psrFactory = new DiactorosFactory();
        $this->resourceProvider = $resourceProvider;
        $this->optionsFormatter = $optionsFormatter;
    }

    /**
     * Catches returns from Controllers, and serialize their content.
     *
     * @param GetResponseForControllerResultEvent $event
     *
     * @throws \ReflectionException
     */
    public function onKernelView(GetResponseForControllerResultEvent $event): void
    {
        $event->setResponse(new Response(
            $this->serializer->serialize(
                $this->getResource($event),
                $this->optionsFormatter->getSpecification(),
                $this->optionsFormatter->getFormat()
            ),
            200,
            $this->optionsFormatter->getFormatSpecificHeaders()
        ));
    }

    /**
     * Stores a link to a controller. Useful to read its annotations.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $this->resourceProvider->setController($controller);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => 'onKernelView',
            KernelEvents::CONTROLLER => 'onKernelController'
        ];
    }

    /**
     * Transforms a entity or a collection to a Fractal resource.
     * If it is a collection, paginate it.
     *
     * @param GetResponseForControllerResultEvent $event
     *
     * @return ResourceAbstract
     *
     * @throws \ReflectionException
     */
    private function getResource(GetResponseForControllerResultEvent $event): ResourceAbstract
    {
        $request = $this->psrFactory->createRequest($event->getRequest());
        $result = $event->getControllerResult();

        return $this->resourceProvider->getResource($result, $request);
    }
}
