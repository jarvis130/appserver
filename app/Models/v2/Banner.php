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
     * 获取列表
     * @param $scene int 场景值 1：轮播图 2：广告位
     * @return array
     */
    public static function getList($scene)
    {
        $model = self::where('scene', $scene)->orderBy('sort');

        if($scene == 2){
            $data = array();
            $banners = $model->get()->groupBy('group')->toArray();
            ksort($banners);
            $i = 0;
            foreach ($banners as $banner){
                $i += 1;
                $data['ad' . $i] = $banner;
            }
            return self::formatBody($data);
        }else{
            $data = $model->get()->toArray();
            return self::formatBody(['banners' => $data]);
        }

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
