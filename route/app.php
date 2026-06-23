<?php
use think\facade\Route;
use app\middleware\Auth;

Route::get('c$',                          'Link/entryCard');
Route::get('c/:code',                     'Link/openCard')->pattern(['code' => '\d{6}']);

Route::get('s/:short_url',                'ShortUrl/s');

Route::group('api', function () {
    Route::post('checkurl/douyin',        'CheckUrl/douyin');
    Route::post('checkurl/weibo',         'CheckUrl/weibo');

    Route::get('cron/miguCookie',         'Cron/miguCookie');
    Route::get('cron/expiredData',        'Cron/expiredData');

    Route::get('link/card',               'Link/card');
    Route::get('link/urlScheme',          'Link/urlScheme');

    Route::post('order/notify/wechat',    'Order/wechat');
    Route::post('order/notify/alipay',    'Order/alipay');

    Route::post('points/notify/wechat',   'Points/wechat');
    Route::post('points/notify/alipay',   'Points/alipay');

    Route::get('qr',                      'Qr/generate');

    Route::get('site/config',             'Site/config');
    Route::get('site/update',             'Site/update');

    Route::post('user/createScene',       'User/createScene');
    Route::post('user/checkScene',        'User/checkScene');
    Route::post('user/markScene',         'User/markScene');
    Route::post('user/wxLogin',           'User/wxLogin');
    Route::post('user/wxBind',            'User/wxBind');
    Route::post('user/login',             'User/login');
    Route::post('user/register',          'User/register');
    Route::get('user/plan',               'User/plan');

    Route::get('wechat/server',           'Wechat/server');
    Route::post('wechat/server',          'Wechat/server');
    Route::get('wechat/mediaList',        'Wechat/mediaList');
});

Route::group('api', function () {
    Route::get('coupon/list',             'Coupon/list');
    Route::post('coupon/add',             'Coupon/add');
    Route::post('coupon/delete',          'Coupon/delete');
    Route::post('coupon/transfer',        'Coupon/transfer');

    Route::get('land/list',               'Land/list');
    Route::get('land/detail',             'Land/detail');
    Route::post('land/add',               'Land/add');
    Route::post('land/edit',              'Land/edit');
    Route::post('land/delete',            'Land/delete');
    Route::post('land/setDefault',        'Land/setDefault');

    Route::get('link/list',               'Link/list');
    Route::get('link/detail',             'Link/detail');
    Route::post('link/add',               'Link/add');
    Route::post('link/edit',              'Link/edit');
    Route::post('link/delete',            'Link/delete');

    Route::get('logs/list',               'Logs/list');

    Route::get('material/list',           'Material/list');
    Route::post('material/delete',        'Material/delete');

    Route::get('menu/list',               'Menu/list');
    Route::get('menu/updateTime',         'Menu/updateTime');

    Route::get('order/list',              'Order/list');
    Route::post('order/create',           'Order/create');
    Route::post('order/pay',              'Order/pay');
    Route::post('order/cancel',           'Order/cancel');
    Route::post('order/status',           'Order/status');

    Route::get('points/list',             'Points/list');
    Route::post('points/create',          'Points/create');
    Route::post('points/pay',             'Points/pay');

    Route::get('qrcode/list',             'Qrcode/list');
    Route::get('qrcode/detail',           'Qrcode/detail');
    Route::post('qrcode/add',             'Qrcode/add');
    Route::post('qrcode/edit',            'Qrcode/edit');
    Route::post('qrcode/delete',          'Qrcode/delete');

    Route::get('shorturl/list',           'ShortUrl/list');
    Route::get('shorturl/detail',         'ShortUrl/detail');
    Route::post('shorturl/add',           'ShortUrl/add');
    Route::post('shorturl/edit',          'ShortUrl/edit');
    Route::post('shorturl/delete',        'ShortUrl/delete');

    Route::post('upload/image',           'Upload/image');

    Route::get('douyinim/list',               'DouyinIm/list');
    Route::post('douyinim/add',               'DouyinIm/add');
    Route::post('douyinim/update',            'DouyinIm/update');
    Route::post('douyinim/status',             'DouyinIm/status');
    Route::post('douyinim/delete',            'DouyinIm/delete');
    Route::post('douyinim/refresh_info',      'DouyinIm/refreshInfo');
    Route::post('douyinim/preview_cookie',    'DouyinIm/previewCookie');


    Route::post('douyinim/user_detail',       'DouyinIm/userDetail');
    Route::post('douyinim/conv_list',         'DouyinIm/convListOnly');
    Route::post('douyinim/fetch_conv_list',   'DouyinIm/convListOnly');
    Route::post('douyinim/conv_messages',     'DouyinIm/convMessages');
    Route::post('douyinim/all_histories',     'DouyinIm/allHistories');
    Route::post('douyinim/ws_info',           'DouyinIm/wsInfo');
    Route::post('douyinim/recall_proxy',      'DouyinIm/recallProxy');
    Route::get('douyinim/cards',              'DouyinIm/cards');
    Route::post('douyinim/card/add',          'DouyinIm/cardAdd');
    Route::post('douyinim/card/update',       'DouyinIm/cardUpdate');
    Route::post('douyinim/card/delete',       'DouyinIm/cardDelete');


    Route::get('user/logout',             'User/logout');
    Route::get('user/info',               'User/info');
    Route::get('user/inviteList',         'User/inviteList');
    Route::post('user/uploadAvatar',      'User/uploadAvatar');
    Route::post('user/editInfo',          'User/editInfo');
})->middleware(Auth::class);

