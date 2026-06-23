<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;

class Points
{
    public function list(Request $request)
    {
        $user    = $request->user;
        $page    = max(1, (int)$request->param('page', 1));
        $limit   = min(100, max(1, (int)$request->param('limit', 50)));
        $keyword = trim($request->param('keyword'));

        $query = Db::name('points')->where('user_id', $user['id']);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('type', "%{$keyword}%")
                  ->whereOr('remark', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();
        $list  = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();

        foreach ($list as &$item) {
            switch ($item['type']) {
                case 1: $item['type_text'] = '充值'; break;
                case 2: $item['type_text'] = '消耗'; break;
                case 3: $item['type_text'] = '管理'; break;
                default: $item['type_text'] = '未知';
            }
            $item['create_time'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : null;
        }

        return json(['code' => 0, 'message' => '查询成功', 'list' => $list, 'total' => $total]);
    }

    public function create(Request $request)
    {
        $user = $request->user;
        $num  = (int)$request->param('num');

        if ($num <= 0) {
            return json(['code' => 1, 'msg' => '请输入正确的数量']);
        }

        $pricePer = env('SITE_POINTS');
        $total    = $pricePer * $num;
        $orderNo  = date('YmdHis') . random_int(1000, 9999);

        Db::name('order')->insertGetId([
            'order_no'    => $orderNo,
            'user_id'     => $user['id'],
            'type'        => 2,
            'plan_id'     => 0,
            'num'         => $num,
            'price'       => $pricePer,
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
            ->whereIn('type', ['points', 'both'])
            ->select();

        $availableCoupons = [];
        foreach ($coupons as $coupon) {
            $availableCoupons[] = [
                'id'             => $coupon['id'],
                'code'           => $coupon['code'],
                'discount_type'  => $coupon['discount_type'],
                'discount_value' => $coupon['discount_value']
            ];
        }

        return json(['code' => 0, 'message' => '订单创建成功', 'order_no' => $orderNo, 'coupons' => $availableCoupons]);
    }

    public function pay(Request $request)
    {
        $user = $request->user;
        $orderNo = $request->param('order_no');
        $couponId = (int)$request->param('coupon_id', 0);

        $order = Db::name('order')->where('order_no', $orderNo)->where('user_id', $user['id'])->where('type', 2)->where('status', 0)->find();

        if (!$order) {
            return json(['code' => 1, 'message' => '订单不存在']);
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
                ->whereIn('type', ['points', 'both'])
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
                    'body'             => '积分充值',
                    'out_trade_no'     => $orderNo,
                    'total_fee'        => (int)($updatedTotal * 100),
                    'trade_type'       => 'NATIVE',
                    'spbill_create_ip' => $request->ip(),
                    'notify_url'       => $request->domain() . '/api/points/notify/wechat',
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
                    'notify_url'   => $request->domain() . '/api/points/notify/alipay',
                ]);
                $result = $alipay->apply([
                    'out_trade_no' => $orderNo,
                    'total_amount' => $updatedTotal,
                    'subject'      => '积分充值',
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
                        $before_points = $user['points'];
                        $after_points  = $before_points + $order['num'];

                        Db::name('points')->insert(['user_id' => $order['user_id'], 'points_change' => $order['num'], 'type' => 1, 'remark' => '积分充值', 'before_points' => $before_points, 'after_points' => $after_points, 'create_time' => time()]);
                        Db::name('user')->where('id', $order['user_id'])->update(['points' => $after_points]);

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
                                        'remark' => '一级大使充值积分'
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
                                                    'remark' => '二级大使充值积分'
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
}