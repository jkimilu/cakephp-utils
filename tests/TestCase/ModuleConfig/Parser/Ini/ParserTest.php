<?php
namespace Qobo\Utils\Test\TestCase\ModuleConfig\Parser\Ini;

use Cake\Core\Configure;
use PHPUnit_Framework_TestCase;
use Qobo\Utils\ModuleConfig\Parser\Ini\Parser;

class ParserTest extends PHPUnit_Framework_TestCase
{
    protected $parser;
    protected $dataDir;

    protected function setUp()
    {
        $this->parser = new Parser();
        $this->dataDir = dirname(dirname(dirname(dirname(__DIR__)))) . DS . 'data' . DS . 'Modules' . DS;
        Configure::write('CsvMigrations.modules.path', $this->dataDir);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testParseFromPathException()
    {
        $result = $this->parser->parseFromPath('some-non-existing-file');
    }

    public function testParseFromPath()
    {
        $file = $this->dataDir . 'Foo' . DS . 'config' . DS . 'config.ini';
        $result = $this->parser->parseFromPath($file);

        $this->assertTrue(is_array($result), "Parser returned a non-array");
        $this->assertFalse(empty($result), "Parser returned empty result");

        $this->assertFalse(empty($result['table']), "Parser missed 'table' section");
        $this->assertFalse(empty($result['table']['display_field']), "Parser missed 'display_field' key");
        $this->assertEquals('name', $result['table']['display_field'], "Parser misinterpreted 'display_field' value");
    }

    public function testParseFromPathTestingArrays()
    {
        $file = $this->dataDir . 'Foo' . DS . 'config' . DS . 'array_in_config.ini';
        $result = $this->parser->parseFromPath($file);

        $this->assertTrue(is_array($result), 'Return data from parser isn\'t array type');
        $this->assertArrayHasKey('associations', $result, "No associations found in the table config");
        $this->assertArrayHasKey('association_labels', $result['associations'], "No associations found in the table config");
        $this->assertTrue(is_array($result['associations']['association_labels']), "Associations label is not an array");
    }
}