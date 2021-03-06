<?php

namespace App\Libs;

/**
 * 工具类
 */

class Utils {

    /**
     * 生成不重复的随机数
     * @param  int $start  需要生成的数字开始范围
     * @param  int $end    结束范围
     * @param  int $length 需要生成的随机数个数
     * @return array       生成的随机数
     */
    public static function get_rand_number($start = 1, $end = 10, $length = 4){
        $connt = 0;
        $temp = array();
        while ($connt < $length) {
            $temp[] = mt_rand($start, $end);
            $data = array_unique($temp);
            $connt = count($data);
        }
        //sort($data);
        return $data;
    }

    /**
     * 根据二维数组某个字段的值查找数组
     */
    public static function filter_by_value($array, $index, $value){
        if(is_array($array) && count($array)>0)
        {
            foreach(array_keys($array) as $key){
                $temp[$key] = $array[$key][$index];
                if ($temp[$key] == $value){
                    $newarray[] = $array[$key];
                }
            }
        }
        return $newarray;
    }
}

?>