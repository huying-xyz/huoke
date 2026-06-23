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
use app\common\service\ProprietaryService;

class Cron
{
    public function miguCookie()
    {
        return ProprietaryService::miguCookie();
    }

    public function expiredData()
    {
        try {
            // 删除3个月前的访问日志
            Db::name('logs')->where('time', '<', strtotime('-3 months'))->delete();

            // 删除5分钟前的登录场景
            Db::name('login_scene')->where('create_time', '<', time() - 300)->delete();

            // 释放1天前未支付和已关闭的订单锁定的优惠券
            $expiredOrders = Db::name('order')->whereIn('status', [0, 2])->where('create_time', '<', strtotime('-1 day'))->where('coupon_id', '>', 0)->column('id, coupon_id');
            if (!empty($expiredOrders)) {
                $couponIds = array_column($expiredOrders, 'coupon_id');
                Db::name('coupon')->whereIn('id', $couponIds)->update(['usage_time' => null, 'status' => 0]);
            }

            // 删除1天前未支付和已关闭的订单
            Db::name('order')->whereIn('status', [0, 2])->where('create_time', '<', strtotime('-1 day'))->delete();

            // 删除1个月前已支付的订单
            Db::name('order')->where('status', 1)->where('create_time', '<', strtotime('-1 month'))->delete();

            // 删除1个月前的积分记录
            Db::name('points')->where('create_time', '<', strtotime('-1 month'))->delete();

            // 删除试用会员关联数据
            $today = date('Y-m-d');
            Db::name('user')->where('plan_id', 1)->where('end_time', '<=', $today)->chunk(500, function ($trialUsers) {
                foreach ($trialUsers as $user) {
                    $userId = $user['id'];
                    Db::name('land')->where('user_id', $userId)->delete();
                    Db::name('link')->where('user_id', $userId)->delete();
                }
            });

            // 删除开通会员关联数据
            $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
            Db::name('user')->where('plan_id', '<>', 1)->where('end_time', '<=', $sevenDaysAgo)->chunk(500, function ($vipUsers) {
                foreach ($vipUsers as $user) {
                    $userId = $user['id'];
                    Db::name('land')->where('user_id', $userId)->delete();
                    Db::name('link')->where('user_id', $userId)->delete();
                    Db::name('user')->where('id', $userId)->update(['plan_id' => 1]);
                }
            });

            // 删除5分钟前的登录场景OSS文件
            $accessKeyId = env('OSS_ACCESS_KEY_ID');
            $accessKeySecret = env('OSS_ACCESS_KEY_SECRET');
            $endpoint = env('OSS_ENDPOINT');
            $bucket = env('OSS_BUCKET');
            if (!empty($accessKeyId) && !empty($accessKeySecret) && !empty($endpoint) && !empty($bucket)) {
                try {
                    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                    $listObjects = $ossClient->listObjects($bucket, [
                        'prefix' => 'upload/scene/',
                        'max-keys' => 1000
                    ]);
                    $getObjectList = $listObjects->getObjectList();

                    if (!empty($getObjectList)) {
                        foreach ($getObjectList as $objectInfo) {
                            $lastModified = strtotime($objectInfo->getLastModified());
                            if (time() - $lastModified > 300) {
                                $ossClient->deleteObject($bucket, $objectInfo->getKey());
                            }
                        }
                    }
                } catch (\OssException $e) {
                } catch (\Exception $e) {
                }
            }

            return 'success';
        } catch (\Exception $e) {
            return 'fail: ' . $e->getMessage();
        }
    }
}