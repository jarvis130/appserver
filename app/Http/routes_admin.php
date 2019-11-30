<?php

use App\Helper\Token;

$app->get('/admin', function () use ($app) {
    return 'Hi admin';
});

//Other
$app->group(['namespace' => 'App\Http\Controllers\v2', 'prefix' => 'v2'], function ($app) {
    $app->get('article.{id:[0-9]+}', 'ArticleController@show');

    $app->get('notice.{id:[0-9]+}', 'NoticeController@show');

    $app->post('order.notify.{code}', 'OrderController@notify');

    $app->get('product.intro.{id:[0-9]+}', 'GoodsController@intro');
    
    $app->get('product.share.{id:[0-9]+}', 'GoodsController@share');

    $app->get('ecapi.auth.web', 'UserController@webOauth');

    $app->get('ecapi.auth.web.callback/{vendor:[0-9]+}', 'UserController@webCallback');
});

//Guest
$app->group(['namespace' => 'App\Http\Controllers\admin','prefix' => 'admin', 'middleware' => ['xss']], function ($app) {

    $app->post('api.auth.signin', 'AdminUserController@signin');

});

//Authorization
$app->group(['prefix' => 'admin', 'namespace' => 'App\Http\Controllers\admin', 'middleware' => ['token', 'xss']], function ($app) {
    //video tag
    $app->post('api.videotag.add', 'VideoTagController@add');
    $app->post('api.videotag.edit', 'VideoTagController@edit');
    $app->get('api.videotag.getList', 'VideoTagController@getList');
    $app->get('api.videotag.info', 'VideoTagController@info');
    //video category
    $app->post('api.videocategory.add', 'VideoCategoryController@add');
    $app->post('api.videocategory.edit', 'VideoCategoryController@edit');
    $app->get('api.videocategory.getList', 'VideoCategoryController@getList');
    $app->get('api.videocategory.getListByParentId', 'VideoCategoryController@getListByParentId');
    $app->get('api.videocategory.info', 'VideoCategoryController@info');
});
