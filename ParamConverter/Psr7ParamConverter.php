<?php

namespace Wizards\RestBundle\ParamConverter;

use Psr\Http\Message\ServerRequestInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Converts a Symfony Request to a Psr7 Request
 */
class Psr7ParamConverter implements ParamConverterInterface
{
    /**
     * @inheritdoc
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $psrFactory = new DiactorosFactory();
        $request->attributes->set($configuration->getName(), $psrFactory->createRequest($request));

        return true;
    }

    /**
     * @inheritdoc
     */
    public function supports(ParamConverter $configuration)
    {
        if ($configuration->getClass() == ServerRequestInterface::class) {
            return true;
        }

        return false;
    }
}
