<?php

namespace Cmslz\QrcodeReader\Common;

use Cmslz\QrcodeReader\ResultPoint;

/**
 * <p>Encapsulates the result of detecting a barcode in an image. This includes the raw
 * matrix of black/white pixels corresponding to the barcode, and possibly points of interest
 * in the image, like the location of finder patterns or corners of the barcode in the image.</p>
 *
 * @author Sean Owen
 */
class DetectorResult {

    private  $bits;
    private  $points;

    public function __construct($bits, $points) {
        $this->bits = $bits;
        $this->points = $points;
    }

    public final function getBits() {
        return $this->bits;
    }

    public final function getPoints() {
        return $this->points;
    }

}