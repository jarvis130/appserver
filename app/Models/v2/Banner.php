<?php

namespace App\Models\v2;

use App\Models\BaseModel;

class Banner extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'banners';
    public $timestamps = false;
    protected $guarded    = [];
    protected $appends    = ['id', 'scene', 'photo', 'link', 'title', 'type'];
    protected $visible    = ['id', 'scene', 'photo', 'link', 'title', 'type'];

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
    public static function getAdsList()
    {
        $homeAdScene = 2;  // 首页广告位场景值
        $detailAdScene = 3;  // 详情页广告位场景值

        $model = self::whereIn('scene', [$homeAdScene, $detailAdScene])->orderBy('sort');

        $data = array(
            'home_ad1' => [],
            'home_ad2' => [],
            'home_ad3' => [],
            'detail_ad1' => [],
        );
        $banners = $model->get()->groupBy('scene', 'group')->toArray();

        $homeAds = $banners[$homeAdScene];
        $detailAds = $banners[$detailAdScene];

        ksort($homeAds);
        ksort($detailAds);

        $hi = 0;
        foreach ($homeAds as $homeAd){
            $hi += 1;
            $data['home_ad' . $hi] = $homeAd;
        }

        $di = 0;
        foreach ($detailAds as $detailAd){
            $di += 1;
            $data['detail_ad' . $di] = $detailAd;
        }

        return self::formatBody($data);
    }

    public function getIdAttribute()
    {
        return $this->attributes['id'];
    }

    public function getSceneAttribute()
    {
        return $this->attributes['scene'];
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
