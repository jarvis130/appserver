<?php

namespace App\Libs\Crypt;

use phpseclib\Crypt\AES;

/**
 * AES工具类.
 */
class AESUtils extends CryptBaseUtils
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
        // AES非对称加密
        $aes = new AES($mode);
        $aes->setKey($key);
        if($iv){
            $aes->setIV($iv);
        }
        $data = $aes->encrypt($data);
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
        // AES非对称解密
        $aes = new AES($mode);
        $aes->setKey($key);
        if($iv){
            $aes->setIV($iv);
        }
        $data = $aes->decrypt($data);
        return $data;
    }
}
