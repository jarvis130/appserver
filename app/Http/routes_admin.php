<?php

use App\Helper\Token;

$router->get('/admin', function () use ($router) {
    return 'Hi admin';
});

//Guest
$router->group(['namespace' => 'admin','prefix' => 'admin', 'middleware' => ['xss']], function ($router) {
    $router->post('api.auth.signin', 'AdminUserController@signin');
});

//Authorization
//$router->group(['prefix' => 'admin', 'namespace' => 'App\Http\Controllers\admin', 'middleware' => ['token', 'xss']], function ($router) {
//    $router->get('api.auth.getUserInfo', 'AdminUserController@getUserInfo');
//    //video tag
//    $router->post('api.videotag.add', 'VideoTagController@add');
//    $router->post('api.videotag.edit', 'VideoTagController@edit');
//    $router->get('api.videotag.getList', 'VideoTagController@getList');
//    $router->get('api.videotag.info', 'VideoTagController@info');
//    //video category
//    $router->post('api.videocategory.add', 'VideoCategoryController@add');
//    $router->post('api.videocategory.edit', 'VideoCategoryController@edit');
//    $router->get('api.videocategory.getList', 'VideoCategoryController@getList');
//    $router->get('api.videocategory.getListByParentId', 'VideoCategoryController@getListByParentId');
//    $router->get('api.videocategory.info', 'VideoCategoryController@info');
//});
