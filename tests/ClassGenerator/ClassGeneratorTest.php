<?php

/*
 * This software is licensed under MIT License.
 *
 * Copyright (c) 2012, Kazuyuki Hayashi
 */

namespace KzykHys\Tests\ClassGenerator;

use KzykHys\ClassGenerator\Builder\ClassBuilder;
use KzykHys\ClassGenerator\Builder\MethodBuilder;
use KzykHys\ClassGenerator\Builder\PropertyBuilder;
use KzykHys\ClassGenerator\Compiler\Compiler;

/**
 * @group functional
 * @coversNothing
 */
class ClassGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleClass()
    {
        $classBuilder = new ClassBuilder();
        $classBuilder->setDocblock([
            'Represents a PHP Test class.',
            '',
            '@author Kazuyuki Hayashi <hayashi@valnur.net>',
        ]);
        $classBuilder->setClass('Test\TestClass');

        $pb = new PropertyBuilder();
        $pb->setName('scalar');
        $pb->setType('string');
        $pb->setVisibility('public');
        $pb->setDefault('default');
        $pb->setComments(['This is a default scalar']);
        $pb->addAccessor('get');
        $pb->addAccessor('set');

        $classBuilder->addProperty($pb);

        $mb = new MethodBuilder();
        $mb->setName('jsonSerialize');
        $mb->setType('array');
        $mb->setVisibility('public');
        $mb->setComments(['returns an array for json_serialize function']);
        $mb->setBody(<<<'EOF'
return [
    'key' => 'value',
    'key2' => 'value2',
];
EOF
        );

        $classBuilder->addMethod($mb);

        $classBuilder->addImport('JsonSerializable');
        $classBuilder->addImport('stdClass');
        $classBuilder->addConstant('MY_CONSTANT', 'constant');
        $classBuilder->setExtends('stdClass');
        $classBuilder->addInterface('JsonSerializable');

        $compiler = new Compiler();
        $writer   = $compiler->compile($classBuilder);

        $class = file_get_contents(__DIR__.'/../Fixtures/TestClass.php');

        $this->assertSame($class, (string) $writer);
    }
}
