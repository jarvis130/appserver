<?php

namespace App\Models\v2;

use App\Models\BaseModel;
use App\Helper\Token;

class Actors extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'actors';

    public $timestamps = false;

    protected $visible = [];

    protected $appends = ['actor_id', 'actor_name', 'country', 'actor_avatar', 'actin_total', 'is_attention'];

    protected $guarded = [];

    protected $primaryKey = 'actor_id';

    public function getActorIdAttribute()
    {
        return $this->attributes['actor_id'];
    }

    public function getActorNameAttribute()
    {
        return $this->attributes['actor_name'];
    }

    public function getCountryAttribute()
    {
        return $this->attributes['country'];
    }

    public function getActorAvatarAttribute()
    {
//        $data = "";
//        $img = $this->attributes['actor_avatar'];
//        if($img){
//            $data = formatPhoto($img);
//        }
        return $this->attributes['actor_avatar'];;
    }

    public function getActinTotalAttribute()
    {
        return 0;
    }

     public function getActorDescAttribute()
     {
         return $this->attributes['actor_desc'];
     }

    public function getIsAttentionAttribute()
    {
        $uid = Token::authorization();
        $result = ActorAttention::isAttentionByActorId($uid, $this->attributes['actor_id']);
        return $result;
    }

    public static function getVideoListByActorId(array $attributes)
    {
        extract($attributes);
        $model = self::where('actor_id', $actor_id)->with('videos');
        $total = $model->count();
        $data = $model->paginate($per_page)->toArray();

        return self::formatBody(['actor' => $data['data'], 'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    public function videos()
    {
        return $this->belongsToMany('App\Models\v2\Video', 'goods_actor','actor_id', 'goods_id');
    }

}
