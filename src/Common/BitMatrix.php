<?php

namespace Cmslz\QrcodeReader\Common;


use InvalidArgumentException;

final class BitMatrix
{
    private $width;
    private $height;
    private $rowSize;
    private $bits;

    public function __construct($width, $height = false, $rowSize = false, $bits = false)
    {
        if (!$height) {
            $height = $width;
        }
        if (!$rowSize) {
            $rowSize = intval(($width + 31) / 32);
        }
        if (!$bits) {
            $bits = fill_array(0, $rowSize * $height, 0);
        }
        $this->width = $width;
        $this->height = $height;
        $this->rowSize = $rowSize;
        $this->bits = $bits;
    }

    public static function parse($stringRepresentation, $setString, $unsetString)
    {
        if (!$stringRepresentation) {
            throw new InvalidArgumentException();
        }
        $bits = array();
        $bitsPos = 0;
        $rowStartPos = 0;
        $rowLength = -1;
        $nRows = 0;
        $pos = 0;
        while ($pos < strlen($stringRepresentation)) {
            if ($stringRepresentation[$pos] == '\n' ||
                $stringRepresentation[$pos] == '\r') {
                if ($bitsPos > $rowStartPos) {
                    if ($rowLength == -1) {
                        $rowLength = $bitsPos - $rowStartPos;
                    } else {
                        if ($bitsPos - $rowStartPos != $rowLength) {
                            throw new InvalidArgumentException("row lengths do not match");
                        }
                    }
                    $rowStartPos = $bitsPos;
                    $nRows++;
                }
                $pos++;
            } else {
                if (substr($stringRepresentation, $pos, strlen($setString)) == $setString) {
                    $pos += strlen($setString);
                    $bits[$bitsPos] = true;
                    $bitsPos++;
                } else {
                    if (substr($stringRepresentation, $pos + strlen($unsetString)) == $unsetString) {
                        $pos += strlen($unsetString);
                        $bits[$bitsPos] = false;
                        $bitsPos++;
                    } else {
                        throw new InvalidArgumentException(
                            "illegal character encountered: " . substr($stringRepresentation, $pos));
                    }
                }
            }
        }

        // no EOL at end?
        if ($bitsPos > $rowStartPos) {
            if ($rowLength == -1) {
                $rowLength = $bitsPos - $rowStartPos;
            } else {
                if ($bitsPos - $rowStartPos != $rowLength) {
                    throw new InvalidArgumentException("row lengths do not match");
                }
            }
            $nRows++;
        }

        $matrix = new BitMatrix($rowLength, $nRows);
        for ($i = 0; $i < $bitsPos; $i++) {
            if ($bits[$i]) {
                $matrix->set($i % $rowLength, $i / $rowLength);
            }
        }
        return $matrix;
    }

    /**
     * <p>Gets the requested bit, where true means black.</p>
     */
    public function get($x, $y)
    {

        $offset = intval($y * $this->rowSize + ($x / 32));
        if (!isset($this->bits[$offset])) {
            $this->bits[$offset] = 0;
        }

        // return (($this->bits[$offset] >> ($x & 0x1f)) & 1) != 0;
        return (uRShift($this->bits[$offset],
                    ($x & 0x1f)) & 1) != 0;//было >>> вместо >>, не знаю как эмулировать беззнаковый сдвиг
    }

    /**
     * <p>Sets the given bit to true.</p>
     *
     */
    public function set($x, $y)
    {
        $offset = intval($y * $this->rowSize + ($x / 32));
        if (!isset($this->bits[$offset])) {
            $this->bits[$offset] = 0;
        }
        //$this->bits[$offset] = $this->bits[$offset];

        //  if($this->bits[$offset]>200748364){
        $bob = $this->bits[$offset];
        $bob |= 1 << ($x & 0x1f);
        $this->bits[$offset] |= overflow32($bob);

        //}
//16777216
    }

