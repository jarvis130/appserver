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
        $goods = [];
        foreach ($data['data'] as $key => $value) {
            $goods[$key] = $data['data'][$key]['goods'];
        }

        return self::formatBody(['products' => $goods, 'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    //关注
    public static function setAttention(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $num = UserAttention::where(['user_id' => $uid, 'att_user_id' => $att_user_id])->count();

        //因为有网站和手机 所以可能$num大于1
        if ($num == 0) {
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
        } elseif ($num >0) {
            return self::formatBody(['is_attention' =>true ]);
        }
    }

    //取消关注
    public static function setUnattention(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $model = self::where(['user_id' => $uid, 'att_user_id' => $att_user_id]);
        $num = $model->count();

        if ($num == 1) {
            $model->delete();
        } elseif ($num > 1) {
            for ($i=0; $i < $num; $i++) {
                $model = $model->first();
                $model->delete();
            }
        }
        if ($model->count() == 0) {
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
        return $this->hasOne('App\Models\v2\Member', 'user_id', 'concern_user_id');
    }
}
