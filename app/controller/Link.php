<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;
use app\common\service\ProprietaryService;
use think\response\File;

class Link
{
    public function list(Request $request)
    {
        $user = $request->user;
        $page = max(1, (int)$request->param('page', 1));
        $limit = min(100, max(1, (int)$request->param('limit', 50)));
        $keyword = trim($request->param('keyword'));

        $query = Db::name('link')->where('user_id', $user['id']);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('code', "%{$keyword}%")->whereOr('remark', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();
        $links = json_decode(file_get_contents(root_path() . 'urls.json'), true);

        $list = $query->order('id', 'desc')->page($page, $limit)->select()->map(function ($link) use ($links) {
            $link['create_time'] = date('Y-m-d H:i', $link['create_time']);

            $channelTypes = ['website', 'securewebsite', 'qq', 'qqqun'];
            if (in_array($link['channel_type'], $channelTypes, false)) {
                $sourceKeys = [
                    'CARD_JUMP_URLS',
                    'CARD_CARD_URLS_DOUYIN',
                    'CARD_CARD_URLS_KUAISHOU',
                ];
            } else {
                $sourceKeys = [
                    'LINK_JUMP_URLS',
                    'LINK_CARD_URLS_DOUYIN',
                    'LINK_CARD_URLS_KUAISHOU',
                ];
            }

            $outputKeys = [
                'link_jump_urls',
                'link_card_urls_douyin',
                'link_card_urls_kuaishou',
            ];

            foreach ($sourceKeys as $index => $sourceKey) {
                $outputKey = $outputKeys[$index];
                $link[$outputKey] = array_map(function ($item) use ($link) {
                    $baseUrl = $item['url'];
                    $code = $link['code'];

                    if (strpos($baseUrl, '{code}') !== false) {
                        $url = str_replace('{code}', $code, $baseUrl);
                    } else {
                        $separator = str_contains($baseUrl, '?') ? '&' : '?';
                        $url = $baseUrl . $separator . 'code=' . $code;
                    }

                    return [
                        'url' => $url,
                        'desc' => $item['desc'],
                    ];
                }, $links[$sourceKey]);
            }

            return $link;
        });

        return json([
            'code' => 0,
            'message' => '查询成功',
            'list' => $list,
            'total' => $total
        ]);
    }

    public function detail(Request $request)
    {
        $user = $request->user;
        $code = $request->param('code');

        $link = Db::name('link')->where('code', $code)->where('user_id', $user['id'])->find();
        if (!$link) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $link_channel = Db::name('link_channel')->where('status', 0)->order('sort')->select();

        $land = Db::name('land')->where('user_id', $user['id'])->order('id')->select();

        return json(['code' => 0, 'message' => '查询成功', 'link' => $link, 'link_channel' => $link_channel, 'land' => $land]);
    }

    public function add(Request $request)
    {
        $user = $request->user;

        if (strtotime($user['end_time']) <= time()) {
            return json(['code' => 1, 'message' => '会员到期']);
        }

        $plan = Db::name('plan')->where('id', $user['plan_id'])->find();

        $link = Db::name('link')->where('user_id', $user['id'])->count();
        if ($link >= $plan['link']) {
            return json(['code' => 1, 'message' => $plan['name'] . '可以创建' . $plan['link'] . '个卡片']);
        }

        do {
            $code = random_int(100000, 999999);
            $exists = Db::name('link')->where('code', $code)->count();
        } while ($exists);

        Db::name('link')->insertGetId(['user_id' => $user['id'], 'create_time' => time(), 'end_time' => '9999-09-09', 'code' => $code]);

        return json(['code' => 0, 'message' => '创建成功', 'newcode' => $code]);
    }

    public function edit(Request $request)
    {
        $user = $request->user;
        $code = $request->param('code');

        $link = Db::name('link')->where('code', $code)->where('user_id', $user['id'])->find();
        if (!$link) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $data = $request->only(['remark', 'end_time', 'land_id', 'channel_type', 'page_title', 'page_desc', 'page_icon', 'www_url', 'txqr_url']);
        $data['openlink'] = null;
        $data['openlink_expires'] = null;

        if (!empty($data['land_id'])) {
            $land = Db::name('land')->where('id', $data['land_id'])->where('user_id', $user['id'])->find();
            if (!$land) {
                return json(['code' => 1, 'message' => '没有跳转模板权限']);
            }
        }

        if (!empty($data['channel_type'])) {
            $link_channel = Db::name('link_channel')->where('type', $data['channel_type'])->where('status', 0)->find();
            if (!$link_channel) {
                return json(['code' => 1, 'message' => '没有跳转渠道权限']);
            }
        }

        Db::name('link')->where('code', $code)->update($data);

        return json(['code' => 0, 'message' => '编辑成功']);
    }

    public function delete(Request $request)
    {
        $user = $request->user;
        $code = $request->param('code');

        $link = Db::name('link')->where('code', $code)->where('user_id', $user['id'])->find();
        if (!$link) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        Db::name('link')->where('code', $code)->delete();

        return json(['code' => 0, 'message' => '删除成功']);
    }

    public function entryCard(Request $request)
    {
        $host = $request->host();

        if (!preg_match('/^(\d{6})\..+$/', $host, $matches)) {
            http_response_code(404);
            exit;
        }

        $code      = $matches[1];
        $scheme    = $request->scheme();
        $timestamp = time();
        $domain    = substr($host, strpos($host, '.') + 1);
        $url       = "{$scheme}://{$timestamp}.{$domain}/c/{$code}";

        return redirect($url, 302);
    }

    public function openCard(Request $request)
    {
        $code   = $request->param('code');
        $ua     = $request->header('user-agent');
        $ip     = $request->ip();
        $uaInfo = $this->getUaInfo();

        $targetUA = [
            'Mozilla/5.0 (Linux; Android 10; HD1900 Build/QKQ1.190716.003; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/75.0.3770.156 Mobile Safari/537.36  aweme_230400 JsSdk/1.0 NetType/WIFI  AppName/aweme app_version/23.4.0 ByteLocale/zh-CN Region/CN AppSkin/white AppTheme/light BytedanceWebview/d8a21c6 WebView/075113004008',
            'Mozilla/5.0 (Linux; Android 13; PFFM10 Build/TP1A.220905.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/88.0.4324.181 Mobile Safari/537.36 aweme_250000 JsSdk/1.0 NetType/WIFI Channel/31725782a AppName/aweme app_version/25.0.0 ByteLocale/zh-CN Region/CN AppSkin/white AppTheme/light BULLET/1 BytedanceWebview/d8a21c6 TTWebView/0881130048402',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        ];

        if (in_array($ua, $targetUA)) {
            return view('link/no_view');
        }

        $link = Db::name('link')->where('code', $code)->find();
        if (!$link) {
            return view('link/no_link');
        }

        $user = Db::name('user')->where('id', $link['user_id'])->find();
        if (!$user || strtotime($user['end_time']) <= time()) {
            return view('link/expire_user');
        }

        if (strtotime($link['end_time']) <= time()) {
            return view('link/expire_link');
        }

        Db::name('logs')->insert([
            'code'    => $code,
            'type'    => 'link',
            'time'    => time(),
            'ip'      => $ip,
            'device'  => $uaInfo['device'],
            'os'      => $uaInfo['os'],
            'browser' => $uaInfo['browser'],
        ]);

        return view('link/land_default', ['link' => $link]);
    }

    public function card(Request $request)
    {
        $code   = $request->param('code');
        $ua     = $request->header('user-agent');
        $ip     = $request->ip();
        $uaInfo = $this->getUaInfo();

        $targetUA = [
            'Mozilla/5.0 (Linux; Android 10; HD1900 Build/QKQ1.190716.003; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/75.0.3770.156 Mobile Safari/537.36  aweme_230400 JsSdk/1.0 NetType/WIFI  AppName/aweme app_version/23.4.0 ByteLocale/zh-CN Region/CN AppSkin/white AppTheme/light BytedanceWebview/d8a21c6 WebView/075113004008',
            'Mozilla/5.0 (Linux; Android 13; PFFM10 Build/TP1A.220905.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/88.0.4324.181 Mobile Safari/537.36 aweme_250000 JsSdk/1.0 NetType/WIFI Channel/31725782a AppName/aweme app_version/25.0.0 ByteLocale/zh-CN Region/CN AppSkin/white AppTheme/light BULLET/1 BytedanceWebview/d8a21c6 TTWebView/0881130048402',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        ];

        if (in_array($ua, $targetUA)) {
            return json(['code' => 1, 'message' => '抖音记录美好生活']);
        }

        $link = Db::name('link')->where('code', $code)->find();
        if (!$link) {
            return json(['code' => 1, 'message' => '卡片不存在']);
        }

        $user = Db::name('user')->where('id', $link['user_id'])->find();
        if (!$user || strtotime($user['end_time']) <= time()) {
            return json(['code' => 1, 'message' => '会员已到期']);
        }

        if (strtotime($link['end_time']) <= time()) {
            return json(['code' => 1, 'message' => '卡片已到期']);
        }

        Db::name('logs')->insert([
            'code'    => $code,
            'type'    => 'link',
            'time'    => time(),
            'ip'      => $ip,
            'device'  => $uaInfo['device'],
            'os'      => $uaInfo['os'],
            'browser' => $uaInfo['browser'],
        ]);

        return json(['code' => 0, 'message' => '查询成功', 'channel_type' => $link['channel_type'], 'page_title' => $link['page_title'], 'page_desc' => $link['page_desc'], 'page_icon' => $link['page_icon'], 'www_url' => $link['www_url']]);
    }

    public function urlScheme(Request $request)
    {
        $code = $request->param('code');

        $link = Db::name('link')->where('code', $code)->find();
        if (!$link) {
            return json(['code' => 1, 'message' => '卡片不存在']);
        }

        $user = Db::name('user')->where('id', $link['user_id'])->find();
        if (!$user || strtotime($user['end_time']) <= time()) {
            return json(['code' => 1, 'message' => '会员已到期']);
        }

        if (strtotime($link['end_time']) <= time()) {
            return json(['code' => 1, 'message' => '卡片已到期']);
        }

        try {
            $openlink = ProprietaryService::getOpenlink($link);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'message' => $e->getMessage()]);
        }

