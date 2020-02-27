<?php

use App\Helper\Token;

$router->get('/', function () use ($router) {
    return $router->app->version();
});

//Other
$router->group(['namespace' => 'v2', 'prefix' => 'v2'], function ($router) {
    $router->get('article.{id:[0-9]+}', 'ArticleController@show');

    $router->get('notice.{id:[0-9]+}', 'NoticeController@show');

    $router->post('order.notify.{code}', 'OrderController@notify');

    $router->get('product.intro.{id:[0-9]+}', 'GoodsController@intro');

    $router->get('product.share.{id:[0-9]+}', 'GoodsController@share');

    $router->get('ecapi.auth.web', 'UserController@webOauth');

    $router->get('ecapi.auth.web.callback/{vendor:[0-9]+}', 'UserController@webCallback');
});

//Guest
$router->group(['namespace' => 'v2','prefix' => 'v2', 'middleware' => ['xss']], function ($router) {
    $router->post('ecapi.ad.list', 'AdController@ad_list');

    $router->post('ecapi.access.dns', 'AccessController@dns');

    $router->post('ecapi.access.batch', 'AccessController@batch');

    $router->post('ecapi.category.list', 'GoodsController@category');

    $router->post('ecapi.activity.list', 'ActivityController@index');

    $router->post('ecapi.activity.get', 'ActivityController@info');

    $router->post('ecapi.product.list', 'GoodsController@index');

    $router->post('ecapi.home.product.list', 'GoodsController@home');

    $router->post('ecapi.search.product.list', 'GoodsController@search');

    $router->post('ecapi.review.product.list', 'GoodsController@review');

    $router->post('ecapi.review.product.subtotal', 'GoodsController@subtotal');

    $router->post('ecapi.recommend.product.list', 'GoodsController@recommendList');

    $router->post('ecapi.product.accessory.list', 'GoodsController@accessoryList');

    $router->post('ecapi.product.get', 'GoodsController@info');

    $router->post('ecapi.auth.signin', 'UserController@signin');

    $router->post('ecapi.auth.social', 'UserController@auth');

    $router->post('ecapi.auth.default.signup', 'UserController@signupByEmail');

    $router->post('ecapi.auth.mobile.signup', 'UserController@signupByMobile');

    $router->post('ecapi.user.profile.fields', 'UserController@fields');

    $router->post('ecapi.auth.mobile.verify', 'UserController@verifyMobile');

    $router->post('ecapi.auth.mobile.send', 'UserController@sendCode');

    $router->post('ecapi.auth.mobile.reset', 'UserController@resetPasswordByMobile');

    $router->post('ecapi.auth.default.reset', 'UserController@resetPasswordByEmail');

    $router->post('ecapi.cardpage.get', 'CardPageController@view');

    $router->post('ecapi.cardpage.preview', 'CardPageController@preview');

    $router->post('ecapi.config.get', 'ConfigController@index');

    $router->post('ecapi.config.wechat', 'ConfigController@wechat');

    $router->post('ecapi.config.affiliate', 'ConfigController@affiliateExpire');

    $router->post('ecapi.config.share', 'ConfigController@share');

    $router->post('ecapi.article.list', 'ArticleController@index');

    $router->post('ecapi.brand.list', 'BrandController@index');

    $router->post('ecapi.search.keyword.list', 'SearchController@index');

    $router->post('ecapi.region.list', 'RegionController@index');

    $router->post('ecapi.invoice.type.list', 'InvoiceController@type');

    $router->post('ecapi.invoice.content.list', 'InvoiceController@content');

    $router->post('ecapi.invoice.status.get', 'InvoiceController@status');

    $router->post('ecapi.notice.list', 'NoticeController@index');

    $router->post('ecapi.banner.list', 'BannerController@index');

    $router->post('ecapi.version.check', 'VersionController@check');

    $router->post('ecapi.recommend.brand.list', 'BrandController@recommend');

    $router->post('ecapi.message.system.list', 'MessageController@system');

    $router->post('ecapi.message.count', 'MessageController@unread');

    $router->post('ecapi.site.get', 'SiteController@index');

    $router->post('ecapi.splash.list', 'SplashController@index');

    $router->post('ecapi.splash.preview', 'SplashController@view');

    $router->post('ecapi.theme.list', 'ThemeController@index');

    $router->post('ecapi.theme.preview', 'ThemeController@view');

    $router->post('ecapi.search.category.list', 'GoodsController@categorySearch');

    $router->post('ecapi.order.reason.list', 'OrderController@reasonList');

    $router->post('ecapi.search.shop.list', 'ShopController@search');

    $router->post('ecapi.recommend.shop.list', 'ShopController@recommand');

    $router->post('ecapi.shop.list', 'ShopController@index');

    $router->post('ecapi.shop.get', 'ShopController@info');

    $router->post('ecapi.areacode.list', 'AreaCodeController@index');

    // 小程序二维码
    $router->get('ecapi.wxa.qrcode', 'SiteController@wxQrcode');

    $router->post('ecapi.site.configs', 'SiteController@configs');

    // 设备注册
    $router->post('ecapi.auth.signinByDevice', 'UserController@signinByDevice');
    $router->post('ecapi.attr.getVideoAttribute', 'AttributeController@getVideoAttribute');
    $router->post('ecapi.video.get', 'VideoController@info');
    // 评论
    $router->post('ecapi.comment.list', 'CommentController@index');
    // 客服联系设置
    $router->post('ecapi.kefu.setting.get', 'KefuSettingsController@index');
    // 保存用户下载信息
    $router->post('ecapi.share.download.insert', 'DownloadController@insertData');
    //
    $router->get('ecapi.version.app.check', 'VersionController@checkApp');

    $router->post('ecapi.virtualcard.use', 'VirtualCardController@use');
});

