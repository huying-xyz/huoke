<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;
use think\facade\Cache;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\Kernel\Messages\News;
use EasyWeChat\Kernel\Messages\NewsItem;

class Wechat
{
    public function server()
    {
        $app = Factory::officialAccount([
            'app_id'  => env('WX_APPID'),
            'secret'  => env('WX_APPSECRET'),
            'token'   => env('WX_TOKEN'),
            'aes_key' => env('WX_AESKEY')
        ]);

        $app->server->push(function ($message) use ($app) {
            $openid   = $message['FromUserName'];
            $msgType  = $message['MsgType'];
            $event    = $message['Event'] ?? '';
            $eventKey = $message['EventKey'] ?? null;

            // -------------------------
            // 辅助函数：获取回复内容
            // -------------------------
            $getReply = function($type, $keyword = null) {
                $query = Db::name('wechat_autoreply')->where('type', $type);
                if ($keyword) {
                    $query->where('keyword', $keyword);
                }

                $replyData = $query->find();
                if (!$replyData) {
                    return null;
                }

                $data = json_decode($replyData['content'], true);
                if (!$data || !isset($data['reply_type'])) {
                    return null;
                }

                switch ($data['reply_type']) {
                    case 'text':
                        return new Text($data['content']);
                    case 'image':
                        return new Image($data['content']);
                    case 'news':
                        return new News([
                            new NewsItem([
                                'title'       => $data['content']['title'],
                                'description' => $data['content']['description'],
                                'url'         => $data['content']['url'],
                                'image'       => $data['content']['image']
                            ])
                        ]);
                }
            };

            // -------------------------
            // 处理事件消息
            // -------------------------
            if ($msgType === 'event') {
                // -------------------------
                // 处理登录事件
                // -------------------------
                if (in_array($event, ['subscribe', 'SCAN']) && $eventKey) {
                    $sceneId = $event === 'subscribe' ? str_replace('qrscene_', '', $eventKey) : $eventKey;

                    $loginScene = Db::name('login_scene')->where('scene_id', $sceneId)->find();
                    if ($loginScene) {
                        $user = Db::name('user')->where('openid', $openid)->find();
                        if ($user) {
                            $userId = $user['id'];
                            $status = empty($user['username']) || empty($user['password']) ? 2 : 3;
                            $templateId = env('SITE_TMPLMSG_LOGIN');
                            $data = [
                                'time3'             => date('Y-m-d H:i:s'),
                                'character_string8' => $loginScene['ip'],
                                'time13'            => $user['end_time']
                            ];
                        } else {
                            $userId = Db::name('user')->insertGetId([
                                'plan_id'   => 1,
                                'end_time'  => date('Y-m-d', strtotime('+' . env('SITE_TRY') . ' days')),
                                'reg_time'  => time(),
                                'reg_ip'    => $loginScene['ip'],
                                'openid'    => $openid,
                                'invite_id' => $loginScene['invite_id']
                            ]);
                            $status = 2;
                            $templateId = env('SITE_TMPLMSG_LOGIN');
                            $data = [
                                'time3'             => date('Y-m-d H:i:s'),
                                'character_string8' => $loginScene['ip'],
                                'time13'            => date('Y-m-d', strtotime('+1 day'))
                            ];
                        }

                        $token = bin2hex(random_bytes(32));
                        Cache::set('token_' . $token, $userId, 2628000);
                        Db::name('login_scene')->where('scene_id', $sceneId)->update([
                            'status' => $status,
                            'token'  => $token,
                            'openid' => $openid
                        ]);

                        try {
                            $app->template_message->send([
                                'touser'      => $openid,
                                'template_id' => $templateId,
                                'data'        => $data,
                            ]);
                        } catch (\Exception $e) {
                            \think\facade\Log::error('模板消息发送失败: ' . $e->getMessage());
                        }

                        return 'success';
                    }
                }

                // -------------------------
                // 处理关注事件
                // -------------------------
                if ($event === 'subscribe') {
                    $subscribeReply = $getReply('subscribe');
                    if ($subscribeReply) {
                        return $subscribeReply;
                    }

                    return 'success';
                }

                // -------------------------
                // 处理点击事件
                // -------------------------
                if ($event === 'CLICK') {
                    $clickReply = $getReply('event_key', $eventKey);
                    if ($clickReply) {
                        return $clickReply;
                    }

                    return 'success';
                }

                return 'success';
            }

            // -------------------------
            // 处理文本消息
            // -------------------------
            if ($msgType === 'text') {
                $content = trim($message['Content']);

                $keywordReply = $getReply('keyword', $content);
                if ($keywordReply) {
                    return $keywordReply;
                }

                $defaultReply = $getReply('default');
                if ($defaultReply) {
                    return $defaultReply;
                }

                return 'success';
            }

            // -------------------------
            // 处理其他消息
            // -------------------------
            $defaultReply = $getReply('default');
            if ($defaultReply) {
                return $defaultReply;
            }

            return 'success';
        });

        $response = $app->server->serve();
        $response->send();
    }

    public function mediaList()
    {
        try {
            $app = Factory::officialAccount([
                'app_id'  => env('WX_APPID'),
                'secret'  => env('WX_APPSECRET'),
                'token'   => env('WX_TOKEN'),
                'aes_key' => env('WX_AESKEY'),
            ]);

            $allMaterials = [];
            $offset = 0;
            $count = 20;

            do {
                $materials = $app->material->list('image', $offset, $count);
                if (!empty($materials['item'])) {
                    $allMaterials = array_merge($allMaterials, $materials['item']);
                }
                $offset += $count;
                $total = $materials['total'] ?? 0;
            } while ($offset < $total);

            echo '<style>body{margin:0}</style>';

            if (!empty($allMaterials)) {
                echo '<div style="display:flex; flex-direction:column; gap:10px;">';
                foreach ($allMaterials as $item) {
                    $url = $item['url'] ?? '';
                    $name = $item['name'] ?? '';
                    $media_id = $item['media_id'];

                    echo <<<HTML
<div style="display:flex;align-items:center;gap: 15px;border:1px solid #eee;border-radius: 8px;background: #fff;">
    <img src="{$url}" style="width:100px;height:100px;object-fit:cover;border-radius: 8px;">
    <div style="display:flex; flex-direction:column; gap:10px;">
        <div style="font-size:14px; font-weight:bold;">{$name}</div>
        <div style="font-size:12px; color:#999;">MediaId: {$media_id}</div>
    </div>
</div>
HTML;
                }
                echo '</div>';
            } else {
                echo '<p>暂无素材</p>';
            }
        } catch (\Exception $e) {
            echo '<p style="color:red;">错误: ' . $e->getMessage() . '</p>';
        }
    }
}