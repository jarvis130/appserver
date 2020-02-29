<?php

namespace App\Models\v2;

use App\Models\BaseModel;

class BannerAds extends BaseModel
{
    public static function getList()
    {
        $data = [];
        $file = @file_get_contents(config('app.shop_url').'/data/flash_ad_data.xml');
        if (strlen($file) > 0) {
            $data = self::get_flash_xml($file);
        }
        return self::formatBody(['ads' => $data]);
    }

    private static function get_flash_xml($file)
    {
        $flashdb = array();

        // 兼容v2.7.0及以前版本
        if (!preg_match_all('/item_url="([^"]+)"\slink="([^"]*)"\stext="([^"]*)"\ssort="([^"]*)"\stype="([^"]*)"/', $file, $t, PREG_SET_ORDER)) {
            preg_match_all('/item_url="([^"]+)"\slink="([^"]*)"\stext="([^"]*)"\stype="([^"]*)"/', $file, $t, PREG_SET_ORDER);
        }
        if (!empty($t)) {
            foreach ($t as $key => $val) {
                $val[4] = isset($val[4]) ? $val[4] : 0;
                $flashdb[] = array('id' => $key, 'photo'=>formatPhoto($val[1]), 'link'=>$val[2],'title'=>$val[3],'sort'=>$val[4], 'type'=>$val[5]);
            }
        }
        return $flashdb;
    }
}
