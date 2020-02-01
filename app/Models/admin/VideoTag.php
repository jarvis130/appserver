<?php
/**
 * Created by PhpStorm.
 * User: jarvis
 * Date: 2019/11/24
 * Time: 3:39 PM
 */

namespace App\Models\admin;

use App\Models\BaseModel;
use App\Helper\Token;

class VideoTag extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'video_tag';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];
    protected $appends = ['id','title','status'];
    protected $visible = ['id','title','status'];

    public function getIdAttribute(){
        return $this->attributes['id'];
    }

    public function getTitleAttribute(){
        return $this->attributes['title'];
    }

    public function getStatusAttribute(){
        return $this->attributes['status'];
    }

    public static function add(array $attributes){
        extract($attributes);
        $uid = Token::authorization();
        $data = array(
            'title' => $title,
            'status' => $status,
            'user_id' => $uid
        );
        self::insert($data);

        return self::formatBody();
    }

    public static function edit(array $attributes){
        extract($attributes);

        $model = self::where('id', $id)->first();
        if($model){
            $model->title = $title;
            $model->status = $status;
            $model->save();
        }else{
            return self::formatError(self::NOT_FOUND);
        }
        return self::formatBody();
    }

    public static function getInfo(array $attributes){
        extract($attributes);
        $uid = Token::authorization();

        $model = self::where('id', $tagId)->first();
        $data = $model->toArray();

        return self::formatBody(['data' => $data]);
    }

    public static function getList(){

        $data = self::all();

        return self::formatBody(['data' => $data]);
    }
}