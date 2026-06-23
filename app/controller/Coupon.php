<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;

class Coupon
{
    public function list(Request $request)
    {
        $user = $request->user;
        $page = max(1, (int)$request->param('page', 1));
        $limit = min(100, max(1, (int)$request->param('limit', 50)));
        $keyword = trim($request->param('keyword'));
        $status = (int)$request->param('status', -1);

        $query = Db::name('coupon')
            ->alias('c')
            ->leftJoin('plan p', 'c.plan_id = p.id')
            ->leftJoin('user u', 'c.from_user_id = u.id')
            ->where('c.user_id', $user['id']);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('c.code', "%{$keyword}%");
            });
        }

        if ($status == 0) {
            $query->where('c.status', 0);
            $query->where('c.end_time', '>', date('Y-m-d'));
        } elseif ($status == 1) {
            $query->where('c.status', 1);
        } elseif ($status == 2) {
            $query->where('c.end_time', '<', date('Y-m-d'));
            $query->where('c.status', '<>', 1);
        }

        $total = $query->count();

        $list = $query->field('c.*, p.name as plan_name, u.username')
            ->order('c.id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['create_time'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : null;
            $item['usage_time'] = $item['usage_time'] ? date('Y-m-d H:i:s', $item['usage_time']) : null;

            if ($item['from_user_id'] == 0) {
                $item['username'] = '系统';
            } else {
                if (!empty($item['username'])) {
                    $item['username'] = substr($item['username'], 0, 3) . '****' . substr($item['username'], 7);
                }
            }

            if ($item['plan_id'] == 0) {
                $item['plan_name'] = '所有套餐';
            }
        }

        return json(['code' => 0, 'message' => '查询成功', 'list' => $list, 'total' => $total]);
    }

    public function add(Request $request)
    {
        $user = $request->user;
        $code = $request->param('code');

        $coupon = Db::name('coupon')
            ->where('code', $code)
            ->where('user_id', '<>', $user['id'])
            ->where('end_time', '>=', date('Y-m-d'))
            ->where('status', 0)
            ->find();

        if (!$coupon) {
            return json(['code' => 1, 'message' => '优惠券不存在或已使用或已过期']);
        }

        Db::name('coupon')->where('id', $coupon['id'])->update(['user_id' => $user['id'], 'from_user_id' => $coupon['user_id']]);

        return json(['code' => 0, 'message' => '兑换成功']);
    }

    public function delete(Request $request)
    {
        $user = $request->user;
        $code = $request->param('code');

        $coupon = Db::name('coupon')->where('code', $code)->where('user_id', $user['id'])->find();
        if (!$coupon) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        Db::name('coupon')->where('code', $code)->delete();

        return json(['code' => 0, 'message' => '删除成功']);
    }

    public function transfer(Request $request)
    {
        $user = $request->user;
        $code = $request->param('code');
        $username = $request->param('username');

        $targetUser = Db::name('user')->where('username', $username)->find();
        if (!$targetUser) {
            return json(['code' => 1, 'message' => '目标用户不存在']);
        }

        $coupon = Db::name('coupon')
            ->where('code', $code)
            ->where('user_id', $user['id'])
            ->where('status', 0)
            ->find();

        if (!$coupon) {
            return json(['code' => 1, 'message' => '优惠券不存在或已使用']);
        }

        if ($coupon['end_time'] < date('Y-m-d')) {
            return json(['code' => 1, 'message' => '优惠券已过期']);
        }

        Db::name('coupon')->where('id', $coupon['id'])->update(['user_id' => $targetUser['id'], 'from_user_id' => $user['id']]);

        return json(['code' => 0, 'message' => '转赠成功']);
    }
}