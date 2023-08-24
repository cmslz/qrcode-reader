<?php
namespace Cmslz\QrcodeReader\Common;

use InvalidArgumentException;

/**
 * <p>This provides an easy abstraction to read bits at a time from a sequence of bytes, where the
 * number of bits read is not often a multiple of 8.</p>
 *
 * <p>This class is thread-safe but not reentrant -- unless the caller modifies the bytes array
 * it passed in, in which case all bets are off.</p>
 *
 */
final class BitSource
{

    private $bytes;
    private $byteOffset = 0;
    private $bitOffset = 0;

    public function __construct($bytes)
    {
        $this->bytes = $bytes;
    }

    public function getBitOffset()
    {
        return $this->bitOffset;
    }

    public function getByteOffset()
    {
        return $this->byteOffset;
    }

    public function readBits($numBits)
    {
        if ($numBits < 1 || $numBits > 32 || $numBits > $this->available()) {
            throw new InvalidArgumentException(strval($numBits));
        }

        $result = 0;

        // First, read remainder from current byte
        if ($this->bitOffset > 0) {
            $bitsLeft = 8 - $this->bitOffset;
            $toRead = min($numBits, $bitsLeft);
            $bitsToNotRead = $bitsLeft - $toRead;
            $mask = (0xFF >> (8 - $toRead)) << $bitsToNotRead;
            $result = ($this->bytes[$this->byteOffset] & $mask) >> $bitsToNotRead;
            $numBits -= $toRead;
            $this->bitOffset += $toRead;
            if ($this->bitOffset == 8) {
                $this->bitOffset = 0;
                $this->byteOffset++;
            }
        }

        // Next read whole bytes
        if ($numBits > 0) {
            while ($numBits >= 8) {
                $result = ($result << 8) | ($this->bytes[$this->byteOffset] & 0xFF);
                $this->byteOffset++;
                $numBits -= 8;
            }

            // Finally read a partial byte
            if ($numBits > 0) {
                $bitsToNotRead = 8 - $numBits;
                $mask = (0xFF >> $bitsToNotRead) << $bitsToNotRead;
                $result = ($result << $numBits) | (($this->bytes[$this->byteOffset] & $mask) >> $bitsToNotRead);
                $this->bitOffset += $numBits;
            }
        }

        return $result;
    }

    public function available()
    {
        return 8 * (count($this->bytes) - $this->byteOffset) - $this->bitOffset;
    }

}
