<?php

namespace App\Libs\Ecshop;

/**
 * ECSHOP 后台对上传文件的处理类(实现图片上传，图片缩小， 增加水印)   有修改
 *
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: Image.php 17217 2011-01-19 06:29:08Z liubo $
*/

class Image
{
    /**
     * 创建图片的缩略图
     *
     * @access  public
     * @param   string      $img    原始图片的路径
     * @param   int         $thumb_width  缩略图宽度
     * @param   int         $thumb_height 缩略图高度
     * @param   string      $path         指定生成图片的目录名
     * @return  mixed         如果成功返回缩略图的文件名，失败则返回false
     */
    public static function make_thumb($img, $thumb_width = 0, $thumb_height = 0, $path = '', $bgcolor='#FFFFFF')
    {
         $gd = self::gd_version(); //获取 GD 版本。0 表示没有 GD 库，1 表示 GD 1.x，2 表示 GD 2.x
         if ($gd == 0)
         {
             return false;
         }

         if(empty($path))
         {
             return false;
         }

        /* 检查缩略图宽度和高度是否合法 */
        if ($thumb_width == 0 && $thumb_height == 0)
        {
            return false;
        }

        /* 检查原始文件是否存在及获得原始文件的信息 */
        $org_info = @getimagesize($img);
        if (!$org_info)
        {
            return false;
        }

        if (!self::check_img_function($org_info[2]))
        {
            return false;
        }

        $img_org = self::img_resource($img, $org_info[2]);

        /* 原始图片以及缩略图的尺寸比例 */
        $scale_org      = $org_info[0] / $org_info[1];
        /* 处理只有缩略图宽和高有一个为0的情况，这时背景和缩略图一样大 */
        if ($thumb_width == 0)
        {
            $thumb_width = $thumb_height * $scale_org;
        }
        if ($thumb_height == 0)
        {
            $thumb_height = $thumb_width / $scale_org;
        }

        /* 创建缩略图的标志符 */
        if ($gd == 2)
        {
            $img_thumb  = imagecreatetruecolor($thumb_width, $thumb_height);
        }
        else
        {
            $img_thumb  = imagecreate($thumb_width, $thumb_height);
        }

        /* 背景颜色 */
        $bgcolor = trim($bgcolor,"#");
        sscanf($bgcolor, "%2x%2x%2x", $red, $green, $blue);
        $clr = imagecolorallocate($img_thumb, $red, $green, $blue);
        imagefilledrectangle($img_thumb, 0, 0, $thumb_width, $thumb_height, $clr);

        if ($org_info[0] / $thumb_width > $org_info[1] / $thumb_height)
        {
            $lessen_width  = $thumb_width;
            $lessen_height  = $thumb_width / $scale_org;
        }
        else
        {
            /* 原始图片比较高，则以高度为准 */
            $lessen_width  = $thumb_height * $scale_org;
            $lessen_height = $thumb_height;
        }

        $dst_x = ($thumb_width  - $lessen_width)  / 2;
        $dst_y = ($thumb_height - $lessen_height) / 2;

        /* 将原始图片进行缩放处理 */
        if ($gd == 2)
        {
            imagecopyresampled($img_thumb, $img_org, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $org_info[0], $org_info[1]);
        }
        else
        {
            imagecopyresized($img_thumb, $img_org, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $org_info[0], $org_info[1]);
        }

        $dir = $path;

        /* 如果目标目录不存在，则创建它 */
        if (!file_exists($dir))
        {
            if (!self::make_dir($dir))
            {
                /* 创建目录失败 */
                return false;
            }
        }

        /* 如果文件名为空，生成不重名随机文件名 */
        $filename = self::unique_name($dir);

        /* 生成文件 */
        if (function_exists('imagejpeg'))
        {
            $filename .= '.jpg';
            imagejpeg($img_thumb, $dir . $filename);
        }
        elseif (function_exists('imagegif'))
        {
            $filename .= '.gif';
            imagegif($img_thumb, $dir . $filename);
        }
        elseif (function_exists('imagepng'))
        {
            $filename .= '.png';
            imagepng($img_thumb, $dir . $filename);
        }
        else
        {
            return false;
        }

        imagedestroy($img_thumb);
        imagedestroy($img_org);

        //确认文件是否生成
        if (file_exists($dir . $filename))
        {
            return $filename;
        }
        else
        {
            return false;
        }
    }

