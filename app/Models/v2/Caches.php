<?php

namespace App\Models\v2;

use App\Models\BaseModel;

use Illuminate\Support\Facades\Redis;

class Caches extends BaseModel
{
    protected $connection = 'shop';

    protected $table = 'caches';

    protected $guarded = [];

    public $timestamps   = false;

    public static function refreshCache(array $attributes)
    {
        extract($attributes);

        switch ($code) {
            case 'video:home:list':
                Redis::del($code);
                Video::getHomeList(array());
                break;

            case 'video:info':
                if(!empty($attrid)){
                    Redis::del($code . ':' . $attrid);
                    Video::getInfo(array('product' => $attrid));
                }else{
                    // 刷新5小时之内更新的视频
                    $time = time() - 5 * 60 * 60;
                    $res = Video::where('last_update', '>=', $time)->where('is_real', 2)->get(['goods_id']);
                    foreach ($res as $key => $row) {
                        $attrid = $row['goods_id'];
                        Redis::del($code . ':' . $attrid);
                        Video::getInfo(array('product' => $attrid));
                    }
                }
                break;
        }

        return self::formatBody();
    }
}
