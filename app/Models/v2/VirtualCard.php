<?php

namespace App\Models\v2;

use App\Libs\Ecshop\Crypt;
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

        $uid = Token::authorization();

        $key = 'this is a key';  // 需要和ecshop的admin管理后台的key保持一致
        $coded_card_sn       = Crypt::encrypt($card_sn, $key);
        $coded_card_password = Crypt::encrypt($card_password, $key);

        /* 查询卡信息 */
        $card = self::where('card_sn', $coded_card_sn)
            ->where('card_password', $coded_card_password)
            ->where('is_used', 0)
            ->first();
        if(empty($card)){
            return self::formatError(self::BAD_REQUEST, trans('message.virtual_card.invalid'));
        }
        $goods_id = $card['goods_id'];

        /* 根据视频商品获取时长 */
        $add_time = Video::getGoodsVipTime($goods_id);
        if($add_time <= 0){
            return self::formatError(self::BAD_REQUEST, trans('message.virtual_card.vip_time_invalid'));
        }

        /* 创建订单 */
        $orderInfo = array(
            'consignee' => $uid,
            'shipping' => 0,
            'cart_good_id' => json_encode([$goods_id])
        );
        $order = Cart::createVideoOrder($orderInfo);
        $order_sn = $order['order']['sn'];

        /* 更新卡为已使用 */
        $updateCount = self::where('card_sn', $coded_card_sn)
            ->where('card_password', $coded_card_password)
            ->where('is_used', 0)
            ->update([
                'is_saled' => 1,
                'order_sn' => $order_sn,
                'is_used' => 1,
                'use_time' => time()
            ]);
        if($updateCount == 0){
            return self::formatError(self::BAD_REQUEST, trans('message.virtual_card.invalid'));
        }

        /* 更新用户vip到期时间 */
        Member::updateVipTime($uid, $add_time);

        /* 修改订单状态 */
        Order::where('order_sn', $order_sn)->update([
            'order_status' => Order::OS_SPLITED,
            'shipping_status' => Order::SS_RECEIVED,
            'pay_status' => Order::PS_PAYED
        ]);

        $user = Member::where('user_id', $uid)->first();
        return self::formatBody(['user' => $user]);
    }
}