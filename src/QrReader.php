<?php

namespace Cmslz\QrcodeReader;

include_once('Reader.php');
require_once('BinaryBitmap.php');
require_once('Common/Detector/MathUtils.php');
require_once('Common/BitMatrix.php');
require_once('Common/BitSource.php');
require_once('Common/BitArray.php');
require_once('Common/CharacterSetECI.php');//
require_once('Common/AbstractEnum.php');//
include_once('LuminanceSource.php');
include_once('GDLuminanceSource.php');
include_once('IMagickLuminanceSource.php');
include_once('Common/customFunctions.php');
include_once('Common/PerspectiveTransform.php');
include_once('Common/GridSampler.php');
include_once('Common/DefaultGridSampler.php');
include_once('Common/DetectorResult.php');
require_once('Common/ReedSolomon/GenericGFPoly.php');
require_once('Common/ReedSolomon/GenericGF.php');
include_once('Common/ReedSolomon/ReedSolomonDecoder.php');
include_once('Common/ReedSolomon/ReedSolomonException.php');

include_once('Qrcode/Decoder/Decoder.php');
include_once('ReaderException.php');
include_once('NotFoundException.php');
include_once('FormatException.php');
include_once('ChecksumException.php');
include_once('Qrcode/Detector/FinderPatternInfo.php');
include_once('Qrcode/Detector/FinderPatternFinder.php');
include_once('ResultPoint.php');
include_once('Qrcode/Detector/FinderPattern.php');
include_once('Qrcode/Detector/AlignmentPatternFinder.php');
include_once('Qrcode/Detector/AlignmentPattern.php');
include_once('Qrcode/Decoder/Version.php');
include_once('Qrcode/Decoder/BitMatrixParser.php');
include_once('Qrcode/Decoder/FormatInformation.php');
include_once('Qrcode/Decoder/ErrorCorrectionLevel.php');
include_once('Qrcode/Decoder/DataMask.php');
include_once('Qrcode/Decoder/DataBlock.php');
include_once('Qrcode/Decoder/DecodedBitStreamParser.php');
include_once('Qrcode/Decoder/Mode.php');
include_once('Common/DecoderResult.php');
include_once('Result.php');
include_once('Barbarize.php');
include_once('Common/GlobalHistogramBarbarize.php');
include_once('Common/HybridBinarizer.php');


final class QrReader
{
    const SOURCE_TYPE_FILE     = 'file';
    const SOURCE_TYPE_BLOB     = 'blob';
    const SOURCE_TYPE_RESOURCE = 'resource';

    public $result;

    function __construct($imgsource, $sourcetype = QrReader::SOURCE_TYPE_FILE, $isUseImagickIfAvailable = true)
    {

        try {
            switch ($sourcetype) {
                case QrReader::SOURCE_TYPE_FILE:
                    if ($isUseImagickIfAvailable && extension_loaded('imagick')) {
                        $im = new \Imagick();
                        $im->readImage($imgsource);
                    } else {
                        $image = file_get_contents($imgsource);
                        $im = imagecreatefromstring($image);
                    }

                    break;

                case QrReader::SOURCE_TYPE_BLOB:
                    if ($isUseImagickIfAvailable && extension_loaded('imagick')) {
                        $im = new \Imagick();
                        $im->readimageblob($imgsource);
                    } else {
                        $im = imagecreatefromstring($imgsource);
                    }

                    break;

                case QrReader::SOURCE_TYPE_RESOURCE:
                    $im = $imgsource;
                    if ($isUseImagickIfAvailable && extension_loaded('imagick')) {
                        $isUseImagickIfAvailable = true;
                    } else {
                        $isUseImagickIfAvailable = false;
                    }

                    break;
            }

            if ($isUseImagickIfAvailable && extension_loaded('imagick')) {
                $width = $im->getImageWidth();
                $height = $im->getImageHeight();
                $source = new \Cmslz\QrcodeReader\IMagickLuminanceSource($im, $width, $height);
            } else {
                $width = imagesx($im);
                $height = imagesy($im);
                $source = new \Cmslz\QrcodeReader\GDLuminanceSource($im, $width, $height);
            }
            $histo = new \Cmslz\QrcodeReader\Common\HybridBinarizer($source);
            $bitmap = new \Cmslz\QrcodeReader\BinaryBitmap($histo);
            $reader = new \Cmslz\QrcodeReader\Qrcode\QRCodeReader();

            $this->result = $reader->decode($bitmap);
        } catch (\Cmslz\QrcodeReader\NotFoundException $er) {
            $this->result = false;
        } catch (\Cmslz\QrcodeReader\FormatException $er) {
            $this->result = false;
        } catch (\Cmslz\QrcodeReader\ChecksumException $er) {
            $this->result = false;
        }
    }

    public function text()
    {
        if ($this->result && method_exists($this->result, 'toString')) {
            return ($this->result->toString());
        }
        return $this->result;
    }

    public function decode()
    {
        return $this->text();
    }
}

