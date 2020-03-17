<?php

namespace App\Models\v2;

use App\Models\BaseModel;

class Banner extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'banners';
    public $timestamps = false;
    protected $guarded    = [];
    protected $appends    = ['id', 'src', 'url', 'text', 'type'];
    protected $visible    = ['id', 'src', 'url', 'text', 'type'];

    /**
     * 获取列表
     * @param $scene int 场景值 null:全部 1：轮播图 2：广告位
     * @return array
     */
    public static function getList($scene = null)
    {
        if($scene){
            $data = self::where('scene', $scene)->get()->toArray();
        }else{
            $data = self::all()->toArray();
        }
        return self::formatBody(['banners' => $data]);
    }
}
