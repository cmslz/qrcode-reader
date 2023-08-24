<?php

namespace Cmslz\QrcodeReader;


final class BinaryBitmap
{

    private $binary;
    private $matrix;

    public function __construct($binary)
    {
        if ($binary == null) {
            throw new \InvalidArgumentException("Binarizer must be non-null.");
        }
        $this->binary = $binary;
    }

    public function getWidth()
    {
        return $this->binary->getWidth();
    }

    public function getHeight()
    {
        return $this->binary->getHeight();
    }

    /**
     * Converts one row of luminance data to 1 bit data. May actually do the conversion, or return
     * cached data. Callers should assume this method is expensive and call it as seldom as possible.
     * This method is intended for decoding 1D barcodes and may choose to apply sharpening.
     */
    public function getBlackRow($y, $row)
    {
        return $this->binary->getBlackRow($y, $row);
    }

    /**
     * Converts a 2D array of luminance data to 1 bit. As above, assume this method is expensive
     * and do not call it repeatedly. This method is intended for decoding 2D barcodes and may or
     * may not apply sharpening. Therefore, a row from this matrix may not be identical to one
     * fetched using getBlackRow(), so don't mix and match between them.
     */
    public function getBlackMatrix()
    {
// The matrix is created on demand the first time it is requested, then cached. There are two
// reasons for this:
// 1. This work will never be done if the caller only installs 1D Reader objects, or if a
//    1D Reader finds a barcode before the 2D Readers run.
// 2. This work will only be done once even if the caller installs multiple 2D Readers.
        if ($this->matrix == null) {
            $this->matrix = $this->binary->getBlackMatrix();
        }
        return $this->matrix;
    }

    public function isCropSupported()
    {
        return $this->binary->getLuminanceSource()->isCropSupported();
    }

    /**
     * Returns a new object with cropped image data. Implementations may keep a reference to the
     * original data rather than a copy. Only callable if isCropSupported() is true.
     */
    public function crop($left, $top, $width, $height)
    {
        $newSource = $this->binary->getLuminanceSource()->crop($left, $top, $width, $height);
        return new BinaryBitmap($this->binary->createBinarizer($newSource));
    }

    public function isRotateSupported()
    {
        return $this->binary->getLuminanceSource()->isRotateSupported();
    }

    /**
     * Returns a new object with rotated image data by 90 degrees counterclockwise.
     * Only callable if {@link #isRotateSupported()} is true.
     */
    public function rotateCounterClockwise()
    {
        $newSource = $this->binary->getLuminanceSource()->rotateCounterClockwise();
        return new BinaryBitmap($this->binary->createBinarizer($newSource));
    }

    /**
     * Returns a new object with rotated image data by 45 degrees counterclockwise.
     * Only callable if {@link #isRotateSupported()} is true.
     */
    public function rotateCounterClockwise45()
    {
        $newSource = $this->binary->getLuminanceSource()->rotateCounterClockwise45();
        return new BinaryBitmap($this->binary->createBinarizer($newSource));
    }

//@Override
    public function toString()
    {
        try {
            return $this->getBlackMatrix()->toString();
        } catch (NotFoundException $e) {
            return "";
        }
    }

}
