<?php

namespace App\Models\v2;

use App\Models\BaseModel;
use App\Helper\Token;
use DB;

class Actors extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'actors';

    public $timestamps = false;

    protected $visible = [];

    protected $appends = ['actor_id', 'actor_name', 'country', 'actor_avatar', 'actin_total', 'is_attention'];

    protected $guarded = [];

    protected $primaryKey = 'actor_id';

    const NOSORT  = 0;
    const NAME    = 1;
    const COUNTRY = 2;

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
        $data = "";
        $img = $this->attributes['actor_avatar'];
        if($img){
            $data = formatPhoto($img);
            $data = $data['thumb'];
        }
        return $data;
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

    public static function getList(array $attributes)
    {
        extract($attributes);

        $where  = ['is_show' => 1];

        $model = self::where($where);

        if(isset($country) && $country){
            $model = $model->where('country', $country);
        }

        if(isset($keyword) && $keyword){
            $keyword = trim($keyword);
            $keyword = strip_tags($keyword);
            $model->where(function ($query) use ($keyword) {
                // keywords
                $query->where('actor_name', 'like', '%' . $keyword . '%');
            });
            // 搜索历史
            Keywords::updateHistory($keyword);
        }

        $total = $model->count();

        if (isset($sort_key)) {
            switch ($sort_value) {
                case '1':
                    $sort = 'ASC';
                    break;

                case '2':
                    $sort = 'DESC';
                    break;

                default:
                    $sort = 'DESC';
                    break;
            }

            switch ($sort_key) {
                case self::NOSORT:
                    $model->orderBy('sort_order', $sort);
                    break;

                case self::NAME:
                    $model->orderBy(DB::raw('convert(`actor_name` using gbk)'), $sort);
                    break;

                case self::COUNTRY:
                    $model->orderBy(DB::raw('convert(`country` using gbk)'), $sort);
                    break;

                default:
                    $model->orderBy('sort_order', 'DESC');
                    break;
            }
        } else {
            $model->orderBy('sort_order', 'DESC');
        }

        $data = $model->paginate($per_page)->toArray();

        return self::formatBody(['actors' => $data['data'],'paged' => self::formatPaged($page, $per_page, $total)]);
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
