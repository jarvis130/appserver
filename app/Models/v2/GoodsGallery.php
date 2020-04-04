<?php

namespace App\Models\v2;

use App\Models\BaseModel;

use App\Helper\Token;

class GoodsGallery extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'goods_gallery';
    public $timestamps = false;

    /**
     * 商品图片
     * @param  [type] $id [description]
     * @return [type]           [description]
     */
    public static function getPhotosById($id)
    {
        $goods_images = [];

        $model = self::where('goods_id', $id)->orderBy('sort_order')->get();

        if (!$model->IsEmpty()) {
            foreach ($model as $value) {
                $photo = formatPhoto($value->img_url, $value->thumb_url);
                if (is_array($photo)) {
                    $goods_images[] = $photo;
                }
            }
        }

        return $goods_images;
    }
    
    
    public static function getCategoryPhoto($cat_id)
    {
        //获取分类ids
        $cat_ids = GoodsCategory::where('parent_id', $cat_id)->orWhere('cat_id', $cat_id)->lists('cat_id')->toArray();
        if (!empty($cat_ids)) {
            $goods_id = Goods::whereIn('cat_id', $cat_ids)->where(['is_delete' => 0])->orderBy('is_hot', 'DESC')->first();
            if ($goods_id) {
                return formatPhoto($goods_id->goods_img);
            }
        }

        return null;
    }

    public function getImgUrlAttribute()
    {
        return $this->getPhotoFullUrl($this->attributes['img_url']);
    }

    public function getThumbUrlAttribute()
    {
        return $this->getPhotoFullUrl($this->attributes['thumb_url']);
    }

    public function getImgOriginalAttribute()
    {
        return $this->getPhotoFullUrl($this->attributes['img_original']);
    }

    public function getDownloadImgOriginalAttribute()
    {
        return $this->getPhotoFullUrl($this->attributes['download_img_original']);
    }

    private function getPhotoFullUrl($img)
    {
        if($img == null) {
            return '';
        }else{
            if (!preg_match('/^http/', $img)  &&!preg_match('/^https/', $img)) {
                $img = config('app.photo_resource_url').'/'.$img ;
            }
            return $img;
        }
    }
}
