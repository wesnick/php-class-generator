<?php
/**
 * This software is licensed under MIT License.
 *
 * Copyright (c) 2012, Kazuyuki Hayashi
 */
namespace KzykHys\ClassGenerator\Builder;

/**
 * Represents a PHP class.
 *
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
class ClassBuilder
{
    private $class;
    private $extends = null;
    private $interfaces;
    private $imports;
    private $docblock;
    private $constants;
    private $properties;
    private $methods;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->interfaces = [];
        $this->imports    = [];
        $this->constants  = [];
        $this->properties = [];
        $this->methods    = [];
    }

    /**
     * Sets class name.
     *
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * Gets class name.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    public function setExtends($extends)
    {
        $this->extends = $extends;
    }

    public function getExtends()
    {
        return $this->extends;
    }

    public function addInterface($interface)
    {
        $this->interfaces[] = $interface;
    }

    public function getInterfaces()
    {
        return $this->interfaces;
    }

    public function addImports($imports)
    {
        $this->imports[] = $imports;
    }

    public function getImports()
    {
        return array_unique($this->imports);
    }

    public function setDocblock($docblock)
    {
        $this->docblock = $docblock;
    }

    public function getDocblock()
    {
        return $this->docblock;
    }

    public function addConstant($key, $value)
    {
        if (!preg_match('~^[a-zA-Z_]{1}[a-zA-Z0-9_]{0,}$~', $key)) {
            throw new \InvalidArgumentException(sprintf('%s has an invalid constant character.', $key));
        }
        $this->constants[$key] = $value;
    }

    public function getConstants()
    {
        return $this->constants;
    }

    /**
     * @return mixed
     */
    public function getConstantMaxLength()
    {
        $length = 0;

        foreach ($this->constants as $key => $value) {
            $length = (strlen($key) > $length) ? strlen($key) : $length;
        }

        return $length;
    }

    public function addProperty(PropertyBuilder $builder)
    {
        $this->properties[] = $builder;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function addMethod(MethodBuilder $builder)
    {
        $this->methods[] = $builder;
    }

    public function getMethods()
    {
        // Add property getters and setters
        /** @var $property PropertyBuilder */
        foreach ($this->properties as $property) {
            foreach ($property->getAccessors() as $access) {
                $methodBuilder = new MethodBuilder();
                $name          = $access.ucfirst($property->getName());
                $methodBuilder->setName($name);
                $methodBuilder->setVisibility('public');

                if ('get' === $access) {
                    $methodBuilder->setType($property->getType());
                    $setterBody = sprintf('return $this->%s;', $property->getName());
                    $methodBuilder->setBody($setterBody);
                }

                if ('set' === $access) {
                    $methodBuilder->addArgument([$property->getName(), $property->getType()]);
                    $setter     = sprintf('$this->%s = $%s;', $property->getName(), $property->getName());
                    $setterBody = <<<EOF
$setter
        return \$this;
EOF;
                    $methodBuilder->setBody($setterBody);
                    $methodBuilder->setType($this->getClass());
                }

                $this->methods[] = $methodBuilder;
            }
        }

        return $this->methods;
    }
}
