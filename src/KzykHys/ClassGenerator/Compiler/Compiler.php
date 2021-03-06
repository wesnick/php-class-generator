<?php

/*
 * This software is licensed under MIT License.
 *
 * Copyright (c) 2012, Kazuyuki Hayashi
 */

namespace KzykHys\ClassGenerator\Compiler;

use KzykHys\ClassGenerator\Builder\ClassBuilder;
use KzykHys\ClassGenerator\Builder\MethodBuilder;
use KzykHys\ClassGenerator\Builder\PropertyBuilder;

/**
 * Compiles ClassBuilder instance to PHP class.
 *
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
class Compiler
{
    private $options;

    /**
     * Constructor.
     *
     * @param array $options Options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'indent_spaces' => '4',
            'lineFeed'      => "\n",
        ], $options);
    }

    /**
     * Compiles a builder.
     *
     * @param ClassBuilder $builder
     *
     * @return StreamWriter
     */
    public function compile(ClassBuilder $builder)
    {
        $writer = new StreamWriter($this->options);
        $writer->writeLine('<?php');
        $writer->writeLine();

        $this->compileClassDefinition($builder, $writer);
        $this->compileConstantDefinitions($builder, $writer);
        $this->compilePropertyDefinitions($builder, $writer);
        $this->compileMethodDefinitions($builder, $writer);

        $writer->writeLine('}');

        return $writer;
    }

    /**
     * Split long class name to namespace and class name.
     *
     * @param string $className
     *
     * @throws \Exception
     *
     * @return array An array includes keys below [namespace, classname, fqcn]
     */
    public function parseClassName($className)
    {
        if (!preg_match('/^\\\\?(.*?)\\\\?([a-zA-Z0-9_]+)$/', $className, $matches)) {
            throw new \Exception('Invalid class name "'.$className.'" given');
        }

        return [
            'namespace' => $matches[1],
            'classname' => $matches[2],
            'fqcn'      => $className,
        ];
    }

    /**
     * Normalize PHP type.
     *
     * @param string $className
     * @param string $type      PHP type
     *
     * @return array An array includes key below [name, hint]
     */
    public function getType($className, $type)
    {
        $aliases = [
            'int'    => 'integer',
            'bool'   => 'boolean',
            'double' => 'float',
            'object' => '\\stdClass',
        ];
        $default = [
            'integer', 'boolean', 'float', '\\stdClass', 'callable', 'string', 'resource', 'void', 'mixed',
        ];
        $typeHintEnabled = false;

        if (isset($aliases[$type])) {
            $type = $aliases[$type];
        }

        if (!in_array($type, $default, true) && strpos($type, 'array<') === false) {
            $typeHintEnabled = true;

            // If Type is same namespace as class, remove namespace
            $class      = $this->parseClassName($className);
            $methodType = $this->parseClassName($type);

            if ($methodType['namespace'] === $class['namespace']) {
                $type = $methodType['classname'];
            }
        }

        return [
            'name' => $type,
            'hint' => $typeHintEnabled,
        ];
    }

    /**
     * Compiles class definition.
     *
     * @param ClassBuilder $builder
     * @param StreamWriter $writer
     */
    protected function compileClassDefinition(ClassBuilder $builder, StreamWriter $writer)
    {
        $namespace = null;
        $class     = $this->parseClassName($builder->getClass());
        if ($class['namespace']) {
            $namespace = $class['namespace'];
            $writer->writeLineF('namespace %s;', trim(ltrim($namespace, '\\')));
            $writer->writeLine();
        }

        $imports = $builder->getImports();
        foreach ($imports as $import) {
            $writer->writeLineF('use %s;', trim($import));
        }
        $writer->writeLine();

        $docblock = $builder->getDocblock();
        if ($docblock) {
            $writer->writeLine('/**');
            foreach ($docblock as $docline) {
                $dls = $this->wrapDescription($docline);

                foreach ($dls as $dl) {
                    if (empty(trim($dl))) {
                        $writer->writeLine(' *');
                    } else {
                        $writer->writeLine(' * '.$dl);
                    }
                }
            }
            $writer->writeLine(' */');
        }

        $writer->writeF('class %s', $class['classname']);

        if ($extends = $builder->getExtends()) {
            $extends = $this->parseClassName($extends);
            if ($extends['namespace'] === $namespace) {
                $writer->writeF(' extends %s', $extends['classname']);
            } else {
                $writer->writeF(' extends %s', $extends['fqcn']);
            }
        }

        $interfaces = $builder->getInterfaces();
        if (count($interfaces)) {
            $writer->write(' implements ');
            $implements = [];
            foreach ($interfaces as $interface) {
                $interface = $this->parseClassName($interface);
                if ($interface['namespace'] === $namespace) {
                    $implements[] = $interface['classname'];
                } else {
                    $implements[] = $interface['fqcn'];
                }
            }
            $writer->write(implode(', ', $implements));
        }

        $writer->writeLine();
        $writer->writeLine('{');
    }

    /**
     * Compiles constant definition.
     *
     * @param ClassBuilder $builder
     * @param StreamWriter $writer
     */
    protected function compileConstantDefinitions(ClassBuilder $builder, StreamWriter $writer)
    {
        foreach ($builder->getConstants() as $constant => $value) {
            $writer->indent()
                ->write('const')
                ->write(' ')
                ->write($constant)
                ->write(str_repeat(' ', $builder->getConstantMaxLength() - strlen($constant) + 1))
                ->write("= '")
                ->write(str_replace("'", "\'", $value))
                ->writeLine("';")
            ;
        }

        $writer->writeLine();
    }

    /**
     * Compiles property definition.
     *
     * @param ClassBuilder $builder
     * @param StreamWriter $writer
     */
    protected function compilePropertyDefinitions(ClassBuilder $builder, StreamWriter $writer)
    {
        foreach ($builder->getProperties() as $property) {
            /* @var PropertyBuilder $property */

            $writer->indent()->writeLine('/**');
            $comments = $property->getComments();
            if (count($comments)) {
                foreach ($comments as $comment) {
                    foreach ($this->wrapDescription($comment) as $cl) {
                        if (empty(trim($cl))) {
                            $writer->indent()->writeLine(' *');
                        } else {
                            $writer->indent()->write(' * ')->writeLine($cl);
                        }
                    }
                }
                $writer->indent()
                    ->write(' *')
                    ->writeLine();
            }
            $writer->indent()->write(' * @var');
            if ($type = $property->getType()) {
                $writer->write(' '.$property->getType());
            }
            $writer
                ->writeLine()
                ->indent()->writeLine(' */');

            $writer->indent()
                ->write($property->getVisibility())
                ->write(' ')
                ->write('$'.$property->getName());

            if ($property->getDefault()) {
                $writer->write(sprintf(" = '%s'", $property->getDefault()));
            }
            $writer->writeLine(';');
        }

        $writer->writeLine();
    }

    /**
     * Compiles method definition.
     *
     * @param ClassBuilder $builder
     * @param StreamWriter $writer
     */
    protected function compileMethodDefinitions(ClassBuilder $builder, StreamWriter $writer)
    {
        foreach ($builder->getMethods() as $method) {
            /* @var MethodBuilder $method */

            $writer->indent()->writeLine('/**');

            $comments = $method->getComments();
            if (count($comments)) {
                foreach ($comments as $comment) {
                    foreach ($this->wrapDescription($comment) as $cl) {
                        if (empty(trim($cl))) {
                            $writer->indent()->writeLine(' *');
                        } else {
                            $writer->indent()->write(' * ')->writeLine($cl);
                        }
                    }
                }
                $writer->indent()->write(' *')->writeLine();
            }

            $arguments          = $method->getArguments();
            $argumentTypeMaxLen = 0;
            if (count($arguments)) {
                foreach ($arguments as $argument) {
                    $argumentTypeMaxLen = max($argumentTypeMaxLen, strlen($argument[1]));
                }
            }

            foreach ($arguments as $argument) {
                $writer->indent()
                    ->write(' * @param '.sprintf('%-'.$argumentTypeMaxLen.'s', $argument[1]))
                    ->writeLine(' $'.$argument[0]);
            }

            if ($method->getType()) {
                $writer->indent()->writeLine(' *');
                if ($method->getType() === $builder->getClass()) {
                    $type = '$this';
                } else {
                    $type = $method->getType();
                }
                $writer->indent()->writeLine(' * @return '.$type);
            }

            $writer->indent()->writeLine(' */');

            $writer->indent()
                ->write($method->getVisibility())
                ->write(' function ')
                ->write($method->getName())
                ->write('(');

            $args = [];
            foreach ($arguments as $argument) {
                $type = $this->getType($builder->getClass(), $argument[1]);
                $item = '';
                if ($type['hint']) {
                    $item .= $type['name'].' ';
                }
                $item .= '$'.$argument[0];
                $args[] = $item;
            }

            if (count($args)) {
                $writer->write(implode(', ', $args));
            }

            $writer->writeLine(')')
                ->indent()->writeLine('{');
            foreach (explode("\n", $method->getBody()) as $bodyLine) {
                if (empty(trim($bodyLine))) {
                    $writer->writeLine();
                } else {
                    $writer->indent()->indent()->writeLine($bodyLine);
                }
            }
            $writer->indent()->writeLine('}')->writeLine();
        }
    }

    private function wrapDescription($docline)
    {
        $wrapped = wordwrap($docline, 100);

        return explode("\n", $wrapped);
    }
}
