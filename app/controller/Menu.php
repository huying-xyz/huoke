<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;

class Menu
{
    public function updateTime(Request $request)
    {
        $updateTime = Db::name('menu')->order('update_time', 'desc')->value('update_time');

        return json(['code' => 0, 'message' => '查询成功', 'updateTime' => $updateTime]);
    }

    public function list(Request $request)
    {
        $user = $request->user;

        if ($user['id'] != 1) {
            $where[] = ['is_admin', '=', 0];
        }

        $where = [ ['is_show', '=', 1] ];

        $menu = Db::name('menu')->where($where)->order('sort', 'asc')->select()->toArray();

        $buildTree = function ($list, $parentId = 0) use (&$buildTree) {
            $tree = [];
            foreach ($list as $item) {
                if ($item['parent_id'] == $parentId) {
                    $children = $buildTree($list, $item['id']);
                    if (!empty($children)) {
                        $item['children'] = $children;
                    }
                    $tree[] = $item;
                }
            }
            return $tree;
        };

        $treeMenu = $buildTree($menu);

        return json(['code' => 0, 'message' => '查询成功', 'list' => $treeMenu]);
    }
}