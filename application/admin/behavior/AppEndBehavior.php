<?php

namespace app\admin\behavior;

/**
 * 系统行为扩展：
 */
class AppEndBehavior {
    protected static $actionName;
    protected static $controllerName;
    protected static $moduleName;
    protected static $method;

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
        // file_put_contents ( DATA_PATH."log.txt", date ( "Y-m-d H:i:s" ) . "  " . var_export('admin_CoreProgramBehavior',true) . "\r\n", FILE_APPEND );
        $this->_initialize();
    }

    private function _initialize() {
        $this->resetAuthor(); // 临时处理授权问题
        $this->clearHtmlCache(); // 变动数据之后，清除页面缓存和数据
    }

    /**
     * 临时处理授权问题
     */
    private function resetAuthor()
    {
        /*在以下相应的控制器和操作名里执行，以便提高性能*/
        $ctlActArr = array(
            'Index@index',
        );
        $ctlActStr = self::$controllerName.'@'.self::$actionName;
        if (in_array($ctlActStr, $ctlActArr) && 'GET' == self::$method) {
            if(!empty($_SESSION['isset_resetAuthor']))
                return true;
            $_SESSION['isset_resetAuthor'] = 1;

            session('isset_author', null);
        }
        /*--end*/
    }

    /**
     * 数据变动之后，清理页面和数据缓存
     */
    private function clearHtmlCache()
    {
        /*在以下相应的控制器和操作名里执行，以便提高性能*/
        $actArr = ['add','edit','del','recovery','changeTableVal'];
        if ('POST' == self::$method) {
            foreach ($actArr as $key => $val) {
                if (preg_match('/^((.*)_)?('.$val.')$/i', self::$actionName) || preg_match('/^(ajax_)?'.$val.'(_(.*))?$/i', self::$actionName)) {
                    \think\Cache::clear();
                    delFile(HTML_ROOT);
                    break;
                }
            }
        }
    }
}
