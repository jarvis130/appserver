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

    public function post($path, $params=array())
    {
        return $this->call('post', $path, $params);
    }

    public function call($method, $path, $params=array())
    {
        $url = $this->base_url.$path;
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

    /**
     *设置debug信息
     */
    public function _setDebugInfo($debugInfo)
    {
        $this->debugInfo = PHP_EOL.$this->debugInfo.$debugInfo.PHP_EOL;
    }
}
