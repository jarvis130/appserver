<?php


/**
 * 请求类
 * ============================================================================
 * api说明：
 * init(),初始化函数，默认给一些参数赋值，如cmdno,date等。
 * getGateURL()/setGateURL(),获取/设置入口地址,不包含参数值
 * getKey()/setKey(),获取/设置密钥
 * getParameter()/setParameter(),获取/设置参数值
 * getAllParameters(),获取所有参数
 * getRequestURL(),获取带参数的请求URL
 * getDebugInfo(),获取debug信息
 *
 * ============================================================================
 *
 */
namespace App\Services\Payment\Juhepay;

use App\Models\v2\Pay;
use App\Models\v2\Payment;
use Log;

class Juhepay3
{
    public $pay_url = 'http://gway.yt888f.com:7060/api/qrCodePay.action';
    public $pay_code = 'juhepay3';

    /**
     * 获取支付方式编码列表
     */
    public function getPayMethodCodeList(){
        $pay_code = $this->pay_code;
        $pay_methods = array(
            $pay_code . '.alipay',
            $pay_code . '.wxpay',
            $pay_code . '.qqpay',
            $pay_code . '.jdpay',
            $pay_code . '.kjpay'
        );
        return $pay_methods;
    }

    /**
     * 支付入口
     * @param $code
     * @param $order
     * @return mixed
     */
    public function pay($code, $order)
    {
        $pay_code = $this->pay_code;
        $payment = Pay::getPayment($pay_code);

        if (!$payment) {
            return array(
                'code' => 10001,
                'message' => '未找到支付'
            );
        }

        $payment_config = $payment->pay_config;

        $juhepay_partner = Pay::getConfigValueByName($payment_config, 'juhepay_partner');
        $juhepay_sign_key = Pay::getConfigValueByName($payment_config, 'juhepay_sign_key');

        if(empty($juhepay_partner) || empty($juhepay_sign_key)){
            return array(
                'code' => 10002,
                'message' => '未找到支付相应配置'
            );
        }

        switch ($code){
            case $pay_code . '.alipay':
                $service = 'ZFB_WAP';
                break;
            case $pay_code . '.wxpay':
                $service = 'WX_WAP';
                break;
            case $pay_code . '.qqpay':
                $service = 'QQ_WAP';
                break;
            case $pay_code . '.jdpay':
                $service = 'JD_WAP';
                break;
            case $pay_code . '.kjpay':
                $service = 'UNION_WAP';
                break;
        }

        $parameter = array(
            'payType'           => $service,
            'appId'             => $juhepay_partner,
            'nonceStr'          => str_random(10),
            'notifyUrl'         => url('/v2/order.notify.' . $code),
            'returnUrl'         => '',
            'requestIp'         => Payment::get_client_ip(), // 终端ip
            /* 业务参数 */
            'goodsInfo'         => '充值',
            'outTradeNo'        => $order->order_sn,
            'totalAmount'       => strval($order->order_amount * 100),
        );

        //生成签名
        $sign = $this->createMd5Sign($parameter, $juhepay_sign_key);
        $parameter['sign'] = $sign;

        //转json
        $data = json_encode($parameter);

        //发送数据
        $param = array(
            'reqData' => $data
        );

        //支付
        $pay_result = $this->doPay($param);

        if(is_string($pay_result)){
            return array(
                'code' => 10003,
                'message' => $pay_result
            );
        }

        if($pay_result['resultCode'] != '00'){
            return array(
                'code' => 10003,
                'message' => $pay_result['resultMsg']
            );
        }

        return array(
            'code' => 0,
            'message' => '支付成功',
            'data' => [
                'url' => $pay_result['qrCode']
            ]
        );
    }

    /**
     *创建package签名
     */
    public function createMd5Sign($signParams, $key)
    {
        ksort($signParams);
        $signPars = json_encode($signParams, 320);
        $signPars .= $key;

        $sign = strtoupper(md5($signPars));

        return $sign;
    }

    public function doPay($param, $result_decode = true)
    {
        $rst = $this->post($this->pay_url, $param);
        if ($result_decode == true) {
            return json_decode($rst, true);
        }

        return $rst;
    }

    public function post($url, $params=array())
    {
        return $this->call('post', $url, $params);
    }

    public function call($method, $url, $params=array())
    {
        $options = array(
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 10,
        );

        $param_string = http_build_query($params);
        switch (strtolower($method)) {
            case 'post':
                $options += array(CURLOPT_POST => 1,
                    CURLOPT_POSTFIELDS => $param_string);
                break;
            case 'put':
                $options += array(CURLOPT_PUT => 1,
                    CURLOPT_POSTFIELDS => $param_string);
                break;
            case 'delete':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                if ($param_string) {
                    $options[CURLOPT_URL] .= '?'.$param_string;
                }
                break;
            default:
                if ($param_string) {
                    $options[CURLOPT_URL] .= '?'.$param_string;
                }
        }

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        if (! $result = curl_exec($ch)) {
            $this->on_error(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }
}
