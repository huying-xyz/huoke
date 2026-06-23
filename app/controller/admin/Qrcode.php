<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller\admin;

use think\Request;
use think\facade\Db;

class Qrcode
{
    public function list(Request $request)
    {
        $user = $request->user;
        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $page = max(1, (int)$request->param('page', 1));
        $limit = min(100, max(1, (int)$request->param('limit', 50)));
        $keyword = trim($request->param('keyword', ''));

        $query = Db::name('qrcode');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")->whereOr('remark', 'like', "%{$keyword}%")->whereOr('channel_type', 'like', "%{$keyword}%")->whereOr('www_url', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();

        $list = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();

        return json(['code' => 0, 'message' => '查询成功', 'list' => $list, 'total' => $total]);
    }

    public function edit(Request $request)
    {
        $user = $request->user;
        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }
        $id = (int)$request->param('id');
        $remark = $request->param('remark');
        if (!$id) {
            return json(['code' => 1, 'message' => '参数错误']);
        }
        Db::name('qrcode')->where('id', $id)->update(['remark' => $remark]);
        return json(['code' => 0, 'message' => '保存成功']);
    }

    public function delete(Request $request)
    {
        $user = $request->user;
        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }
        $id = (int)$request->param('id');
        if (!$id) {
            return json(['code' => 1, 'message' => '参数错误']);
        }
        Db::name('qrcode')->where('id', $id)->delete();
        return json(['code' => 0, 'message' => '删除成功']);
    }

    public function channelList(Request $request)
    {
        $user = $request->user;
        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }
        $list = Db::name('qrcode_channel')->order('sort')->select();
        return json(['code' => 0, 'message' => '查询成功', 'list' => $list]);
    }

    public function channelAdd(Request $request)
    {
        $user = $request->user;
        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }
        $data = $request->only(['type', 'name', 'icon', 'sort', 'status', 'points']);
        $id = Db::name('qrcode_channel')->insertGetId($data);
        return json(['code' => 0, 'message' => '新增成功', 'newid' => $id]);
    }

    public function channelEdit(Request $request)
    {
        $user = $request->user;
        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }
        $data = $request->only(['id', 'type', 'name', 'icon', 'sort', 'status', 'points']);
        if (empty($data['id'])) {
            return json(['code' => 1, 'message' => '参数错误']);
        }
        Db::name('qrcode_channel')->where('id', (int)$data['id'])->update($data);
        return json(['code' => 0, 'message' => '更新成功']);
    }

    public function channelDelete(Request $request)
    {
        $user = $request->user;
        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }
        $id = (int)$request->param('id');
        if (!$id) {
            return json(['code' => 1, 'message' => '参数错误']);
        }
        Db::name('qrcode_channel')->where('id', $id)->delete();
        return json(['code' => 0, 'message' => '删除成功']);
    }
}