Route::group('api/admin', function () {
    Route::post('coupon/create',          'Coupon/create');

    Route::get('link/channel/list',       'Link/channelList');
    Route::post('link/channel/add',       'Link/channelAdd');
    Route::post('link/channel/edit',      'Link/channelEdit');
    Route::post('link/channel/delete',    'Link/channelDelete');
    Route::get('link/list',               'Link/list');
    Route::post('link/edit',              'Link/edit');
    Route::post('link/delete',            'Link/delete');

    Route::get('material/list',           'Material/list');
    Route::post('material/delete',        'Material/delete');

    Route::get('menu/list',               'Menu/list');
    Route::post('menu/add',               'Menu/add');
    Route::post('menu/edit',              'Menu/edit');
    Route::post('menu/delete',            'Menu/delete');

    Route::get('plan/list',               'Plan/list');
    Route::post('plan/add',               'Plan/add');
    Route::post('plan/edit',              'Plan/edit');
    Route::post('plan/delete',            'Plan/delete');

    Route::get('qrcode/channel/list',     'Qrcode/channelList');
    Route::post('qrcode/channel/add',     'Qrcode/channelAdd');
    Route::post('qrcode/channel/edit',    'Qrcode/channelEdit');
    Route::post('qrcode/channel/delete',  'Qrcode/channelDelete');
    Route::get('qrcode/list',             'Qrcode/list');
    Route::post('qrcode/edit',            'Qrcode/edit');
    Route::post('qrcode/delete',          'Qrcode/delete');

    Route::get('shorturl/channel/list',   'Shorturl/channelList');
    Route::post('shorturl/channel/add',   'Shorturl/channelAdd');
    Route::post('shorturl/channel/edit',  'Shorturl/channelEdit');
    Route::post('shorturl/channel/delete','Shorturl/channelDelete');
    Route::get('shorturl/list',           'Shorturl/list');
    Route::post('shorturl/edit',          'Shorturl/edit');
    Route::post('shorturl/delete',        'Shorturl/delete');

    Route::get('site/urls/list',          'Site/urlsList');
    Route::post('site/urls/edit',         'Site/urlsEdit');
    Route::get('site/selfmenu/list',      'Site/selfmenuList');
    Route::post('site/selfmenu/delete',   'Site/selfmenuDelete');
    Route::post('site/selfmenu/create',   'Site/selfmenuCreate');
    Route::get('site/autoreply/list',     'Site/autoreplyList');
    Route::post('site/autoreply/save',    'Site/autoreplySave');
    Route::post('site/autoreply/delete',  'Site/autoreplyDelete');
    Route::post('site/version/check',     'Site/versionCheck');
    Route::post('site/version/update',    'Site/versionUpdate');

    Route::get('user/list',               'User/list');
    Route::post('user/edit',              'User/edit');
    Route::post('user/delete',            'User/delete');
})->prefix('app\controller\admin\\')->middleware(Auth::class);