        $land = Db::name('land')->where('id', $link['land_id'] ?? 0)->find();
        if (!$land) {
            $land = Db::name('land')->where('id', 1)->find();
        }
        $land = [
            'type' => $land['type'],
            'config' => $land['config']
        ];

        return json(['code' => 0, 'message' => '查询成功', 'openlink' => $openlink, 'land' => $land]);
    }

    private function getUaInfo()
    {
        $ua = request()->header('user-agent');

        if (strpos($ua, 'iPhone') !== false) {
            $device = 'iPhone';
        } elseif (strpos($ua, 'iPad') !== false) {
            $device = 'iPad';
        } elseif (strpos($ua, 'iPod') !== false) {
            $device = 'iPod';
        } elseif (strpos($ua, 'BlackShark') !== false) {
            $device = '黑鲨';
        } elseif (strpos($ua, 'RedMagic') !== false) {
            $device = '红魔';
        } elseif (strpos($ua, 'ROG') !== false) {
            $device = 'ROG';
        } elseif (strpos($ua, 'Legion') !== false) {
            $device = '拯救者';
        } elseif (strpos($ua, 'Redmi') !== false) {
            $device = '红米';
        } elseif (strpos($ua, 'Xiaomi') !== false) {
            $device = '小米';
        } elseif (strpos($ua, 'HUAWEI') !== false) {
            $device = '华为';
        } elseif (strpos($ua, 'Honor') !== false) {
            $device = '荣耀';
        } elseif (strpos($ua, 'OPPO') !== false) {
            $device = 'OPPO';
        } elseif (strpos($ua, 'realme') !== false) {
            $device = '真我';
        } elseif (strpos($ua, 'OnePlus') !== false) {
            $device = '一加';
        } elseif (strpos($ua, 'VIVO') !== false) {
            $device = 'VIVO';
        } elseif (strpos($ua, 'iQOO') !== false) {
            $device = 'iQOO';
        } elseif (strpos($ua, 'Samsung') !== false) {
            $device = '三星';
        } elseif (strpos($ua, 'LG') !== false) {
            $device = 'LG';
        } elseif (strpos($ua, 'Meizu') !== false) {
            $device = '魅族';
        } elseif (strpos($ua, 'ZTE') !== false) {
            $device = '中兴';
        } elseif (strpos($ua, 'nubia') !== false) {
            $device = '努比亚';
        } elseif (strpos($ua, 'Lenovo') !== false) {
            $device = '联想';
        } elseif (strpos($ua, 'Motorola') !== false) {
            $device = '摩托罗拉';
        } elseif (strpos($ua, 'Coolpad') !== false) {
            $device = '酷派';
        } elseif (strpos($ua, 'Hisense') !== false) {
            $device = '海信';
        } elseif (strpos($ua, 'Infinix') !== false || strpos($ua, 'Tecno') !== false) {
            $device = '传音';
        } elseif (strpos($ua, 'Smartisan') !== false) {
            $device = '坚果';
        } elseif (strpos($ua, 'Nokia') !== false) {
            $device = '诺基亚';
        } elseif (strpos($ua, 'Surface') !== false) {
            $device = '微软';
        } elseif (strpos($ua, 'HUAWEI Pad') !== false) {
            $device = '华为平板';
        } elseif (strpos($ua, 'Honor Pad') !== false) {
            $device = '荣耀平板';
        } elseif (strpos($ua, 'Xiaomi Pad') !== false) {
            $device = '小米平板';
        } elseif (strpos($ua, 'Redmi Pad') !== false) {
            $device = '红米平板';
        } elseif (strpos($ua, 'vivo Pad') !== false) {
            $device = 'vivo平板';
        } elseif (strpos($ua, 'OPPO Pad') !== false) {
            $device = 'OPPO平板';
        } else {
            $device = '未知';
        }

        if (strpos($ua, 'Windows NT 10.0; Win64; x64') !== false) {
            $os = 'Windows 11';
        } elseif (strpos($ua, 'Windows NT 10.0') !== false) {
            $os = 'Windows 10';
        } elseif (strpos($ua, 'Windows NT 6.3') !== false) {
            $os = 'Windows 8.1';
        } elseif (strpos($ua, 'Windows NT 6.2') !== false) {
            $os = 'Windows 8';
        } elseif (strpos($ua, 'Windows NT 6.1') !== false) {
            $os = 'Windows 7';
        } elseif (strpos($ua, 'Windows NT 6.0') !== false) {
            $os = 'Windows Vista';
        } elseif (strpos($ua, 'Windows NT 5.1') !== false) {
            $os = 'Windows XP';
        } elseif (strpos($ua, 'Windows Server') !== false) {
            $os = 'Windows Server';
        } elseif (strpos($ua, 'iPad; CPU OS') !== false || preg_match('/iPadOS (\d+_\d+)/', $ua, $match)) {
            $os = 'iPadOS ' . str_replace('_', '.', $match[1] ?? '');
        } elseif (preg_match('/(iPhone OS|iOS) (\d+_\d+(?:_\d+)?)/', $ua, $match)) {
            $os = 'iOS ' . str_replace('_', '.', $match[2]);
        } elseif (strpos($ua, 'iPhone OS') !== false || strpos($ua, 'iOS') !== false) {
            $os = 'iOS';
        } elseif (preg_match('/Mac OS X (\d+_\d+(?:_\d+)?)/', $ua, $match)) {
            $os = 'macOS ' . str_replace('_', '.', $match[1]);
        } elseif (strpos($ua, 'Mac OS X') !== false || strpos($ua, 'macOS') !== false) {
            $os = 'macOS';
        } elseif (preg_match('/Android (\d+\.\d+(?:\.\d+)?)/', $ua, $match)) {
            $os = 'Android ' . $match[1];
        } elseif (strpos($ua, 'Android') !== false) {
            $os = 'Android';
        } elseif (strpos($ua, 'HarmonyOS') !== false) {
            $os = 'HarmonyOS';
        } elseif (strpos($ua, 'UOS') !== false) {
            $os = 'UOS';
        } elseif (strpos($ua, 'Kylin') !== false) {
            $os = '麒麟';
        } elseif (strpos($ua, 'CentOS') !== false) {
            $os = 'CentOS';
        } elseif (strpos($ua, 'Debian') !== false) {
            $os = 'Debian';
        } elseif (strpos($ua, 'Ubuntu') !== false) {
            $os = 'Ubuntu';
        } elseif (strpos($ua, 'Linux') !== false) {
            $os = 'Linux';
        } else {
            $os = '未知';
        }

        if (strpos($ua, 'MicroMessenger') !== false) {
            $browser = '微信';
        } elseif (strpos($ua, 'QQ/') !== false) {
            $browser = 'QQ';
        } elseif (strpos($ua, 'aweme') !== false || strpos($ua, 'ByteDanceWebview') !== false) {
            $browser = '抖音';
        } elseif (strpos($ua, 'AlipayClient') !== false) {
            $browser = '支付宝';
        } elseif (strpos($ua, 'Taobao') !== false) {
            $browser = '淘宝';
        } elseif (strpos($ua, 'Tmall') !== false) {
            $browser = '天猫';
        } elseif (strpos($ua, 'DingTalk') !== false) {
            $browser = '钉钉';
        } elseif (strpos($ua, 'Weibo') !== false) {
            $browser = '微博';
        } elseif (strpos($ua, 'Lark') !== false) {
            $browser = '飞书';
        } elseif (strpos($ua, 'Xiaohongshu') !== false) {
            $browser = '小红书';
        } elseif (strpos($ua, 'bilibili') !== false) {
            $browser = '哔哩';
        } elseif (strpos($ua, 'Zhihu') !== false) {
            $browser = '知乎';
        } elseif (strpos($ua, 'Douban') !== false) {
            $browser = '豆瓣';
        } elseif (strpos($ua, 'iQIYI') !== false || strpos($ua, 'Qiyi') !== false) {
            $browser = '爱奇艺';
        } elseif (strpos($ua, 'QQVideo') !== false) {
            $browser = '腾讯视频';
        } elseif (strpos($ua, 'Youku') !== false) {
            $browser = '优酷';
        } elseif (strpos($ua, 'Baidu') !== false) {
            $browser = '百度';
        } elseif (strpos($ua, 'Quark') !== false) {
            $browser = '夸克浏览器';
        } elseif (strpos($ua, 'Meituan') !== false) {
            $browser = '美团';
        } elseif (strpos($ua, 'Eleme') !== false) {
            $browser = '饿了么';
        } elseif (strpos($ua, 'Ctrip') !== false) {
            $browser = '携程';
        } elseif (strpos($ua, 'Maimai') !== false) {
            $browser = '脉脉';
        } elseif (strpos($ua, 'Autohome') !== false) {
            $browser = '汽车之家';
        } elseif (strpos($ua, 'Xunlei') !== false) {
            $browser = '迅雷';
        } elseif (strpos($ua, 'MiuiBrowser') !== false) {
            preg_match('/MiuiBrowser\/(\d+)/', $ua, $match);
            $browser = $match ? '小米浏览器 ' . $match[1] : '小米浏览器';
        } elseif (strpos($ua, 'HuaweiBrowser') !== false) {
            preg_match('/HuaweiBrowser\/(\d+)/', $ua, $match);
            $browser = $match ? '华为浏览器 ' . $match[1] : '华为浏览器';
        } elseif (strpos($ua, 'OPPOBrowser') !== false) {
            preg_match('/OPPOBrowser\/(\d+)/', $ua, $match);
            $browser = $match ? 'OPPO浏览器 ' . $match[1] : 'OPPO浏览器';
        } elseif (strpos($ua, 'vivoBrowser') !== false) {
            preg_match('/vivoBrowser\/(\d+)/', $ua, $match);
            $browser = $match ? 'vivo浏览器 ' . $match[1] : 'vivo浏览器';
        } elseif (strpos($ua, 'SamsungBrowser') !== false) {
            preg_match('/SamsungBrowser\/(\d+)/', $ua, $match);
            $browser = $match ? '三星浏览器 ' . $match[1] : '三星浏览器';
        } elseif (strpos($ua, 'QQBrowser') !== false) {
            preg_match('/QQBrowser\/(\d+)/', $ua, $match);
            $browser = $match ? 'QQ浏览器 ' . $match[1] : 'QQ浏览器';
        } elseif (strpos($ua, 'UCBrowser') !== false || strpos($ua, 'UCWEB') !== false) {
            preg_match('/UCBrowser\/(\d+)/', $ua, $match);
            $browser = $match ? 'UC浏览器 ' . $match[1] : 'UC浏览器';
        } elseif (strpos($ua, 'Sogou') !== false) {
            preg_match('/SogouMobileBrowser\/(\d+)/', $ua, $match);
            $browser = $match ? '搜狗浏览器 ' . $match[1] : '搜狗浏览器';
        } elseif (strpos($ua, '360SE') !== false || strpos($ua, '360EE') !== false) {
            preg_match('/360SE\/(\d+)|360EE\/(\d+)/', $ua, $match);
            $browser = $match[1] ? '360安全浏览器 ' . $match[1] : ($match[2] ? '360极速浏览器 ' . $match[2] : '360浏览器');
        } elseif (strpos($ua, 'Maxthon') !== false) {
            preg_match('/Maxthon\/(\d+)/', $ua, $match);
            $browser = $match ? '傲游浏览器 ' . $match[1] : '傲游浏览器';
        } elseif (strpos($ua, 'Liebao') !== false) {
            $browser = '猎豹浏览器';
        } elseif (strpos($ua, '2345Explorer') !== false) {
            $browser = '2345浏览器';
        } elseif (strpos($ua, 'CentBrowser') !== false) {
            $browser = '百分浏览器';
        } elseif (preg_match('/Edg\/(\d+)/', $ua, $match)) {
            $browser = 'Edge ' . $match[1];
        } elseif (preg_match('/Chrome\/(\d+)/', $ua, $match)) {
            $browser = 'Chrome ' . $match[1];
        } elseif (preg_match('/Safari\/(\d+)/', $ua, $match)) {
            $browser = 'Safari ' . $match[1];
        } elseif (preg_match('/Firefox\/(\d+)/', $ua, $match)) {
            $browser = 'Firefox ' . $match[1];
        } else {
            $browser = '未知';
        }

        return compact('device', 'os', 'browser');
    }
}
