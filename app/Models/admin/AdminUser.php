<?php
/**
 * Created by PhpStorm.
 * User: jarvis
 * Date: 2019/11/23
 * Time: 9:38 PM
 */

namespace App\Models\admin;

use App\Models\BaseModel;
use App\Helper\Token;

class AdminUser extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'admin_user';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $guarded = [];
    protected $appends = ['user_id','user_name','email','add_time','last_login','last_ip'];
    protected $visible = ['user_id','user_name','email','add_time','last_login','last_ip'];

    public static function login(array $attributes)
    {
        extract($attributes);

        if ($model = self::validatePassword($username, $password)) {

            $token = Token::encode(['uid' => $model->user_id]);

            return self::formatBody(['token' => $token, 'user' => $model->toArray()]);
        }

        return self::formatError(self::BAD_REQUEST, trans('message.member.failed'));
    }

    public static function getUserInfo()
    {
        $uid = Token::authorization();

        $model = self::where('user_id', $uid)->first();
        $data = $model->toArray();

        $access = [
            'admin'
        ];

        $data['access'] = $access;
        return self::formatBody(['data' => $data]);
    }

    private static function validatePassword($username, $password)
    {
        $type = self::getUsernameType($username);

        if ($type == 'email') {
            $model = self::where('email', $username)->first();
        } else {
            $model = self::where('user_name', $username)->first();
        }

        if ($model && $model->password == self::setPassword($password, $model->ec_salt)) {
            $model->last_login = time();
            $model->last_ip = app('request')->ip();
            $model->save();

            return $model;
        }

        return false;
    }

    public static function getUsernameType($username)
    {
        if (preg_match("/^\d{11}$/", $username)) {
            return 'mobile';
        } elseif (preg_match("/^\w+@\w+\.\w+$/", $username)) {
            return 'email';
        } else {
            return 'username';
        }
    }

    private static function setPassword($password, $salt = false)
    {
        if ($salt) {
            return md5(md5($password).$salt);
        }
        return md5($password);
    }

    public function getUserIdAttribute(){
        return $this->attributes['user_id'];
    }

    public function getUserNameAttribute(){
        return $this->attributes['user_name'];
    }

    public function getEmailAttribute(){
        return $this->attributes['email'];
    }
    public function getAddTimeAttribute(){
        return $this->attributes['add_time'];
    }
    public function getLastLoginAttribute(){
        return $this->attributes['last_login'];
    }
    public function getLastIpAttribute(){
        return $this->attributes['last_ip'];
    }
}