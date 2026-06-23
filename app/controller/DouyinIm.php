<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use app\common\service\DouyinImService;
use think\Request;
use think\facade\Db;

class DouyinIm
{
    public function list(Request $request)
    {
        $user = $request->user;

        $list = Db::name('douyinim_account')->where('user_id', $user['id'])->order('id', 'desc')->select()->toArray();

        foreach ($list as &$item) {
            $item['meta'] = $item['meta'] ? json_decode($item['meta'], true) : [];
        }
        unset($item);

        return json(['code' => 0, 'message' => '查询成功', 'list' => $list, 'points' => env('SITE_POINTS_DOUYIN')]);
    }

    public function add(Request $request)
    {
        $user = $request->user;

        $cookie = $request->param('cookie');
        $remark = $request->param('remark');

        if (!$cookie) {
            return json(['code' => 1, 'message' => 'Cookie不能为空']);
        }

        $points = env('SITE_POINTS_DOUYIN');

        Db::startTrans();
        try {
            if ($points > 0) {
                $affected = Db::name('user')->where('id', $user['id'])->where('points', '>=', $points)->dec('points', $points)->update();

                if ($affected === 0) {
                    throw new \Exception('积分不足');
                }

                $before_points = $user['points'];
                $after_points  = $before_points - $points;

                Db::name('points')->insert(['user_id' => $user['id'], 'points_change' => -$points, 'type' => 2, 'remark' => '添加抖音账号', 'before_points' => $before_points, 'after_points' => $after_points, 'create_time' => time()]);
            }

            $userInfo = new DouyinImService()->fetchCreatorUserInfo($cookie);
            $name = !empty($userInfo['nick_name']) ? $userInfo['nick_name'] : '未设置名字';
            $meta = !empty($userInfo) ? json_encode($userInfo, JSON_UNESCAPED_UNICODE) : null;

            Db::name('douyinim_account')->insert(['user_id' => $user['id'], 'name' => $name, 'meta' => $meta, 'cookie' => $cookie, 'remark' => $remark, 'status' => 1, 'create_time' => date('Y-m-d H:i:s'), 'end_time' => date('Y-m-d H:i:s', strtotime('+1 month'))]);

            Db::commit();
            return json(['code' => 0, 'message' => '添加成功']);
        
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 1, 'message' => $e->getMessage()]);
        }
    }

    public function update(Request $request)
    {
        $user = $request->user;

        $id = $request->param('id');
        if (!$id) {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        $douyinim_account = Db::name('douyinim_account')->where('id', $id)->where('user_id', $user['id'])->find();
        if (!$douyinim_account) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $data = $request->only(['cookie', 'remark']);
        $data['update_time'] = date('Y-m-d H:i:s');

        if (!empty($data['cookie']) && $data['cookie'] !== $douyinim_account['cookie']) {
            $userInfo = new DouyinImService()->fetchCreatorUserInfo($data['cookie']);
            if (!empty($userInfo)) {
                $data['meta'] = json_encode($userInfo, JSON_UNESCAPED_UNICODE);
                if (!empty($userInfo['nick_name'])) {
                    $data['name'] = $userInfo['nick_name'];
                }
            }
        }

        Db::name('douyinim_account')->where('id', $id)->update($data);

        return json(['code' => 0, 'message' => '编辑成功']);
    }

    public function delete(Request $request)
    {
        $user = $request->user;

        $id = $request->param('id');
        if (!$id) {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        $douyinim_account = Db::name('douyinim_account')->where('id', $id)->where('user_id', $user['id'])->find();
        if (!$douyinim_account) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        Db::name('douyinim_account')->where('id', $id)->delete();

        return json(['code' => 0, 'message' => '删除成功']);
    }

    public function status(Request $request)
    {
        $user = $request->user;

        $id = $request->param('id');
        if (!$id) {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        $douyinim_account = Db::name('douyinim_account')->where('id', $id)->where('user_id', $user['id'])->find();
        if (!$douyinim_account) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        Db::name('douyinim_account')->where('id', $id)->update(['status' => $douyinim_account['status'] ? 0 : 1, 'update_time' => date('Y-m-d H:i:s')]);

        return json(['code' => 0, 'message' => '操作成功']);
    }

    public function refreshInfo(Request $request)
    {
        $user = $request->user;

        $id = $request->param('id');
        if (!$id) {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        $douyinim_account = Db::name('douyinim_account')->where('id', $id)->where('user_id', $user['id'])->find();
        if (!$douyinim_account) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $userInfo = new DouyinImService()->fetchCreatorUserInfo($douyinim_account['cookie']);
        if (empty($userInfo)) {
            return json(['code' => 1, 'message' => 'Cookie无效']);
        }

        $data = ['update_time' => date('Y-m-d H:i:s'), 'meta' => json_encode($userInfo, JSON_UNESCAPED_UNICODE)];

        if (!empty($userInfo['nick_name'])) {
            $data['name'] = $userInfo['nick_name'];
        }

        Db::name('douyinim_account')->where('id', $id)->update($data);

        return json(['code' => 0, 'message' => '刷新成功']);
    }

    public function previewCookie(Request $request)
    {
        $cookie = $request->param('cookie');
        if (!$cookie) {
            return json(['code' => 1, 'message' => 'Cookie错误']);
        }

        $userInfo = new DouyinImService()->fetchCreatorUserInfo($cookie);
        if (empty($userInfo)) {
            return json(['code' => 1, 'message' => 'Cookie无效']);
        }

        return json(['code' => 0, 'message' => '获取成功', 'data' => $userInfo]);
    }

    public function userDetail(Request $request)
    {
        $user = $request->user;

        $ckId = (int)$request->param('ck_id', 0);
        $secUids = $request->param('sec_uids/a', []);

        if (!$ckId || empty($secUids)) {
            return json(['code' => 1, 'message' => 'ck_id和sec_uids不能为空']);
        }

        $account = Db::name('douyinim_account')->where('id', $ckId)->where('user_id', $user['id'])->find();
        if (!$account) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $result = (new DouyinImService())->fetchUserDetail($secUids, $account['cookie']);
        return json(['code' => 0, 'message' => '查询成功', 'data' => $result]);
    }

    public function convListOnly(Request $request)
    {
        set_time_limit(0);
        $user = $request->user;

        $ckId = (int)$request->param('ck_id', 0);
        if (!$ckId) {
            return json(['code' => 1, 'message' => 'ck_id不能为空']);
        }

        $account = Db::name('douyinim_account')->where('id', $ckId)->where('user_id', $user['id'])->find();
        if (!$account) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $result = (new DouyinImService())->fetchConvListOnly($account['cookie']);
        if (isset($result['error'])) {
            return json(['code' => 1, 'message' => $result['error']]);
        }

        return json(['code' => 0, 'message' => '查询成功', 'data' => $result]);
    }

    public function convMessages(Request $request)
    {
        set_time_limit(0);
        $user = $request->user;

        $ckId = (int)$request->param('ck_id', 0);
        $token = trim($request->param('token', ''));
        $convId = trim($request->param('conv_id', ''));
        $convShortId = (int)$request->param('conv_short_id', 0);
        $maxPages = max(1, min(50, (int)$request->param('max_pages', 1)));
        $anchor = (int)$request->param('anchor', 0);
        $perPage = max(10, min(100, (int)$request->param('per_page', 50)));
        $selfUid = trim($request->param('self_uid', ''));

        if (!$ckId || !$token || !$convId) {
            return json(['code' => 1, 'message' => 'ck_id、token和conv_id不能为空']);
        }

        $account = Db::name('douyinim_account')->where('id', $ckId)->where('user_id', $user['id'])->find();
        if (!$account) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $svc = new DouyinImService();
        [$msgs, $hasMore, $nextAnchor] = $svc->fetchConvMessagesPaged(
            $convId, $token, $account['cookie'], $convShortId, $anchor, $perPage, $maxPages, $selfUid
        );

        return json(['code' => 0, 'message' => '查询成功', 'data' => [
            'messages' => $msgs,
            'has_more' => $hasMore,
            'next_anchor' => (string)$nextAnchor,
        ]]);
    }

    public function allHistories(Request $request)
    {
        set_time_limit(0);
        $user = $request->user;

        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true) ?: [];
        $ckId = (int)($json['ck_id'] ?? 0);
        $token = trim((string)($json['token'] ?? ''));
        $conversations = is_array($json['conversations'] ?? null) ? $json['conversations'] : [];

        if (!$ckId || !$token) {
            return json(['code' => 1, 'message' => 'ck_id和token不能为空']);
        }

        $account = Db::name('douyinim_account')->where('id', $ckId)->where('user_id', $user['id'])->find();
        if (!$account) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $result = (new DouyinImService())->fetchAllHistories($token, $account['cookie'], $conversations);
        return json(['code' => 0, 'message' => '查询成功', 'data' => ['histories' => $result]]);
    }

    public function wsInfo(Request $request)
    {
        $user = $request->user;

        $id = (int)$request->param('id', 0);
        if (!$id) {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        $account = Db::name('douyinim_account')->where('id', $id)->where('user_id', $user['id'])->find();
        if (!$account) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $imData = (new DouyinImService())->getImUserToken($account['cookie']);
        if (empty($imData) || empty($imData['token'])) {
            return json(['code' => 1, 'message' => '获取IM Token失败']);
        }

        $token = $imData['token'];
        $tsSign = $imData['ts_sign'] ?? '';
        $sdkCert = $imData['sdk_cert'] ?? '';
        $priKey = $imData['pri_key_pem'] ?? '';
        $deviceId = (string)($imData['user_id'] ?? '');

        $accessKey = md5('9' . 'e1bd35ec9db7b8d846de66ed140b1ad9' . $deviceId . 'f8a69f1719916z');

        return json(['code' => 0, 'message' => '获取成功', 'data' => [
            'im_data' => [
                'token' => $token,
                'ts_sign' => $tsSign,
                'sdk_cert' => $sdkCert,
                'pri_key_pem' => $priKey,
                'user_id' => $deviceId,
                'access_key' => $accessKey,
            ],
        ]]);
    }

    public function recallProxy(Request $request)
    {
        $user = $request->user;

        $raw = file_get_contents('php://input');
        $json = $raw ? json_decode($raw, true) : null;

        $id = (int)($json['ck_id'] ?? $request->param('ck_id', 0));
        if (!$id) {
            return json(['code' => 1, 'message' => 'ck_id不能为空']);
        }

        $account = Db::name('douyinim_account')->where('id', $id)->where('user_id', $user['id'])->find();
        if (!$account) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $bodyB64 = trim((string)($json['body_b64'] ?? ''));
        if (!$bodyB64) {
            return json(['code' => 1, 'message' => 'body_b64不能为空']);
        }

        $body = base64_decode($bodyB64);
        if ($body === false || $body === '') {
            return json(['code' => 1, 'message' => 'body_b64解码失败']);
        }

        $result = (new DouyinImService())->forwardRecallHttp($account['cookie'], $body);

        if (!$result['ok']) {
            return json(['code' => 1, 'message' => '撤回失败：' . ($result['error'] ?? '')]);
        }

        return json(['code' => 0, 'message' => '撤回成功', 'data' => $result]);
    }

    // ==================== 卡片管理 ====================

    public function cards(Request $request)
    {
        $user = $request->user;

        $list = Db::name('link')->where('user_id', $user['id'])->order('id', 'desc')->select()->toArray();
        $links = json_decode(file_get_contents(root_path() . 'urls.json'), true);

        // 转换字段名适配前端
        foreach ($list as &$item) {
            $item['title'] = $item['page_title'] ?? '';
            $item['desc'] = $item['page_desc'] ?? '';
            $item['cover_url'] = $item['page_icon'] ?? '';

            // 从 urls.json 获取 link_url（数组）
            $channelTypes = ['website', 'securewebsite', 'qq', 'qqqun'];
            if (in_array($item['channel_type'], $channelTypes, false)) {
                $sourceKey = 'CARD_JUMP_URLS';
            } else {
                $sourceKey = 'LINK_JUMP_URLS';
            }

            $code = $item['code'];
            $jumpUrls = $links[$sourceKey] ?? [];
            $linkUrls = [];
            foreach ($jumpUrls as $j) {
                $baseUrl = $j['url'];
                if (strpos($baseUrl, '{code}') !== false) {
                    $linkUrls[] = [
                        'url' => str_replace('{code}', $code, $baseUrl),
                        'desc' => $j['desc'] ?? ''
                    ];
                } else {
                    $separator = str_contains($baseUrl, '?') ? '&' : '?';
                    $linkUrls[] = [
                        'url' => $baseUrl . $separator . 'code=' . $code,
                        'desc' => $j['desc'] ?? ''
                    ];
                }
            }
            $item['link_url'] = $linkUrls;
        }
        unset($item);

        return json(['code' => 0, 'message' => '查询成功', 'data' => $list]);
    }

    public function cardAdd(Request $request)
    {
        $user = $request->user;

        $title = trim($request->param('title', ''));
        $desc = trim($request->param('desc', ''));
        $linkUrl = trim($request->param('link_url', ''));
        $coverUrl = trim($request->param('cover_url', ''));
        $remark = trim($request->param('remark', ''));

        if (!$title) {
            return json(['code' => 1, 'message' => '标题不能为空']);
        }

        if (!$linkUrl) {
            return json(['code' => 1, 'message' => '链接不能为空']);
        }

        Db::name('api_card')->insert([
            'user_id' => $user['id'],
            'channel_type' => 'douyin',
            'page_title' => $title,
            'page_desc' => $desc ?: '',
            'www_url' => $linkUrl,
            'page_icon' => $coverUrl ?: '',
            'remark' => $remark ?: '',
            'code' => substr(md5(uniqid()), 0, 6),
            'create_time' => time(),
        ]);

        return json(['code' => 0, 'message' => '添加成功']);
    }

    public function cardUpdate(Request $request)
    {
        $user = $request->user;

        $id = (int)$request->param('id', 0);
        if (!$id) {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        $card = Db::name('api_card')->where('id', $id)->where('user_id', $user['id'])->find();
        if (!$card) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $data = [
            'page_title' => $request->param('title', $card['page_title']),
            'page_desc' => $request->param('desc', $card['page_desc']),
            'www_url' => $request->param('link_url', $card['www_url']),
            'page_icon' => $request->param('cover_url', $card['page_icon']),
            'remark' => $request->param('remark', $card['remark']),
        ];

        Db::name('api_card')->where('id', $id)->update($data);

        return json(['code' => 0, 'message' => '编辑成功']);
    }

    public function cardDelete(Request $request)
    {
        $user = $request->user;

        $id = (int)$request->param('id', 0);
        if (!$id) {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        $card = Db::name('api_card')->where('id', $id)->where('user_id', $user['id'])->find();
        if (!$card) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        Db::name('api_card')->where('id', $id)->delete();

        return json(['code' => 0, 'message' => '删除成功']);
    }
}
