<?php


/* *
 * 类名：AlipayNotify
 * 功能：支付宝通知处理类
 * 详细：处理支付宝各接口通知返回
 * 版本：3.2
 * 日期：2011-03-25
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考

 *************************注意*************************
 * 调试通知返回时，可查看或改写log日志的写入TXT里的数据，来检查通知返回是否正常
 */

namespace App\Services\Payment\Juhepay;

use Log;

class Juhepay1Notify
{
    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $key 签名key
     * @return bool 签名验证结果
     */
    public function getSignVeryfy($para_temp, $key)
    {
        $sign = $para_temp['sign'];
        unset($para_temp['sign']);

        //除去待签名参数数组中的空值和签名参数
        $juhepay = new Juhepay1();
        $mySign = $juhepay->createMd5Sign($para_temp, $key);

        if($mySign == strtoupper($sign)){
            return true;
        }else{
            return false;
        }
    }
}
