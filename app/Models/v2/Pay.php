<?php

namespace App\Models\v2;

use App\Libs\Utils;
use App\Models\BaseModel;

class Pay extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'payment';
    
    public $timestamps = false;

    public static function checkConfig($pay_code)
    {
        // $sql = "SELECT * FROM " . $ecs->table('payment') . " WHERE pay_code = '$_REQUEST[code]' AND enabled = '1'";
        if ($payment = self::where('pay_code', $pay_code)->where('enabled', '1')->first()) {
            return true;
        }
        return false;
    }

    public static function getConfigValueByName($pay_config, $name)
    {
        $result = unserialize($pay_config);
        $result = Utils::filter_by_value($result, 'name', $name);
        return $result[0]['value'];
    }
}
