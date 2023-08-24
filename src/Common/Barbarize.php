<?php

namespace Cmslz\QrcodeReader;

abstract class Barbarize
{

    private $source;

    protected function __construct($source)
    {
        $this->source = $source;
    }

    public final function getLuminanceSource()
    {
        return $this->source;
    }

    public abstract function getBlackRow($y, $row);

    public abstract function getBlackMatrix();

    public abstract function createBinarizer($source);

    public final function getWidth()
    {
        return $this->source->getWidth();
    }

    public final function getHeight()
    {
        return $this->source->getHeight();
    }

}
