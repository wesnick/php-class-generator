<?php

namespace Test;

use JsonSerializable;
use stdClass;

/**
 * Represents a PHP Test class.
 *
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
class TestClass extends stdClass implements JsonSerializable
{
    const MY_CONSTANT = 'constant';

    /**
     * This is a default scalar
     *
     * @var string
     */
    public $scalar = 'default';

    /**
     * returns an array for json_serialize function
     *
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'key' => 'value',
            'key2' => 'value2',
        ];
    }

    /**
     *
     * @return string
     */
    public function getScalar()
    {
        return $this->scalar;
    }

    /**
     * @param string $scalar
     *
     * @return $this
     */
    public function setScalar($scalar)
    {
        $this->scalar = $scalar;

        return $this;
    }

}
