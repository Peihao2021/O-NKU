<?php

namespace weapp\SpiderVisit\behavior;

use think\Db;

/**
 * 系统行为扩展：新增/更新/删除之后的后置操作
 */
class ModuleInitBehavior {
    protected static $actionName;
    protected static $controllerName;
    protected static $moduleName;
    protected static $method;
    protected static $spiderTypes;

    /**
     * 构造方法
     * @param Request $request Request对象
     * @access public
     */
    public function __construct()
    {

    }

    // 行为扩展的执行入口必须是run
    public function run(&$params){
        self::$actionName = request()->action();
        self::$controllerName = request()->controller();
        self::$moduleName = request()->module();
        self::$method = request()->method();
        self::$spiderTypes = [
            1   => '谷歌',
            2   => '百度',
            3   => '搜狗',
            4   => '360',
            5   => 'Yandex',
            6   => '微软bing',
            99   => '其他',
        ];

        $this->spiderVisit();
    }

    /**
     * FeedDemon 内容采集
     * BOT/0.1 (BOT for JCE) sql注入
     * CrawlDaddy sql注入
     * Java 内容采集
     * Jullo 内容采集
     * Feedly 内容采集
     * UniversalFeedParser 内容采集
     * ApacheBench cc攻击器
     * Swiftbot 无用爬虫
     * YandexBot 无用爬虫
     * AhrefsBot 无用爬虫
     * YisouSpider 无用爬虫（已被UC神马搜索收购，此蜘蛛可以放开！）
     * MJ12bot 无用爬虫
     * ZmEu phpmyadmin 漏洞扫描
     * WinHttp 采集cc攻击
     * EasouSpider 无用爬虫
     * HttpClient tcp攻击
     * Microsoft URL Control 扫描
     * YYSpider 无用爬虫
     * jaunty wordpress爆破扫描器
     * oBot 无用爬虫
     * Python-urllib 内容采集
     * Indy Library 扫描
     * FlightDeckReports Bot 无用爬虫
     * Linguee Bot 无用爬虫
     * @return [type] [description]
     */
    private function spiderVisit() {
        if (in_array(self::$moduleName, ['admin','api','user'])) {
            return true;
        }

        //判断插件是否启用
        $weappInfo = Db::name('weapp')->where(['code'=>'SpiderVisit'])->find();
        if (!empty($weappInfo['status'])) {

            /*初始化配置信息*/
            $dataInfo = !empty($weappInfo['data']) ? json_decode($weappInfo['data'], true) : [];
            if (!isset($dataInfo['maxnum']) || !isset($dataInfo['spiders'])) {
                $maxnum = Db::name('weapp_spider_visit')->count();
                if ($maxnum < 5000) {
                    $maxnum = 5000;
                }
                $dataInfo = [
                    'maxnum'    => $maxnum,
                    'spiders'    => array_keys(self::$spiderTypes),
                ];
                Db::name('weapp')->where(['code'=>'SpiderVisit'])->update([
                        'data'  => json_encode($dataInfo),
                        'update_time'   => getTime(),
                    ]);
            }
            /*end*/

            $spider = 0;
            $useragent = addslashes(strtolower($_SERVER['HTTP_USER_AGENT']));

            /*将恶意USER_AGENT存入数组*/
            $now_useragent = ['FeedDemon ','BOT/0.1 (BOT for JCE)','CrawlDaddy ','Java','Feedly','UniversalFeedParser','ApacheBench','Swiftbot','ZmEu','Indy Library','oBot','jaunty','YandexBot','AhrefsBot','MJ12bot','WinHttp','EasouSpider','HttpClient','Microsoft URL Control','YYSpider','jaunty','Python-urllib','lightDeckReports Bot'];

            if(empty($useragent)) {
                abort(404);
                // header("Content-type: text/html; charset=utf-8");
                // die('请勿采集本站，因为采集的站长木有小JJ！');
                return true;
            }else{
                foreach ($now_useragent as $val) {
                    //判断是否是数组中存在的UA
                    if (stristr($useragent, $val)) {
                        abort(404);
                        // header("Content-type: text/html; charset=utf-8");
                        // die('请勿采集本站，因为采集的站长木有小JJ！');
                        return true;
                    }
                }
            }
            /*end*/

            $request_url = $_SERVER["REQUEST_URI"];

            if (stripos($useragent, 'googlebot') !== false || stripos($useragent,'mediapartners-google') !== false) {
                $spider = 1; // Google
            } elseif (stripos($useragent,'baiduspider') !== false) {
                $spider = 2; // Baidu
            } elseif (stripos($useragent,'sogou spider') !== false || stripos($useragent,'sogou web') !== false) {
                $spider = 3; // Sougou
            } elseif (stripos($useragent,'360spider') !== false) {
                $spider = 4; // 360
            } elseif (stripos($useragent,'yandexbot') !== false) {
                $spider = 5; // Yandex
            } elseif (stripos($useragent,'bingbot') !== false) {
                $spider = 6; // 微软 bing
            } elseif (stripos($useragent,'bot') !== false) {
                $spider = 99; // 其他
            }

            if (!empty($spider) && strlen($_SERVER["REMOTE_ADDR"]) < 20 && strlen($request_url) < 200) {
                if ($this->check_valid_url($request_url)) {
                    abort(404);
                } else {
                    if (isset($dataInfo['spiders']) && !in_array($spider, $dataInfo['spiders'])) {
                        return true;
                    }
                }

                $saveData = [
                    'spider'    => $spider,
                    'useragent' => $useragent,
                    'url'       => htmlspecialchars($request_url),
                    'ip'        => htmlspecialchars($_SERVER["REMOTE_ADDR"]),
                    'add_time'  => getTime(),
                ];
                $r = Db::name('weapp_spider_visit')->save($saveData);
                if ($r !== false) {
                    // 最近30天
                    $mtime = strtotime("-30 day");
                    $month_total = Db::name('weapp_spider_visit')->where([
                            'spider'    => $spider,
                            'add_time'  => ['egt', $mtime],
                        ])->count();
                    // 上一周
                    $beginPreweek = mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
                    $endPreweek = mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));
                    $pre_week_total = Db::name('weapp_spider_visit')->where([
                            'spider'    => $spider,
                            'add_time'  => ['between', [$beginPreweek, $endPreweek]],
                        ])->count();
                    // 本周
                    $beginweek = mktime(0,0,0,date('m'),date('d')-date('w')+1,date('Y'));
                    $endweek = mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));
                    $week_total = Db::name('weapp_spider_visit')->where([
                            'spider'    => $spider,
                            'add_time'  => ['between', [$beginweek, $endweek]],
                        ])->count();
                    // 昨天
                    $beginYesterday = mktime(0,0,0,date('m'),date('d')-1,date('Y'));
                    $endYesterday = mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
                    $pre_day_total = Db::name('weapp_spider_visit')->where([
                            'spider'    => $spider,
                            'add_time'  => ['between', [$beginYesterday, $endYesterday]],
                        ])->count();
                    // 今天
                    $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
                    $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
                    $day_total = Db::name('weapp_spider_visit')->where([
                            'spider'    => $spider,
                            'add_time'  => ['between', [$beginToday, $endToday]],
                        ])->count();

