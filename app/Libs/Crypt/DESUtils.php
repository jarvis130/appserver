<?php

namespace App\Libs\Crypt;

use phpseclib\Crypt\DES;

/**
 * DES工具类.
 */
class DESUtils extends CryptBaseUtils
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
        // DES非对称加密
        $des = new DES($mode);
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
        // DES非对称解密
        $des = new DES($mode);
        $des->setKey($key);
        if($iv){
            $des->setIV($iv);
        }
        $data = $des->decrypt($data);
        return $data;
    }
}
