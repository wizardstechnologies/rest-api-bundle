<?php

namespace Wizards\RestBundle\Controller;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

trait JsonControllerTrait
{
    protected function handleJsonForm(FormInterface $form, Request $request)
    {
        $bodyJson = $request->getContent();
        $body = json_decode($bodyJson, true);

        if (null === $body) {
            throw new \InvalidArgumentException('content should be in json');
        }

        if (!isset($body[$form->getName()])) {
            throw new \InvalidArgumentException(sprintf('json should contain a %s key', $form->getName()));
        }

        $form->submit($body[$form->getName()], $request->getMethod() !== 'PATCH');
    }
}