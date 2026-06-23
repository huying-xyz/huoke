<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;
use GuzzleHttp\Client;

class ShortUrl
{
    public function list(Request $request)
    {
        $user = $request->user;
        $page = max(1, (int)$request->param('page', 1));
        $limit = min(100, max(1, (int)$request->param('limit', 50)));
        $keyword = trim($request->param('keyword'));
        $channel_type = trim($request->param('channel_type'));

        if ($channel_type === '') {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        $query = Db::name('shorturl')->where('user_id', $user['id'])->where('channel_type', $channel_type);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('code', "%{$keyword}%")->whereOr('remark', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();

        $list = $query->order('id', 'desc')->page($page, $limit)->select();

        if ($channel_type === 'short') {
            $links = json_decode(file_get_contents(root_path() . 'urls.json'), true);
    
            $list = $list->map(function ($shorturl) use ($links) {
                $shorturl['short_urls'] = array_map(function ($item) use ($shorturl) {
                    return [
                        'url'  => rtrim($item['url'], '/') . '/s/' . $shorturl['short_url'],
                        'desc' => $item['desc'],
                    ];
                }, $links['SHORT_URLS']);
                return $shorturl;
            });
        }

        $shorturl_channel = Db::name('shorturl_channel')->where('status', 0)->order('sort')->select();

        return json(['code' => 0, 'message' => '查询成功', 'list' => $list, 'total' => $total, 'shorturl_channel' => $shorturl_channel]);
    }

    public function detail(Request $request)
    {
        $user = $request->user;
        $code = $request->param('code');

        $shorturl = Db::name('shorturl')->where('code', $code)->where('user_id', $user['id'])->find();
        if (!$shorturl) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $shorturl_channel = Db::name('shorturl_channel')->where('status', 0)->order('sort')->select();

        return json(['code' => 0, 'message' => '查询成功', 'shorturl' => $shorturl, 'shorturl_channel' => $shorturl_channel]);
    }

    public function add(Request $request)
    {
        $user = $request->user;
        $channel_type = $request->param('channel_type');

        $shorturl_channel = Db::name('shorturl_channel')->where('type', $channel_type)->where('status', 0)->find();
        if (!$shorturl_channel) {
            return json(['code' => 1, 'message' => '没有渠道权限']);
        }

        $feature_fee_json = isset($user['feature_fee']) && $user['feature_fee'] !== null ? $user['feature_fee'] : '{}';
        $feature_fee = json_decode($feature_fee_json, true) ?: [];

        $permissionKey = 'shorturl-' . $channel_type;
        $isFree = isset($feature_fee[$permissionKey]) && $feature_fee[$permissionKey] === true;

        do {
            $code = random_int(100000, 999999);
        } while (Db::name('shorturl')->where('code', $code)->count() > 0);

        Db::startTrans();
        try {
            if (!$isFree) {
                $points = (int)$shorturl_channel['points'];

                if ($points > 0) {
                    $affected = Db::name('user')->where('id', $user['id'])->where('points', '>=', $points)->dec('points', $points)->update();

                    if ($affected === 0) {
                        throw new \Exception('积分不足');
                    }

                    $before_points = $user['points'];
                    $after_points  = $before_points - $points;

                    Db::name('points')->insert(['user_id' => $user['id'], 'points_change' => -$points, 'type' => 2, 'remark' => '创建短链', 'before_points' => $before_points, 'after_points' => $after_points, 'create_time' => time()]);
                }
            }

            Db::name('shorturl')->insert(['user_id' => $user['id'], 'channel_type' => $channel_type, 'code' => $code]);

            Db::commit();
            return json(['code' => 0, 'message' => '创建成功', 'newcode' => $code]);
        } catch (\Throwable $e) {
            Db::rollback();

            return json(['code' => 1, 'message' => $e->getMessage()]);
        }
    }

    public function edit(Request $request)
    {
        $user = $request->user;
        $code = $request->param('code');

        $shorturl = Db::name('shorturl')->where('code', $code)->where('user_id', $user['id'])->find();
        if (!$shorturl) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $data = $request->only(['remark', 'www_url']);
        $updateData = ['remark' => $data['remark']];
        $channelType = $shorturl['channel_type'];

        if ($channelType === 'short') {
            if (empty($data['www_url'])) {
                return json(['code' => 1, 'message' => '参数错误']);
            }

            $updateData['www_url'] = $data['www_url'];

            if (empty($shorturl['short_url'])) {
                $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $charsLength = strlen($chars) - 1;
                $shortCode = '';
                $exists = true;

                while ($exists) {
                    $shortCode = '';
                    for ($i = 0; $i < 6; $i++) {
                        $shortCode .= $chars[mt_rand(0, $charsLength)];
                    }
                    $exists = Db::name('shorturl')->where('short_url', $shortCode)->where('channel_type', 'short')->find();
                }

                $updateData['short_url'] = $shortCode;
            }
        } else {
            if (empty($shorturl['short_url'])) {
                if (empty($data['www_url'])) {
                    return json(['code' => 1, 'message' => '参数错误']);
                }

                $channelApiMap = [
                    'coze'   => 'api/coze/shorturl',
                    'douyin' => 'api/douyin/shorturl',
                    'ixigua' => 'api/ixigua/shorturl',
                    'weibo'  => 'api/weibo/shorturl',
                    'xhs'    => 'api/xhs/shorturl',
                ];

                if (!isset($channelApiMap[$channelType])) {
                    return json(['code' => 1, 'message' => '没有渠道权限']);
                }

                $appId     = env('OPEN_APPID');
                $appSecret = env('OPEN_APPSECRET');
                $timestamp = time();
                $signData  = ['app_id' => $appId, 'app_secret' => $appSecret, 'timestamp' => $timestamp];

                ksort($signData);
                $sign = md5(http_build_query($signData));

                $client = new \GuzzleHttp\Client();
                $response = $client->request('POST', env('OPEN_URL') . $channelApiMap[$channelType], [
                    'query' => [
                        'url'       => $data['www_url'],
                        'app_id'    => $appId,
                        'timestamp' => $timestamp,
                        'sign'      => $sign,
                        'data'      => $request->param(),
                    ],
                    'timeout' => 10,
                    'verify'  => false,
                ]);

                $res = json_decode($response->getBody(), true);

                if ($res['code'] != 0) {
                    return json(['code' => 1, 'message' => $res['message']]);
                }

                $updateData['short_url'] = $res['url'];
                $updateData['www_url']   = $data['www_url'];
            }
        }

        Db::name('shorturl')->where('code', $code)->update($updateData);

        return json(['code' => 0, 'message' => '编辑成功']);
    }

    public function delete(Request $request)
    {
        $user = $request->user;
        $code = $request->param('code');

        $shorturl = Db::name('shorturl')->where('code', $code)->where('user_id', $user['id'])->find();
        if (!$shorturl) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        Db::name('shorturl')->where('code', $code)->delete();

        return json(['code' => 0, 'message' => '删除成功']);
    }

    public function s(Request $request)
    {
        $short_url = $request->param('short_url');

        if (empty($short_url) || !preg_match('/^[0-9a-zA-Z]{6}$/', $short_url)) {
            return response('您访问的网址不正确', 404);
        }

        $shorturl = Db::name('shorturl')->where('short_url', $short_url)->where('channel_type', 'short')->find();
        if (!$shorturl) {
            return response('您访问的网址不存在', 404);
        }

        if (empty($shorturl['www_url']) || !preg_match('/^https?:\/\/.+$/', $shorturl['www_url'])) {
            return response('您访问的网址未配置', 404);
        }

        $code   = $shorturl['code'];
        $ua     = $request->header('user-agent');
        $ip     = $request->ip();
        $uaInfo = $this->getUaInfo();

        Db::name('logs')->insert([
            'code'    => $code,
            'type'    => 'shorturl',
            'time'    => time(),
            'ip'      => $ip,
            'device'  => $uaInfo['device'],
            'os'      => $uaInfo['os'],
            'browser' => $uaInfo['browser'],
        ]);

        return redirect($shorturl['www_url'], 302);
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