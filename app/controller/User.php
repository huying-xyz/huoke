<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;
use think\facade\Validate;
use OSS\OssClient;
use OSS\Core\OssException;
use think\facade\Cache;
use EasyWeChat\Factory;

class User
{
    public function createScene(Request $request)
    {
        $invite_id = (int)$request->param('invite_id', 0);
        $scenes = [];
        if (env('XCX_APPID') && env('XCX_APPSECRET')) {
            $wxa_scene = 'wxa_' . date('YmdHis') . random_int(10, 99);
            $wxa = Factory::miniProgram(['app_id' => env('XCX_APPID'), 'secret' => env('XCX_APPSECRET')]);
            $response = $wxa->app_code->getUnlimit($wxa_scene, [
                'page' => 'pages/user/login',
                'width' => 280
            ]);
            if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                try {
                    $content = $response->getBody()->getContents();
                    $ossPath = 'upload/scene/' . $wxa_scene . '.png';
                    $ossClient = new \OSS\OssClient(env('OSS_ACCESS_KEY_ID'), env('OSS_ACCESS_KEY_SECRET'), env('OSS_ENDPOINT'));
                    $ossClient->putObject(env('OSS_BUCKET'), $ossPath, $content);
                    Db::name('login_scene')->insert(['scene_id' => $wxa_scene, 'status' => 0, 'create_time' => time(), 'qrcode_url' => env('OSS_URL') . $ossPath, 'invite_id' => $invite_id, 'ip' => request()->ip()]);
                    $scenes['wxa'] = ['scene_id' => $wxa_scene, 'qrcode_url' => env('OSS_URL') . $ossPath];
                } catch (\Throwable $e) {
                    return json(['code' => 1, 'message' => '生成小程序码失败：' . $e->getMessage()]);
                }
            }
        }
        if (env('WX_APPID') && env('WX_APPSECRET')) {
            $wxmp_scene = 'wxmp_' . date('YmdHis') . random_int(10, 99);
            $wxmp = Factory::officialAccount(['app_id' => env('WX_APPID'), 'secret' => env('WX_APPSECRET'), 'token' => env('WX_TOKEN'), 'aes_key' => env('WX_AES_KEY')]);
            try {
                $result = $wxmp->qrcode->temporary($wxmp_scene, 300);
                $qrcodeUrl = $wxmp->qrcode->url($result['ticket']);
                $qrcodeContent = file_get_contents($qrcodeUrl);
                $ossPath = 'upload/scene/' . $wxmp_scene . '.png';
                $ossClient = new \OSS\OssClient(env('OSS_ACCESS_KEY_ID'), env('OSS_ACCESS_KEY_SECRET'), env('OSS_ENDPOINT'));
                $ossClient->putObject(env('OSS_BUCKET'), $ossPath, $qrcodeContent);
                Db::name('login_scene')->insert(['scene_id' => $wxmp_scene, 'status' => 0, 'create_time' => time(), 'qrcode_url' => env('OSS_URL') . $ossPath, 'invite_id' => $invite_id, 'ip' => request()->ip()]);
                $scenes['wxmp'] = ['scene_id' => $wxmp_scene, 'qrcode_url' => env('OSS_URL') . $ossPath];
            } catch (\Throwable $e) {
                return json(['code' => 1, 'message' => '生成公众号码失败：' . $e->getMessage()]);
            }
        }
        return json(['code' => 0, 'scenes' => $scenes]);
    }

    public function checkScene(Request $request)
    {
        $scene_id = $request->param('scene_id');
        $login_scene = Db::name('login_scene')->where('scene_id', $scene_id)->find();
        if (!$login_scene || time() - $login_scene['create_time'] > 300) {
            return json(['code' => 4, 'message' => '当前二维码已失效']);
        }
        if ($login_scene['status'] == 0) {
            return json(['code' => 0, 'message' => '使用微信扫一扫登录']);
        }
        if ($login_scene['status'] == 1) {
            return json(['code' => 1, 'message' => '请点击授权微信登录']);
        }
        if ($login_scene['status'] == 2) {
            return json(['code' => 2, 'message' => '请先绑定账号', 'token' => $login_scene['token']]);
        }
        if ($login_scene['status'] == 3) {
            return json(['code' => 3, 'message' => '扫码登录成功', 'token' => $login_scene['token']]);
        }
    }

    public function markScene(Request $request)
    {
        $scene_id = $request->param('scene_id');
        $login_scene = Db::name('login_scene')->where('scene_id', $scene_id)->find();
        if (!$login_scene) {
            return json(['code' => 1, 'message' => '二维码不存在']);
        }
        if (time() - $login_scene['create_time'] > 300) {
            return json(['code' => 1, 'message' => '二维码已失效']);
        }
        if ($login_scene['status'] == 0) {
            Db::name('login_scene')->where('scene_id', $scene_id)->update(['status' => 1]);
            return json(['code' => 0, 'message' => '扫码成功']);
        }
    }

    public function wxLogin(Request $request)
    {
        $code = $request->param('code');
        $scene_id = $request->param('scene_id');
        if (empty($code)) {
            return json(['code' => 1, 'message' => '参数错误']);
        }
        $app = Factory::miniProgram(['app_id' => env('XCX_APPID'), 'secret' => env('XCX_APPSECRET')]);
        $response = $app->auth->session($code);
        if (!isset($response['openid'])) {
            return json(['code' => 1, 'message' => '登录失败']);
        }
        $openid = $response['openid'];
        $user = Db::name('user')->where('openid', $openid)->find();
        if ($user) {
            $user_id = $user['id'];
            if (empty($user['username']) || empty($user['password'])) {
                $status = 2;
            } else {
                $status = 3;
            }
        } else {
            $invite_id = 0;
            if ($scene_id) {
                $loginScene = Db::name('login_scene')->where('scene_id', $scene_id)->find();
                if ($loginScene && isset($loginScene['invite_id'])) {
                    $invite_id = $loginScene['invite_id'];
                }
            }
            $user_id = Db::name('user')->insertGetId(['plan_id' => 1, 'end_time' => date('Y-m-d', strtotime('+1 days')), 'reg_time' => time(), 'reg_ip' => $request->ip(), 'openid' => $openid, 'invite_id' => $invite_id]);
            $status = 2;
        }
        $token = bin2hex(random_bytes(32));
        Cache::set('token_' . $token, $user_id, 2628000);
        if ($scene_id) {
            Db::name('login_scene')->where('scene_id', $scene_id)->update(['status' => $status, 'token' => $token, 'openid' => $openid]);
        }
        return json(['code' => 0, 'message' => '登录成功', 'token' => $token]);
    }

    public function wxBind(Request $request)
    {
        $username = $request->param('username');
        $password = $request->param('password');
        $scene_id = $request->param('scene_id');
        if (empty($username) || empty($password)) {
            return json(['code' => 1, 'message' => '请输入手机号码和登录密码']);
        }
        if (!preg_match('/^1[3-9]\d{9}$/', $username)) {
            return json(['code' => 1, 'message' => '手机号码格式错误']);
        }
        if (strlen($password) < 6) {
            return json(['code' => 1, 'message' => '登录密码格式错误']);
        }
        if (empty($scene_id)) {
            return json(['code' => 1, 'message' => '登录场景参数错误']);
        }
        $login_scene = Db::name('login_scene')->where('scene_id', $scene_id)->find();
        if (!$login_scene) {
            return json(['code' => 1, 'message' => '登录场景扫码过期']);
        }
        if (empty($login_scene['openid'])) {
            return json(['code' => 1, 'message' => '登录微信授权失败']);
        }
        $user = Db::name('user')->where('username', $username)->find();
        Db::startTrans();
        try {
            if ($user) {
                return json(['code' => 1, 'message' => '手机号码已被其他账号绑定']);
            } else {
                $openidUser = Db::name('user')->where('openid', $login_scene['openid'])->find();
                Db::name('user')->where('id', $openidUser['id'])->update(['username' => $username, 'password' => password_hash($password, PASSWORD_BCRYPT)]);
                $user_id = $openidUser['id'];
            }
            Db::commit();
            $token = bin2hex(random_bytes(32));
            Cache::set('token_' . $token, $user_id, 2628000);
            return json(['code' => 0, 'message' => '登录成功', 'token' => $token]);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'message' => '绑定失败: ' . $e->getMessage()]);
        }
    }

    public function login(Request $request)
    {
        $username = $request->param('username');
        $password = $request->param('password');
        if (empty($username) || empty($password)) {
            return json(['code' => 1, 'message' => '请输入手机号码和密码']);
        }
        if (!preg_match('/^1[3-9]\d{9}$/', $username)) {
            return json(['code' => 1, 'message' => '手机号码格式错误']);
        }
        if (strlen($password) < 6) {
            return json(['code' => 1, 'message' => '登录密码格式错误']);
        }
        $user = Db::name('user')->where('username', $username)->find();
        if (!$user || !password_verify($password, $user['password'])) {
            return json(['code' => 1, 'message' => '手机号码或密码错误']);
        }
        $token = bin2hex(random_bytes(32));
        Cache::set('token_' . $token, $user['id'], 2628000);
        return json(['code' => 0, 'message' => '登录成功', 'token' => $token]);
    }

    public function register(Request $request)
    {
        if (!env('SITE_REGISTER', false)) {
            return json(['code' => 1, 'message' => '用户注册已关闭']);
        }

        $username = $request->param('username');
        $password = $request->param('password');
        $invite_id = (int)$request->param('invite_id', 0);

        if (empty($username) || empty($password)) {
            return json(['code' => 1, 'message' => '请输入手机号码和密码']);
        }
        if (!preg_match('/^1[3-9]\d{9}$/', $username)) {
            return json(['code' => 1, 'message' => '手机号码格式错误']);
        }
        if (strlen($password) < 6) {
            return json(['code' => 1, 'message' => '登录密码格式错误']);
        }

        $user = Db::name('user')->where('username', $username)->find();
        if ($user) {
            return json(['code' => 1, 'message' => '该手机号码已注册']);
        }

        if ($invite_id > 0) {
            $inviter = Db::name('user')->where('id', $invite_id)->find();
            if (!$inviter) {
                return json(['code' => 1, 'message' => '邀请码无效']);
            }
        }

        Db::startTrans();
        try {
            $user_id = Db::name('user')->insertGetId([
                'plan_id'   => 1,
                'end_time'  => date('Y-m-d', strtotime('+' . env('SITE_TRY') . ' days')),
                'username'  => $username,
                'password'  => password_hash($password, PASSWORD_BCRYPT),
                'reg_time'  => time(),
                'reg_ip'    => $request->ip(),
                'invite_id' => $invite_id
            ]);

            $token = bin2hex(random_bytes(32));
            Cache::set('token_' . $token, $user_id, 2628000);

            Db::commit();

            return json(['code' => 0, 'message' => '注册成功', 'token' => $token]);
        } catch (\Throwable $e) {
            Db::rollback();
            return json(['code' => 1, 'message' => '注册失败：' . $e->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        $token = $request->header('Authorization');
        Cache::delete('token_' . substr($token, 7));
        return json(['code' => 0, 'message' => '退出成功']);
    }

    public function info(Request $request)
    {
        $user = $request->user;
        $plan = Db::name('plan')->where('id', $user['plan_id'])->find();
        $link = Db::name('link')->where('user_id', $user['id'])->count();
        $qrcode = Db::name('qrcode')->where('user_id', $user['id'])->count();
        $shorturl = Db::name('shorturl')->where('user_id', $user['id'])->count();
        $material = Db::name('material')->where('user_id', $user['id'])->count();

        $parent = '';
        if ($user['invite_id'] > 0) {
            $parentUser = Db::name('user')->where('id', $user['invite_id'])->field('username')->find();
            if ($parentUser) {
                $parent = substr($parentUser['username'], 0, 3) . '****' . substr($parentUser['username'], 7);
            }
        }

        return json(['code' => 0, 'message' => '获取成功', 'user' => $user, 'plan' => $plan, 'link' => $link, 'qrcode' => $qrcode, 'shorturl' => $shorturl, 'material' => $material, 'parent' => $parent]);
    }

    public function inviteList(Request $request)
    {
        $user    = $request->user;
        $page    = max(1, (int)$request->param('page', 1));
        $limit   = min(100, max(1, (int)$request->param('limit', 50)));
        $keyword = trim($request->param('keyword'));
        $level   = (int)$request->param('level');

        if ($level == 1) {
            $query = Db::name('user')->where('invite_id', $user['id']);
        } else {
            $query = Db::name('user')->whereIn('invite_id', function($q) use ($user) {
                $q->name('user')->where('invite_id', $user['id'])->field('id');
            });
        }

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('id', $keyword)
                  ->whereOr('username', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();

        $list = $query->order('id desc')->page($page, $limit)->field('id, plan_id, end_time, username, reg_time, reg_ip')->select();

        $listArray = $list->toArray();

        $planIds = array_column($listArray, 'plan_id');
        $planNames = Db::name('plan')->whereIn('id', $planIds)->column('name', 'id');

        $userIds = array_column($listArray, 'id');
        $commissionList = Db::name('commission')->where('user_id', $user['id'])->whereIn('agent_id', $userIds)->group('agent_id')->column('sum(amount) as commission', 'agent_id');

        $list = $list->map(function ($item) use ($planNames, $commissionList) {
            $item['plan_name'] = $planNames[$item['plan_id']];

            if (!empty($item['username'])) {
                $item['username'] = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $item['username']);
            }

            $item['reg_time'] = date('Y-m-d H:i:s', $item['reg_time']);

            $item['commission'] = isset($commissionList[$item['id']]) ? number_format($commissionList[$item['id']], 2) : 0.00;

            return $item;
        });

        return json(['code' => 0, 'list' => $list, 'total' => $total]);
    }

    public function plan(Request $request)
    {
        $user = $request->user;
        $list = Db::name('plan')->select();
        return json(['code' => 0, 'list' => $list]);
    }

    public function uploadAvatar(Request $request)
    {
        $user = $request->user;
        $file = $request->file('avatar');

        if (!$file) {
            return json(['code' => 1, 'message' => '请上传图片']);
        }

        $validate = Validate::rule([
            'avatar' => 'file|image|fileSize:5242880|fileExt:jpg,jpeg,png,gif'
        ]);
        if (!$validate->check(['avatar' => $file])) {
            return json(['code' => 1, 'message' => $validate->getError()]);
        }

        $fileExt = $file->extension();
        $fileName = $user['id'] . '_' . time() . '.' . $fileExt;
        $tmpDir = runtime_path() . 'tmp/';
        $localPath = $tmpDir . $fileName;
        $ossUrl = '';

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        try {
            $file->move($tmpDir, $fileName);
            if (!file_exists($localPath)) {
                throw new \Exception('临时文件保存失败');
            }

            $ossPath = 'upload/avatar/' . $fileName;
            $ossClient = new OssClient(env('OSS_ACCESS_KEY_ID'), env('OSS_ACCESS_KEY_SECRET'), env('OSS_ENDPOINT'));
            $ossClient->uploadFile(env('OSS_BUCKET'), $ossPath, $localPath);
            $ossUrl = env('OSS_URL') . $ossPath;

            Db::startTrans();
            $updateResult = Db::name('user')->where('id', $user['id'])->update(['avatar' => $ossUrl]);

            if ($updateResult === false) {
                throw new \Exception('头像信息更新失败');
            }
            Db::commit();

            return json([
                'code' => 0,
                'message' => '头像修改成功',
                'data' => [
                    'avatar' => $ossUrl
                ]
            ]);
        } catch (OssException $e) {
            return json(['code' => 1, 'message' => '头像上传到OSS失败']);
        } catch (\Throwable $e) {
            if (Db::inTransaction()) {
                Db::rollback();
            }
            return json(['code' => 1, 'message' => $e->getMessage()]);
        } finally {
            if (file_exists($localPath)) {
                @unlink($localPath);
            }
        }
    }

    public function editInfo(Request $request)
    {
        $user   = $request->user;
        $params = $request->param();

        $updateData = [];

        if (isset($params['username']) && trim($params['username']) !== '') {
            $username = trim($params['username']);
            if (!preg_match('/^1[3-9]\d{9}$/', $username)) {
                return json(['code' => 1, 'message' => '手机号码格式错误']);
            }
            $usernameExist = Db::name('user')->where('username', $username)->where('id', '<>', $user['id'])->find();
            if ($usernameExist) {
                return json(['code' => 1, 'message' => '该手机号码已注册']);
            }
            $updateData['username'] = $username;
        }

        if (isset($params['password']) && trim($params['password']) !== '') {
            $password = trim($params['password']);
            if (strlen($password) < 6) {
                return json(['code' => 1, 'message' => '登录密码格式错误']);
            }
            $updateData['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        if (empty($updateData)) {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        Db::startTrans();
        try {
            $updateResult = Db::name('user')->where('id', $user['id'])->update($updateData);

            if ($updateResult === false) {
                throw new \Exception('编辑资料失败');
            }

            Db::commit();

            $returnData = $updateData;

            unset($returnData['password']);

            return json(['code' => 0, 'message' => '保存成功', 'data' => $returnData]);
        } catch (\Throwable $e) {
            Db::rollback();

            return json(['code' => 1, 'message' => '保存失败：' . $e->getMessage()]);
        }
    }
}