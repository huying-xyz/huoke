<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;

class Land
{
    public function list(Request $request)
    {
        $user = $request->user;
        $user_id = $request->param('user_id');
        if ((string)$user_id === '0') {
            $list = Db::name('land')->where('user_id', 0)->order('id')->select();
        } else {
            $list = Db::name('land')->where('user_id', $user['id'])->order('id')->select();
        }
        return json(['code' => 0, 'message' => '查询成功', 'list' => $list]);
    }

    public function detail(Request $request)
    {
        $user = $request->user;
        $id = $request->param('id');
        if ((int)$user['id'] === 1) {
            $land = Db::name('land')->where('id', $id)->find();
        } else {
            $land = Db::name('land')->where('id', $id)->where('user_id', $user['id'])->find();
        }
        if (!$land) {
            return json(['code' => 1, 'message' => '没有模板权限']);
        }
        $land['config'] = json_encode($this->sortConfig(json_decode($land['config'], true), $land['type']), JSON_UNESCAPED_UNICODE);
        return json(['code' => 0, 'message' => '查询成功', 'land' => $land]);
    }

    public function add(Request $request)
    {
        $user = $request->user;
        $id = $request->param('id');
        $land = Db::name('land')->where('id', $id)->where('user_id', 0)->find();
        if (!$land) {
            return json(['code' => 1, 'message' => '没有模板权限']);
        }
        unset($land['id']);
        $land['user_id'] = $user['id'];
        $id = Db::name('land')->insertGetId($land);
        return json(['code' => 0, 'message' => '创建成功', 'id' => $id]);
    }

    public function edit(Request $request)
    {
        $user = $request->user;
        $id = $request->param('id');
        $title = $request->param('title');
        $cover = $request->param('cover');
        $config = $request->param('config');
        if (!is_string($config)) {
            return json(['code' => 1, 'message' => '模板参数错误']);
        }
        $decodedConfig = json_decode($config, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json(['code' => 1, 'message' => '模板参数错误']);
        }
        if ((int)$user['id'] === 1) {
            $land = Db::name('land')->where('id', $id)->find();
        } else {
            $land = Db::name('land')->where('id', $id)->where('user_id', $user['id'])->find();
        }
        if (!$land) {
            return json(['code' => 1, 'message' => '没有模板权限']);
        }
        Db::name('land')->where('id', $id)->update(['title'  => $title, 'config' => $config, 'cover' => $cover]);
        return json(['code' => 0, 'message' => '更新成功']);
    }

    public function delete(Request $request)
    {
        $user = $request->user;
        $id = $request->param('id');
        $land = Db::name('land')->where('id', $id)->where('user_id', $user['id'])->find();
        if (!$land) {
            return json(['code' => 1, 'message' => '没有模板权限']);
        }
        Db::name('land')->where('id', $id)->delete();
        return json(['code' => 0, 'message' => '删除成功']);
    }

    public function setDefault(Request $request)
    {
        $user = $request->user;
        if ($user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }
        $id = $request->param('id');
        if ($id == 1) {
            return json(['code' => 1, 'message' => '当前已是默认模板']);
        }
        $target = Db::name('land')->where('id', $id)->where('user_id', 0)->find();
        if (!$target) {
            return json(['code' => 1, 'message' => '模板不存在']);
        }
        $current = Db::name('land')->where('id', 1)->find();
        if (!$current) {
            return json(['code' => 1, 'message' => '默认模板不存在']);
        }
        Db::name('land')->where('id', 1)->update([
            'title' => $target['title'],
            'type'  => $target['type'],
            'cover' => $target['cover'],
            'config' => $target['config'],
        ]);
        Db::name('land')->where('id', $id)->update([
            'title' => $current['title'],
            'type'  => $current['type'],
            'cover' => $current['cover'],
            'config' => $current['config'],
        ]);
        return json(['code' => 0, 'message' => '设置成功']);
    }

    // 模板字段自定义排序
    protected function sortConfig(array $config, string $type): array
    {
        $map = [
            'default' => [
                'logo' => ['url', 'width', 'height', 'borderRadius'],
                'title' => ['text', 'color', 'fontSize'],
                'button' => ['text', 'color', 'fontSize', 'borderRadius', 'backgroundColor', 'enableAnimation', 'animationType'],
            ],
            'default2' => [
                'logo' => ['url', 'width', 'height', 'borderRadius', 'animationType', 'circleAnimation'],
                'notice' => ['icon', 'text', 'color', 'fontSize', 'backgroundColor', 'borderRadius'],
                'button' => ['text', 'color', 'fontSize', 'borderRadius', 'backgroundColor', 'enableAnimation', 'animationType'],
            ],
            'wxid' => [
                'logo' => ['url', 'width', 'height', 'borderRadius'],
                'name' => ['text', 'color', 'fontSize'],
                'account' => ['type', 'text', 'color', 'fontSize'],
                'auth' => ['url', 'width', 'height', 'borderRadius'],
                'desc' => ['text', 'color', 'fontSize'],
                'button' => ['text', 'color', 'fontSize', 'borderRadius', 'backgroundColor', 'enableAnimation', 'animationType'],
            ],
            'dialog' => [
                'images' => ['bannerUrl', 'myAvatar', 'clientAvatar'],
                'messages' => ['welcomeMessage', 'endMessage', 'rejectMessage'],
                'button' => ['text', 'color', 'fontSize', 'borderRadius', 'backgroundColor', 'enableAnimation', 'animationType'],
                'questions' => [],
            ],
        ];
        $result = [];
        foreach ($map[$type] as $key => $subKeys) {
            if (!isset($config[$key])) {
                continue;
            }
            if ($subKeys && is_array($config[$key])) {
                $subResult = [];
                foreach ($subKeys as $subKey) {
                    if (array_key_exists($subKey, $config[$key])) {
                        $subResult[$subKey] = $config[$key][$subKey];
                    }
                }
                foreach ($config[$key] as $k => $v) {
                    if (!isset($subResult[$k])) {
                        $subResult[$k] = $v;
                    }
                }
                $result[$key] = $subResult;
            } else {
                $result[$key] = $config[$key];
            }
        }
        foreach ($config as $k => $v) {
            if (!isset($result[$k])) {
                $result[$k] = $v;
            }
        }
        return $result;
    }
}