<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;

class Order
{
    public function list(Request $request)
    {
        $user   = $request->user;
        $page   = max(1, (int)$request->param('page', 1));
        $limit  = min(100, max(1, (int)$request->param('limit', 50)));
        $keyword = trim($request->param('keyword'));

        $query = Db::name('order')->alias('o')->where('o.user_id', $user['id'])->leftJoin('plan p', 'o.plan_id = p.id')->field('o.*, p.name as plan_name');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('o.order_no', "%{$keyword}%")->whereOr('o.transaction_id', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();

        $list = $query->order('o.id', 'desc')->page($page, $limit)->select()->toArray();

        foreach ($list as &$item) {
            $item['create_time'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : null;

            $item['pay_time'] = $item['pay_time'] ? date('Y-m-d H:i:s', $item['pay_time']) : null;
        }

        return json(['code' => 0, 'message' => '查询成功', 'list' => $list, 'total' => $total]);
    }

    public function create(Request $request)
    {
        $user = $request->user;
        $plan_id = (int)$request->param('plan_id');
        $num = max(1, (int)$request->param('num'));

        $plan = Db::name('plan')->where('id', $plan_id)->find();
        if (!$plan) {
            return json(['code' => 1, 'message' => '参数错误']);
        }
        if ($plan['price'] <= 0) {
            return json(['code' => 1, 'message' => $plan['name'] . '禁止开通']);
        }

        $linkCount = Db::name('link')->where('user_id', $user['id'])->count();

        if ($linkCount > $plan['link']) {
            return json(['code' => 1, 'message' => '您当前已创建' . $linkCount . '个外链卡片，超出' . $plan['name'] . '限额' . $plan['link'] . '个，请先删除多余外链卡片或选择更高会员。']);
        }

        $currentPlan = Db::name('plan')->where('id', $user['plan_id'])->find();
        $nowDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime($user['end_time']));
        $remainingDays = max(0, (strtotime($endDate) - strtotime($nowDate)) / 86400);

        if ($plan['price'] > $currentPlan['price']) {
            $priceDiffPerDay = ($plan['price'] - $currentPlan['price']) / 30;
            $total = $num * $plan['price'] + $remainingDays * $priceDiffPerDay;
        } elseif ($plan['price'] < $currentPlan['price']) {
            $priceDiffPerDay = ($currentPlan['price'] - $plan['price']) / 30;
            $total = $num * $plan['price'] - $remainingDays * $priceDiffPerDay;
            if ($total <= 0) {
                return json(['code' => 1, 'message' => '订单金额小于 0，请增加购买月份']);
            }
        } else {
            $total = $num * $plan['price'];
        }

        $total   = round($total, 2);
        $orderNo = date('YmdHis') . random_int(1000, 9999);

        Db::name('order')->insertGetId([
            'order_no'    => $orderNo,
            'user_id'     => $user['id'],
            'type'        => 1,
            'plan_id'     => $plan_id,
            'num'         => $num,
            'price'       => $plan['price'],
            'total'       => $total,
            'status'      => 0,
            'create_time' => time(),
        ]);

        $coupons = Db::name('coupon')
            ->where('user_id', $user['id'])
            ->where('status', 0)
            ->where('start_time', '<=', date('Y-m-d'))
            ->where('end_time', '>=', date('Y-m-d'))
            ->where('min_purchase', '<=', $total)
            ->whereIn('type', ['vip', 'both'])
            ->where(function ($query) use ($plan_id) {
                $query->where('plan_id', 0)
                      ->whereOr('plan_id', $plan_id);
            })
            ->select();

        $availableCoupons = [];
        foreach ($coupons as $coupon) {
            $availableCoupons[] = [
                'id'             => $coupon['id'],
                'code'           => $coupon['code'],
                'discount_type'  => $coupon['discount_type'],
                'discount_value' => $coupon['discount_value'],
            ];
        }

        return json(['code' => 0, 'message' => '订单创建成功', 'order_no' => $orderNo, 'coupons' => $availableCoupons]);
    }

    public function pay(Request $request)
    {
        $user = $request->user;
        $orderNo = $request->param('order_no');
        $couponId = (int)$request->param('coupon_id', 0);

        $order = Db::name('order')->where('order_no', $orderNo)->where('user_id', $user['id'])->where('type', 1)->where('status', 0)->find();

        if (!$order) {
            return json(['code' => 1, 'message' => '订单不存在']);
        }

        $plan = Db::name('plan')->where('id', $order['plan_id'])->find();
        if (!$plan) {
            return json(['code' => 1, 'message' => '套餐信息不存在']);
        }

        $originalTotal = $order['total'];
        $discountAmount = 0;

        if ($couponId > 0) {
            $coupon = Db::name('coupon')->where('id', $couponId)
                ->where('user_id', $user['id'])
                ->where('status', 0)
                ->where('start_time', '<=', date('Y-m-d'))
                ->where('end_time', '>=', date('Y-m-d'))
                ->where('min_purchase', '<=', $originalTotal)
                ->whereIn('type', ['vip', 'both'])
                ->where(function ($query) use ($order) {
                    $query->where('plan_id', 0)
                          ->whereOr('plan_id', $order['plan_id']);
                })
                ->find();

            if ($coupon) {
                if ($coupon['discount_type'] === 'fixed') {
                    $discountAmount = $coupon['discount_value'];
                } elseif ($coupon['discount_type'] === 'percent') {
                    $discountAmount = $originalTotal * (1 - $coupon['discount_value'] / 100);
                }
                Db::name('coupon')->where('id', $couponId)->update(['usage_time' => time(), 'status' => 1]);
            } else {
                return json(['code' => 1, 'message' => '优惠券无效']);
            }
        }

        $updatedTotal = floor(($originalTotal - $discountAmount) * 100) / 100;
        $updatedTotal = max(0.01, $updatedTotal);

        Db::name('order')->where('order_no', $orderNo)->update(['total' => $updatedTotal, 'coupon_id' => $couponId]);

        $payData = [];

        if (env('WECHAT_APPID') && env('WECHAT_MCH_ID') && env('WECHAT_MCH_KEY')) {
            try {
                $wechat = \WePay\Order::instance([
                    'appid'            => env('WECHAT_APPID'),
                    'mch_id'           => env('WECHAT_MCH_ID'),
                    'mch_key'          => env('WECHAT_MCH_KEY'),
                ]);
                $result = $wechat->create([
                    'body'             => $plan['name'],
                    'out_trade_no'     => $orderNo,
                    'total_fee'        => (int)($updatedTotal * 100),
                    'trade_type'       => 'NATIVE',
                    'spbill_create_ip' => $request->ip(),
                    'notify_url'       => $request->domain() . '/api/order/notify/wechat',
                ]);
                if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                    $payData['wechat'] = $result['code_url'];
                }
            } catch (\Exception $e) {
                return json(['code' => 1, 'message' => '创建微信支付失败: ' . $e->getMessage()]);
            }
        }

        if (env('ALIPAY_APPID') && env('ALIPAY_PUBLIC_KEY') && env('ALIPAY_PRIVATE_KEY')) {
            try {
                $alipay = \AliPay\Scan::instance([
                    'debug'        => false,
                    'sign_type'    => 'RSA2',
                    'appid'        => env('ALIPAY_APPID'),
                    'public_key'   => env('ALIPAY_PUBLIC_KEY'),
                    'private_key'  => env('ALIPAY_PRIVATE_KEY'),
                    'notify_url'   => $request->domain() . '/api/order/notify/alipay',
                ]);
                $result = $alipay->apply([
                    'out_trade_no' => $orderNo,
                    'total_amount' => $updatedTotal,
                    'subject'      => $plan['name'],
                ]);
                if ($result['code'] == '10000') {
                    $payData['alipay'] = $result['qr_code'];
                }
            } catch (\Throwable $e) {
                return json(['code' => 1, 'message' => '创建支付宝失败: ' . $e->getMessage()]);
            }
        }

        return json(['code' => 0, 'message' => '订单更新成功', 'total' => $updatedTotal, 'order_no' => $orderNo, 'pay' => $payData]);
    }

