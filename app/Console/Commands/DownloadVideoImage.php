<?php

namespace App\Console\Commands;

use App\Libs\Ecshop\Image AS EcshopImage;
use App\Libs\ImageUtils;
use App\Models\BaseModel;
use App\Models\v2\Goods;
use Illuminate\Console\Command;


set_time_limit(0); //解除PHP脚本时间30s限制
ini_set('memory_limit','256M');//修改内存值

class DownloadVideoImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download:video_image {--scope=} {--rootDit=} {--subDir=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'download video_image';

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
        $sub_dir = $this->option('subDir') ? $this->option('subDir') : 'data/images/video';

        $limit = 1000;

        $model = BaseModel::query()->from('goods as g')->where('g.is_real', 2);
        switch ($scope){
            case 'all':
                $model->where(function ($query) {
                    $query->where('g.goods_thumb', '')
                        ->orWhere('g.original_img', '');
                });
                break;
            case 'original':
                $model->where('g.original_img', '');
                break;
            case 'thumb':
                $model->where('g.goods_thumb', '');
                break;
            default:
                exit('无效范围');
        }

        $model->orderBy('g.add_time', 'DESC');

        $i = 0;  //计数
        while ($images = $model->limit($limit)->get(['g.goods_id', 'g.goods_img', 'g.goods_thumb', 'g.original_img', 'g.add_time'])->toArray())
        {
            echo "处理批次: " . ++$i;
            foreach ($images as $image){
                $date = date('Ymd', $image['add_time']);
                self::$scope($image, $root_dit, $sub_dir, $date);
            }
        }

        echo "处理成功";
        return true;
    }


    /**
     * 下载全部图片
     * @param $image array 图片信息
     * @param $root_dit string 根目录
     * @param $sub_dir string 子目录
     * @param $date string 日期  格式：20200101
     */
    private static function all($image, $root_dit, $sub_dir, $date)
    {
        self::original($image, $root_dit, $sub_dir, $date);
        self::thumb($image, $root_dit, $sub_dir, $date);
    }

    /**
     * 原图（无压缩）
     * @param $image array 图片信息
     * @param $root_dit string 根目录
     * @param $sub_dir string 子目录
     * @param $date string 日期  格式：20200101
     * @return bool
     */
    private static function original($image, $root_dit, $sub_dir, $date)
    {
        if($image['original_img']){
            return true;
        }

        $temp_dir = $root_dit . '/' . $sub_dir . '/temp/' . $date . '/' . $image['goods_id'] . '/source/';

        $goods_img = $image['goods_img'];
        // 判断是否本地图片
        if (!preg_match('/^http/', $goods_img)  && !preg_match('/^https/', $goods_img)) {
            // 拼接完整URL
            $goods_img = config('app.video_resource_url') . '/' . $goods_img;
        }

        $original_temp_filename = EcshopImage::download_image($goods_img, $temp_dir);

        if (!$original_temp_filename)
        {
            echo "原图下载失败: goods_id: " . $image['goods_id'] . "----原始图片: " . $goods_img;
            return false;
        }

        $original_temp_fullname = $temp_dir . $original_temp_filename;

        /* 重新格式化图片名称 */
        $original_url = EcshopImage::reformat_image_name('goods', $image['goods_id'], $original_temp_fullname, 'source', $root_dit, $sub_dir, $date);

        Goods::query()
            ->where('goods_id', $image['goods_id'])
            ->update([
                'original_img' => $original_url
            ]);

        return true;
    }

    /**
     * 缩略图（压缩到8KB以下）
     * @param $image array 图片信息
     * @param $root_dit string 根目录
     * @param $sub_dir string 子目录
     * @param $date string 日期  格式：20200101
     * @return bool
     */
    private static function thumb($image, $root_dit, $sub_dir, $date)
    {
        if($image['goods_thumb']){
            return true;
        }

        $temp_dir = $root_dit . '/' . $sub_dir . '/temp/' . $date . '/' . $image['goods_id'] . '/thumb/';

        $goods_img = $image['goods_img'];
        // 判断是否本地图片
        if (!preg_match('/^http/', $goods_img)  && !preg_match('/^https/', $goods_img)) {
            // 拼接完整URL
            $goods_img = config('app.video_resource_url') . '/' . $goods_img;
        }

        $thumb_temp_fullname = ImageUtils::make_thumb($goods_img, $temp_dir, 8);

        if (!$thumb_temp_fullname)
        {
            echo "缩略图下载失败: goods_id: " . $image['goods_id'] . "----原始图片: " . $goods_img;
            return false;
        }

        /* 重新格式化图片名称 */
        $thumb_url = EcshopImage::reformat_image_name('goods_thumb', $image['goods_id'], $thumb_temp_fullname, 'thumb', $root_dit, $sub_dir, $date);

        Goods::query()
            ->where('goods_id', $image['goods_id'])
            ->update([
                'goods_thumb' => $thumb_url
            ]);

        return true;
    }
}
