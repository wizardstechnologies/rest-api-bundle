<?php

namespace WizardsTest\ObjectManager;

use PHPUnit\Framework\TestCase;
use Wizards\RestBundle\Services\FormatOptions;
use WizardsRest\Serializer;

class FormatOptionsTest extends TestCase
{
    public function testGetFormat()
    {
        $formatOptions = new FormatOptions('array');
        $this->assertEquals(Serializer::FORMAT_ARRAY, $formatOptions->getFormat());
        $formatOptions = new FormatOptions('jsonapi');
        $this->assertEquals(Serializer::FORMAT_JSON, $formatOptions->getFormat());
    }
}
