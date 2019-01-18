<?php

namespace WizardsTest\ObjectManager;

use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Wizards\RestBundle\Services\ResourceProvider;
use WizardsRest\Paginator\PaginatorInterface;
use WizardsRest\Provider;

class ResourceProviderTest extends TestCase
{
    private $provider;

    private $paginator;

    private $reader;

    private $request;

    protected function setUp()
    {
        $this->provider = $this->createMock(Provider::class);
        $this->paginator = $this
            ->getMockBuilder(PaginatorInterface::class)
            ->setMethods(['paginate','getPaginationAdapter'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->reader = $this->createMock(Reader::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
    }

    public function testEmptyArrayIsPaginated()
    {
        $resourceProvider = new ResourceProvider($this->provider, $this->paginator, $this->reader);
        $this->paginator->expects($this->once())->method('paginate');
        $resourceProvider->getResource([], $this->request);
    }

    public function testObjectArrayIsPaginated()
    {
        $resourceProvider = new ResourceProvider($this->provider, $this->paginator, $this->reader);
        $this->paginator->expects($this->never())->method('paginate');
        $object = (object)['id' => 1, 'name' => 'test'];
        $object2 = (object)['id' => 2, 'name' => 'test2'];
        $resourceProvider->getResource([$object, $object2], $this->request);
    }

    public function testResourceArrayIsPaginated()
    {
        $resourceProvider = new ResourceProvider($this->provider, $this->paginator, $this->reader);
        $this->paginator->expects($this->once())->method('paginate');
        $resourceProvider->getResource([['id' => 1, 'name' => 'test']], $this->request);
    }

    public function testResourceIsNotPaginated()
    {
        $resourceProvider = new ResourceProvider($this->provider, $this->paginator, $this->reader);
        $this->paginator->expects($this->never())->method('paginate');
        $resourceProvider->getResource(['id' => 1, 'name' => 'test'], $this->request);
    }

    public function testObjectIsNotPaginated()
    {
        $resourceProvider = new ResourceProvider($this->provider, $this->paginator, $this->reader);
        $this->paginator->expects($this->never())->method('paginate');
        $object = (object)['id' => 1, 'name' => 'test'];
        $resourceProvider->getResource($object, $this->request);
    }
}
