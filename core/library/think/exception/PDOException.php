<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://zjzit.cn>
// +----------------------------------------------------------------------

namespace think\exception;

use think\Config;

/**
 * PDO异常处理类
 * 重新封装了系统的\PDOException类
 */
class PDOException extends DbException
{
    /**
     * PDOException constructor.
     * @param \PDOException $exception
     * @param array         $config
     * @param string        $sql
     * @param int           $code
     */
    public function __construct(\PDOException $exception, array $config, $sql, $code = 10501)
    {
        $error = $exception->errorInfo;
        $code0 = $exception->getCode();
        $code1 = isset($error[1]) ? $error[1] : 0;
        $code2 = isset($error[2]) ? $error[2] : '';

        $this->setData('PDO Error Info', [
            'SQLSTATE'             => $code0,
            'Driver Error Code'    => $code1,
            'Driver Error Message' => $code2,
        ]);

        /*提高错误提示的友好性 by 小虎哥*/
        $errcode = "{$code0}:{$code1}";
        if (stristr($code2, "Incorrect string value:")) {
            if (stristr($code2, "for column '")) {
                $errcode = "HY000:-{$code1}";
            }
        }
        $mysqlcode = Config::get('error_code.mysql');
        $eyou_message = "";
        if (!empty($mysqlcode[$errcode])) {
            $code = 'eyou';
            $eyou_message = $mysqlcode[$errcode];
        }
        /*--end*/

        $message = $exception->getMessage();
        try {
            $message = iconv('GB2312', 'UTF-8', $message); // 转化编码 by 小虎哥
        } catch (\Exception $e) {
            if (function_exists('mb_convert_encoding')) {
                $message = mb_convert_encoding($message, "UTF-8", "GBK");
            }
        }
        $message = $eyou_message."\n\n[错误代码]\n".$message;

        parent::__construct($message, $config, $sql, $code);
    }
}
