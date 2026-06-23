<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller\admin;

use think\Request;
use think\facade\Db;
use OSS\OssClient;
use OSS\Core\OssException;

class Material
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

        $query = Db::name('material');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('user_id', 'like', "%{$keyword}%")->whereOr('name', 'like', "%{$keyword}%")->whereOr('url', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();

        $list = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();

        $list = array_map(function($item) {
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            return $item;
        }, $list);

        return json(['code' => 0, 'message' => '查询成功', 'list' => $list, 'total' => $total]);
    }

    public function delete(Request $request)
    {
        $user = $request->user;
        $id = (int)$request->param('id');
        if ((int)$user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }
        $material = Db::name('material')->where('id', $id)->find();
        if (!$material) {
            return json(['code' => 1, 'message' => '素材不存在']);
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