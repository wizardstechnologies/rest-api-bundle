<?php

namespace Wizards\RestBundle\Controller;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

trait JsonControllerTrait
{
    protected function handleJsonForm(FormInterface $form, Request $request)
    {
        $body = $this->decode($request, $form);

        if (empty($body)) {
            throw new \InvalidArgumentException('invalid, empty or not json/jsonapi body provided');
        }

        if (empty($body[$form->getName()])) {
            throw new \InvalidArgumentException(sprintf('json should contain a %s key', $form->getName()));
        }

        $form->submit($body[$form->getName()], $request->getMethod() !== 'PATCH');
    }

    private function decode(Request $request, FormInterface $form): array
    {
        if ('application/json' === $request->headers->get('Content-Type')) {
            return json_decode($request->getContent(), true);
        }

        if ('application/vnd.api+json' === $request->headers->get('Content-Type')) {
            return $this->decodeJsonApi($request->getContent(), $form);
        }

        return [];
    }

    private function decodeJsonApi(string $content, FormInterface $form): array
    {
        $jsonApi = json_decode($content, true);

        if (empty($jsonApi)) {
            return [];
        }

        return [$form->getName() => $jsonApi['attributes']];
    }
}
