<?php
namespace app\middleware;

use think\facade\Db;
use think\facade\Cache;

class Auth {
    public function handle($request, \Closure $next) {
        $token = $request->header('Authorization');
        if (empty($token) || strpos($token, 'Bearer ') !== 0) {
            return json(['code' => 401, 'message' => '用户未登录']);
        }

        $userId = Cache::get('token_' . substr($token, 7));
        if (!$userId) {
            return json(['code' => 401, 'message' => '登录已过期']);
        }

        $user = Db::name('user')->where('id', $userId)->field('id, plan_id, end_time, username, avatar, reg_time, reg_ip, openid, invite_id, first_commission, second_commission, commission_balance, points, feature_fee')->find();
        if (!$user) {
            return json(['code' => 401, 'message' => '用户不存在']);
        }
        $user['reg_time'] = date('Y-m-d H:i:s', $user['reg_time']);

        $request->user = $user;
        return $next($request);
    }
}