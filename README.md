#php qr decoder
> php识别二维码, 不需要安装扩展 从哪里弄来的我也忘了，毕竟好几年了

### 安装
`composer require cmslz/qrcode-reader`

### 使用
```
include __DIR__.'/vendor/autoload.php';

$qrcode = new \Cmslz\QrcodeReader\QrReader('./qr.png');  //图片路径
$text = $qrcode->text(); //返回识别后的文本
echo $text;

$qrcode = new \Cmslz\QrcodeReader\QrReader(file_get_contents('https://pbwci.qun.hk/FmPZqFEJnFkkihvZ9wAVjKAZzOUs?imageView2/2/q/70/w/400'),
            \Cmslz\QrcodeReader\QrReader::SOURCE_TYPE_BLOB);  //图片路径
$text = $qrcode->text(); //返回识别后的文本
echo $text;
```

### 需要
```
PHP >= 5.3
GD Library
```