    public function cancel(Request $request)
    {
        Db::startTrans();

        try {
            $user = $request->user;
            $orderNo = $request->param('order_no');

            if (!$orderNo) {
                return json(['code' => 1, 'message' => '缺少订单号']);
            }

            $order = Db::name('order')->where('order_no', $orderNo)->where('user_id', $user['id'])->find();

            if (!$order) {
                return json(['code' => 1, 'message' => '订单不存在']);
            }

            if ($order['status'] !== 0) {
                return json(['code' => 1, 'message' => '订单无法取消']);
            }

            $cancelResult = Db::name('order')->where('id', $order['id'])->update(['coupon_id' => 0, 'status' => 2]);

            if (!$cancelResult) {
                throw new \Exception('订单状态更新失败');
            }

            if (isset($order['coupon_id']) && $order['coupon_id'] > 0) {
                $coupon = Db::name('coupon')->where('id', $order['coupon_id'])->where('user_id', $user['id'])->find();

                if ($coupon) {
                    $couponResult = Db::name('coupon')->where('id', $coupon['id'])->update(['status' => 0, 'usage_time' => null]);

                    if (!$couponResult) {
                        throw new \Exception('优惠券释放失败');
                    }
                }
            }

            Db::commit();

            return json(['code' => 0, 'message' => '订单取消成功']);
        } catch (\Exception $e) {
            Db::rollback();

            return json(['code' => 1, 'message' => '取消订单失败：' . $e->getMessage()]);
        }
    }

    public function wechat(Request $request)
    {
        $this->notify($request, 'wechat');
    }

    public function alipay(Request $request)
    {
        $this->notify($request, 'alipay');
    }