                    /*蜘蛛抓取统计*/
                    $row = Db::name('weapp_spider_tongji')->where(['spider'=>$spider])->find();
                    if (empty($row)) {
                        $addData = [
                            'spider'    => $spider,
                            'total'     => 1,
                            'month'     => $month_total,
                            'pre_week'  => $pre_week_total,
                            'week'      => $week_total,
                            'pre_day'   => $pre_day_total,
                            'day'       => $day_total,
                            'add_time'  => getTime(),
                            'update_time'   => getTime(),
                        ];
                        Db::name('weapp_spider_tongji')->insert($addData);
                    } else {
                        $updateData = [
                            'total'         => Db::raw('total + 1'),
                            'month'         => $month_total,
                            'pre_week'      => $pre_week_total,
                            'week'          => $week_total,
                            'pre_day'       => $pre_day_total,
                            'day'           => $day_total,
                            'update_time'   => getTime(),
                        ];
                        Db::name('weapp_spider_tongji')->where(['spider'=>$spider])->update($updateData);
                    }
                    /*end*/

                    // 只保留最近30天内指定条数的爬取记录
                    $mtime = strtotime("-30 day");
                    $maxnum = isset($dataInfo['maxnum']) ? intval($dataInfo['maxnum']) : 5000;
                    if (empty($maxnum)) {
                        Db::name('weapp_spider_visit')->where([
                                'id'  => ['>', 0],
                            ])->delete();
                    } else {
                        $subQuery = Db::name('weapp_spider_visit')
                            ->field('id')
                            ->order('id desc')
                            ->limit($maxnum)
                            ->buildSql();
                        $min_id = Db::table($subQuery.' a')->min('id');
                        $min_id = intval($min_id);
                        Db::name('weapp_spider_visit')->where([
                                'id'  => ['<', $min_id],
                            ])->whereOr([
                                'add_time'  => ['<', $mtime],
                            ])->delete();
                    }
                }
            }
        }
    }

    /**
     * 验证是否在过滤的URL规则中
     * @return [type] [description]
     */
    private function check_valid_url($request_url = '')
    {
        $is_valid = false;

        $no_valid_url = [
            'm=api&c=Ajax&a=',
            'm=api&c=Diyajax&a=',
            'm=plugins&c=WeixinShare&a=',
            '&uiset=([a-z]+)&v=([a-z]+)',
        ];

        foreach ($no_valid_url as $key => $rule) {
            if (preg_match('/'.$rule.'/i', $request_url)) {
                $is_valid = true;
                break;
            }
        }

        return $is_valid;
    }
}
