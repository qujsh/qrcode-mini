<?php

//初始数据准备
define('PATH', "./".date("Y-m-d")."/");

/**
 * 创建一个文件夹，
 * @param $dir
 * @return bool
 */
function createDir($dir)
{
    if (!file_exists($dir)){
        return mkdir ($dir,0777,true);
    } else {
        return true;
    }
}

/**
 * 存储 网络图片到本地
 * @param $img
 * @param string $filename
 * @return bool|int
 */
function saveImg($img, $filename='')
{
    //保存原始头像
    $img_file = file_get_contents($img);               //小程序传的头像是网络地址需要周转一下
    $img_content= base64_encode($img_file);
    $file_tou_name = time();
    if ($filename)
        $file_tou_name = $filename;
    $file_tou_name .= ".png";

    $headurl = PATH.$file_tou_name;
    if (file_put_contents($headurl, base64_decode($img_content)))
        return $headurl;

    return false;
}

/**
 * 剪切头像为圆形
 * @param $img_path
 * @param string $filename
 * @return resource
 */
function roundImg($img_path, $filename='') {
    $ename = getimagesize($img_path);
    $ename = explode('/', $ename['mime']);
    $ext = $ename[1];

    $src_img = null;
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $src_img = imagecreatefromjpeg($img_path);
            break;
        case 'png':
            $src_img = imagecreatefrompng($img_path);
            break;
        case 'gif':
            $src_img = imagecreatefromgif($img_path);
            break;
    }
    $wh  = getimagesize($img_path);
    $w   = $wh[0];
    $h   = $wh[1];
    $w   = min($w, $h);
    $h   = $w;
    $img = imagecreatetruecolor($w, $h);
    //这一句一定要有
    imagesavealpha($img, true);
    //拾取一个完全透明的颜色,最后一个参数127为全透明
    $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
    imagefill($img, 0, 0, $bg);
    $r   = $w / 2; //圆半径
    $y_x = $r; //圆心X坐标
    $y_y = $r; //圆心Y坐标
    for ($x = 0; $x < $w; $x++) {
        for ($y = 0; $y < $h; $y++) {
            $rgbColor = imagecolorat($src_img, $x, $y);
            if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                imagesetpixel($img, $x, $y, $rgbColor);
            }
        }
    }

    $watermark_img = "round".time();

    if ($filename)
        $watermark_img = $filename;
    $watermark_img = PATH.$watermark_img.".png";

    imagepng($img, $watermark_img);
    imagedestroy($img);

    return $watermark_img;
}

/**
 * @param $qrcode_img
 * @param $watermark_img
 * @param string $filename
 * @return string
 */
function scaleRoundImg($qrcode_img, $watermark_img, $filename='')
{
    list($dwidth, $dheight, $dtype) = getimagesize($qrcode_img);
    list($wwidth, $wheight, $wtype) = getimagesize($watermark_img);
    $lwidth = 193;          //现设置 微信中心图大小
    $proportion = $lwidth/430;          //小程序码430， 中间192的图标大小，获取比例，比 192稍微大一点 为了覆盖完全，设置为193

    //430的小程序码， 192的中间logo
    $target_im = imagecreatetruecolor($dwidth*$proportion, $dheight*$proportion);     //创建一个新的画布（缩放后的），从左上角开始填充透明背景
    imagesavealpha($target_im, true);
    $trans_colour = imagecolorallocatealpha($target_im, 0, 0, 0, 127);
    imagefill($target_im, 0, 0, $trans_colour);

    $o_image = imagecreatefrompng($watermark_img);                 //获取上文已保存的修改之后头像的内容
    imagecopyresampled($target_im, $o_image, 0, 0,0, 0, $dwidth*$proportion, $dheight*$proportion, $wwidth, $wheight);

    $comp_path = "scale".time();
    if ($filename)
        $comp_path = $filename;
    $comp_path = PATH.$comp_path.".png";

    imagepng($target_im, $comp_path);
    imagedestroy($target_im);

    return $comp_path;
}

/**
 * 使用添加水印的方式，将需要剪圆的图片 添加到小图片中间
 * @param $dest_image       需要添加水印的图片，这儿就是小程序码
 * @param $watermark        水印图片，这儿就是中间那张原图
 * @param string $locate 添加水印位置， center,left_buttom,right_buttom
 * @param int $cut 水印位置 需要做的迁移调整， 如：192的中心图大小，设置193的圆图大小，cut为1，需要做 -0.5特殊处理
 * @param string $filename
 * @return string
 */
function createImgWatermark($dest_image, $watermark, $locate='center', $cut=1, $filename=''){
    list($dwidth, $dheight, $dtype) = getimagesize($dest_image);
    list($wwidth, $wheight, $wtype) = getimagesize($watermark);
    $types = array(1 => "GIF", 2 => "JPEG", 3 => "PNG",
        4 => "SWF", 5 => "PSD", 6 => "BMP",
        7 => "TIFF", 8 => "TIFF", 9 => "JPC",
        10 => "JP2", 11 => "JPX", 12 => "JB2",
        13 => "SWC", 14 => "IFF", 15 => "WBMP", 16 => "XBM");
    $dtype = strtolower($types[$dtype]);//原图类型
    $wtype = strtolower($types[$wtype]);//水印图片类型
    $created = "imagecreatefrom".$dtype;
    $createw = "imagecreatefrom".$wtype;
    $imgd = $created($dest_image);
    $imgw = $createw($watermark);
    switch($locate){
        case 'center':
            $x = ($dwidth-$wwidth)/2 - $cut/2;      //特殊处理，
            $y = ($dheight-$wheight)/2 - $cut/2;
            break;
        case 'left_buttom':
            $x = 1;
            $y = ($dheight-$wheight-2);
            break;
        case 'right_buttom':
            $x = ($dwidth-$wwidth-1);
            $y = ($dheight-$wheight-2);
            break;
        default:
            die("未指定水印位置!");
            break;
    }
    imagecopy($imgd, $imgw, $x, $y,0,0, $wwidth,$wheight);
    $save = "image".$dtype;
    //保存到服务器

    $f_file_name = "water".time();
    if ($filename)
        $f_file_name = $filename;
    $f_file_name .= ".png";

    imagepng($imgd,PATH.$f_file_name);          //保存
    imagedestroy($imgw);
    imagedestroy($imgd);

    //传回处理好的图片
//    $url = 'https://www.qubaobei.com/'.str_replace('/opt/ci123/www/html/markets/app2/baby/','',PATH.$f_file_name);
    $url = PATH.$f_file_name;
    return $url;
}

/**
 * curl post 请求
 * @param $url
 * @param $data
 * @return mixed
 */
function postCurl($url,$data)
{
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检测
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:')); //解决数据包大不能提交
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
        echo 'Errno'.curl_error($curl);
    }
    curl_close($curl); // 关键CURL会话
    return $tmpInfo; // 返回数据
}