    protected function notify(Request $request, $type)
    {
        if ($type === 'wechat') {
            $wechat = \WePay\Order::instance([
                'appid'   => env('WECHAT_APPID'),
                'mch_id'  => env('WECHAT_MCH_ID'),
                'mch_key' => env('WECHAT_MCH_KEY'),
            ]);

            $result = $wechat->getNotify();

            $orderNo = $result['out_trade_no'];
            $transactionId = $result['transaction_id'];
            $payAmount = $result['total_fee'] / 100;
        } else {
            $alipay = \AliPay\Scan::instance([
                'debug'       => false,
                'sign_type'   => 'RSA2',
                'appid'       => env('ALIPAY_APPID'),
                'public_key'  => env('ALIPAY_PUBLIC_KEY'),
                'private_key' => env('ALIPAY_PRIVATE_KEY'),
            ]);

            $result = $alipay->notify();

            $orderNo = $result['out_trade_no'];
            $transactionId = $result['trade_no'];
            $payAmount = $result['total_amount'];
        }

        if ($orderNo) {
            $order = Db::name('order')->where('order_no', $orderNo)->find();

            if ($order && round($order['total'], 2) == round($payAmount, 2)) {
                if ($order['status'] != 1) {
                    Db::startTrans();
                    try {
                        Db::name('order')->where('id', $order['id'])->update([
                            'status'         => 1,
                            'transaction_id' => $transactionId,
                            'pay_type'       => $type,
                            'pay_time'       => time(),
                        ]);

                        $user = Db::name('user')->where('id', $order['user_id'])->find();
                        $plan = Db::name('plan')->where('id', $order['plan_id'])->find();

                        if ($user && $plan) {
                            $now = time();
                            $oldEnd = strtotime($user['end_time']);
                            $base = $oldEnd > $now ? $oldEnd : $now;
                            $newEnd = strtotime("+{$order['num']} month", $base);
                            $endDate = date('Y-m-d', $newEnd);

                            Db::name('user')->where('id', $user['id'])->update([
                                'plan_id'  => $plan['id'],
                                'end_time' => $endDate,
                            ]);
                        }

                        if ($user['invite_id'] == 0) {
                            $coupon = Db::name('coupon')->where('id', $order['coupon_id'])->find();
                            if ($coupon) {
                                Db::name('user')->where('id', $order['user_id'])->update(['invite_id' => $coupon['from_user_id']]);
                            }
                        }

                        $firstAgentId = $user['invite_id'];
                        if ($firstAgentId > 0) {
                            $firstAgent = Db::name('user')->where('id', $firstAgentId)->find();
                            if ($firstAgent) {
                                $firstRatio = $firstAgent['first_commission'] ?? env('SITE_FIRST_COMMISSION');
                                $firstCommission = round($order['total'] * (float)$firstRatio / 100, 2);
                                if ($firstCommission > 0) {
                                    Db::name('commission')->insert([
                                        'user_id' => $firstAgentId,
                                        'agent_id' => $order['user_id'],
                                        'level' => 1,
                                        'order_no' => $order['order_no'],
                                        'order_total' => $order['total'],
                                        'ratio' => $firstRatio,
                                        'amount' => $firstCommission,
                                        'create_time' => time(),
                                        'remark' => '一级大使开通会员'
                                    ]);
                                    Db::name('user')->where('id', $firstAgentId)->inc('commission_balance', $firstCommission)->update();

                                    $secondAgentId = $firstAgent['invite_id'];
                                    if ($secondAgentId > 0) {
                                        $secondAgent = Db::name('user')->where('id', $secondAgentId)->find();
                                        if ($secondAgent) {
                                            $secondRatio = $secondAgent['second_commission'] ?? env('SITE_SECOND_COMMISSION');
                                            $secondCommission = round($order['total'] * (float)$secondRatio / 100, 2);
                                            if ($secondCommission > 0) {
                                                Db::name('commission')->insert([
                                                    'user_id' => $secondAgentId,
                                                    'agent_id' => $order['user_id'],
                                                    'level' => 2,
                                                    'order_no' => $order['order_no'],
                                                    'order_total' => $order['total'],
                                                    'ratio' => $secondRatio,
                                                    'amount' => $secondCommission,
                                                    'create_time' => time(),
                                                    'remark' => '二级大使开通会员'
                                                ]);
                                                Db::name('user')->where('id', $secondAgentId)->inc('commission_balance', $secondCommission)->update();
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        Db::commit();
                    } catch (\Throwable $e) {
                        Db::rollback();
                    }
                }
            }
        }

        if ($type === 'wechat') {
            echo $wechat->getNotifySuccessReply();
        } elseif ($type === 'alipay') {
            echo 'success';
        }
    }

    public function status(Request $request)
    {
        $user = $request->user;
        $orderNo = $request->param('order_no');

        if (!$orderNo) {
            return json(['code' => 1, 'message' => '缺少订单号']);
        }

        $order = Db::name('order')->where('order_no', $orderNo)->where('user_id', $user['id'])->find();

        if (!$order) {
            return json(['code' => 1, 'message' => '订单不存在']);
        }

        return json(['code' => 0, 'message' => '查询成功', 'order_no' => $orderNo, 'status' => $order['status']]);
    }
}