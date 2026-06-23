<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use GuzzleHttp\Client;

class CheckUrl
{
    public function douyin(Request $request)
    {
        $www_url = $request->param('url');
        if (!$www_url) {
            return json(['code' => 1, 'message' => '缺少 url 参数']);
        }

        try {
            $client = new Client();
            $response = $client->get('https://seclink.bytedance.com/?target=' . $www_url . '&app_version=37.5.0&lang=zh&scene=im&device_id=3097756329882732&app_id=1128&aid=1128&jumper_version=1&seclink_verion=1.1.0&device_platform=iphone&os_name=iOS&did=3097756329882732&app_version=37.5.0&channel=AppStore&biz_id=AnnieX&device_platform=iOS', [
                'allow_redirects' => false,
                'timeout' => 6.0,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 aweme_37.5.0 Region/CN AppTheme/light NetType/2G JsSdk/2.0 Channel/App ByteLocale/zh  ByteFullLocale/zh-Hans-CN WKWebView/1 Bullet/1 aweme/37.5.0 BulletTag/8929F1C6-C0AF-4C1D-846D-92CD43891AC6 BytedanceWebview/d8a21c6'
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $htmlContent = (string)$response->getBody();

            preg_match('/<p class="prompt_below_img_word"[^>]*>(.*?)<\/p>/is', $htmlContent, $alertMatches);
            $alertText = trim(strip_tags($alertMatches[1] ?? ''));

            preg_match('/<p class="prompt_detail_word"[^>]*>(.*?)<\/p>/is', $htmlContent, $detailMatches);
            $detailText = trim(strip_tags($detailMatches[1] ?? ''));

            return json([
                'code' => 0,
                'message' => '检测成功',
                'data' => [
                    'status_code' => $statusCode,
                    'alert_text' => $alertText,
                    'detail_text' => $detailText
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'message' => '检测失败']);
        }
    }

    public function weibo(Request $request)
    {
        $www_url = $request->param('url');
        if (!$www_url) {
            return json(['code' => 1, 'message' => '缺少 url 参数']);
        }

        try {
            $client = new Client();
            $response = $client->get('https://weibo.cn/sinaurl?to=m&skin=default&c=iphone&lang=zh_CN&sflag=1&uicode=80000001&ua=iPhone14%2C4__weibo__16.4.2__iphone__os16.1.1&moduleID=composer&u=' . urlencode($www_url) . '&wm=3333_2001&launchid=10000365--x&ft=0&luicode=10000073&orifid=232823%24%242028810631&network=wifi&oriuicode=10000414_10000073&from=10G4293010&v_p=93&networktype=wifi&source_code=10000414_232823&lfid=2028810631&b=0', [
                'allow_redirects' => false,
                'timeout' => 6.0,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 aweme_37.5.0 Region/CN AppTheme/light NetType/2G JsSdk/2.0 Channel/App ByteLocale/zh  ByteFullLocale/zh-Hans-CN WKWebView/1 Bullet/1 aweme/37.5.0 BulletTag/8929F1C6-C0AF-4C1D-846D-92CD43891AC6 BytedanceWebview/d8a21c6'
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $htmlContent = (string)$response->getBody();

            preg_match('/<div class="text"[^>]*>\s*(.*?)\s*<\/div>/is', $htmlContent, $alertMatches);
            $alert_text = trim(strip_tags($alertMatches[1] ?? ''));

            preg_match_all('/<div class="desc"[^>]*>(.*?)<\/div>/is', $htmlContent, $detailMatches);
            $detail_text = '';
            if (!empty($detailMatches[1])) {
                $detail_text = trim(strip_tags($detailMatches[1][1] ?? ''));
            }

            return json([
                'code' => 0,
                'message' => '检测成功',
                'data' => [
                    'status_code' => $statusCode,
                    'alert_text' => $alert_text,
                    'detail_text' => $detail_text
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'message' => '检测失败']);
        }
    }
}