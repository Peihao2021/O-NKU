<?php

namespace weapp\Linkkeyword\behavior\home;

use weapp\Linkkeyword\model\LinkkeywordModel;
use think\Db;

class LinkkeywordBehavior
{
    protected static $actionName;
    protected static $controllerName;
    protected static $moduleName;
    protected static $method;

    /**
     * 构造方法
     *
     * @param Request $request Request对象
     *
     * @access public
     */
    public function __construct()
    {
        ! isset(self::$moduleName) && self::$moduleName = request()->module();
        ! isset(self::$controllerName) && self::$controllerName = request()->controller();
        ! isset(self::$actionName) && self::$actionName = request()->action();
        ! isset(self::$method) && self::$method = strtoupper(request()->method());
    }

    /**
     * 模块初始化
     *
     * @param array $params 传入参数
     *
     * @access public
     */
    public function moduleInit(&$params)
    {

    }

    /**
     * 操作开始执行
     *
     * @param array $params 传入参数
     *
     * @access public
     */
    public function actionBegin(&$params)
    {

    }

    /**
     * 视图内容过滤
     *
     * @param array $params 传入参数
     *
     * @access public
     */
    public function viewFilter(&$params)
    {
        if (!stristr($params, '</head>')) {
            return true;
        }
        /*只有相应的内容详情页的控制器和操作名才执行，以便提高性能*/
        $ctlActArr = array(
            'Article@view',//article_content表
            'Product@view',//product_content表
            'Images@view',//images_content表
            'Download@view',//download_content表
            'Single@lists',//single_content表
            'Lists@index',//single_content表
            'View@index',//article_content表product_content表images_content表download_content表
            'Buildhtml@*',// 支持生成静态HTML
        );
        $ctlActStr = self::$controllerName.'@'.self::$actionName;
        $ctlAllStr = self::$controllerName.'@*';
        if (in_array($ctlActStr, $ctlActArr) || in_array($ctlAllStr, $ctlActArr)) {

            //判断插件是否启用
            $weappOpen = Db::name('weapp')->where(array(
                'code'   => LinkkeywordModel::WEAPP_CODE,
                'status' => LinkkeywordModel::WEAPP_STATUS_ENABLE,
            ))->value('id');
            if ($weappOpen > 0) {
                $aid = request()->param('aid', 0);
                $tid = request()->param('tid', 0);
                $content = '';
                $title = '';
                try {
                    if (0 < $aid) {
                        //有aid需要判断内容频道类型，获取内容的频道类型
                        $archivesInfo = Db::name('archives')->field('channel,title,seo_description')->where('aid', $aid)->find();
                        if (!empty($archivesInfo['channel'])) {
                            $title = $archivesInfo['title'];
                            //获取该频道内容的表名
                            $table = Db::name('channeltype')->where([
                                'id'     => $archivesInfo['channel'],
                                'status' => LinkkeywordModel::WEAPP_STATUS_ENABLE,
                            ])->value('table');
                            //拼接内容详情的表名
                            $contentModel = $table.'_content';
                            //获取内容详情
                            $content = Db::name($contentModel)->where('aid', $aid)->value('content');
                        }
                    } else {
                        if (strval(intval($tid)) != strval($tid)) {
                            $map = array('dirname'=>$tid);
                        } else {
                            $map = array('id'=>$tid);
                        }
                        $arctypeInfo = M('arctype')->field('id,typename')->where($map)->find();
                        if (!empty($arctypeInfo)) {
                            $title = $arctypeInfo['typename'];
                            //没有aid的是单篇内容，单篇内容详情表名是single_content
                            $contentModel = 'single_content';
                            //获取内容详情
                            $content = Db::name($contentModel)->where('typeid', $arctypeInfo['id'])->value('content');
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
                $content = handle_subdir_pic($content, 'html');
                $session_key = md5("weapp_archives_content");

                if (false === stripos($params, $content)) {
                    $content_cache = cache($session_key);
                    !empty($content_cache) && $content = $content_cache;
                }
                /*end*/

                /*追加指定内嵌样式到编辑器内容的img标签，兼容图片自动适应页面*/
                $titleNew = !empty($archivesInfo['title']) ? $archivesInfo['title'] : '';
                $content = img_style_wh($content, $titleNew);
                $content = handle_subdir_pic($content, 'html');
                /*--end*/
                $contentNew = $content;
                preg_match_all('/<img.*(\/)?>/iUs', $contentNew, $imginfo);
                $imginfo = $imginfo[0];
                if ($imginfo) {
                    foreach ($imginfo as $key => $value) {
                        $imgmd5 = md5($value);
                        $contentNew = str_ireplace($value, $imgmd5, $contentNew);
                    }
                }
                //查询关键字和链接
                $linkkeywordModel = new LinkkeywordModel;
                $linkKeyword      = $linkkeywordModel->getList();
                $contentNew = $linkkeywordModel->link_substr_replace($contentNew,$linkKeyword);
                //图片替换回原来
                if ($imginfo) {
                    foreach ($imginfo as $key => $value) {
                        $imgmd5 = md5($value);
                        $contentNew = str_ireplace($imgmd5, $value, $contentNew);
                    }
                }


                //替换内容详情
                $params = str_ireplace($content, $contentNew, $params);
                /*处理标题与内容一样的情况下*/
                if ($title == $content || strstr($title, $content)) {
                    $title_tmp = str_replace('/', '\/', $contentNew);
                    $params = preg_replace('/<title>'.$title_tmp.'(.*)<\/title>/i', '<title>'.$title.'${1}</title>', $params);
                    $title2 = str_replace('"', '', $title);
                    $params = preg_replace('/(title|alt|value)=[\'|\"]'.$title_tmp.'[\'|\"]/', '${1}="'.$title2.'"', $params);
                }
                if (!empty($archivesInfo['seo_description']) && ($archivesInfo['seo_description'] == $content || strstr($archivesInfo['seo_description'], $content))) {
                    $params = preg_replace('/<meta(\s+)(.*)name=[\'|\"]description[\'|\"](\s+)(.*)content=(.*)\/?>/', '<meta ${2} name="description" content="'.$archivesInfo['seo_description'].'" />', $params);
                    $params = preg_replace('/<meta(\s+)(.*)content=(.*)(\s+)name=[\'|\"]description[\'|\"](.*)\/?>/', '<meta ${2} name="description" content="'.$archivesInfo['seo_description'].'" />', $params);
                }
                /*end*/

                // 处理每个插件获取的内容应当是处理过的内容
                cache($session_key, $contentNew);
            }
        }
    }


    /**
     * 应用结束
     *
     * @param array $params 传入参数
     *
     * @access public
     */
    public function appEnd(&$params)
    {

    }
}