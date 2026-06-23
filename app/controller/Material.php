<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;
use OSS\OssClient;
use OSS\Core\OssException;

class Material
{
    public function list(Request $request)
    {
        $user = $request->user;
        $page = max(1, (int)$request->param('page', 1));
        $limit = min(80, max(1, (int)$request->param('limit', 10)));
        $total = Db::name('material')->where('user_id', $user['id'])->count();
        $list = Db::name('material')->where('user_id', $user['id'])->order('id', 'desc')->page($page, $limit)->select();
        return json(['code' => 0, 'message' => '查询成功', 'list' => $list, 'total' => $total]);
    }

    public function delete(Request $request)
    {
        $user = $request->user;
        $id = $request->param('id');
        $material = Db::name('material')->where('id', $id)->where('user_id', $user['id'])->find();
        if (!$material) {
            return json(['code' => 1, 'message' => '没有素材权限']);
        }
        if (!empty($material['url'])) {
            try {
                $ossClient = new OssClient(env('OSS_ACCESS_KEY_ID'), env('OSS_ACCESS_KEY_SECRET'), env('OSS_ENDPOINT'));
                $object = parse_url($material['url'], PHP_URL_PATH);
                $object = ltrim($object, '/');
                $ossClient->deleteObject(env('OSS_BUCKET'), $object);
            } catch (OssException $e) {
                return json(['code' => 1, 'message' => 'OSS删除失败: ' . $e->getMessage()]);
            }
        }
        Db::name('material')->where('id', $id)->delete();
        return json(['code' => 0, 'message' => '删除成功']);
    }
}