    public function _unset($x, $y)
    {//было unset, php не позволяет использовать unset
        $offset = intval($y * $this->rowSize + ($x / 32));
        $this->bits[$offset] &= ~(1 << ($x & 0x1f));
    }


    /**1 << (249 & 0x1f)
     * <p>Flips the given bit.</p>
     *
     */
    public function flip($x, $y)
    {
        $offset = $y * $this->rowSize + intval($x / 32);

        $this->bits[$offset] = overflow32($this->bits[$offset] ^ (1 << ($x & 0x1f)));
    }

    /**
     * Exclusive-or (XOR): Flip the bit in this {@code BitMatrix} if the corresponding
     * mask bit is set.
     *
     * @param $mask ;  XOR mask
     */
    public function _xor($mask)
    {//было xor, php не позволяет использовать xor
        if ($this->width != $mask->getWidth() || $this->height != $mask->getHeight()
            || $this->rowSize != $mask->getRowSize()) {
            throw new InvalidArgumentException("input matrix dimensions do not match");
        }
        $rowArray = new BitArray($this->width / 32 + 1);
        for ($y = 0; $y < $this->height; $y++) {
            $offset = $y * $this->rowSize;
            $row = $mask->getRow($y, $rowArray)->getBitArray();
            for ($x = 0; $x < $this->rowSize; $x++) {
                $this->bits[$offset + $x] ^= $row[$x];
            }
        }
    }

    /**
     * Clears all bits (sets to false).
     */
    public function clear()
    {
        $max = count($this->bits);
        for ($i = 0; $i < $max; $i++) {
            $this->bits[$i] = 0;
        }
    }

    /**
     * <p>Sets a square region of the bit matrix to true.</p>
     *
     * @param $left ;  The horizontal position to begin at (inclusive)
     * @param $top ;  The vertical position to begin at (inclusive)
     * @param $width ;  The width of the region
     * @param $height ;  The height of the region
     */
    public function setRegion($left, $top, $width, $height)
    {
        if ($top < 0 || $left < 0) {
            throw new InvalidArgumentException("Left and top must be nonnative");
        }
        if ($height < 1 || $width < 1) {
            throw new InvalidArgumentException("Height and width must be at least 1");
        }
        $right = $left + $width;
        $bottom = $top + $height;
        if ($bottom > $this->height || $right > $this->width) { //> this.height || right > this.width
            throw new InvalidArgumentException("The region must fit inside the matrix");
        }
        for ($y = $top; $y < $bottom; $y++) {
            $offset = $y * $this->rowSize;
            for ($x = $left; $x < $right; $x++) {
                $this->bits[$offset + intval($x / 32)] = overflow32($this->bits[$offset + intval($x / 32)] |= 1 << ($x & 0x1f));
            }
        }
    }

    /**
     * A fast method to retrieve one row of data from the matrix as a BitArray.
     *
     * @param $y ;  The row to retrieve
     * @param $row ;  An optional caller-allocated BitArray, will be allocated if null or too small
     */
    public function getRow($y, $row)
    {
        if ($row == null || $row->getSize() < $this->width) {
            $row = new BitArray($this->width);
        } else {
            $row->clear();
        }
        $offset = $y * $this->rowSize;
        for ($x = 0; $x < $this->rowSize; $x++) {
            $row->setBulk($x * 32, $this->bits[$offset + $x]);
        }
        return $row;
    }

    /**
     * @param $y ;  row to set
     * @param $row ;  {@link BitArray} to copy from
     */
    public function setRow($y, $row)
    {
        $this->bits = array_copy($row->getBitArray(), 0, $this->bits, $y * $this->rowSize, $this->rowSize);
    }

    /**
     * Modifies this {@code BitMatrix} to represent the same but rotated 180 degrees
     */
    public function rotate180()
    {
        $width = $this->getWidth();
        $height = $this->getHeight();
        $topRow = new BitArray($width);
        $bottomRow = new BitArray($width);
        for ($i = 0; $i < ($height + 1) / 2; $i++) {
            $topRow = $this->getRow($i, $topRow);
            $bottomRow = $this->getRow($height - 1 - $i, $bottomRow);
            $topRow->reverse();
            $bottomRow->reverse();
            $this->setRow($i, $bottomRow);
            $this->setRow($height - 1 - $i, $topRow);
        }
    }

