<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;
use think\facade\Validate;
use OSS\OssClient;
use OSS\Core\OssException;
use app\common\service\ProprietaryService;

class Upload
{
    public function image(Request $request)
    {
        $user = $request->user;
        $file = $request->file('file');
        $type = $request->param('type');
        $code = $request->param('code');
        if (!$file) {
            return json(['code' => 1, 'message' => '请上传图片']);
        }
        $validate = Validate::rule([
            'file' => 'file|image|fileSize:5242880|fileExt:jpg,jpeg,png,gif'
        ]);
        if (!$validate->check(['file' => $file])) {
            return json(['code' => 1, 'message' => $validate->getError()]);
        }
        $fileExt = $file->extension();
        $fileName = uniqid() . '.' . $fileExt;
        $tmpDir = runtime_path() . 'tmp/';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }
        $localPath = $tmpDir . $fileName;
        $file->move($tmpDir, $fileName);
        $wwwUrl = '';
        try {
            if (in_array($type, ['migu', 'cli', 'zto', 'zhaopin', 'zhaopin123', 'txwj'])) {
                try {
                    $result = ProprietaryService::upload($type, $localPath, $fileName, $code);
                    $wwwUrl = $result['url'];
                    if ($result['txqr_url']) {
                        return json(['code' => 0, 'message' => '上传成功', 'url' => $wwwUrl, 'txqr_url' => $result['txqr_url']]);
                    }
                } catch (\RuntimeException $e) {
                    return json(['code' => 1, 'message' => '上传失败']);
                }
            } else {
                $ossPath = 'upload/image/' . $fileName;
                $ossClient = new OssClient(env('OSS_ACCESS_KEY_ID'), env('OSS_ACCESS_KEY_SECRET'), env('OSS_ENDPOINT'));
                $ossClient->uploadFile(env('OSS_BUCKET'), $ossPath, $localPath);
                Db::name('material')->insert(['user_id' => $user['id'], 'name' => pathinfo($file->getOriginalName(), PATHINFO_FILENAME), 'url' => env('OSS_URL') . $ossPath, 'create_time' => time()]);
                $wwwUrl = env('OSS_URL') . $ossPath;
            }
            return json(['code' => 0, 'message' => '上传成功', 'url' => $wwwUrl]);
        } finally {
            if (file_exists($localPath)) {
                @unlink($localPath);
            }
        }
    }
}