    /*------------------------------------------------------ */
    //-- 工具函数
    /*------------------------------------------------------ */

    /**
     * 检查图片处理能力
     *
     * @access  public
     * @param   string  $img_type   图片类型
     * @return  bool
     */
    public static function check_img_function($img_type)
    {
        switch ($img_type)
        {
            case 'image/gif':
            case 1:

                if (PHP_VERSION >= '4.3')
                {
                    return function_exists('imagecreatefromgif');
                }
                else
                {
                    return (imagetypes() & IMG_GIF) > 0;
                }
            break;

            case 'image/pjpeg':
            case 'image/jpeg':
            case 2:
                if (PHP_VERSION >= '4.3')
                {
                    return function_exists('imagecreatefromjpeg');
                }
                else
                {
                    return (imagetypes() & IMG_JPG) > 0;
                }
            break;

            case 'image/x-png':
            case 'image/png':
            case 3:
                if (PHP_VERSION >= '4.3')
                {
                     return function_exists('imagecreatefrompng');
                }
                else
                {
                    return (imagetypes() & IMG_PNG) > 0;
                }
            break;

            default:
                return false;
        }
    }

    /**
     * 生成随机的数字串
     *
     * @author: weber liu
     * @return string
     */
    public static function random_filename()
    {
        $str = '';
        for($i = 0; $i < 9; $i++)
        {
            $str .= mt_rand(0, 9);
        }

        return time() . $str;
    }

    /**
     *  生成指定目录不重名的文件名
     *
     * @access  public
     * @param   string      $dir        要检查是否有同名文件的目录
     *
     * @return  string      文件名
     */
    public static function unique_name($dir)
    {
        $filename = '';
        while (empty($filename))
        {
            $filename = self::random_filename();
            if (file_exists($dir . $filename . '.jpg') || file_exists($dir . $filename . '.gif') || file_exists($dir . $filename . '.png'))
            {
                $filename = '';
            }
        }

        return $filename;
    }

    /**
     *  返回文件后缀名，如‘.php’
     *
     * @access  public
     * @param
     *
     * @return  string      文件后缀名
     */
    public static function get_filetype($path)
    {
        $pos = strrpos($path, '.');
        if ($pos !== false)
        {
            return substr($path, $pos);
        }
        else
        {
            return '';
        }
    }

     /**
     * 根据来源文件的文件类型创建一个图像操作的标识符
     *
     * @access  public
     * @param   string      $img_file   图片文件的路径
     * @param   string      $mime_type  图片文件的文件类型
     * @return  resource    如果成功则返回图像操作标志符，反之则返回错误代码
     */
    public static function img_resource($img_file, $mime_type)
    {
        switch ($mime_type)
        {
            case 1:
            case 'image/gif':
                $res = imagecreatefromgif($img_file);
                break;

            case 2:
            case 'image/pjpeg':
            case 'image/jpeg':
                $res = imagecreatefromjpeg($img_file);
                break;

            case 3:
            case 'image/x-png':
            case 'image/png':
                $res = imagecreatefrompng($img_file);
                break;

            default:
                return false;
        }

        return $res;
    }

    /**
     * 获得服务器上的 GD 版本
     *
     * @access      public
     * @return      int         可能的值为0，1，2
     */
    public static function gd_version()
    {
        static $version = -1;

        if ($version >= 0)
        {
            return $version;
        }

        if (!extension_loaded('gd'))
        {
            $version = 0;
        }
        else
        {
            // 尝试使用gd_info函数
            if (PHP_VERSION >= '4.3')
            {
                if (function_exists('gd_info'))
                {
                    $ver_info = gd_info();
                    preg_match('/\d/', $ver_info['GD Version'], $match);
                    $version = $match[0];
                }
                else
                {
                    if (function_exists('imagecreatetruecolor'))
                    {
                        $version = 2;
                    }
                    elseif (function_exists('imagecreate'))
                    {
                        $version = 1;
                    }
                }
            }
            else
            {
                if (preg_match('/phpinfo/', ini_get('disable_functions')))
                {
                    /* 如果phpinfo被禁用，无法确定gd版本 */
                    $version = 1;
                }
                else
                {
                  // 使用phpinfo函数
                   ob_start();
                   phpinfo(8);
                   $info = ob_get_contents();
                   ob_end_clean();
                   $info = stristr($info, 'gd version');
                   preg_match('/\d/', $info, $match);
                   $version = $match[0];
                }
             }
        }

        return $version;
     }

