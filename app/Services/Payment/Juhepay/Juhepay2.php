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

use App\Libs\Crypt\RSAUtils;
use App\Models\v2\Pay;
use Log;

class Juhepay2
{
    public $pay_url = 'http://netway.xfzfpay.com:90/api/pay';
    public $pay_code = 'juhepay2';

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
            $pay_code . '.kjpay',
            $pay_code . '.alipay_hb'
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
        $juhepay_pay_public_key = Pay::getConfigValueByName($payment_config, 'juhepay_pay_public_key');

        if(empty($juhepay_partner) || empty($juhepay_sign_key) || empty($juhepay_pay_public_key)){
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
            case $pay_code . '.alipay_hb':
                $service = 'ZFB_HB_H5';
                break;
        }

        $parameter = array(
            'payType'           => $service,
            'version'           => 'V3.3.0.0',
            'charsetCode'       => 'UTF-8',
            'merchNo'           => $juhepay_partner,
            'randomNum'         => str_random(8),
            'notifyUrl'         => url('/v2/order.notify.' . $code),
            'notifyViewUrl'     => '',
            /* 业务参数 */
            'goodsName'         => '充值',
            'orderNo'           => $order->order_sn,
            'amount'            => $order->order_amount * 100,
        );

        //生成签名
        $sign = $this->createMd5Sign($parameter, $juhepay_sign_key);
        $parameter['sign'] = $sign;

        //加密
        $data = json_encode($parameter);
        $data = $this->encrypt($data, $juhepay_pay_public_key);
        $data = urlencode($data);

        //支付
        $pay_result = $this->doPay($data);

        if(is_string($pay_result)){
            return array(
                'code' => 10003,
                'message' => $pay_result
            );
        }

        if($pay_result['stateCode'] != '00'){
            return array(
                'code' => 10003,
                'message' => $pay_result['msg']
            );
        }

        return array(
            'code' => 0,
            'message' => '支付成功',
            'data' => [
                'url' => $pay_result['qrcodeUrl']
            ]
        );
    }

    /**
     *创建package签名
     */
    public function createMd5Sign($signParams, $key)
    {
        ksort($signParams);
        $signPars = json_encode($signParams);
        $signPars .= $key;

        $sign = strtoupper(md5($signPars));

        return $sign;
    }

    /**
     *数据加密
     */
    public function encrypt($params, $key)
    {
        $rsa = new RSAUtils();
        $data = $rsa->encrypt($params, $key);
        $data = base64_encode($data);
        return $data;
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
