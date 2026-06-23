<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller\admin;

use think\Request;
use think\facade\Db;

class Menu
{
    public function list(Request $request)
    {
        $user = $request->user;

        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $list = Db::name('menu')->order('parent_id', 'asc')->order('sort', 'asc')->select()->toArray();

        return json(['code' => 0, 'message' => '获取成功', 'data' => $list]);
    }

    public function add(Request $request)
    {
        $user = $request->user;

        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $param = $request->only(['parent_id', 'name', 'icon', 'url', 'sort', 'is_show', 'is_admin']);
        $now   = time();

        Db::name('menu')->insert([
            'parent_id'   => $param['parent_id'] ?: 0,
            'name'        => $param['name'],
            'icon'        => $param['icon'] ?: '',
            'url'         => $param['url'] ?: '',
            'sort'        => $param['sort'] ?: 0,
            'is_show'     => $param['is_show'] ?? 1,
            'is_admin'    => $param['is_admin'] ?? 0,
            'create_time' => $now,
            'update_time' => $now
        ]);

        return json(['code' => 0, 'message' => '添加成功']);
    }

    public function edit(Request $request)
    {
        $user = $request->user;

        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $id = (int)$request->param('id');

        if (empty($id)) {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        $param = $request->only(['parent_id', 'name', 'icon', 'url', 'sort', 'is_show', 'is_admin']);

        Db::name('menu')->where('id', $id)->update([
            'parent_id'   => $param['parent_id'] ?: 0,
            'name'        => $param['name'],
            'icon'        => $param['icon'] ?: '',
            'url'         => $param['url'] ?: '',
            'sort'        => $param['sort'] ?: 0,
            'is_show'     => $param['is_show'] ?? 1,
            'is_admin'    => $param['is_admin'] ?? 0,
            'update_time' => time()
        ]);

        return json(['code' => 0, 'message' => '修改成功']);
    }

    public function delete(Request $request)
    {
        $user = $request->user;

        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $id = (int)$request->param('id');

        if (empty($id)) {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        $hasChild = Db::name('menu')->where('parent_id', $id)->count();

        if ($hasChild > 0) {
            return json(['code' => 1, 'message' => '请先删除下级菜单']);
        }

        Db::name('menu')->where('id', $id)->delete();

        return json(['code' => 0, 'message' => '删除成功']);
    }
}