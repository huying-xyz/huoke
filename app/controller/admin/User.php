<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller\admin;

use think\Request;
use think\facade\Db;

class User
{
    public function list(Request $request)
    {
        $user = $request->user;

        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $page = max(1, (int)$request->param('page', 1));
        $limit = min(100, max(1, (int)$request->param('limit', 100)));
        $keyword = trim($request->param('keyword', ''));

        $query = Db::name('user')->alias('u')->leftJoin('plan p', 'u.plan_id = p.id')->field('u.*, p.name as plan_name');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('u.id', 'like', "%{$keyword}%")->whereOr('u.username', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();

        $list = $query->order('u.id', 'desc')->page($page, $limit)->select()->toArray();

        $list = array_map(function($item) {
            $item['password'] = !empty($item['password']);
            $item['reg_time'] = date('Y-m-d H:i:s', $item['reg_time']);
            return $item;
        }, $list);

        return json(['code' => 0, 'message' => '查询成功', 'list' => $list, 'total' => $total]);
    }

    public function edit(Request $request)
    {
        $user = $request->user;
        $id = (int)$request->param('id');

        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $user = Db::name('user')->where('id', $id)->find();
        if (!$user) {
            return json(['code' => 1, 'message' => '用户不存在']);
        }

        $data = [];

        if ($request->has('plan_id')) {
            $plan_id = $request->param('plan_id');
            if ($plan_id !== '') {
                $data['plan_id'] = (int)$plan_id;
            }
        }

        if ($request->has('end_time')) {
            $end_time = $request->param('end_time');
            if ($end_time !== '') {
                $data['end_time'] = $end_time;
            }
        }

        if ($request->has('username')) {
            $username = trim($request->param('username'));
            if ($username !== '') {
                $data['username'] = $username;
            }
        }

        if ($request->has('password')) {
            $password = $request->param('password');
            if ($password !== '') {
                $data['password'] = password_hash($password, PASSWORD_BCRYPT);
            }
        }

        if ($request->has('invite_id')) {
            $invite_id = $request->param('invite_id');
            if ($invite_id !== '') {
                $data['invite_id'] = (int)$invite_id;
            }
        }

        if ($request->has('points')) {
            $points = (int)$request->param('points');
            if ($points !== '' && $points !== $user['points']) {
                $data['points'] = $points;

                $before_points = $user['points'];
                $after_points = $points;

                Db::name('points')->insert(['user_id' => $user['id'], 'points_change' => $after_points - $before_points, 'type' => 3, 'remark' => '后台编辑', 'before_points' => $before_points, 'after_points' => $after_points, 'create_time' => time()]);
            }
        }

        if ($request->has('feature_fee')) {
            $feature_fee = $request->param('feature_fee');
            if ($feature_fee !== '') {
                json_decode($feature_fee);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['feature_fee'] = $feature_fee;
                } else {
                    return json(['code' => 1, 'message' => '参数错误']);
                }
            }
        }

        if ($request->has('first_commission')) {
            $first_commission = $request->param('first_commission');
                if ($first_commission !== '') {
                    $data['first_commission'] = (float)$first_commission;
                } else {
                    $data['first_commission'] = null;
                }
        }

        if ($request->has('second_commission')) {
            $second_commission = $request->param('second_commission');
            if ($second_commission !== '') {
                $data['second_commission'] = (float)$second_commission;
            } else {
                $data['second_commission'] = null;
            }
        }

        Db::name('user')->where('id', $id)->update($data);

        return json(['code' => 0, 'message' => '更新成功']);
    }

    public function delete(Request $request)
    {
        $user = $request->user;
        $id = $request->param('id');

        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $user = Db::name('user')->where('id', $id)->find();
        if (!$user) {
            return json(['code' => 1, 'message' => '用户不存在']);
        }

        Db::name('user')->where('id', $id)->delete();

        return json(['code' => 0, 'message' => '删除成功']);
    }
}