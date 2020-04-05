<?php

namespace App\Console\Commands;

use App\Libs\Ecshop\Image AS EcshopImage;
use App\Libs\ImageUtils;
use App\Models\BaseModel;
use App\Models\v2\Goods;
use App\Models\v2\GoodsGallery;
use Illuminate\Console\Command;


set_time_limit(0); //解除PHP脚本时间30s限制
ini_set('memory_limit','256M');//修改内存值

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

        $model = BaseModel::query()->from('goods as g')
            ->leftjoin('goods_gallery as p', 'p.goods_id','=','g.goods_id')
            ->where('g.is_real', 3);
        switch ($scope){
            case 'all':
                $model->where(function ($query) {
                    $query->where('p.thumb_url', '')
                        ->orWhere('p.download_img_original', '')
                        ->orWhere('p.img_url', 'p.img_original');
                });
                break;
            case 'original':
                $model->where('p.download_img_original', '');
                break;
            case 'normal':
                $model->where('p.img_url', 'p.img_original');
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
     * @param $date string 日期  格式：20200101
     */
    private static function all($photo, $root_dit, $sub_dir, $date)
    {
        self::original($photo, $root_dit, $sub_dir, $date);
        self::normal($photo, $root_dit, $sub_dir, $date);
        self::thumb($photo, $root_dit, $sub_dir, $date);
    }

    /**
     * 原图（无压缩）
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
     * 普通图（压缩到80KB以下）
     * @param $photo array 图片信息
     * @param $root_dit string 根目录
     * @param $sub_dir string 子目录
     * @param $date string 日期  格式：20200101
     * @return bool
     */
    private static function normal($photo, $root_dit, $sub_dir, $date)
    {
        if($photo['img_url'] && $photo['img_url'] != $photo['img_original']){
            return true;
        }

        $temp_dir = $root_dit . '/' . $sub_dir . '/temp/' . $date . '/' . $photo['goods_id'] . '/normal/';

        $thumb_temp_fullname = ImageUtils::make_thumb($photo['img_original'], $temp_dir, 80);

        if (!$thumb_temp_fullname)
        {
            return false;
        }

        /* 重新格式化图片名称 */
        $thumb_url = EcshopImage::reformat_image_name('gallery_normal', $photo['goods_id'], $thumb_temp_fullname, 'normal', $root_dit, $sub_dir, $date);

        GoodsGallery::query()
            ->where('img_id', $photo['img_id'])
            ->update([
                'img_url' => $thumb_url
            ]);

        return true;
    }

    /**
     * 缩略图（压缩到10KB以下）
     * @param $photo array 图片信息
     * @param $root_dit string 根目录
     * @param $sub_dir string 子目录
     * @param $date string 日期  格式：20200101
     * @return bool
     */
    private static function thumb($photo, $root_dit, $sub_dir, $date)
    {
        if($photo['thumb_url']){
            return true;
        }

        $temp_dir = $root_dit . '/' . $sub_dir . '/temp/' . $date . '/' . $photo['goods_id'] . '/thumb/';

        $thumb_temp_fullname = ImageUtils::make_thumb($photo['img_original'], $temp_dir, 10);

        if (!$thumb_temp_fullname)
        {
            return false;
        }

        /* 重新格式化图片名称 */
        $thumb_url = EcshopImage::reformat_image_name('gallery_thumb', $photo['goods_id'], $thumb_temp_fullname, 'thumb', $root_dit, $sub_dir, $date);

        GoodsGallery::query()
            ->where('img_id', $photo['img_id'])
            ->update([
                'thumb_url' => $thumb_url
            ]);

        if($photo['sort_order'] == 1){
            Goods::query()
                ->where('goods_id', $photo['goods_id'])
                ->update([
                    'goods_thumb' => $thumb_url
                ]);
        }

        return true;
    }
}
