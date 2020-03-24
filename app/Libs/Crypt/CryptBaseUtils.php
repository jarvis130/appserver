<?php

namespace App\Libs\Crypt;

use phpseclib\Crypt\Base;
use phpseclib\Crypt\RSA;

/**
 * 加/解密BASE工具类.
 */
class CryptBaseUtils
{
    /* RSA 填充方式 */
    const RSA_PADDING_MODE_PKCS1 = RSA::ENCRYPTION_PKCS1;

    /* 加密模式 */
    const ENCRYPTION_MODE_CTR = Base::MODE_CTR;
    const ENCRYPTION_MODE_ECB = Base::MODE_ECB;
    const ENCRYPTION_MODE_CBC = Base::MODE_CBC;
    const ENCRYPTION_MODE_CFB = Base::MODE_CFB;
    const ENCRYPTION_MODE_OFB = Base::MODE_OFB;
    const ENCRYPTION_MODE_STREAM = Base::MODE_STREAM;
}
