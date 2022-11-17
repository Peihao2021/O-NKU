<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 陈风任 <491085389@qq.com>
 * Date: 2022-03-10
 */

namespace app\api\model\v1;

use think\Db;
use think\Cache;
use Grafika\Color;
use Grafika\Grafika;
require_once './vendor/grafika/src/autoloader.php';

/**
 * 微信小程序商品海报模型
 */
load_trait('controller/Jump');

class Poster extends Base
{
    use \traits\controller\Jump;

    //初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();

        $this->aid = 0;
        $this->typeid = 0;
        $this->channel = 1;
        $this->Product = [];
        $this->PostData = [];
        $this->PosterPath = '';
        $this->PosterImage = '';
        $this->AppletsQrcode = [];
    }

    // 商品海报生成处理
    public function GetCreateGoodsShareQrcodePoster($Post = [], $channel = 1)
    {
        // 商品ID
        $this->aid = $Post['aid'];
        // 商品栏目ID
        $this->typeid = $Post['typeid'];
        // 模型ID
        $this->channel = $channel;
        // 图片、海报保存目录
        $this->PosterPath = UPLOAD_PATH . 'tmp/poster_' . $this->typeid . '_' . $this->aid . '/';
        // 背景图片处理
        if (1 == $this->channel) {
            $this->PosterImage = './public/static/common/images/article-bg.png';
        } else if (2 == $this->channel) {
            $this->PosterImage = './public/static/common/images/product-bg.png';
        }
        // 获取商品信息
        $this->Product = $this->GetProductData();

        // 生成小程序二维码需携带参数
        $page = 'pages/archives/product/view';
        $scene = 'aid=' . $this->aid . '&typeid=' . $this->typeid;
        $width = '430';
        $this->PostData = compact('page', 'scene', 'width');

        // 小程序二维码处理
        $this->AppletsQrcode = $this->GetAppletsQrcode();
        // 组合并返回商品分享海报图片
        return $this->GetProductSharePosterImage();
    }

    // 返回已处理的商品信息
    private function GetProductData()
    {
        // 查询商品信息
        $where['aid'] = $this->aid;
        $field = 'aid, title, litpic, users_price, seo_description';
        $Product = Db::name("archives")->where($where)->field($field)->find();

        if (!empty($Product)) {
            // 商品图片处理
            $ProductLitpic = $this->get_default_pic($Product['litpic'], true);
            // 保存图片的完整路径
            $LitpicSavePath = $this->PosterPath . 'product_' . md5($this->aid . $this->typeid) . '.png';
            // 若文件夹不存在则创建
            !is_dir($this->PosterPath) && tp_mkdir($this->PosterPath);

            // 图片保存到文件处理
            $ch = curl_init($ProductLitpic);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($ch,CURLOPT_BINARYTRANSFER,true);
            // https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);   //重要,源文件链接带https的话就必须使用
            curl_setopt($ch,CURLOPT_TIMEOUT,60);

            $img = curl_exec($ch);
            curl_close($ch);
            $fp = fopen($LitpicSavePath, 'w');
            fwrite($fp, $img);
            fclose($fp);
            // 返回数据
            $Product['litpic'] = $LitpicSavePath;

            return $Product;
        } else {
            $this->error('商品不存在');
        }
    }

    // 返回已处理的小程序二维码
    private function GetAppletsQrcode()
    {
        // 保存图片的完整路径
        $QrcodeSavePath = $this->PosterPath . 'qrcode_' . md5($this->aid . $this->typeid) . '.png';

        // 若文件夹不存在则创建
        !is_dir($this->PosterPath) && tp_mkdir($this->PosterPath);
        
        // 是否配置小程序信息
        $AppletsSetting = $this->GetWeChatAppletsSetting();
        if (empty($AppletsSetting['status'])) $this->error($AppletsSetting['prompt']);

        // 调用微信接口查询小程序AccessToken
        $AppletsToken = $this->GetWeChatAppletsAccessToken($AppletsSetting['values']);
        if (empty($AppletsToken['status'])) $this->error($AppletsToken['prompt']);
        // 调用微信接口获取小程序二维码
        return $this->GetWeChatAppletsQrcode($AppletsToken['token'], $QrcodeSavePath);
    }

    // 返回微信小程序配置信息
    private function GetWeChatAppletsSetting()
    {
        $inc = tpSetting("OpenMinicode.conf_weixin", [], self::$lang);
        $inc = json_decode($inc, true);

        // 处理返回小程序配置
        if (!empty($inc['appid'])) {
            $AppletsSetting = [
                'status' => true,
                'values' => $inc,
            ];
        } else {
            $AppletsSetting = [
                'status' => false,
                'prompt' => '请先配置并发布小程序再生成分销商二维码',
            ];
        }

        return $AppletsSetting;
    }

    // 返回微信access_token
    private function GetWeChatAppletsAccessToken($AppletsSetting = [])
    {
        $time = getTime();
        $ExecuteFetch = 1;
        $appid = $AppletsSetting['appid'];
        $appsecret = $AppletsSetting['appsecret'];
        $WechatToken = tpSetting('wechat');
        if (!empty($WechatToken['wechat_token_value']) && !empty($WechatToken['wechat_token_time'])) {
            $ExecuteFetch = 0;
            if ($time > ($WechatToken['wechat_token_time'] + 7000)) {
                $ExecuteFetch = 1;
            } else {
                $data = [
                    'status' => true,
                    'token'  => $WechatToken['wechat_token_value'],
                ];
            }
        }

        if (!empty($ExecuteFetch)) {
            // 接口限制10万次/天
            $get_token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$appsecret;
            $TokenData = json_decode(httpRequest($get_token_url), true);
            if (!empty($TokenData['access_token'])) {
                // 存入缓存配置
                $WechatToken  = [
                    'wechat_token_value' => $TokenData['access_token'],
                    'wechat_token_time'  => $time,
                ];
                tpSetting('wechat', $WechatToken);
                $data = [
                    'status' => true,
                    'token'  => $WechatToken['wechat_token_value'],
                ];
            } else {
                $data = [
                    'status' => false,
                    'prompt' => '错误提示：WeChatApplets，请检查小程序AppId和AppSecret是否正确！',
                ];
            }
        }
        
        return $data;
    }

    // 返回微信小程序商品详情页二维码
    private function GetWeChatAppletsQrcode($AccessToken = null, $QrcodeSavePath = null)
    {
        // 获取微信小程序二维码
        $PostUrl = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $AccessToken;
        $AppletsQrcode = httpRequest($PostUrl, 'POST', json_encode($this->PostData, JSON_UNESCAPED_UNICODE));

        // 保存图片，保存成功则返回图片路径
        if (@file_put_contents($QrcodeSavePath, $AppletsQrcode)) {
            $data = [
                'status' => true,
                'qrcode' => $QrcodeSavePath,
            ];
        } else {
            $data = [
                'status' => false,
                'qrcode' => '获取二维码失败，请重试',
            ];
        }
        return $data;
    }

    // 返回商品分享海报图片
    private function GetProductSharePosterImage()
    {
        $Grafika = new Grafika;
        $editor = $Grafika::createEditor(['Gd']);
        // 打开海报背景图
        $editor->open($backdropImage, $this->PosterImage);
        // 打开商品图片
        $editor->open($ProductLitpic, $this->Product['litpic']);
        // 重设商品图片宽高
        $editor->resizeExact($ProductLitpic, 690, 690);
        // 商品图片添加到背景图
        $editor->blend($backdropImage, $ProductLitpic, 'normal', 1.0, 'top-left', 30, 30);

        // 字体文件路径
        $fontPath = Grafika::fontsDir() . '/' . 'st-heiti-light.ttc';
        // 商品名称处理换行
        $fontSize = 30;
        $ProductName = $this->wrapText($fontSize, 0, $fontPath, $this->Product['title'], 680, 2);
        // 写入商品名称
        $editor->text($backdropImage, $ProductName, $fontSize, 30, 750, new Color('#333333'), $fontPath);

        // 写入商品价格
        if (1 == $this->channel) {
            // 字体文件路径
            $fontPath = Grafika::fontsDir() . '/' . 'st-heiti-light.ttc';
            // 文档描述处理换行
            $fontSize = 20;
            $SeoDescription = $this->wrapText($fontSize, 0, $fontPath, $this->Product['seo_description'], 500, 4);
            // 写入文档描述
            $editor->text($backdropImage, $SeoDescription, $fontSize, 30, 920, new Color('#333333'), $fontPath);
        } else if (2 == $this->channel) {
            $editor->text($backdropImage, $this->Product['users_price'], 38, 62, 964, new Color('#ff4444'));
        }

        // 打开小程序码
        $editor->open($QrcodeImage, $this->AppletsQrcode['qrcode']);
        // 重设小程序码宽高
        $editor->resizeExact($QrcodeImage, 140, 140);
        // 小程序码添加到背景图
        $editor->blend($backdropImage, $QrcodeImage, 'normal', 1.0, 'top-left', 570, 914);

        // 保存商品海报
        $PosterImageName = 'product_poster_' . md5($this->aid . $this->typeid) . '.png';
        $PosterImagePath = $this->PosterPath . $PosterImageName;
        $editor->save($backdropImage, $PosterImagePath);

        // 返回商品海报
        $PosterImagePath = request()->domain() . ROOT_DIR . '/' . $PosterImagePath;
        return [
            'name' => $PosterImageName,
            'path' => $this->PosterPath,
            'poster' => $PosterImagePath
        ];
    }

    // 处理文字超出长度自动换行
    private function wrapText($fontsize, $angle, $fontface, $string, $width, $max_line = null)
    {
        // 这几个变量分别是 字体大小, 角度, 字体名称, 字符串, 预设宽度
        $content = "";
        // 将字符串拆分成一个个单字 保存到数组 letter 中
        $letter = [];
        for ($i = 0; $i < mb_strlen($string, 'UTF-8'); $i++) {
            $letter[] = mb_substr($string, $i, 1, 'UTF-8');
        }
        $line_count = 0;
        foreach ($letter as $l) {
            $testbox = imagettfbbox($fontsize, $angle, $fontface, $content . ' ' . $l);
            // 判断拼接后的字符串是否超过预设的宽度
            if (($testbox[2] > $width) && ($content !== "")) {
                $line_count++;
                if ($max_line && $line_count >= $max_line) {
                    $content = mb_substr($content, 0, -1, 'UTF-8') . "...";
                    break;
                }
                $content .= "\n";
            }
            $content .= $l;
        }
        return $content;
    }
}