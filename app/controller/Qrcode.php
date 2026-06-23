<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;
use OSS\OssClient;
use GuzzleHttp\Client;

class Qrcode
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

        $query = Db::name('qrcode')->where('user_id', $user['id'])->where('channel_type', $channel_type);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('code', "%{$keyword}%")->whereOr('remark', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();

        $list = $query->order('id', 'desc')->page($page, $limit)->select();

        $qrcode_channel = Db::name('qrcode_channel')->where('status', 0)->order('sort')->select();

        return json(['code' => 0, 'message' => '查询成功', 'list' => $list, 'total' => $total, 'qrcode_channel' => $qrcode_channel]);
    }

    public function detail(Request $request)
    {
        $user = $request->user;
        $code = $request->param('code');

        $qrcode = Db::name('qrcode')->where('code', $code)->where('user_id', $user['id'])->find();
        if (!$qrcode) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $qrcode_channel = Db::name('qrcode_channel')->where('status', 0)->order('sort')->select();

        return json(['code' => 0, 'message' => '查询成功', 'qrcode' => $qrcode, 'qrcode_channel' => $qrcode_channel]);
    }

    public function add(Request $request)
    {
        $user = $request->user;
        $channel_type = $request->param('channel_type');

        $qrcode_channel = Db::name('qrcode_channel')->where('type', $channel_type)->where('status', 0)->find();
        if (!$qrcode_channel) {
            return json(['code' => 1, 'message' => '没有渠道权限']);
        }

        $feature_fee_json = isset($user['feature_fee']) && $user['feature_fee'] !== null ? $user['feature_fee'] : '{}';
        $feature_fee = json_decode($feature_fee_json, true) ?: [];

        $permissionKey = 'qrcode-' . $channel_type;
        $isFree = isset($feature_fee[$permissionKey]) && $feature_fee[$permissionKey] === true;

        do {
            $code = random_int(100000, 999999);
        } while (Db::name('qrcode')->where('code', $code)->count() > 0);

        Db::startTrans();
        try {
            if (!$isFree) {
                $points = (int)$qrcode_channel['points'];

                if ($points > 0) {
                    $affected = Db::name('user')->where('id', $user['id'])->where('points', '>=', $points)->dec('points', $points)->update();

                    if ($affected === 0) {
                        throw new \Exception('积分不足');
                    }

                    $before_points = $user['points'];
                    $after_points  = $before_points - $points;

                    Db::name('points')->insert(['user_id' => $user['id'], 'points_change' => -$points, 'type' => 2, 'remark' => '创建圆码', 'before_points' => $before_points, 'after_points' => $after_points, 'create_time' => time()]);
                }
            }

            Db::name('qrcode')->insert(['user_id' => $user['id'], 'channel_type' => $channel_type, 'code' => $code]);

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

        $qrcode = Db::name('qrcode')->where('code', $code)->where('user_id', $user['id'])->find();
        if (!$qrcode) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $data = $request->only(['remark', 'icon_url', 'www_url']);

        if (!empty($qrcode['qrcode_url'])) {
            $data = ['remark' => $data['remark']];
        } else {
            if ($qrcode['channel_type'] === 'douyin') {
                if (empty($data['icon_url']) || empty($data['www_url'])) {
                    return json(['code' => 1, 'message' => '参数错误']);
                }

                $appId     = env('OPEN_APPID');
                $appSecret = env('OPEN_APPSECRET');
                $timestamp = time();

                $signData = ['app_id' => $appId, 'app_secret' => $appSecret, 'timestamp' => $timestamp];
                ksort($signData);
                $sign = md5(http_build_query($signData));

                $client = new Client();
                $response = $client->request('POST', env('OPEN_URL') . 'api/douyin/qrcode', [
                    'query' => [
                        'www_url' => $data['www_url'],
                        'icon_url' => $data['icon_url'],
                        'app_id' => $appId,
                        'timestamp' => $timestamp,
                        'sign' => $sign,
                        'data' => $request->param(),
                    ],
                    'timeout' => 10,
                    'verify' => false,
                ]);

                $res = json_decode($response->getBody(), true);
                if ($res['code'] != 0) {
                    return json(['code' => 1, 'message' => $res['message']]);
                }

                $qrcodeUrl = @file_get_contents($res['url']);

                $fileName = basename(parse_url($res['url'], PHP_URL_PATH));

                $ossPath = 'upload/image/' . $fileName;
                $ossClient = new OssClient(env('OSS_ACCESS_KEY_ID'), env('OSS_ACCESS_KEY_SECRET'), env('OSS_ENDPOINT'));
                $ossClient->putObject(env('OSS_BUCKET'), $ossPath, $qrcodeUrl);
                $ossUrl = rtrim(env('OSS_URL'), '/') . '/' . ltrim($ossPath, '/');

                Db::name('material')->insert(['user_id' => $user['id'], 'name' => pathinfo($fileName, PATHINFO_FILENAME), 'url' => $ossUrl, 'create_time' => time()]);

                $data['qrcode_url'] = $ossUrl;
            }
        }

        Db::name('qrcode')->where('code', $code)->update($data);

        return json(['code' => 0, 'message' => '编辑成功']);
    }

    public function delete(Request $request)
    {
        $user = $request->user;
        $code = $request->param('code');

        $qrcode = Db::name('qrcode')->where('code', $code)->where('user_id', $user['id'])->find();
        if (!$qrcode) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        Db::name('qrcode')->where('code', $code)->delete();

        return json(['code' => 0, 'message' => '删除成功']);
    }
}