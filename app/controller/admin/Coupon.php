<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller\admin;

use think\Request;
use think\facade\Db;

class Coupon
{
    public function create(Request $request)
    {
        $user = $request->user;
        if ($user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $data = $request->post();

        $userId = Db::name('user')->where('username', $data['username'])->value('id');

        if (!$userId) {
            return json(['code' => 1, 'message' => '手机号码错误']);
        }

        if (empty($data['quantity']) || $data['quantity'] <= 0) {
            return json(['code' => 1, 'message' => '发券数量错误']);
        }

        if (!in_array($data['type'], ['both', 'vip', 'points'])) {
            return json(['code' => 1, 'message' => '适用类型错误']);
        }

        if (!in_array($data['discount_type'], ['fixed', 'percent'])) {
            return json(['code' => 1, 'message' => '优惠类型错误']);
        }

        if (empty($data['discount_value']) || $data['discount_value'] <= 0) {
            return json(['code' => 1, 'message' => '优惠金额错误']);
        }

        if ($data['type'] == 'vip' && $data['plan_id'] === null) {
            return json(['code' => 1, 'message' => '会员套餐错误']);
        }

        if (empty($data['min_purchase']) || $data['min_purchase'] < 0) {
            return json(['code' => 1, 'message' => '满减金额错误']);
        }

        if (empty($data['start_time'])) {
            return json(['code' => 1, 'message' => '开始时间错误']);
        }

        if (empty($data['end_time'])) {
            return json(['code' => 1, 'message' => '结束时间错误']);
        }

        if ($data['start_time'] > $data['end_time']) {
            return json(['code' => 1, 'message' => '开始时间不能晚于结束时间']);
        }

        try {
            for ($i = 0; $i < $data['quantity']; $i++) {
                Db::name('coupon')->insert([
                    'user_id' => $userId,
                    'from_user_id' => 0,
                    'type' => $data['type'],
                    'plan_id' => $data['plan_id'],
                    'discount_type' => $data['discount_type'],
                    'discount_value' => $data['discount_value'],
                    'min_purchase' => $data['min_purchase'],
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'create_time' => time(),
                    'status' => 0,
                    'code' => strtoupper(substr(uniqid(), -10)),
                ]);
            }

            return json(['code' => 0, 'message' => '发券成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'message' => '发券失败: ' . $e->getMessage()]);
        }
    }
}