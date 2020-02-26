<?php

namespace App\Models\v2;

use App\Models\BaseModel;
use App\Helper\Token;
use DB;

class Comment extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'comment';
    public $timestamps = false;

    protected $appends = ['id','username','grade','content', 'is_anonymous', 'created_at','updated_at', 'avatar_url', 'comment_rank'];

    protected $visible = ['id','username','grade','content', 'is_anonymous', 'created_at','updated_at', 'avatar_url', 'comment_rank'];

    protected $primaryKey = 'comment_id';

    protected $guarded = [];


    const GOODS = 0;
    const ARTICLE = 1;
    const VIDEO = 2;

    const BAD     = 1;            // 差评
    const MEDIUM  = 2;            // 中评
    const GOOD    = 3;            // 好评

    /**
    * 获取商品评论总数
    *
    * @access public
    * @param integer $goods_id
    * @return integer
    */
    public static function getCommentCountById($goods_id)
    {
        return self::where(['id_value' => $goods_id,'status' => 1])->where(['comment_type' => self::GOODS])->count();
    }

    /**
    * 获取商品评论好评率
    *
    * @access public
    * @param integer $goods_id
    * @return integer
    */
    public static function getCommentRateById($goods_id)
    {
        $rate = self::select('*', DB::raw('concat( sum(comment_rank)/(count(id_value) * 5)) AS goods_rank_rate'))->where('id_value', $goods_id)->where('comment_type', self::GOODS)->where('user_id', '<>', 0)->value('goods_rank_rate');
        return round($rate * 100);
    }

    public static function getReview(array $attributes)
    {
        extract($attributes);
//        $model = self::where(['id_value' => $product, 'status' => 1])->with('avatar')->orderBy('add_time', 'DESC');

        $model = self::select('c.*', 'a.avatar')
            ->from('comment as c')
            ->leftJoin('avatar as a', 'a.user_id', '=', 'c.id_value')
            ->where(['c.id_value' => $product, 'c.status' => 1])
            ->orderBy('add_time', 'DESC');

        if (isset($grade) && is_numeric($grade)) {
            if ($grade == self::BAD) {
                $model->where(function ($query) {
                    $query->where('comment_rank', '<', '3')->where('comment_rank', '>', 0);
                });
            } elseif ($grade == self::MEDIUM) {
                $model->where('comment_rank', '=', '3');
            } elseif ($grade == self::GOOD) {
                $model->where(function ($query) {
                    $query->where('comment_rank', '>', '3')->orWhere('comment_rank', 0);
                });
            }
        }

        $total = $model->count();

        $data = $model
            ->paginate($per_page)->toArray();

        $_data = $ids = [];
        foreach ($data['data'] as $review) {
            $_data[$review['id']] = $review;
            $ids[] = $review['id'];
        }
        $replys = self::whereIn('parent_id',$ids)->get();
        if($replys){
            foreach ($replys as $reply) {
                $_data[$reply['parent_id']]['reply'] = $reply;
            }
        }
        $_data = array_values($_data);
        return self::formatBody(['reviews' => $_data, 'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    public function avatar()
    {
        return $this->hasOne('App\Models\v2\Avatar', "user_id", "user_id");
    }

    public static function getSubtotal(array $attributes)
    {
        extract($attributes);

        $bad = self::where(['comment_type' => self::GOODS, 'id_value' => $product])
                    ->where('status',1)
                    ->where(function ($query) {
                        $query->where('comment_rank', '<', '3')->where('comment_rank', '>', 0);
                    })
                    ->count();

        $medium = self::where(['comment_type' => self::GOODS, 'id_value' => $product])
                ->where('status',1)
                ->where('comment_rank', '=', 3)->count();

        $good = self::where(['comment_type' => self::GOODS, 'id_value' => $product])
                ->where('status',1)
                ->where(function ($query) {
                        $query->where('comment_rank', '>', 3)->orWhere('comment_rank', 0);
                    })
                ->count();

        $total = self::where(['comment_type' => self::GOODS, 'id_value' => $product])->where('status',1)->count();

        return self::formatBody(['subtotal' => ['total' => $total, 'bad' => $bad, 'medium' => $medium, 'good' => $good]]);
    }

    public static function toCreate($uid, array $attributes, $is_anonymous, $comment_status)
    {
        extract($attributes);

        if ($member = Member::where('user_id', $uid)->first()) {
            return self::create([
                'comment_type' => 0,
                'id_value' => $goods,
                'email' => $member->email,
                //匿名时 用户名默认为ecshop
                'user_name' => ($is_anonymous == 0) ? $member->user_name : '匿名用户',
                'content' => $content,
                'comment_rank' => ($grade == 2) ? 3 : (($grade == 3) ? 5 : 1),
                'add_time' => time(),
                'ip_address' => app('request')->ip(),
                'status' => $comment_status,
                'parent_id' => 0,
                'user_id' => $uid,
            ]);
        }

        return false;
    }

    public static function add(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $member = Member::where('user_id', $uid)->first();
        if (self::where(['user_id'=> $uid, 'id_value'=> $id_value])->count() == 0) {
            return self::create([
                'comment_type' => $comment_type,
                'id_value' => $id_value,
                'email' => $member->email,
                //匿名时 用户名默认为ecshop
                'user_name' => $member->user_name,
                'content' => $content,
                'comment_rank' => $comment_rank,
                'add_time' => time(),
                'ip_address' => app('request')->ip(),
                'status' => $status,
                'parent_id' => 0,
                'user_id' => $uid,
            ]);
        }else{
            self::where('user_id', $uid)->update([
                'comment_type' => $comment_type,
                'id_value' => $id_value,
                'email' => $member->email,
                //匿名时 用户名默认为ecshop
                'user_name' => $member->user_name,
                'content' => $content,
                'comment_rank' => $comment_rank,
                'add_time' => time(),
                'ip_address' => app('request')->ip(),

            ]);
        }

        return false;
    }

    public static function addRate(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();

        if ($member = Member::where('user_id', $uid)->first()) {
            return self::create([
                'comment_type' => $comment_type,
                'id_value' => $id_value,
                'email' => $member->email,
                //匿名时 用户名默认为ecshop
                'user_name' => $member->user_name,
                'content' => $content,
                'comment_rank' => $comment_rank,
                'add_time' => time(),
                'ip_address' => app('request')->ip(),
                'status' => $status,
                'parent_id' => 0,
                'user_id' => $uid,
            ]);
        }

        return false;
    }

    public static function getGradeByGoodsId($goodsId){
//        $data = DB::select('select sum(comment_rank) / count(1) as rate, id_value from ecs_comment where id_value = ? and comment_type = 1 GROUP BY id_value', $goodsId);
//        return $data;

        $data =  self::select(DB::raw('sum(comment_rank) / count(1) as rate'))
            ->where('id_value', $goodsId)
            ->where('comment_type', 1)
            ->groupBy('id_value')
            ->value('rate');

        return intval($data);
    }

    public static function getInfo(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();

        $data = self::where(['user_id'=> $uid, 'id_value'=> $product])->first();

        return $data;
    }

    public function author()
    {
        return $this->belongsTo('App\Models\v2\Member', 'user_id', 'user_id');
    }


    public function getIdAttribute()
    {
        return $this->attributes['comment_id'];
    }


    public function getGradeAttribute()
    {
        $rank = $this->attributes['comment_rank'];
        if ($rank > 0 && $rank < 3) {
            return self::BAD;
        }
        if ($rank == 3) {
            return self::MEDIUM;
        }
        if ($rank > 3 && $rank < 6) {
            return self::GOOD;
        }
    }

    public function getContentAttribute()
    {
        return $this->attributes['content'];
    }

    public function getCommentRankAttribute()
    {
        return $this->attributes['comment_rank'];
    }

    public function getAvatarUrlAttribute()
    {
        return empty($this->attributes['avatar']) ? null : $this->attributes['avatar'];
    }

    public function getIsAnonymousAttribute()
    {
        if ($this->attributes['user_name'] == '匿名用户' || $this->attributes['user_name'] == '' || $this->attributes['user_id'] == 0) {
            return 1;
        }
        return 0;
    }

    public function getUsernameAttribute()
    {
        return $this->attributes['user_name'];
    }


    public function getCreatedatAttribute()
    {
        return time_difference($this->attributes['add_time']);
    }

    public function getUpdatedatAttribute()
    {
        return $this->attributes['add_time'];
    }
}
