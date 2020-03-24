<?php

namespace App\Libs\Crypt;

use phpseclib\Crypt\TripleDES;

/**
 * 3DES工具类.
 */
class TripleDESUtils extends CryptBaseUtils
{
    /**
     * 加密.
     *
     * @param  string  $data
     * @param  string  $key
     * @param  string  $iv
     * @param  int  $mode
     * @return string
     */
    public function encrypt($data, $key, $iv = null, $mode = parent::ENCRYPTION_MODE_CBC)
    {
        // 3DES非对称加密
        $des = new TripleDES($mode);
        $des->setKey($key);
        if($iv){
            $des->setIV($iv);
        }
        $data = $des->encrypt($data);
        return $data;
    }

    /**
     * 解密.
     *
     * @param  string  $data
     * @param  string  $key
     * @param  string  $iv
     * @param  int  $mode
     * @return string
     */
    public function decrypt($data, $key, $iv = null, $mode = parent::ENCRYPTION_MODE_CBC)
    {
        // 3DES非对称解密
        $des = new TripleDES($mode);
        $des->setKey($key);
        if($iv){
            $des->setIV($iv);
        }
        $data = $des->decrypt($data);
        return $data;
    }
}
