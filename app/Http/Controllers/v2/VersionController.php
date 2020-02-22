<?php

namespace App\Http\Controllers\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\v2\Version;

class VersionController extends Controller
{
    /**
     * POST ecapi.version.check
     */
    public function check(Request $request)
    {
        $data = Version::checkVersion();
        return $this->json($data);
    }

    /**
     * POST ecapi.version.app.check
     */
    public function checkApp(Request $request)
    {
        $data = array(
              "Code"=> 0,//0代表请求成功，非0代表失败
              "Msg"=> "", //请求出错的信息
              "UpdateStatus"=> 1, //0代表不更新，1代表有版本更新，不需要强制升级，2代表有版本更新，需要强制升级
              "VersionCode"=> 3, //编译版本号(唯一)
              "VersionName"=> "1.0.2", //版本名(用于展示)
              "ModifyContent"=> "1.更新收藏页面。\n2.祭天了多名程序猿。\n3.新增版本检查页面。", //更新内容
              "DownloadUrl"=> "http://221.229.197.4:8003/app-release.apk",// 文件下载地址
              "ApkSize"=> 2048, //文件的大小(单位:kb)
              "ApkMd5"=> ""  //md5值没有的话，就无法保证apk是否完整，每次都会重新下载。框架默认使用的是md5加密。
        );
        return $this->json($data);
    }
}
