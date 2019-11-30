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

class VideoCategory extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'video_category';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];
    protected $appends = ['id','title','parent_id', 'is_delete', 'user_id'];
    protected $visible = ['id','title','parent_id', 'is_delete', 'user_id'];

    public function getIdAttribute(){
        return $this->attributes['id'];
    }

    public function getTitleAttribute(){
        return $this->attributes['title'];
    }

    public function getParentIdAttribute(){
        return $this->attributes['parent_id'];
    }

    public function getIsDeleteAttribute(){
        return $this->attributes['is_delete'];
    }

    public function getUserIdAttribute(){
        return $this->attributes['user_id'];
    }

    /**
     * add
     * @param array $attributes
     * @return array
     */
    public static function add(array $attributes){
        extract($attributes);
        $uid = Token::authorization();
        $data = array(
            'title' => $title,
            'is_delete' => $is_delete,
            'parent_id' => $parent_id,
            'user_id' => $uid
        );
        self::insert($data);

        return self::formatBody();
    }

    /**
     * edit
     * @param array $attributes
     * @return array|mixed
     */
    public static function edit(array $attributes){
        extract($attributes);

        $model = self::where('id', $id)->first();
        if($model){
            $model->title = $title;
            $model->is_delete = $is_delete;
            $model->parent_id = $parent_id;
            $model->save();
        }else{
            return self::formatError(self::NOT_FOUND);
        }
        return self::formatBody();
    }

    /**
     * getInfo
     * @param array $attributes
     * @return array
     */
    public static function getInfo(array $attributes){
        extract($attributes);
        $uid = Token::authorization();

        $model = self::where('id', $id)->first();
        $data = $model->toArray();

        return self::formatBody(['data' => $data]);
    }

    /**
     * getListByParentId
     * @param array $attributes
     * @return array
     */
    public static function getListByParentId(array $attributes){
        extract($attributes);
        $uid = Token::authorization();

        $data = self::where('parent_id', $parent_id)->get();

        return self::formatBody(['data' => $data]);
    }

    /**
     *
     * @return array
     */
    public static function getList(){

        $data = self::where('is_delete', 0)->all();

        return self::formatBody(['data' => $data]);
    }
}