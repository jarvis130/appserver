<?php

namespace App\Models\v2;

use App\Helper\XXTEA;
use App\Models\BaseModel;

class Download extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'download';
    public $timestamps = false;
    protected $guarded = [];

    public static function getInfo()
    {

        $data = self::get();

        return self::formatBody(['kefuSettings' => $data]);
    }

    public static function insertData(array $attributes)
    {

        extract($attributes);

        $num = self::where(['ip'=>$ip, 'user_id'=>$userId, 'status' => '0'])->count();
        if($num == 0){
            $data = array(
                'ip' => $ip,
                'user_id' => $userId,
                'create_time' => time(),
                'status' => 0
            );
            if($model = self::create($data)){
                return self::formatBody(['downloadInfo' => $model->toArray()]);
            } else {
                return self::formatError(self::UNKNOWN_ERROR);
            }
        }

    }

}
