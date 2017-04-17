<?php
/**
 * This software is licensed under MIT License.
 *
 * Copyright (c) 2012, Kazuyuki Hayashi
 */
namespace KzykHys\ClassGenerator;

use KzykHys\ClassGenerator\Builder\ClassBuilder;
use KzykHys\ClassGenerator\Compiler\StreamWriter;

/**
 * Generates PHP classes from plain text document (*.pcg).
 *
 * @author    Kazuyuki Hayashi <hayashi@valnur.net>
 * @copyright Copyright (c) 2012, Kazuyuki Hayashi
 * @license   http://opensource.org/licenses/mit-license.php MIT License
 */
class ClassGenerator
{
    const VERSION = '1.0.0';

    private $options;
    /**
     * @var ClassBuilder
     */
    private $classBuilder;
    /**
     * @var StreamWriter
     */
    private $streamWriter;

    /**
     * Constructor.
     *
     * @param array $options Options
     */
    public function __construct($options = [])
    {
        $this->options = array_merge([
            'indent_spaces' => '4',
            'lineFeed'      => "\n",
        ], $options);
    }

    /**
     * Returns ClassBuilder.
     *
     * @return \KzykHys\ClassGenerator\Builder\ClassBuilder
     */
    public function getClassBuilder()
    {
        return $this->classBuilder;
    }

    /**
     * Returns StreamWriter.
     *
     * @return StreamWriter
     */
    public function getStreamWriter()
    {
        return $this->streamWriter;
    }

    /**
     * Returns PHP class as a string.
     *
     * @return string
     */
    public function getString()
    {
        return (string) $this->streamWriter;
    }

    /**
     * Writes PHP class as a file.
     */
    public function write($filename)
    {
        $this->streamWriter->save($filename);
    }
}
