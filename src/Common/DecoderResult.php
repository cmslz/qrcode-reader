<?php

namespace Cmslz\QrcodeReader\Common;


final class DecoderResult
{

    private $rawBytes;
    private $text;
    private $byteSegments;
    private $ecLevel;
    private $errorsCorrected;
    private $erasures;
    private $other;
    private $structuredAppendParity;
    private $structuredAppendSequenceNumber;


    public function __construct(
        $rawBytes,
        $text,
        $byteSegments,
        $ecLevel,
        $saSequence = -1,
        $saParity = -1
    ) {
        $this->rawBytes = $rawBytes;
        $this->text = $text;
        $this->byteSegments = $byteSegments;
        $this->ecLevel = $ecLevel;
        $this->structuredAppendParity = $saParity;
        $this->structuredAppendSequenceNumber = $saSequence;
    }

    public function getRawBytes()
    {
        return $this->rawBytes;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getByteSegments()
    {
        return $this->byteSegments;
    }

    public function getECLevel()
    {
        return $this->ecLevel;
    }

    public function getErrorsCorrected()
    {
        return $this->errorsCorrected;
    }

    public function setErrorsCorrected($errorsCorrected)
    {
        $this->errorsCorrected = $errorsCorrected;
    }

    public function getErasures()
    {
        return $this->erasures;
    }

    public function setErasures($erasures)
    {
        $this->erasures = $erasures;
    }

    public function getOther()
    {
        return $this->other;
    }

    public function setOther($other)
    {
        $this->other = $other;
    }

    public function hasStructuredAppend()
    {
        return $this->structuredAppendParity >= 0 && $this->structuredAppendSequenceNumber >= 0;
    }

    public function getStructuredAppendParity()
    {
        return $this->structuredAppendParity;
    }

    public function getStructuredAppendSequenceNumber()
    {
        return $this->structuredAppendSequenceNumber;
    }

}