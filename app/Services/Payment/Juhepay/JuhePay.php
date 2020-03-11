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

use App\Services\Payment\wxpay\TenpayHttpClient;
use Log;

class JuhePay
{
    /**
     *创建package签名
     */
    public function createMd5Sign($signParams, $key)
    {
        $signPars = '';

        ksort($signParams);
        foreach ($signParams as $k =>$v) {
            if ($v != "" && 'sign' !=$k) {
                $signPars .= $k . '=' .$v.'&';
            }
        }
        $signPars .= '&key=' . $key;

        $sign = strtoupper(md5($signPars));
        //debug信息
        $this->_setDebugInfo('md5签名:'.$signPars . ' => sign:' .$sign);

        return $sign;
    }

    /**
     *设置debug信息
     */
    public function _setDebugInfo($debugInfo)
    {
        $this->debugInfo = PHP_EOL.$this->debugInfo.$debugInfo.PHP_EOL;
    }
}
