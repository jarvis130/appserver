<?php
/**/
namespace App\Models\v2;

use App\Models\BaseModel;

use App\Helper\Token;

class UserAttention extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'user_attention';

    protected $primaryKey = 'att_id';

    public $timestamps = false;

    protected $guarded = [];


    public static function getList(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $model = self::where(['user_id' => $uid])->with('Member')->orderBy('add_time', 'DESC');

        //paged
        $total = $model->count();
        $data = $model->paginate($per_page)
            ->toArray();

        //format
        $users = [];
        foreach ($data['data'] as $key => $value) {
            $users[$key] = $data['data'][$key]['member'];
        }

        return self::formatBody(['users' => $users, 'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    //关注
    public static function setAttention(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $model = UserAttention::where(['user_id' => $uid, 'att_user_id' => $att_user_id]);

        //因为有网站和手机 所以可能$num大于1
        if ($model->count() == 0) {
            $model = new UserAttention();
            $model->user_id             = $uid;
            $model->att_user_id            = $att_user_id;
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
        $model = self::where(['user_id' => $uid, 'att_user_id' => $att_user_id]);
        $num = $model->count();

        if ($num == 0) {
            return self::formatBody(['is_attention' => 0 ]);
        }else{
            return self::formatBody(['is_attention' => 1 ]);
        }
    }

    public function member()
    {
        return $this->hasOne('App\Models\v2\Member', 'user_id', 'att_user_id');
    }
}
