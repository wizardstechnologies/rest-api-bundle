<?php

namespace Wizards\RestBundle\ParamConverter;

use Psr\Http\Message\ServerRequestInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Converts a Symfony Request to a Psr7 Request
 */
class Psr7ParamConverter implements ParamConverterInterface
{
    public function apply(Request $request, ParamConverter $configuration)
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        $request->attributes->set($configuration->getName(), $psrHttpFactory->createRequest($request));

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        if ($configuration->getClass() == ServerRequestInterface::class) {
            return true;
        }

        return false;
    }
}
