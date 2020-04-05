<?php

namespace App\Libs;

use App\Libs\Ecshop\Image as EcshopImage;

/**
 * 图片工具类
 */

class ImageUtils {

    /**
     * 压缩
     * @param $img string 图片
     * @param $dir string 缩略图存放路径
     * @param $max_size int 图片最大尺寸（KB）
     * @return string
     */
    public static function make_thumb($img, $dir, $max_size)
    {
        // 判断是否本地图片
        if (!preg_match('/^http/', $img)  && !preg_match('/^https/', $img)) {
            $is_local_img = true;
        } else {
            $is_local_img = false;
        }

        // 网络图片需要先下载
        if(!$is_local_img){
            $thumb_filename = EcshopImage::download_image($img, $dir);
            if(!$thumb_filename){
                return false;
            }
            $img = $dir . $thumb_filename;
        }

        $percent = self::get_thumb_percent($img, $max_size);

        if($percent == 1){
            $thumb = $img;
        }else{
            $thumb_filename = EcshopImage::make_thumb($img, $percent, $dir);

            // 删除原图
            @unlink($img);
            if(!$thumb_filename){
                return false;
            }

            $img = $dir . $thumb_filename;
            $thumb = self::make_thumb($img, $dir, $max_size);
        }

        return $thumb;
    }

    /**
     * 获取压缩比例
     * @param $img string 图片
     * @param $max_size int 图片最大尺寸（KB）
     * @return float
     */
    private static function get_thumb_percent($img, $max_size = null)
    {
        if(empty($max_size)){
            return 1;
        }

        // 获取图片大小
        $size = filesize($img);

        // 计算压缩比例
        if($size <= $max_size * 1024) {  // 如果小于等于$max_size（K）则不压缩
            $percent = 1;
        }elseif($size > $max_size * 1024 && $size <= 100 * 1024) {  // 如果大于$max_size（K）、小于等于100K则压缩至80%
            $percent = 0.8;
        }elseif($size > 100 * 1024 && $size <= 1000 * 1024) {  // 如果大于100K、小于等于1M则压缩至60%
            $percent = 0.6;
        }elseif($size > 1000 * 1024 && $size <= 2000 * 1024) {  // 如果大于1M、小于等于2M则压缩至40%
            $percent = 0.4;
        }elseif($size > 2000 * 1024 && $size <= 3000 * 1024) {  // 如果大于1M、小于等于3M则压缩至20%
            $percent = 0.2;
        }else{  // 如果大于3M则压缩至10%
            $percent = 0.1;
        }

        return $percent;
    }
}

?>