<?php

namespace App\Models\v2;

use App\Models\BaseModel;

use App\Helper\Token;
use App\Models\v2\Member;
use DB;

class VideoWatchLog extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'video_watch_log';

    protected $primaryKey = 'rec_id';

    public $timestamps = false;

    protected $guarded = [];


    public static function getList(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $model = self::where(['user_id' => $uid])->with('goods')->orderBy('add_time', 'DESC');

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

    public function goods()
    {
        return $this->hasOne('App\Models\v2\Goods', 'goods_id', 'video_id');
    }

    public static function checkWatchTimes(array $attributes){
        extract($attributes);
        $times = 0;
        $prefix = DB::connection('shop')->getTablePrefix();
        $uid = Token::authorization();
        $user = Member::where('user_id', $uid)->first();
        $userRank = $user['user_rank'];
        if($userRank == 0){
            $times = 5;
        }elseif($userRank == 1){
            $times = 10;
        }

        $result = DB::select("select count(1) as num from ".$prefix."video_watch_log where date_format('add_time','%Y-%m-%d') = date_format(now(),'%Y-%m-%d')");
        $num = $result[0]->num;

        if($times >= $num){
            $surplus = $times - $num;
            return self::formatBody(['times' => $surplus ]);
        }

    }
}