    /**
     * 下载远程图片
     * @param $url
     * @param string $dir
     * @return string
     */
    public static function download_image($url, $dir = '')
    {
        if (!self::make_dir($dir))
        {
            /* 创建目录失败 */
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);

        $filename = pathinfo($url, PATHINFO_BASENAME);
        $resource = fopen($dir . $filename, 'a');
        fwrite($resource, $file);
        fclose($resource);

        //确认文件是否生成
        if (file_exists($dir . $filename))
        {
            return $filename;
        }
        else
        {
            return false;
        }
    }

    /**
     * 格式化图片名称（按目录存储）
     *
     */
    public static function reformat_image_name($type, $goods_id, $source_img, $position, $root_dit, $sub_dir, $date = null)
    {
        $rand_name = time() . sprintf("%03d", mt_rand(1,999));
        $img_ext = substr($source_img, strrpos($source_img, '.'));

        if(empty($date)){
            $date = date('Ymd');
        }
        $sub_sub_dir = $date . '/' . $goods_id;

        $full_dir = $root_dit.'/'.$sub_dir.'/'.$sub_sub_dir;
        $format_dir = $sub_dir.'/'.$sub_sub_dir;

        if (!self::make_dir($full_dir))
        {
            return false;
        }
        if (!self::make_dir($full_dir.'/source_img'))
        {
            return false;
        }
        if (!self::make_dir($full_dir.'/thumb_img'))
        {
            return false;
        }
        switch($type)
        {
            case 'goods':
                $img_name = $goods_id . '_G_' . $rand_name;
                break;
            case 'goods_thumb':
                $img_name = $goods_id . '_thumb_G_' . $rand_name;
                break;
            case 'gallery':
                $img_name = $goods_id . '_P_' . $rand_name;
                break;
            case 'gallery_thumb':
                $img_name = $goods_id . '_thumb_P_' . $rand_name;
                break;
        }
        if ($position == 'source')
        {
            if (self::move_image_file($source_img, $full_dir.'/source_img/'.$img_name.$img_ext))
            {
                return $format_dir.'/source_img/'.$img_name.$img_ext;
            }
        }
        elseif ($position == 'thumb')
        {
            if (self::move_image_file($source_img, $full_dir.'/thumb_img/'.$img_name.$img_ext))
            {
                return $format_dir.'/thumb_img/'.$img_name.$img_ext;
            }
        }
        return false;
    }

    public static function move_image_file($source, $dest)
    {
        if (@copy($source, $dest))
        {
            @unlink($source);
            return true;
        }
        return false;
    }

    /**
     * 检查目标文件夹是否存在，如果不存在则自动创建该目录
     *
     * @access      public
     * @param       string      folder     目录路径。不能使用相对于网站根目录的URL
     *
     * @return      bool
     */
    public static function make_dir($folder)
    {
        $reval = false;

        if (!file_exists($folder))
        {
            /* 如果目录不存在则尝试创建该目录 */
            @umask(0);

            /* 将目录路径拆分成数组 */
            preg_match_all('/([^\/]*)\/?/i', $folder, $atmp);

            /* 如果第一个字符为/则当作物理路径处理 */
            $base = ($atmp[0][0] == '/') ? '/' : '';

            /* 遍历包含路径信息的数组 */
            foreach ($atmp[1] AS $val)
            {
                if ('' != $val)
                {
                    $base .= $val;

                    if ('..' == $val || '.' == $val)
                    {
                        /* 如果目录为.或者..则直接补/继续下一个循环 */
                        $base .= '/';

                        continue;
                    }
                }
                else
                {
                    continue;
                }

                $base .= '/';

                if (!file_exists($base))
                {
                    /* 尝试创建目录，如果创建失败则继续循环 */
                    if (@mkdir(rtrim($base, '/'), 0777))
                    {
                        @chmod($base, 0777);
                        $reval = true;
                    }
                }
            }
        }
        else
        {
            /* 路径已经存在。返回该路径是不是一个目录 */
            $reval = is_dir($folder);
        }

        clearstatcache();

        return $reval;
    }
}

?>