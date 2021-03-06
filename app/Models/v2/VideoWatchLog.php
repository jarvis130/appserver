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
            $goods[$key]['goods'] = $data['data'][$key]['goods'];
            $goods[$key]['add_time'] = date('Y-m-d', $data['data'][$key]['add_time']);
            $goods[$key]['breadcrumb'] = $data['data'][$key]['breadcrumb'];  // TODO 此信息已经废弃（为兼容旧版客户端，暂时保留），现在从goods属性获取
        }

        return self::formatBody(['products' => $goods, 'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    public function goods()
    {
        return $this->hasOne('App\Models\v2\Goods', 'goods_id', 'video_id');
    }

    public static function checkWatchTimes(array $attributes){
        extract($attributes);

        $uid = Token::authorization();
        $user = Member::where('user_id', $uid)->first();
        $userRank = $user['user_rank'];
        $vip_end_time = $user['original_vip_end_time'];

        // 如果VIP有效
        if($userRank >= 2 && $vip_end_time >= time()){
            return self::formatBody(['times' => 1]);
        }

        $times = $user['credit_line'];

        $watchedTimes = 0;//已经观看次数
        if($userRank < 2) {
            $watchedTimes = Video::getTodayWatchedTimes($uid);
        }

        if($times >= $watchedTimes){
            $surplus = $times - $watchedTimes;
        }else{
            $surplus = 0;
        }
        return self::formatBody(['times' => $surplus]);
    }
}