    /**
     * This is useful in detecting the enclosing rectangle of a 'pure' barcode.
     */
    public function getEnclosingRectangle()
    {
        $left = $this->width;
        $top = $this->height;
        $right = -1;
        $bottom = -1;

        for ($y = 0; $y < $this->height; $y++) {
            for ($x32 = 0; $x32 < $this->rowSize; $x32++) {
                $theBits = $this->bits[$y * $this->rowSize + $x32];
                if ($theBits != 0) {
                    if ($y < $top) {
                        $top = $y;
                    }
                    if ($y > $bottom) {
                        $bottom = $y;
                    }
                    if ($x32 * 32 < $left) {
                        $bit = 0;
                        while (($theBits << (31 - $bit)) == 0) {
                            $bit++;
                        }
                        if (($x32 * 32 + $bit) < $left) {
                            $left = $x32 * 32 + $bit;
                        }
                    }
                    if ($x32 * 32 + 31 > $right) {
                        $bit = 31;
                        while ((sdvig3($theBits, $bit)) == 0) {//>>>
                            $bit--;
                        }
                        if (($x32 * 32 + $bit) > $right) {
                            $right = $x32 * 32 + $bit;
                        }
                    }
                }
            }
        }

        $width = $right - $left;
        $height = $bottom - $top;

        if ($width < 0 || $height < 0) {
            return null;
        }

        return array($left, $top, $width, $height);
    }

    /**
     * This is useful in detecting a corner of a 'pure' barcode.
     */
    public function getTopLeftOnBit()
    {
        $bitsOffset = 0;
        while ($bitsOffset < count($this->bits) && $this->bits[$bitsOffset] == 0) {
            $bitsOffset++;
        }
        if ($bitsOffset == count($this->bits)) {
            return null;
        }
        $y = $bitsOffset / $this->rowSize;
        $x = ($bitsOffset % $this->rowSize) * 32;

        $theBits = $this->bits[$bitsOffset];
        $bit = 0;
        while (($theBits << (31 - $bit)) == 0) {
            $bit++;
        }
        $x += $bit;
        return array($x, $y);
    }

    public function getBottomRightOnBit()
    {
        $bitsOffset = count($this->bits) - 1;
        while ($bitsOffset >= 0 && $this->bits[$bitsOffset] == 0) {
            $bitsOffset--;
        }
        if ($bitsOffset < 0) {
            return null;
        }

        $y = $bitsOffset / $this->rowSize;
        $x = ($bitsOffset % $this->rowSize) * 32;

        $theBits = $this->bits[$bitsOffset];
        $bit = 31;
        while ((sdvig3($theBits, $bit)) == 0) {//>>>
            $bit--;
        }
        $x += $bit;

        return array($x, $y);
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getRowSize()
    {
        return $this->rowSize;
    }

    //@Override
    public function equals($o)
    {
        if (!($o instanceof BitMatrix)) {
            return false;
        }
        $other = $o;
        return $this->width == $other->width && $this->height == $other->height && $this->rowSize == $other->rowSize &&
            $this->bits === $other->bits;
    }

    //@Override
    public function hashCode()
    {
        $hash = $this->width;
        $hash = 31 * $hash + $this->width;
        $hash = 31 * $hash + $this->height;
        $hash = 31 * $hash + $this->rowSize;
        return 31 * $hash + hashCode($this->bits);
    }

    //@Override
    public function toString($setString = '', $unsetString = '')
    {
        if (!$setString || !$unsetString) {
            return "X " . "  ";
        }
        return $setString . $unsetString . "\n";
    }

//  @Override
    public function _clone()
    {//clone()
        return new BitMatrix($this->width, $this->height, $this->rowSize, $this->bits);
    }

}