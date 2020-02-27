<?php

namespace App\Models\v2;

use App\Libs\Crypt;
use App\Models\BaseModel;

use App\Helper\Token;
use \DB;
use Log;


class VirtualCard extends BaseModel
{
    protected $connection = 'shop';

    protected $table = 'virtual_card';

    public $timestamps = false;

    protected $primaryKey = 'card_id';

    protected $guarded = [];

    public static function use(array $attributes)
    {
        extract($attributes);

        //$uid = Token::authorization();
        $uid = 1;

        $key = 'this is a key';  // 需要和ecshop的admin管理后台的key保持一致
        $coded_card_sn       = Crypt::encrypt($card_sn, $key);
        $coded_card_password = Crypt::encrypt($card_password, $key);

        /* 更新卡为已使用 */
        $updateCount = self::where('card_sn', $coded_card_sn)
            ->where('card_password', $coded_card_password)
            ->where('is_used', 0)
            ->update([
                'is_saled' => 1,
                'is_used' => 1,
                'use_time' => time()
            ]);
        if($updateCount == 0){
            return self::formatError(self::BAD_REQUEST, trans('message.virtual_card.invalid'));
        }

        /* 查询卡信息 */
        $card = self::where('card_sn', $coded_card_sn)->where('card_password', $coded_card_password)->first();
        $goods_id = $card['goods_id'];

        /* 更新用户vip到期时间 */
        // 根据视频商品获取时长
        $add_time = Video::getGoodsVipTime($goods_id);
        if($add_time <= 0){
            return self::formatError(self::BAD_REQUEST, trans('message.virtual_card.vip_time_invalid'));
        }
        // 查询用户当前vip到期时间
        $curr_time = time();
        $user = Member::find($uid);
        $vip_end_time = $user['vip_end_time'];
        // 计算到期时间
        if($vip_end_time < $curr_time){
            $new_vip_end_time = $curr_time + $add_time;
        }else{
            $new_vip_end_time = $vip_end_time + $add_time;
        }
        Member::where('user_id', $uid)->update(['vip_end_time' => $new_vip_end_time]);

        return self::formatBody();
    }

    /**
     * 加密函数
     * @param   string  $str    加密前的字符串
     * @param   string  $key    密钥
     * @return  string  加密后的字符串
     */
    function card_encrypt($str, $key)
    {
        $coded = '';
        $keylength = strlen($key);

        for ($i = 0, $count = strlen($str); $i < $count; $i += $keylength)
        {
            $coded .= substr($str, $i, $keylength) ^ $key;
        }

        return str_replace('=', '', base64_encode($coded));
    }

    /**
     * 解密函数
     * @param   string  $str    加密后的字符串
     * @param   string  $key    密钥
     * @return  string  加密前的字符串
     */
    function decrypt($str, $key)
    {
        $coded = '';
        $keylength = strlen($key);
        $str = base64_decode($str);

        for ($i = 0, $count = strlen($str); $i < $count; $i += $keylength)
        {
            $coded .= substr($str, $i, $keylength) ^ $key;
        }

        return $coded;
    }
}