<?php
require './function.php';

//$shop_logo = 'https://jmttest.oss-cn-hangzhou.aliyuncs.com/AABaJjb1ZntDfsau.png';

$qrcode_url = './shop_id.png';
$shop_logo = './shop_logo.png';

if (createDir(PATH))    //确定文件夹存在
{
    //将图片保存到本地
    $qrcode_img = saveImg($qrcode_url, 'shop_id');
    $shop_img = saveImg($shop_logo, 'shop_logo');

    $watermark_img = roundImg($shop_img, 'shop_id1');     //缩成圆图，

    list($dwidth, $dheight, $dtype) = getimagesize($qrcode_img);
    list($wwidth, $wheight, $wtype) = getimagesize($watermark_img);

    $comp_path = scaleRoundImg($qrcode_img, $watermark_img, 'shop_id2');        //将圆图，按比例缩小

    //传入保存后的二维码地址
    $url = createImgWatermark($qrcode_img, $comp_path,"center", $dwidth/430, 'shop_id3');

    echo $url;
}



