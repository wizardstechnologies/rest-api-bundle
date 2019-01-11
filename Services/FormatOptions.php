<?php

namespace Wizards\RestBundle\Services;

use WizardsRest\Serializer;

class FormatOptions
{
    /**
     * @var string
     */
    private $format;

    public function __construct($format)
    {
        $this->format = $format;
    }

    public function getFormat(): string
    {
        if ('jsonapi' === $this->format) {
            return Serializer::FORMAT_JSON;
        }

        if ('array' === $this->format) {
            return Serializer::FORMAT_ARRAY;
        }

        return Serializer::FORMAT_JSON;
    }

    public function getSpecification(): string
    {
        if ('jsonapi' === $this->format) {
            return Serializer::SPEC_JSONAPI;
        }

        return Serializer::SPEC_ARRAY;
    }

    public function getFormatSpecificHeaders(): array
    {
        if ('jsonapi' === $this->format) {
            return ['Content-Type' => 'application/vnd.api+json'];
        }

        return [];
    }
}