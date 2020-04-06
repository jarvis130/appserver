<?php

namespace App\Models\v2;

use App\Models\BaseModel;

class Banner extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'banners';
    public $timestamps = false;
    protected $guarded    = [];
    protected $appends    = ['id', 'photo', 'link', 'title', 'type'];
    protected $visible    = ['id', 'photo', 'link', 'title', 'type'];

    /**
     * 获取轮播图列表
     * @return array
     */
    public static function getCarouselList()
    {
        $model = self::where('scene', 1)->orderBy('sort');

        $data = $model->get()->toArray();
        return self::formatBody(['banners' => $data]);
    }

    /**
     * 获取广告位列表
     * @return array
     */
    public static function getAdsList(array $attributes)
    {
        extract($attributes);

        if(empty($type) || $type == 1){  // 首页广告位
            $scene = 2;
            $data_key = 'home_ad';
        }else{  // 详情页广告位
            $scene = 3;
            $data_key = 'detail_ad';
        }

        $model = self::where('scene', $scene)->orderBy('sort');

        $data = array(
            $data_key . '1' => [],
            $data_key . '2' => [],
            $data_key . '3' => []
        );
        $banners = $model->get()->groupBy('group')->toArray();
        ksort($banners);

        $i = 0;
        foreach ($banners as $banner){
            $i += 1;
            $data[$data_key . $i] = $banner;
        }

        return self::formatBody($data);
    }

    public function getIdAttribute()
    {
        return $this->attributes['id'];
    }

    public function getPhotoAttribute()
    {
        return formatPhoto($this->attributes['src']);
    }

    public function getLinkAttribute()
    {
        return $this->attributes['url'];
    }

    public function getTitleAttribute()
    {
        return $this->attributes['text'];
    }

    public function getTypeAttribute()
    {
        return $this->attributes['type'];
    }
}
