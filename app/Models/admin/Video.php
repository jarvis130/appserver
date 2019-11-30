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

class Video extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'video';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];
    protected $appends = ['id', 'uid', 'title', 'thumb', 'thumb_s', 'href', 'likes', 'views', 'comments', 'steps', 'shares', 'addtime', 'lat', 'lng', 'city', 'isdel', 'status', 'xiajia_reason', 'show_val', 'nppass_time', 'watch_ok', 'is_ad', 'ad_endtime', 'ad_url', 'orderno', 'description', 'collects', 'cate_id1', 'cate_id2', 'cate_id3'];

    protected $visible = ['id', 'uid', 'title', 'thumb', 'thumb_s', 'href', 'likes', 'views', 'comments', 'steps', 'shares', 'addtime', 'lat', 'lng', 'city', 'isdel', 'status', 'xiajia_reason', 'show_val', 'nppass_time', 'watch_ok', 'is_ad', 'ad_endtime', 'ad_url', 'orderno', 'description', 'collects', 'cate_id1', 'cate_id2', 'cate_id3'];

    public function getIdAttribute(){
        return $this->attributes['id'];
    }

    public function getTitleAttribute(){
        return $this->attributes['title'];
    }

    public function getUidAttribute(){
        return $this->attributes['uid'];
    }

    public function getThumbAttribute(){
        return $this->attributes['thumb'];
    }

    public function getThumbsAttribute(){
        return $this->attributes['thumbs'];
    }

    public function getHrefAttribute(){
        return $this->attributes['href'];
    }

    public function getLikesAttribute(){
        return $this->attributes['likes'];
    }

    public function getViewsAttribute(){
        return $this->attributes['views'];
    }

    public function getCommentsAttribute(){
        return $this->attributes['comments'];
    }

    public function getStepsAttribute(){
        return $this->attributes['steps'];
    }

    public function getSharesAttribute(){
        return $this->attributes['shares'];
    }

    public function getAddtimeAttribute(){
        return $this->attributes['addtime'];
    }

    public function getLatAttribute(){
        return $this->attributes['lat'];
    }

    public function getLngAttribute(){
        return $this->attributes['lng'];
    }

    public function getCityAttribute(){
        return $this->attributes['city'];
    }

    public function getIsdelAttribute(){
        return $this->attributes['isdel'];
    }

    public function getStatusAttribute(){
        return $this->attributes['status'];
    }

    public function getXiajiareasonAttribute(){
        return $this->attributes['xiajia_reason'];
    }

    public function getShowValAttribute(){
        return $this->attributes['show_val'];
    }
    public function getNppassTimeAttribute(){
        return $this->attributes['nppass_time'];
    }
    public function getWatchOkAttribute(){
        return $this->attributes['watch_ok'];
    }
    public function getIsAdAttribute(){
        return $this->attributes['is_ad'];
    }
    public function getAdEndtimeAttribute(){
        return $this->attributes['ad_endtime'];
    }
    public function getAdUrlAttribute(){
        return $this->attributes['ad_url'];
    }
    public function getOrdernoAttribute(){
        return $this->attributes['orderno'];
    }
    public function getDescriptionAttribute(){
        return $this->attributes['description'];
    }
    public function getCollectsAttribute(){
        return $this->attributes['collects'];
    }
    public function getCateId1Attribute(){
        return $this->attributes['cate_id1'];
    }
    public function getCateId2Attribute(){
        return $this->attributes['cate_id2'];
    }
    public function getCateId3Attribute(){
        return $this->attributes['cate_id3'];
    }


    public static function add(array $attributes){
        extract($attributes);
        $uid = Token::authorization();
        $data = array(
            'uid' => $uid,
            'title' => $title,
            'thumb' => $thumb,
            'thumb_s' => $thumb_s,
            'href' => $href,
            'addtime' => time(),
            'lat' => $lat,
            'lng' => $lng,
            'city' => $city,
            'isdel' => 0,
            'status' => 1,
            'description' => $description,
            'cate_id1' => $cate_id1,
            'cate_id2' => $cate_id2,
            'cate_id3' => $cate_id3,
        );
        self::insert($data);

        return self::formatBody();
    }

    public static function edit(array $attributes){
        extract($attributes);

        $model = self::where('id', $id)->first();
        if($model){
            $model->title = $title;
            $model->thumb = $thumb;
            $model->thumb_s = $thumb_s;
            $model->href = $href;
            $model->isdel = $isdel;
            $model->status = $status;
            $model->description = $description;
            $model->cate_id1 = $cate_id1;
            $model->cate_id2 = $cate_id2;
            $model->cate_id3 = $cate_id3;
            $model->save();
        }else{
            return self::formatError(self::NOT_FOUND);
        }
        return self::formatBody();
    }

    public static function getInfo(array $attributes){
        extract($attributes);
        $uid = Token::authorization();

        $model = self::where('id', $id)->first();
        $data = $model->toArray();

        return self::formatBody(['data' => $data]);
    }

    public static function getList(){

        $data = self::all();

        return self::formatBody(['data' => $data]);
    }
}