<?php
/**/
namespace App\Models\v2;

use App\Models\BaseModel;

use App\Helper\Token;

class ActorAttention extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'actor_attention';

    protected $primaryKey = 'att_id';

    public $timestamps = false;

    protected $guarded = [];


    public static function getList(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $model = self::where(['user_id' => $uid])->with('actorInfo')->orderBy('add_time', 'DESC');

        //paged
        $total = $model->count();
        $data = $model->paginate($per_page)
            ->toArray();

        //format
//        $users = [];
//        foreach ($data['data'] as $key => $value) {
//            $users[$key] = $data['data'][$key]['member'];
//        }

        return self::formatBody(['actorAttention' => $data['data'], 'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    //关注
    public static function setAttention(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $model = self::where(['user_id' => $uid, 'actor_id' => $actor_id]);

        //因为有网站和手机 所以可能$num大于1
        if ($model->count() == 0) {
            $model = new ActorAttention();
            $model->user_id             = $uid;
            $model->actor_id            = $actor_id;
            $model->add_time            = time();
            $model->is_attention        = 1;

            if ($model->save()) {
                return self::formatBody(['is_attention' =>true ]);
            } else {
                return self::formatError(self::UNKNOWN_ERROR);
            }
        } else {
            $model->delete();
            return self::formatBody(['is_attention' =>false ]);
        }
    }

    //关注信息
    public static function getAttention(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $model = self::where(['user_id' => $uid, 'actor_id' => $actor_id]);
        $num = $model->count();

        if ($num == 0) {
            return self::formatBody(['is_attention' => 0 ]);
        }else{
            return self::formatBody(['is_attention' => 1 ]);
        }
    }

    public static function isAttentionByActorId($userId, $actorId){
        $count = self::where(['actor_id'=>$actorId, 'user_id'=>$userId])->count();
        return $count;
    }

    public function actorInfo()
    {
        return $this->hasOne('App\Models\v2\Actors', 'actor_id', 'actor_id');
    }

}
