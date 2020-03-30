<?php

namespace App\Console\Commands;

use App\Libs\Ecshop\Image AS EcshopImage;
use App\Models\v2\GoodsGallery;
use App\Models\v2\ShopConfig;
use Illuminate\Console\Command;


set_time_limit(0); //解除PHP脚本时间30s限制
ini_set('memory_limit','128M');//修改内存值

class DownloadPhoto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download:photo {--scope=} {--rootDit=} {--subDir=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'download photo';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $scope = $this->option('scope') ? $this->option('scope') : 'all';
        $root_dit = $this->option('rootDit') ? $this->option('rootDit') : dirname(base_path()) . '/ecshop';
        $sub_dir = $this->option('subDir') ? $this->option('subDir') : 'data/photo';

        $limit = 1000;

        $model = GoodsGallery::query()->from('goods as g')
            ->leftjoin('goods_gallery as p', 'p.goods_id','=','g.goods_id')
            ->where('g.is_real', 3);
        switch ($scope){
            case 'all':
                $model->where(function ($query) {
                    $query->where('p.thumb_url', '')
                        ->orWhere('p.download_img_original', '');
                });
                break;
            case 'original':
                $model->where('p.download_img_original', '');
                break;
            case 'thumb':
                $model->where('p.thumb_url', '');
                break;
            default:
                exit('无效范围');
        }

        $model->orderBy('g.add_time', 'DESC');

        while ($photos = $model->limit($limit)->get(['p.*', 'g.add_time'])->toArray())
        {
            foreach ($photos as $photo){
                $date = date('Ymd', $photo['add_time']);
                self::$scope($photo, $root_dit, $sub_dir, $date);
            }
        }

        echo "处理成功";
        return true;
    }


    /**
     * 下载全部图片
     * @param $photo array 图片信息
     * @param $root_dit string 根目录
     * @param $sub_dir string 子目录
     */
    private static function all($photo, $root_dit, $sub_dir, $date)
    {
        self::original($photo, $root_dit, $sub_dir, $date);
        self::thumb($photo, $root_dit, $sub_dir, $date);
    }

    /**
     * 下载原图
     * @param $photo array 图片信息
     * @param $root_dit string 根目录
     * @param $sub_dir string 子目录
     * @param $date string 日期  格式：20200101
     * @return bool
     */
    private static function original($photo, $root_dit, $sub_dir, $date)
    {
        if($photo['download_img_original']){
            return true;
        }

        $temp_dir = $root_dit . '/' . $sub_dir . '/temp/' . $date . '/' . $photo['goods_id'] . '/source/';

        $original_temp_filename = EcshopImage::download_image($photo['img_original'], $temp_dir);

        if (!$original_temp_filename)
        {
            return false;
        }

        $original_temp_fullname = $temp_dir . $original_temp_filename;

        /* 重新格式化图片名称 */
        $original_url = EcshopImage::reformat_image_name('gallery', $photo['goods_id'], $original_temp_fullname, 'source', $root_dit, $sub_dir, $date);

        GoodsGallery::query()
            ->where('img_id', $photo['img_id'])
            ->update([
                'download_img_original' => $original_url
            ]);

        return true;
    }

    /**
     * 下载缩略图
     * @param $photo array 图片信息
     * @param $root_dit string 根目录
     * @param $sub_dir string 子目录
     * @param $date string 日期  格式：20200101
     * @return bool
     */
    private static function thumb($photo, $root_dit, $sub_dir, $date)
    {
        if($photo['thumb_url'] && $photo['img_url']){
            return true;
        }

        $temp_dir = $root_dit . '/' . $sub_dir . '/temp/' . $date . '/' . $photo['goods_id'] . '/thumb/';

        $thumb_temp_fullname = self::make_thumb($photo['img_original'], $temp_dir);

        if (!$thumb_temp_fullname)
        {
            return false;
        }

        /* 重新格式化图片名称 */
        $thumb_url = EcshopImage::reformat_image_name('gallery_thumb', $photo['goods_id'], $thumb_temp_fullname, 'thumb', $root_dit, $sub_dir, $date);

        GoodsGallery::query()
            ->where('img_id', $photo['img_id'])
            ->update([
                'img_url' => $thumb_url,
                'thumb_url' => $thumb_url
            ]);

        return true;
    }

    /**
     * 压缩
     * @param $img string 图片
     * @param $dir string 缩略图存放路径
     * @return string
     */
    private static function make_thumb($img, $dir)
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

        // 获取图片大小
        $size = filesize($img);

        // 计算压缩比例
        if($size <= 30 * 1024) {  // 如果小于等于30K则不压缩
            $percent = 1;
        }elseif($size > 30 * 1024 && $size <= 100 * 1024) {  // 如果大于30K、小于等于100K则压缩至80%
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

        if($percent == 1){
            $thumb = $img;
        }else{
            $thumb_filename = EcshopImage::make_thumb($img, $percent, $dir);
            $img = $dir . $thumb_filename;
            $thumb = self::make_thumb($img, $dir);

            // 删除原图
            if($thumb != $img){
                @unlink($img);
            }
        }

        return $thumb;
    }
}
