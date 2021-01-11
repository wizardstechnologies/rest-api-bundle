<?php

namespace Wizards\RestBundle\Controller;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Wizards\RestBundle\Exception\MultiPartHttpException;
use WizardsRest\Exception\HttpException;

/**
 * A trait that helps in building restful json controllers.
 */
trait JsonControllerTrait
{
    protected function throwRestErrorFromForm(FormInterface $form)
    {
        throw new MultiPartHttpException(400, $this->convertFormErrorsToArray($form));
    }

    /**
     * Transform a json request body to a valid symfony form and submits it.
     *
     * @param FormInterface $form
     * @param Request $request
     */
    protected function handleJsonForm(FormInterface $form, Request $request)
    {
        $body = $this->decode($request, $form);

        if (empty($body)) {
            throw new HttpException(400, 'invalid, empty or not json/jsonapi body provided');
        }

        if (empty($body[$form->getName()])) {
            throw new HttpException(400, sprintf('json should contain a %s key', $form->getName()));
        }

        $form->submit($body[$form->getName()], $request->getMethod() !== 'PATCH');
    }

    private function decode(Request $request, FormInterface $form): array
    {
        if ('application/json' === $request->headers->get('Content-Type')) {
            return \json_decode($request->getContent(), true);
        }

        if ('application/vnd.api+json' === $request->headers->get('Content-Type')) {
            return $this->decodeJsonApi($request->getContent(), $form);
        }

        return [];
    }

    private function decodeJsonApi(string $content, FormInterface $form): array
    {
        $jsonApi = \json_decode($content, true);

        if (empty($jsonApi)) {
            return [];
        }

        $fields = isset($jsonApi['data']['id'])
            ? \array_merge(['id' => $jsonApi['data']['id']], $jsonApi['data']['attributes'])
            : $jsonApi['data']['attributes'];

        if (isset($jsonApi['relationships']) && is_array($jsonApi['relationships'])) {
            foreach ($jsonApi['relationships'] as $relationshipName => $relationshipValue) {
                $fields[$relationshipName] = $relationshipValue['data']['id'];
            }
        }

        return [$form->getName() => $fields];
    }

    /**
     * Transform form errors in a simple array.
     */
    private function convertFormErrorsToArray(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->getErrors() as $error) {
            $errors[] = $this->getTopLevelErrorMessage($error);
        }

        foreach ($form->all() as $key => $child) {
            $childrenErrors = $this->convertFormErrorsToArray($child);

            foreach ($childrenErrors as $childrenError) {
                $errors[] = \sprintf('%s: %s', $key, $childrenError);
            }
        }

        return $errors;
    }

    private function getTopLevelErrorMessage(FormError $error)
    {
        $cause = $error->getCause();
        if ($cause instanceof ConstraintViolation) {
            return \sprintf('%s: %s', $cause->getPropertyPath(), $error->getMessage());
        }

        return $error->getMessage();
    }
}
