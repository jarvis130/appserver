<?php

namespace App\Models\v2;

use App\Models\BaseModel;
use App\Helper\Token;
use Log;

class Attribute extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'attribute';
    public $timestamps = false;
    protected $primaryKey = 'attr_id';


    public static function getVideoAttribute()
    {
        //地区
        $arr = [];
        $row = self::where('attr_id', 38)->first();
        if($row){
            $data = $row['attr_values'];
            $strs = str_replace("\r\n", ',', $data);
            $temp = explode(',', $strs);
            foreach ($temp as $key => $value){
                $arr[$key]['title'] = $value;
                $arr[$key]['value'] = $key;
            }
        }
        //分类
        $arrType = [];
        $row = self::where('attr_id', 45)->first();
        if($row){
            $data = $row['attr_values'];
            $strs = str_replace("\r\n", ',', $data);
            $temp = explode(',', $strs);
            foreach ($temp as $key => $value){
                $arrType[$key]['title'] = $value;
                $arrType[$key]['value'] = $key;
            }
        }

        return self::formatBody(['videoarea' => $arr, 'videotype' => $arrType]);
    }

}
