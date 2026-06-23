<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;

class Site
{
    public function config(Request $request)
    {
        $plan = Db::name('plan')->select();
        return json([
            'code'              => 0,
            'message'           => 'success',
            'plan'              => $plan,
            'version'           => env('APP_VERSION'),
            'name'              => env('SITE_NAME'),
            'notice'            => env('SITE_NOTICE'),
            'notice_mobile'     => env('SITE_NOTICE_MOBILE'),
            'notice_pop'        => env('SITE_NOTICE_POP'),
            'avatar'            => env('SITE_AVATAR'),
            'logo'              => env('SITE_LOGO'),
            'logo_mini'         => env('SITE_LOGO_MINI'),
            'kefu_qr'           => env('SITE_KEFU_QR'),
            'app_qr'            => env('SITE_APP_QR'),
            'app_link'          => env('SITE_APP_LINK'),
            'app_version'       => env('SITE_APP_VERSION'),
            'copyright'         => env('SITE_COPYRIGHT'),
            'icp'               => env('SITE_ICP'),
            'opints'            => env('SITE_POINTS'),
            'first_commission'  => env('SITE_FIRST_COMMISSION'),
            'second_commission' => env('SITE_SECOND_COMMISSION'),
            'register'          => env('SITE_REGISTER'),
            'dy_clientkey'      => env('DY_CLIENTKEY')
        ]);
    }

    public function update(Request $request)
    {
        $platform = $request->param('platform');
        $version = $request->param('version');
        $latestVersion = env('SITE_APP_VERSION');
        $updateUrl = $platform === 'ios' ? env('SITE_APP_LINK') : env('SITE_APP_LINK');
        if ($version !== $latestVersion) {
            return json(['code' => 0, 'message' => '发现新版本!', 'url' => $updateUrl]);
        }
    }
}