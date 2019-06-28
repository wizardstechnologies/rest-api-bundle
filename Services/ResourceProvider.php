<?php

namespace Wizards\RestBundle\Services;

use Doctrine\Common\Annotations\Reader;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\ResourceAbstract;
use Psr\Http\Message\ServerRequestInterface;
use WizardsRest\Annotation\Type;
use WizardsRest\Paginator\PaginatorInterface;
use WizardsRest\Provider;

/**
 * Get a fractal Resource from a result set and a request.
 *
 * @package Wizards\RestBundle\Services
 */
class ResourceProvider
{
    /**
     * @var Provider
     */
    private $provider;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var array|null
     */
    private $controller;

    public function __construct(
        Provider $provider,
        PaginatorInterface $paginator,
        Reader $reader
    ) {
        $this->provider = $provider;
        $this->paginator = $paginator;
        $this->reader = $reader;
    }

    /**
     * Transforms a entity or a collection to a Fractal resource.
     * If it is a collection, paginate it.
     *
     * @param mixed $result
     * @param ServerRequestInterface $request
     *
     * @return ResourceAbstract
     *
     * @throws \ReflectionException
     */
    public function getResource($result, ServerRequestInterface $request): ResourceAbstract
    {
        if ($this->isCollection($result)) {
            $result = $this->paginator->paginate($result, $request);
        }

        /**
         * @var Collection $resource
         */
        $resource = $this->provider->transform(
            $result,
            $request,
            $this->getTransformer($result),
            $this->getResourceName()
        );

        if ($resource instanceof Collection) {
            $resource->setPaginator($this->paginator->getPaginationAdapter($resource->getData(), $request));
        }

        return $resource;
    }

    /**
     * @param array $controller
     */
    public function setController(array $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * Try to get the resource name/type by annotation, first on the method then on the class of the controller.
     *
     * @return string|null
     *
     * @throws \ReflectionException
     */
    private function getResourceName(): ?string
    {
        if (null === $this->controller) {
            return null;
        }

        $reflection = new \ReflectionClass($this->controller[0]);

        /**
         * @var Type $annotation
         */
        $annotation = $this->reader->getMethodAnnotation(
            $reflection->getMethod($this->controller[1]),
            Type::class
        );

        if (null !== $annotation) {
            return $annotation->getType();
        }

        /**
         * @var Type $annotation
         */
        $annotation = $this->reader->getClassAnnotation(
            $reflection,
            Type::class
        );

        if (null !== $annotation) {
            return $annotation->getType();
        }

        return null;
    }

    /**
     * If the result is an array (and contains and id or children with id), then pass a dummy transformer,
     * otherwise, use the default transformer from the library.
     *
     * @TODO: it might not feel really natural that the entity transformer is the default one.
     * We might want a simpler default, or no default at all. Discussion is open !
     *
     * @param mixed $result
     *
     * @return \Closure|null
     */
    private function getTransformer($result)
    {
        if (is_array($result)) {
            if (is_object(current($result))) {
                return null;
            }

            return function ($data) {
                return $data;
            };
        }

        return null;
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
        if (is_array($resource) && count($resource) === count($resource, COUNT_RECURSIVE) && !empty($resource)) {
            return false;
        }

        // It's a collection
        if (is_array($resource) || $resource instanceof \Traversable) {
            return true;
        }

        return false;
    }
}