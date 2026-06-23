<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2026 HuYing All rights reserved.
// +----------------------------------------------------------------------
// | Author: HuYing ( https://huying.xyz )
// +----------------------------------------------------------------------
namespace app\controller;

use think\Request;
use think\facade\Db;

class Logs
{
    public function list(Request $request)
    {
        $user    = $request->user;
        $page    = max(1, (int)$request->param('page', 1));
        $limit   = min(100, max(1, (int)$request->param('limit', 50)));
        $keyword = trim($request->param('keyword'));
        $code    = $request->param('code');
        $type    = $request->param('type');

        if ($type === '' || $code === '') {
            return json(['code' => 1, 'message' => '参数错误']);
        }

        $sourceTable = '';
        switch ($type) {
            case 'shorturl':
                $sourceTable = 'shorturl';
                break;
            case 'link':
                $sourceTable = 'link';
                break;
            default:
                return json(['code' => 1, 'message' => '渠道错误']);
        }

        $checkCode = Db::name($sourceTable)->where('user_id', $user['id'])->where('code', $code)->find();
        if (empty($checkCode)) {
            return json(['code' => 1, 'message' => '没有权限']);
        }

        $getQuery = function() use($code, $type, $keyword) {
            $query = Db::name('logs')->where('code', $code)->where('type', $type);
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('ip', "%{$keyword}%")
                      ->whereOr('device', 'like', "%{$keyword}%")
                      ->whereOr('os', 'like', "%{$keyword}%")
                      ->whereOr('browser', 'like', "%{$keyword}%");
                });
            }
            return $query;
        };

        $today_start = strtotime(date('Y-m-d'));          
        $today_end   = strtotime(date('Y-m-d 23:59:59')); 
        $yesterday_start = strtotime("-1 day", $today_start);
        $yesterday_end   = $today_start - 1;                
        $month_start = strtotime(date('Y-m-01'));          
        $month_end   = strtotime(date('Y-m-t 23:59:59'));  
        $last_month_start = strtotime(date('Y-m-01', strtotime('-1 month')));
        $last_month_end   = strtotime(date('Y-m-t 23:59:59', strtotime('-1 month')));

        $countData = [
            'today'      => $getQuery()->whereBetween('time', [$today_start, $today_end])->count(),
            'yesterday'  => $getQuery()->whereBetween('time', [$yesterday_start, $yesterday_end])->count(),
            'month'      => $getQuery()->whereBetween('time', [$month_start, $month_end])->count(),
            'last_month' => $getQuery()->whereBetween('time', [$last_month_start, $last_month_end])->count(),
            'total_all'  => $getQuery()->count(),
        ];

        $query = $getQuery();
        $total = $query->count();
        $list = $query->order('time', 'desc')->page($page, $limit)->select();

        $ip_stat_query = $getQuery();
        $ip_data = $ip_stat_query->field([
            'ip',
            'COUNT(*) AS ip_visit_total',
            'MIN(time) AS ip_first_time',
            'MAX(time) AS ip_last_time'
        ])->group('ip')->select()->toArray();
        
        $ip_map = [];
        foreach ($ip_data as $item) {
            $ip_map[$item['ip']] = [
                'ip_visit_total' => $item['ip_visit_total'],
                'ip_first_time'  => date('Y-m-d H:i:s', $item['ip_first_time']),
                'ip_last_time'   => date('Y-m-d H:i:s', $item['ip_last_time'])
            ];
        }

        $list = $list->map(function ($item) use ($ip_map) {
            $item['time'] = date('Y-m-d H:i:s', $item['time']);
            $item['ip_visit_total'] = $ip_map[$item['ip']]['ip_visit_total'] ?? 1;
            $item['ip_first_time']  = $ip_map[$item['ip']]['ip_first_time']  ?? $item['time'];
            $item['ip_last_time']   = $ip_map[$item['ip']]['ip_last_time']   ?? $item['time'];
            return $item;
        });

        return json([
            'code' => 0,
            'message' => '查询成功',
            'today'      => $countData['today'],
            'yesterday'  => $countData['yesterday'],
            'month'      => $countData['month'],
            'last_month' => $countData['last_month'],
            'total_all'  => $countData['total_all'],
            'list'       => $list,
            'total'      => $total
        ]);
    }
}