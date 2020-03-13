<?php

namespace App\Models\v2;

use App\Models\BaseModel;

use App\Helper\Token;

class GoodsVideoAttr extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'goods_video_attr';
    public $timestamps = false;


    protected $visible = ['id','goods_id','attr_value1','attr_value2'];

    protected $appends = ['id','goods_id','attr_value1','attr_value2'];

    protected $guarded = [];

    // protected $with = ['attribute'];

    public function getIdAttribute()
    {
        return $this->id;
    }

    public function getGoodsIdAttribute()
    {
        return $this->goods_id;
    }

    public function getAttrValue1Attribute()
    {
        return $this->attributes['attr_value1'];
    }

    public function getAttrValue2Attribute()
    {
        return $this->attributes['attr_value2'];
    }



}
