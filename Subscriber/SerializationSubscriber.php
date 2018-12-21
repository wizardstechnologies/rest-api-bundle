<?php

namespace Wizards\RestBundle\Subscriber;

use Doctrine\Common\Annotations\Reader;
use League\Fractal\Resource\ResourceAbstract;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
        $this->format = $format;
        $this->controller = null;
        $this->psrFactory = new DiactorosFactory();
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
            $this->serializer->serialize($this->getResource($event), $this->getSpecification(), $this->getFormat()),
            200,
            $this->getFormatSpecificHeaders()
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
            $this->controller = $controller;
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

        if ($this->isCollection($result)) {
            $result = $this->paginator->paginate($result, $request);
        }

        $resource = $this->provider->transform(
            $result,
            $request,
            $this->getTransformer($result),
            $this->getResourceName()
        );

        if ($this->isCollection($result)) {
            $resource->setPaginator($this->paginator->getPaginationAdapter($resource, $request));
        }

        return $resource;
    }

    /**
     * Try to get the resource name/type by annotation, first on the method then on the class of the controller.
     * @return null
     * @throws \ReflectionException
     */
    private function getResourceName()
    {
        if (null === $this->controller) {
            return null;
        }

        $reflection = new \ReflectionClass($this->controller[0]);
        $methodTypeAnnotation = $this->reader->getMethodAnnotation(
            $reflection->getMethod($this->controller[1]),
            Type::class
        );

        if (null !== $methodTypeAnnotation) {
            return $methodTypeAnnotation->getType();
        }

        $resourceTypeAnnotation = $this->reader->getClassAnnotation(
            $reflection,
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

    /**
     * Is the given resource an collection of resources ?
     *
     * @param mixed $resource
     *
     * @return bool
     */
    private function isCollection($resource): bool
    {
        // This is a resource presented as an array
        if (is_array($resource) && count($resource) === count($resource, COUNT_RECURSIVE)) {
            return false;
        }

        // It's a collection
        if (is_array($resource) || $resource instanceof \Traversable) {
            return true;
        }

        return false;
    }
}
