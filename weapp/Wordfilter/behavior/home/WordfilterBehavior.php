<?php

namespace weapp\Wordfilter\behavior\home;
use think\Db;
use weapp\Wordfilter\model\WordfilterModel;

/**
 * 行为扩展
 */
class WordfilterBehavior
{
    protected static $actionName;
    protected static $controllerName;
    protected static $moduleName;
    protected static $method;
    protected static $ctlActArr = [];

    /**
     * 构造方法
     * @param Request $request Request对象
     * @access public
     */
    public function __construct()
    {
        !isset(self::$moduleName) && self::$moduleName = request()->module();
        !isset(self::$controllerName) && self::$controllerName = request()->controller();
        !isset(self::$actionName) && self::$actionName = request()->action();
        !isset(self::$method) && self::$method = strtoupper(request()->method());

        /*只有相应的内容详情页的控制器和操作名才执行，以便提高性能*/
        self::$ctlActArr = array(
            'Article@view',//article_content表
            'Product@view',//product_content表
            'Images@view',//images_content表
            'Download@view',//download_content表
            'Single@lists',//single_content表
            'Lists@index',//single_content表
            'View@index',//article_content表product_content表images_content表download_content表
            'Buildhtml@*',// 支持生成静态HTML
       );
    }

    /**
     * 模块初始化
     * @param array $params 传入参数
     * @access public
     */
    public function moduleInit(&$params)
    {

    }

    /**
     * 操作开始执行
     * @param array $params 传入参数
     * @access public
     */
    public function actionBegin(&$params)
    {

    }


    /**
     * 视图内容过滤
     * @param array $params 传入参数
     * @access public
     */
    public function viewFilter(&$params)
    {
        if (!stristr($params, '</head>')) {
            return true;
        }

        $ctlActStr = self::$controllerName.'@'.self::$actionName;
        $ctlAllStr = self::$controllerName.'@*';
        if (in_array($ctlActStr, self::$ctlActArr) || in_array($ctlAllStr, self::$ctlActArr)) {
            //判断插件是否启用
            $weappOpen = Db::name('weapp')->where(array(
                'code'   => WordfilterModel::WEAPP_CODE,
                'status' => WordfilterModel::WEAPP_STATUS_ENABLE,
            ))->value('id');
            if ($weappOpen > 0) {
                //查询关键字和链接
                $wordfilterModel = new WordfilterModel;
                $linkKeyword      = $wordfilterModel->getList();
                if (empty($linkKeyword)) {
                    return true;
                }

                $aid = request()->param('aid', 0);
                $tid = request()->param('tid', 0);
                $content = '';
                try {
                    if (0 < $aid) {
                        //有aid需要判断内容频道类型，获取内容的频道类型
                        $channel = Db::name('archives')->where('aid', $aid)->value('channel');
                        if (0 < intval($channel)) {
                            //获取该频道内容的表名
                            $table = Db::name('channeltype')->where([
                                'id' => $channel,
                                'status' => WordfilterModel::WEAPP_STATUS_ENABLE,
                            ])->value('table');
                            //拼接内容详情的表名
                            $contentModel = $table . '_content';
                            //获取内容详情
                            $content = Db::name($contentModel)->where('aid', $aid)->value('content');
                        }
                    } else {
                        if (strval(intval($tid)) != strval($tid)) {
                            $map = array('dirname' => $tid);
                        } else {
                            $map = array('id' => $tid);
                        }
                        $tid = M('arctype')->where($map)->getField('id');
                        if (!empty($tid)) {
                            //没有aid的是单篇内容，单篇内容详情表名是single_content
                            $contentModel = 'single_content';
                            //获取内容详情
                            $content = Db::name($contentModel)->where('typeid', $tid)->value('content');
                        }
                    }
                } catch (\Exception $e) {}

                if (empty($content)) {
                    return true;
                }

                //内容详情解码
                $content    = htmlspecialchars_decode($content);

                /*处理每个插件获取的内容应当是处理过的内容 - 【插件涉及到内容替换必备代码】*/
                // 追加指定内嵌样式到编辑器内容的img标签，兼容图片自动适应页面
                $titleNew = !empty($archivesInfo['title']) ? $archivesInfo['title'] : '';
                $content = img_style_wh($content, $titleNew);
                $session_key = md5('weapp_archives_content');
                if (false === stripos($params, $content)) {
                    $content_cache = cache($session_key);
                    !empty($content_cache) && $content = $content_cache;
                }
                /*end*/

                $contentNew = $this->contentHandler($linkKeyword,$content);
                //替换内容详情
                $params = str_ireplace($content, $contentNew, $params);
                // 处理每个插件获取的内容应当是处理过的内容
                cache($session_key, $contentNew);
            }
        }
    }

    /**
     * 应用结束
     * @param array $params 传入参数
     * @access public
     */
    public function appEnd(&$params)
    {

    }

    private function printStar($length)
    {
        $str = '';
        for($i=0; $i<$length; $i++)
        {
            $str.='*';
        }
        return $str;
    }

    private function contentHandler($linkKeyword, $contentNew)
    {
        for($i=0; $i<count($linkKeyword); $i++)
        {
            $len = strlen(trim($linkKeyword[$i])) == 3 ? 3 : strlen(trim($linkKeyword[$i])) / 3;
            $starLength[$i] = $len;
            $star[$i] = $this->printStar($starLength[$i]);
        }
        //处理内容详情
        $word = array_combine($linkKeyword,$star);
        $str = strtr($contentNew, $word);
        return $str;
    }
}