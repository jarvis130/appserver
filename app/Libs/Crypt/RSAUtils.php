<?php

namespace App\Libs\Crypt;

use phpseclib\Crypt\RSA;

/**
 * RSA工具类.
 */
class RSAUtils extends CryptBaseUtils
{
    /**
     * 加密.
     *
     * @param  string  $data
     * @param  string  $key
     * @param  int  $padding
     * @return string
     */
    public function encrypt($data, $key, $padding = parent::RSA_PADDING_MODE_PKCS1)
    {
        // RSA非对称加密
        $rsa = new RSA();
        $rsa->loadKey($key);
        $rsa->setEncryptionMode($padding);
        $data = $rsa->encrypt($data);
        return $data;
    }

    /**
     * 解密.
     *
     * @param  string  $data
     * @param  string  $key
     * @param  int  $padding
     * @return string
     */
    public function decrypt($data, $key, $padding = parent::RSA_PADDING_MODE_PKCS1)
    {
        // RSA非对称解密
        $rsa = new RSA();
        $rsa->loadKey($key);
        $rsa->setEncryptionMode($padding);
        $data = $rsa->decrypt($data);
        return $data;
    }
}
