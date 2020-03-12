<?php

namespace App\Models\v2;

use App\Models\BaseModel;
use DB;

class GoodsActors extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'goods_actor';

    public $timestamps = false;

    protected $visible = [];

//    protected $appends = ['actor_id', 'actor_name', 'country', 'actor_avatar', 'actin_total'];

    protected $guarded = [];

    public static function getActors($goodsId){
        $data =  self::select(DB::raw('sum(comment_rank) / count(1) as rate'))
            ->where('id_value', $goodsId)
            ->where('comment_type', 1)
            ->groupBy('id_value')
            ->value('rate');

        return intval($data);
    }

    public static function getVideoTotalByActorId($actorId){
        $data =  self::select(DB::raw('count(1) as total'))
            ->where('actor_id', $actorId)
            ->value('total');

        return intval($data);
    }
}