//Authorization
$router->group(['prefix' => 'v2', 'namespace' => 'v2', 'middleware' => ['token', 'xss']], function ($router) {
    $router->post('ecapi.user.profile.get', 'UserController@profile');

    $router->post('ecapi.user.profile.update', 'UserController@updateProfile');

    $router->post('ecapi.user.password.update', 'UserController@updatePassword');

    $router->post('ecapi.order.list', 'OrderController@index');

    $router->post('ecapi.order.get', 'OrderController@view');

    $router->post('ecapi.order.confirm', 'OrderController@confirm');

    $router->post('ecapi.order.cancel', 'OrderController@cancel');

    $router->post('ecapi.order.price', 'OrderController@price');

    $router->post('ecapi.product.like', 'GoodsController@setLike');

    $router->post('ecapi.product.unlike', 'GoodsController@setUnlike');

    $router->post('ecapi.product.liked.list', 'GoodsController@likedList');

    $router->post('ecapi.order.review', 'OrderController@review');

    $router->post('ecapi.order.subtotal', 'OrderController@subtotal');

    $router->post('ecapi.payment.types.list', 'OrderController@paymentList');

    $router->post('ecapi.payment.pay', 'OrderController@pay');

    $router->post('ecapi.shipping.vendor.list', 'ShippingController@index');

    $router->post('ecapi.shipping.status.get', 'ShippingController@info');

    $router->post('ecapi.consignee.list', 'ConsigneeController@index');

    $router->post('ecapi.consignee.update', 'ConsigneeController@modify');

    $router->post('ecapi.consignee.add', 'ConsigneeController@add');

    $router->post('ecapi.consignee.delete', 'ConsigneeController@remove');

    $router->post('ecapi.consignee.setDefault', 'ConsigneeController@setDefault');

    $router->post('ecapi.score.get', 'ScoreController@view');

    $router->post('ecapi.score.history.list', 'ScoreController@history');

    $router->post('ecapi.cashgift.list', 'CashGiftController@index');

    $router->post('ecapi.cashgift.available', 'CashGiftController@available');

    $router->post('ecapi.cashgift.add', 'CashGiftController@add');

    $router->post('ecapi.push.update', 'MessageController@updateDeviceId');
    //cart
    $router->post('ecapi.cart.add', 'CartController@add');

    $router->post('ecapi.cart.clear', 'CartController@clear');

    $router->post('ecapi.cart.delete', 'CartController@delete');

    $router->post('ecapi.cart.get', 'CartController@index');

    $router->post('ecapi.cart.quantity', 'CartController@quantity');

    $router->post('ecapi.cart.update', 'CartController@update');

    $router->post('ecapi.cart.checkout', 'CartController@checkout');

    $router->post('ecapi.cart.promos', 'CartController@promos');

    $router->post('ecapi.product.purchase', 'GoodsController@purchase');

    $router->post('ecapi.product.validate', 'GoodsController@checkProduct');

    $router->post('ecapi.message.order.list', 'MessageController@order');

    $router->post('ecapi.shop.watch', 'ShopController@watch');

    $router->post('ecapi.shop.unwatch', 'ShopController@unwatch');

    $router->post('ecapi.shop.watching.list', 'ShopController@watchingList');

    $router->post('ecapi.coupon.list', 'CouponController@index');

    $router->post('ecapi.coupon.available', 'CouponController@available');

    $router->post('ecapi.recommend.bonus.list', 'AffiliateController@index');
    $router->post('ecapi.recommend.bonus.info', 'AffiliateController@info');

    $router->post('ecapi.withdraw.submit', 'AccountController@submit');
    $router->post('ecapi.withdraw.cancel', 'AccountController@cancel');
    $router->post('ecapi.withdraw.list', 'AccountController@index');
    $router->post('ecapi.withdraw.info', 'AccountController@getDetail');

    $router->post('ecapi.balance.get', 'AccountController@surplus');
    $router->post('ecapi.balance.list', 'AccountController@accountDetail');

    //关注用户
    $router->post('ecapi.user.setAttention', 'UserController@setAttention');
    $router->post('ecapi.user.getAttention', 'UserController@getAttention');
    $router->post('ecapi.user.attentioned.list', 'UserController@attentionedList');
    $router->post('ecapi.user.getProfileByUserId', 'UserController@getProfileByUserId');
    $router->post('ecapi.auth.mobile.binding', 'UserController@bindByMobile');
    $router->post('ecapi.user.avatar.update', 'UserController@updateAvatar');
    //视频
    $router->post('ecapi.home.video.list', 'VideoController@home');
    $router->post('ecapi.video.list', 'VideoController@index');
    $router->post('ecapi.video.addWatchLog', 'VideoController@addWatchLog');
    $router->post('ecapi.video.checkWatchTimes', 'VideoController@checkWatchTimes');
    $router->post('ecapi.video.like', 'VideoController@setLike');
    $router->post('ecapi.video.unlike', 'VideoController@setUnlike');
    $router->post('ecapi.video.liked.list', 'VideoController@likedList');
    $router->post('ecapi.video.getWatchLog', 'VideoController@getWatchLog');
    //订单
    $router->post('ecapi.cart.createVideoOrder', 'CartController@createVideoOrder');
    // 评论
    $router->post('ecapi.comment.create', 'CommentController@create');
    $router->post('ecapi.comment.createRate', 'CommentController@createRate');
    $router->post('ecapi.comment.getInfo', 'CommentController@getInfo');
    // 演员
    $router->post('ecapi.actor.getvideolistbyactorid', 'ActorController@getVideoListByActorId');
    //关注演员
    $router->post('ecapi.actor.setAttention', 'ActorController@setAttention');
    $router->post('ecapi.actor.getAttention', 'ActorController@getAttention');
    $router->post('ecapi.actor.attentioned.list', 'ActorController@attentionedList');
    // 虚拟卡
//    $router->post('ecapi.virtualcard.use', 'VirtualCardController@use');
});
