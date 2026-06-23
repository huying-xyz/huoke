<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller\admin;

use think\Request;
use think\facade\Db;
use EasyWeChat\Factory;
use GuzzleHttp\Client;
use ZipArchive;

class Site
{
    public function urlsList(Request $request)
    {
        $user = $request->user;
        if ($user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $file = root_path() . 'urls.json';

        $json = @file_get_contents($file);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return json(['code' => 1, 'message' => '配置文件urls.json格式错误']);
        }

        return json(['code' => 0, 'data' => $data]);
    }

    public function urlsEdit(Request $request)
    {
        $user = $request->user;
        if ($user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $file = root_path() . 'urls.json';

        $raw = $request->getInput();
        $dataArr = json_decode($raw, true);

        if (!isset($dataArr['data']) || !is_array($dataArr['data'])) {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        $data = $dataArr['data'];

        foreach ($data as $key => &$items) {
            if (is_array($items)) {
                $items = array_filter($items, function ($item) {
                    return isset($item['url']) && $item['url'] !== '';
                });
            } else {
                $items = [];
            }
        }

        $result = file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($result === false) {
            return json(['code' => 1, 'message' => '保存失败']);
        }

        return json(['code' => 0, 'message' => '保存成功']);
    }

    public function selfmenuList(Request $request)
    {
        $user = $request->user;
        if ($user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $app = Factory::officialAccount(['app_id' => env('WX_APPID'), 'secret' => env('WX_APPSECRET'), 'token' => env('WX_TOKEN'), 'aes_key' => env('WX_AESKEY')]);

        try {
            $res = $app->menu->list();
            return json(['code' => 0, 'message' => '获取成功', 'data' => $res]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'message' => $e->getMessage()]);
        }
    }

    public function selfmenuDelete(Request $request)
    {
        $user = $request->user;
        if ($user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $app = Factory::officialAccount(['app_id' => env('WX_APPID'), 'secret' => env('WX_APPSECRET'), 'token' => env('WX_TOKEN'), 'aes_key' => env('WX_AESKEY')]);

        try {
            $res = $app->menu->delete();
            return json(['code' => 0, 'message' => '停用成功', 'data' => $res]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'message' => $e->getMessage()]);
        }
    }

    public function selfmenuCreate(Request $request)
    {
        $user = $request->user;
        if ($user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $buttons = $request->param('buttons');
        if (empty($buttons)) {
            return json(['code' => 1, 'message' => '菜单不能为空']);
        }

        $app = Factory::officialAccount(['app_id' => env('WX_APPID'), 'secret' => env('WX_APPSECRET'), 'token' => env('WX_TOKEN'), 'aes_key' => env('WX_AESKEY')]);

        try {
            $res = $app->menu->create($buttons);
            return json(['code' => 0, 'message' => '发布成功', 'data' => $res]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'message' => $e->getMessage()]);
        }
    }

    public function autoreplyList(Request $request)
    {
        $user = $request->user;
        if ($user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $type = $request->param('type');

        $wechat_autoreply = Db::name('wechat_autoreply')->where('type', $type)->order('id desc')->select()->toArray();

        foreach ($wechat_autoreply as &$item) {
            if (is_string($item['content'])) {
                $decoded = json_decode($item['content'], true);
                $item['content'] = $decoded ?: ['reply_type' => 'text', 'content' => $item['content']];
            }
        }

        return json(['code' => 0, 'data' => $wechat_autoreply]);
    }

    public function autoreplySave(Request $request)
    {
        $user = $request->user;
        if ($user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $post = $request->post();

        $data = [
            'type'    => $post['type'],
            'keyword' => $post['keyword'] ?? null,
            'content' => is_array($post['content']) ? json_encode($post['content'], JSON_UNESCAPED_UNICODE) : $post['content'],
        ];

        if (isset($post['id']) && $post['id']) {
            Db::name('wechat_autoreply')->where('id', $post['id'])->update($data);
        } else {
            if (in_array($data['type'], ['subscribe', 'default'])) {
                Db::name('wechat_autoreply')->where('type', $data['type'])->delete();
            }
            Db::name('wechat_autoreply')->insert($data);
        }

        return json(['code' => 0, 'message' => '保存成功']);
    }

    public function autoreplyDelete(Request $request)
    {
        $user = $request->user;
        if ($user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $id = $request->param('id');
        if (!$id) {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        Db::name('wechat_autoreply')->where('id', $id)->delete();
        return json(['code' => 0, 'message' => '删除成功']);
    }

    public function versionCheck(Request $request)
    {
        $user = $request->user;
        if ($user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $current_ver = env('APP_VERSION');

        try {
            $client = new Client();
            $response = $client->post(env('OPEN_URL') . 'version/check', [
                'form_params' => [
                    'current_ver' => $current_ver,
                    'domain'      => $request->domain(),
                ],
                'timeout' => 30
            ]);
            $result = json_decode($response->getBody()->getContents(), true);

            if ($result['code'] == 1) {
                return json(['code' => 1, 'message' => $result['message']]);
            }

            return json([
                'code'    => $result['code'],
                'message' => $result['message'],
                'data'    => [
                    'current_ver' => $current_ver,
                    'latest_ver'  => $result['data']['latest_ver'],
                    'update_log'  => $result['data']['update_log']
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'message' => '检查更新失败：' . $e->getMessage()]);
        }
    }

    public function versionUpdate(Request $request)
    {
        $user = $request->user;
        if ($user['id'] !== 1) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $currentVer     = env('APP_VERSION');
        $siteName       = env('SITE_NAME');
        $customerDomain = $request->domain() . '/';

        // 更新前检查服务器扩展
        $missing = $this->checkExtensions();
        if (!empty($missing)) {
            return json(['code' => 1, 'message' => '缺少必要扩展：' . implode(', ', $missing)]);
        }

        // 并发锁，防止重复更新
        $lockFile = root_path() . 'runtime/update/updating.lock';
        $lockDir  = dirname($lockFile);
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }
        $fp = fopen($lockFile, 'w');
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return json(['code' => 1, 'message' => '系统正在更新中，请稍后再试']);
        }

        try {
            $client   = new Client();
            $response = $client->post(env('OPEN_URL') . 'version/check', [
                'form_params' => [
                    'current_ver' => $currentVer,
                    'domain'      => $request->domain(),
                ],
                'timeout'     => 30
            ]);
            $result = json_decode($response->getBody()->getContents(), true);

            if ($result['code'] == 1) {
                return json(['code' => 1, 'message' => $result['message']]);
            }

            $updateInfo = $result['data'];

            // SQL增量更新
            if (!empty($updateInfo['sql_list'])) {
                // 先下载所有SQL文件
                $sqlFiles = [];
                foreach ($updateInfo['sql_list'] as $sqlUrl) {
                    $sqlFiles[] = $this->downloadFile($sqlUrl);
                }

                // 收集所有SQL语句
                $allSql = [];
                foreach ($sqlFiles as $sqlFile) {
                    $content = trim(file_get_contents($sqlFile));
                    $allSql  = array_merge($allSql, array_filter(array_map('trim', explode(';', $content))));
                }

                // 单事务全部执行
                Db::startTrans();
                try {
                    foreach ($allSql as $sql) {
                        Db::execute($sql);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    // 清理临时文件
                    foreach ($sqlFiles as $sqlFile) {
                        @unlink($sqlFile);
                    }
                    throw new \Exception('SQL更新失败：' . $e->getMessage());
                }

                // 清理临时文件
                foreach ($sqlFiles as $sqlFile) {
                    @unlink($sqlFile);
                }
            }

            // 后端全量更新
            if (!empty($updateInfo['backend_package'])) {
                $backendZip = $this->downloadFile($updateInfo['backend_package']);
                $this->unzipFile($backendZip, root_path());
                @unlink($backendZip);

                // 自定义 public/ 下的前端配置（替换域名和标题）
                $publicDir = root_path() . 'public';
                $allFiles  = [];
                $indexHtml = '';
                if (is_dir($publicDir)) {
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($publicDir, \RecursiveDirectoryIterator::SKIP_DOTS)
                    );
                    foreach ($iterator as $file) {
                        if (!$file->isFile()) continue;
                        $path = $file->getRealPath();
                        $ext  = $file->getExtension();
                        if ($ext === 'js' || strtolower($file->getFilename()) === 'index.html') {
                            $allFiles[] = $path;
                            if (strtolower($file->getFilename()) === 'index.html') {
                                $indexHtml = $path;
                            }
                        }
                    }
                }
                // 替换 API 域名
                $jsSuccess = false;
                $pattern   = '/apiUrl:"[^"]+"/';
                $apiUrl    = 'apiUrl:"' . $customerDomain . '"';
                foreach ($allFiles as $path) {
                    if (pathinfo($path, PATHINFO_EXTENSION) !== 'js') continue;
                    $content    = file_get_contents($path);
                    $newContent = preg_replace($pattern, $apiUrl, $content);
                    if ($newContent !== $content) {
                        file_put_contents($path, $newContent, LOCK_EX);
                        $jsSuccess = true;
                    }
                }
                if (!$jsSuccess) {
                    throw new \Exception("替换后端域名失败");
                }
                // 替换标题
                if (!empty($indexHtml) && file_exists($indexHtml)) {
                    $html = file_get_contents($indexHtml);
                    $html = preg_replace('/<title>(.*?)<\/title>/i', "<title>{$siteName}</title>", $html);
                    file_put_contents($indexHtml, $html, LOCK_EX);
                } else {
                    throw new \Exception("替换前端标题失败");
                }
            }

            // 更新ENV版本号
            $envPath = root_path() . '.env';
            $env     = file_get_contents($envPath);
            $newenv  = preg_replace('/^APP_VERSION\h*=.*$/im', "APP_VERSION = {$updateInfo['latest_ver']}", $env);
            file_put_contents($envPath, $newenv);

            return json(['code' => 0, 'message' => '更新成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'message' => '更新失败：' . $e->getMessage()]);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    private function checkExtensions()
    {
        $required = ['zip', 'pdo_mysql', 'curl', 'gd', 'mbstring', 'openssl'];

        $missing = [];
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        return $missing;
    }

    private function downloadFile($url)
    {
        $save_dir = root_path() . 'runtime/update/';

        if (!is_dir($save_dir)) {
            mkdir($save_dir, 0755, true);
            @chmod($save_dir, 0755);
        }

        $file_name = basename(parse_url($url, PHP_URL_PATH));
        $save_path = $save_dir . $file_name;

        $client = new Client();
        $client->get($url, ['sink' => $save_path]);

        return $save_path;
    }

    private function unzipFile($zip_file, $target_dir)
    {
        $zip = new ZipArchive();

        if ($zip->open($zip_file) !== true) {
            throw new \Exception('更新文件解压失败');
        }

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
            @chmod($target_dir, 0755);
        }

        $zip->extractTo($target_dir);
        $zip->close();
    }

}