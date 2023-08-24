<?php

namespace Cmslz\QrcodeReader;

require_once('Qrcode/QRCodeReader.php');

interface Reader {

    public function decode($image);


    public  function reset();


}