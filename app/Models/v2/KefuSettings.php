<?php

namespace App\Models\v2;

use App\Helper\XXTEA;
use App\Models\BaseModel;

class KefuSettings extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'kefu_settings';
    public $timestamps = false;
    protected $guarded = [];

    public static function getList()
    {

        $data = self::get();

        return self::formatBody(['kefuSettings' => $data]);
    }

}
