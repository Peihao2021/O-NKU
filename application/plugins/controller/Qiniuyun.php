<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 小虎哥 <1105415366@qq.com>
 * Date: 2018-4-3
 */

namespace app\plugins\controller;

use think\Db;

class Qiniuyun extends Base
{
    /**
     * 构造方法
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取七牛云token
     */
    public function qiniu_upload()
    {
        if (IS_AJAX_POST) {
            $weappInfo     = Db::name('weapp')->where('code','Qiniuyun')->field('id,status,data')->find();
            if (empty($weappInfo)) {
                $this->error('请先安装配置【七牛云图片加速】插件!', null, ['code'=>-1]);
            } else if (1 != $weappInfo['status']) {
                $this->error('请先启用【七牛云图片加速】插件!', null, ['code'=>-2,'id'=>$weappInfo['id']]);
            } else {
                $Qiniuyun = json_decode($weappInfo['data'], true);
                if (empty($Qiniuyun)) {
                    $this->error('请先配置【七牛云图片加速】插件!', null, ['code'=>-3]);
                } else if (empty($Qiniuyun['domain'])) {
                    $this->error('请先配置【七牛云图片加速】插件中的域名!', null, ['code'=>-3]);
                }
            }

            //引入七牛云的相关文件
            weapp_vendor('Qiniu.src.Qiniu.Auth', 'Qiniuyun');
            weapp_vendor('Qiniu.src.Qiniu.Storage.UploadManager', 'Qiniuyun');
            require_once ROOT_PATH.'weapp/Qiniuyun/vendor/Qiniu/autoload.php';

            // 配置信息
            $accessKey = $Qiniuyun['access_key'];
            $secretKey = $Qiniuyun['secret_key'];
            $bucket    = $Qiniuyun['bucket'];
            if (2 == $Qiniuyun['tcp']) {
                $tcp = 'https://';
            } else {
                $tcp = 'http://';
            }
            $domain    = $tcp.$Qiniuyun['domain'];

            // 区域对应的上传URl
            $config = new \Qiniu\Config(null);
            $uphost  = $config->getUpHost($accessKey, $bucket);
            $uphost = str_replace('http://', '//', $uphost);

            // 生成上传Token
            $auth = new \Qiniu\Auth($accessKey, $secretKey);
            $token = $auth->uploadToken($bucket);
            if ($token) {
                $down = input('post.down/d');
                if (!empty($down)){
                    $filePath = UPLOAD_PATH.'soft/';
                }else{
                    $filePath = UPLOAD_PATH.'media/' . date('Ymd/') . session('admin_id') . '-' . dd2char(date("ymdHis") . mt_rand(100, 999));
                }
                $data = [
                    'token'  => $token,
                    'domain'  => $domain,
                    'uphost'  => $uphost,
                    'filePath'  => $filePath,
                ];
                $this->success('获取token成功!', null, $data);
            } else {
                $this->error('获取token失败!');
            }
        }

    }

    //删除
    public function deleteQny($filenames='') {
        $weappInfo     = Db::name('weapp')->where('code','Qiniuyun')->field('id,status,data')->find();
        if (empty($weappInfo)) {
            $this->error('请先安装配置【七牛云图片加速】插件!', null, ['code'=>-1]);
        } else {
            $Qiniuyun = json_decode($weappInfo['data'], true);
            if (empty($Qiniuyun)) {
                $this->error('请先配置【七牛云图片加速】插件!', null, ['code'=>-3]);
            } else if (empty($Qiniuyun['domain'])) {
                $this->error('请先配置【七牛云图片加速】插件中的域名!', null, ['code'=>-3]);
            }
        }
        if (2 == $Qiniuyun['tcp']) {
            $tcp = 'https://';
        } else {
            $tcp = 'http://';
        }
        $domain    = $tcp.$Qiniuyun['domain'].'/';
        $filenames = str_replace($domain,'',$filenames);
        //引入七牛云的相关文件
        weapp_vendor('Qiniu.src.Qiniu.Auth', 'Qiniuyun');
        weapp_vendor('Qiniu.src.Qiniu.Storage.UploadManager', 'Qiniuyun');
        require_once ROOT_PATH.'weapp/Qiniuyun/vendor/Qiniu/autoload.php';

        // 配置信息
        $access_key = $Qiniuyun['access_key'];
        $secret_key = $Qiniuyun['secret_key'];
        $bucket    = $Qiniuyun['bucket'];

        $auth = new \Qiniu\Auth($access_key, $secret_key);
        $config = new \Qiniu\Config();
        $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);
        //删除图片的名称
        $key = $filenames;
        $err = $bucketManager->delete($bucket, $key);
        $this->error($err);
    }
}