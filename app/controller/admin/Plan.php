<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller\admin;

use think\Request;
use think\facade\Db;

class Plan
{
    public function list(Request $request)
    {
        $user = $request->user;
        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }
        $list = Db::name('plan')->select();
        return json(['code' => 0, 'message' => '查询成功', 'list' => $list]);
    }

    public function add(Request $request)
    {
        $user = $request->user;
        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }
        $data = $request->only(['name', 'price', 'link', 'desc']);
        $id = Db::name('plan')->insertGetId(['name' => $data['name'], 'price' => $data['price'], 'link' => $data['link'], 'desc' => $data['desc']]);
        return json(['code' => 0, 'message' => '新增成功', 'newid' => $id]);
    }

    public function edit(Request $request)
    {
        $user = $request->user;
        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }
        $data = $request->only(['id', 'name', 'price', 'link', 'desc']);
        if (empty($data['id'])) {
            return json(['code' => 1, 'message' => '参数错误']);
        }
        Db::name('plan')->where('id', (int)$data['id'])->update(['name' => $data['name'], 'price' => $data['price'], 'link' => $data['link'], 'desc' => $data['desc']]);
        return json(['code' => 0, 'message' => '保存成功']);
    }

    public function delete(Request $request)
    {
        $user = $request->user;
        $id = (int)$request->param('id');
        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }
        $plan = Db::name('plan')->where('id', $id)->find();
        if (!$plan) {
            return json(['code' => 1, 'message' => '套餐不存在']);
        }
        Db::name('plan')->where('id', $id)->delete();
        return json(['code' => 0, 'message' => '删除成功']);
    }
}