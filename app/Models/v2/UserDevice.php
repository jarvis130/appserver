<?php

namespace App\Models\v2;

use App\Models\BaseModel;
use App\Helper\Token;
use Log;

class UserDevice extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'user_device';
    public $timestamps = false;

    protected $guarded = [];
    protected $appends = ['device_id', 'os', 'user_id', 'remark'];
    protected $visible = ['device_id', 'os', 'user_id', 'remark'];

    public function getDeviceIdAttribute()
    {
        return $this->attributes['device_id'];
    }

    public function getOsAttribute()
    {
        return $this->attributes['os'];
    }

    public function getUserIdAttribute()
    {
        return $this->attributes['user_id'];
    }

    public function getRemarkAttribute()
    {
        return $this->attributes['remark'];
    }

    public static function createDevice($user_id, $device_id, $os, $ip, $remark)
    {
        $data = [
            'device_id' => $device_id,
            'os' => $os,
            'user_id' => $user_id,
            'ip' => $ip,
            'remark' => $remark
        ];

        if (!$model = self::create($data)) {
            return self::formatError(self::UNKNOWN_ERROR);
        }

        return $model;
    }

}
