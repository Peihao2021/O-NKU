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

namespace app\api\model\v1;

use think\Db;

/**
 * 小程序模型类
 */
class Api extends Base
{
    //初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();
    }

    /**
     * 文档详情
     * @param int $aid 文档ID
     */
    public function getArchivesView($aid = '', $users = [])
    {
        $aid = intval($aid);
        $args = [$aid, $users];
        $cacheKey = 'api-'.md5(__CLASS__.__FUNCTION__.json_encode($args));
        $result = cache($cacheKey);
        if (true || empty($result)) {
            $detail = $this->getViewInfo($aid, $users);
            if (!empty($detail['detail'])) {
                if (0 <= $detail['detail']['arcrank']) { // 待审核稿件
                    $detail['detail']['title'] = htmlspecialchars_decode($detail['detail']['title']);
                    $add_time = $detail['detail']['add_time'];
                    $detail['detail']['add_time_format'] = $this->time_format($add_time);
                    $detail['detail']['add_time'] = date('Y-m-d H:i:s', $add_time); // 格式化发布时间，兼容早期开源小程序
                    $detail['detail']['content'] = $this->html_httpimgurl($detail['detail']['content'], true); // 转换内容图片为http路径

                    !empty($detail['product']) && $result['product'] = $detail['product']; // 推荐商品
                    !empty($detail['coupon_list']) && $result['coupon_list'] = $detail['coupon_list']; // 优惠券列表
                } else {
                    $detail['detail'] = [
                        'arcrank'   => $detail['detail']['arcrank'],
                    ];
                }
            }

            $result['detail']['data'] = !empty($detail['detail']) ? $detail['detail'] : false;

            cache($cacheKey, $result, null, 'archives');
        }

        if (!empty($result['detail']['data'])) {
            // 浏览量
            Db::name('archives')->where(['aid'=>$aid])->setInc('click'); 
            $result['detail']['data']['click'] += 1;
        }

        return $result;
    }

    /**
     * 获取单条文档记录
     * @author wengxianhu by 2017-7-26
     */
    private function getViewInfo($aid, $users = [])
    {
        $result = [];
        $detail = Db::name('archives')
            ->alias('a')
            ->field('a.aid,a.typeid,a.channel,a.title,a.litpic,a.author,a.click,a.arcrank,a.seo_title,a.seo_keywords,a.seo_description,a.users_price,a.users_free,a.old_price,
            a.sales_num,a.stock_show,a.stock_count,a.prom_type,a.arc_level_id,a.downcount,a.add_time,a.attrlist_id,b.typename')
            ->join('arctype b','a.typeid = b.id','left')
            ->where([
                'a.aid'       => $aid,
                'a.status'    => 1,
                'a.is_del'    => 0,
            ])
            ->find();
        if (!empty($detail)) {
            // 模型标题处理
            $channeltype_row = \think\Cache::get('extra_global_channeltype');
            $channeltypeInfo = !empty($channeltype_row[$detail['channel']]) ? $channeltype_row[$detail['channel']] : [];
            $detail['channel_ntitle'] = !empty($channeltypeInfo['ntitle']) ? $channeltypeInfo['ntitle'] : '文章';
            $detail['seo_title'] = $this->set_arcseotitle($detail['title'], $detail['seo_title']); // seo标题
            $detail['litpic'] = $this->get_default_pic($detail['litpic']); // 默认封面图
            // $detail['forward'] = Db::name('users_forward')->where('aid',$aid)->count(); // 转发记录，废弃了，有需要就通过请求传参进行判断是否要查表返回数据，或者写成标签apiForward
            $detail['content'] = '';
            if (1 == $detail['channel']) { // 文章模型
                unset($detail['sales_num']);
                unset($detail['stock_show']);
                unset($detail['stock_count']);
                unset($detail['prom_type']);
                unset($detail['downcount']);
            }
            else if (2 == $detail['channel']) { // 产品模型
                unset($detail['users_free']);
                unset($detail['downcount']);

                /*产品参数*/
                if (!empty($detail['attrlist_id'])){ // 新版参数
                    $productAttrModel = new \app\home\model\ProductAttr;
                    $attr_list = $productAttrModel->getProAttrNew($aid, 'a.attr_id,a.attr_name,b.attr_value,b.aid');
                }else{ // 旧版参数
                    $productAttrModel = new \app\home\model\ProductAttr;
                    $attr_list = $productAttrModel->getProAttr($aid);
                }
                $attr_list = !empty($attr_list[$aid]) ? $attr_list[$aid] : [];
                foreach ($attr_list as $key => $val) {
                    $val['attr_value'] = htmlspecialchars_decode($val['attr_value']);
                    unset($val['aid']);
                    $attr_list[$key] = $val;
                }
                $detail['attr_list'] = !empty($attr_list) ? $attr_list : false;

                /*规格数据*/
                $detail['spec_attr'] = $this->getSpecAttr($aid, $users);
                /* END */

                // 产品相册
                $productImgModel = new \app\home\model\ProductImg;
                $image_list = $productImgModel->getProImg($aid, 'aid,image_url,intro');
                $image_list = !empty($image_list[$aid]) ? $image_list[$aid] : [];
                foreach ($image_list as $key => $val) {
                    $val['image_url'] = $this->get_default_pic($val['image_url']);
                    isset($val['intro']) && $val['intro'] = htmlspecialchars_decode($val['intro']);
                    $image_list[$key] = $val;
                }
                $detail['image_list'] = !empty($image_list) ? $image_list : false;

                /*可控制的主表字段列表*/
                $detail['ifcontrolRow'] = Db::name('channelfield')->field('id,name')->where([
                        'channel_id'    => $detail['channel'],
                        'ifmain'        => 1,
                        'ifeditable'    => 1,
                        'ifcontrol'     => 0,
                        'status'        => 1,
                    ])->getAllWithIndex('name');

                // 设置默认原价
                $detail['old_price'] = $detail['users_price'];
                $detail['product_num'] = 1;
                $detail['spec_value_id'] = '';

                $result['product'] = $this->getRecomProduct();
                $result['coupon_list'] = $this->getCoupon($detail,$users);
                if ('v1.5.1' < getVersion()) {
                    //总评论数
                    $detail['comment_data_count'] = Db::name('shop_order_comment')->where(['product_id'=>$aid,'is_show'=>1])->count();
                    $good_count = Db::name('shop_order_comment')->where(['product_id'=>$aid,'is_show'=>1,'total_score'=>1])->count();
                    //好评率
                    $detail['comment_good_per'] = !empty($detail['comment_data_count']) ? round($good_count/$detail['comment_data_count'],2) : 0;
                    if ($detail['comment_data_count'] > 0){
                        $detail['comment_data'] = Db::name('shop_order_comment')
                            ->alias('a')
                            ->field('a.*,b.nickname,b.head_pic')
                            ->join('users b','a.users_id = b.users_id','left')
                            ->where(['a.product_id'=>$aid,'a.is_show'=>1])
                            ->order('a.total_score asc')
                            ->limit(2)
                            ->select();
                        foreach ($detail['comment_data'] as $k => $v){
                            if (1 == $v['is_anonymous']){
                                $v['nickname'] = '匿名用户';
                            }
                            $v['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
                            $v['content'] = unserialize($v['content']);
                            $v['head_pic'] = get_default_pic($v['head_pic'],true);
                            $detail['comment_data'][$k] = $v;
                        }
                    }
                }

                $detail['cart_total_num'] = 0;
                if (!empty($users['users_id'])){
                    //购物车数量
                    $detail['cart_total_num'] = Db::name('shop_cart')->where(['users_id' => $users['users_id']])->sum('product_num');
                }
            }
            else if (3 == $detail['channel']) { // 图集模型
                unset($detail['users_price']);
                unset($detail['users_free']);
                unset($detail['old_price']);
                unset($detail['sales_num']);
                unset($detail['stock_show']);
                unset($detail['stock_count']);
                unset($detail['prom_type']);
                unset($detail['downcount']);
                // 图集相册
                $imagesUploadModel = new \app\home\model\ImagesUpload;
                $image_list = $imagesUploadModel->getImgUpload($aid, 'aid,image_url,intro');
                $image_list = !empty($image_list[$aid]) ? $image_list[$aid] : [];
                foreach ($image_list as $key => $val) {
                    $val['image_url'] = $this->get_default_pic($val['image_url']);
                    isset($val['intro']) && $val['intro'] = htmlspecialchars_decode($val['intro']);
                    $image_list[$key] = $val;
                }
                $detail['image_list'] = !empty($image_list) ? $image_list : false;
            }
            else if (4 == $detail['channel']) { // 下载模型
                unset($detail['users_price']);
                unset($detail['users_free']);
                unset($detail['old_price']);
                unset($detail['sales_num']);
                unset($detail['stock_show']);
                unset($detail['stock_count']);
                unset($detail['prom_type']);
                if (0 == $detail['arc_level_id']) {
                    $downloadFileModel = new \app\home\model\DownloadFile;
                    $down_list = $downloadFileModel->getDownFile($aid);
                    $down_list = !empty($down_list[$aid]) ? $down_list[$aid] : [];
                    foreach ($down_list as $key => $val) {
                        $val['file_url'] = handle_subdir_pic($val['file_url']);
                        $down_list[$key] = $val;
                    }
                } else {
                    $down_list = false;
                }
                $detail['down_list'] = !empty($down_list) ? $down_list : false;
            }
            else if (5 == $detail['channel']) { // 视频模型
                unset($detail['sales_num']);
                unset($detail['stock_show']);
                unset($detail['stock_count']);
                unset($detail['prom_type']);
                unset($detail['downcount']);
                // 视频附件列表
                $mediaFileModel = new \app\home\model\MediaFile;
                $media_list = $mediaFileModel->getMediaFile($aid);
                foreach ($media_list as $key => $val) {
                    $val['file_url'] = handle_subdir_pic($val['file_url'], 'media');
                    $media_list[$key] = $val;
                }
                $detail['media_list'] = !empty($media_list) ? $media_list : false;
            }
            else {
                unset($detail['users_price']);
                unset($detail['users_free']);
                unset($detail['old_price']);
                unset($detail['sales_num']);
                unset($detail['stock_show']);
                unset($detail['stock_count']);
                unset($detail['prom_type']);
                unset($detail['downcount']);
            }

            // 获取自定义字段的数据
            $customField = [];
            $addtableName = $channeltypeInfo['table'].'_content';
            $detailRow = Db::name($addtableName)->field('id,aid,add_time,update_time', true)->where('aid', $aid)->find();
            if (!empty($detailRow)) {
                $fieldLogic = new \app\home\logic\FieldLogic();
                $detailExt = $fieldLogic->getChannelFieldList($detailRow, $detail['channel']); // 自定义字段的数据格式处理
                if (empty($channeltypeInfo['ifsystem'])) { // 自定义模型
                    // 如果存在自定义字段content，默认作为文档的正文内容。
                    // 如果不存在，将获取第一个html类型的内容，作为文档的正文内容。
                    if (!isset($detailExt['content'])) {
                        $contentField = Db::name('channelfield')->where([
                                'channel_id'    => $detail['channel'],
                                'dtype'         => 'htmltext',
                            ])->getField('name');
                        $detailExt['content'] = !empty($detailExt[$contentField]) ? $detailExt[$contentField] : '';
                    }
                }
                $detail['content'] = $detailExt['content'];
                unset($detailExt['content']);

                // 手机端详情内容
                if (isset($detailExt['content_ey_m'])) {
                    $detail['content'] = empty($detailExt['content_ey_m']) ? $detail['content'] : $detailExt['content_ey_m'];
                    unset($detailExt['content_ey_m']);
                }

                if (!empty($detailExt)) {
                    $field = 'name, title, dtype';
                    $customField = Db::name('channelfield')->field($field)->where([
                        'name' => ['IN', array_keys($detailExt)],
                        'channel_id' => $detail['channel'],
                        'ifeditable' => 1
                    ])->getAllWithIndex('name');
                    if (!empty($customField)) {
                        foreach ($customField as $key => $value) {
                            if ('img' == $value['dtype']) {
                                $customField[$key]['value'] = $this->get_default_pic($detailExt[$key]);
                            } else if ('media' == $value['dtype']) {
                                $customField[$key]['value'] = $this->get_default_pic($detailExt[$key]);
                            } else if ('imgs' == $value['dtype']) {
                                foreach ($detailExt[$key] as $kk => $vv) {
                                    $detailExt[$key][$kk]['image_url'] = $this->get_default_pic($vv['image_url']);
                                }
                                $customField[$key]['value'] = $detailExt[$key];
                            } else {
                                $customField[$key]['value'] = $detailExt[$key];
                            }
                        }
                    }
                }
                $customField = array_values($customField);  
            }
            $detail['customField'] = !empty($customField) ? $customField : false;

            $result['detail'] = $detail;
        }

        return $result;
    }

    /**
     * 留言栏目表单
     * @param int $typeid 栏目ID
     */
    public function getGuestbookForm($typeid)
    {
        $typeid = intval($typeid);
        if (empty($typeid)) {
            $typeid = Db::name('arctype')->where([
                'current_channel'   => 8,
                'is_del' => 0,
                'status'    => 1,
                'lang'  => parent::$lang,
            ])->getField('id');
        }

        $attr_list = array();
        $typename = '';
        if (0 < $typeid) {
            $detail = Db::name('arctype')->field('id,id as typeid,typename,seo_title,seo_keywords,seo_description')->where([
                'id'    => $typeid,
                'lang'  => parent::$lang,
            ])->find();
            $detail['seo_title'] = $this->set_arcseotitle($detail['typename'], $detail['seo_title']);
            $attr_list = Db::name('GuestbookAttribute')->field('attr_id,attr_name,attr_input_type,attr_values')
                ->where([
                    'typeid'    => $typeid,
                    'is_del'    => 0,
                ])
                ->order('sort_order asc, attr_id asc')
                ->select();
            foreach ($attr_list as $key => $val) {
                if (in_array($val['attr_input_type'], array(1,3,4))) {
                    $val['attr_values'] = explode(PHP_EOL, $val['attr_values']);
                    $attr_list[$key] = $val;
                }
            }
        }

        /*表单令牌*/
        $token_name = md5('guestbookform_token_'.$typeid.md5(getTime().uniqid(mt_rand(), TRUE)));
        $token_value = md5($_SERVER['REQUEST_TIME_FLOAT']);
        $session_path = \think\Config::get('session.path');
        $session_file = ROOT_PATH . $session_path . "/sess_".$token_name;
        $fp = fopen($session_file, "w+");
        if (!empty($fp)) {
            if (fwrite($fp, $token_value)) {
                fclose($fp);
            }
        } else {
            file_put_contents ( $session_file,  $token_value);
        }
        /*end*/

        $result = array(
            'detail'    => $detail,
            'attr_list' => $attr_list,
            'token' => [
                'name'  => '__token__'.$token_name,
                'value' => $token_value,
            ],
        );

        return $result;
    }

    /**
     *  给指定留言添加表单值到 guestbook_attr
     * @param int $aid 留言id
     * @param int $typeid 留言栏目id
     */
    public function saveGuestbookAttr($post, $aid, $typeid)
    {
        $attrArr = [];
        foreach ($post as $k => $v) {
            if (!strstr($k, 'attr_')) {
                continue;
            }

            $attr_id = str_replace('attr_', '', $k);
            is_array($v) && $v = implode(PHP_EOL, $v);
            $v       = trim($v);
            $adddata = array(
                'aid'         => $aid,
                'attr_id'     => $attr_id,
                'attr_value'  => $v,
                'lang'        => parent::$lang,
                'add_time'    => getTime(),
                'update_time' => getTime(),
            );
            Db::name('GuestbookAttr')->add($adddata);
        }
    }

    // 获取指定商品的规格数据
    public function getSpecAttr($aid = null, $users = [],$order2 = '')
    {
        $ReturnData = $SpecData = $SpecValue = $SelectSpecData = [];
        if (empty($aid)) return $ReturnData;
        if (empty($users['level_discount'])) $users['level_discount'] = 100;
        // 会员折扣率
        $SelectSpecData['users_discount'] = (intval($users['level_discount']) / 100);
        
        $SpecWhere = [
            'aid'  => $aid,
            'lang' => get_home_lang(),
            'spec_is_select' => 1,
        ];
        $order = 'spec_value_id asc, spec_id asc';
        $field = '*, false checked';
        $ProductSpecData = Db::name('product_spec_data')->field($field)->where($SpecWhere)->order($order)->select();
        if (!empty($ProductSpecData)) {
            $ProductSpecData = group_same_key($ProductSpecData, 'spec_mark_id');
            foreach ($ProductSpecData as $key => $value) {
//                $value[0]['checked'] = true;
                $SpecData[] = [
                    'spec_value_id' => $value[0]['spec_value_id'],
                    'spec_mark_id'  => $value[0]['spec_mark_id'],
                    'spec_name'     => $value[0]['spec_name'],
                    'spec_data_new' => $value,
                ];
            }

            unset($SpecWhere['spec_is_select']);
            $order2 = !empty($order2) ? $order2 : 'seckill_price asc,spec_price asc';
            $ProductSpecValue = Db::name('product_spec_value')->where($SpecWhere)->order($order2)->select();

            if (!empty($ProductSpecValue)) {
                // 默认的规格值，取价格最低者
                $SelectSpecData['spec_value_id'] = explode('_', $ProductSpecValue[0]['spec_value_id']);

                // 若存在规格并且价格存在则覆盖原有价格
                $SelectSpecData['users_price'] = sprintf("%.2f", strval($ProductSpecValue[0]['spec_price']) * strval($SelectSpecData['users_discount']));

                // 产品原价
                $SelectSpecData['old_price'] = $ProductSpecValue[0]['spec_price'];

                // 若存在规格并且库存存在则覆盖原有库存
                $SelectSpecData['stock_count'] = $ProductSpecValue[0]['spec_stock'];

                // 价格及库存
                $SpecValue = $ProductSpecValue;
            }
            foreach ($SpecData as $key => $value) {
                foreach ($value['spec_data_new'] as $kk => $vv) {
                    // 追加默认规格class
                    if (in_array($vv['spec_value_id'], $SelectSpecData['spec_value_id'])) {
                        $SpecData[$key]['spec_data_new'][$kk]['checked'] = true;
                    }
                }
            }
        }

        $ReturnData = [
            'spec_data' => $SpecData,
            'spec_value' => $SpecValue,
            'select_spec_data' => $SelectSpecData
        ];

        return $ReturnData;
    }

    /**
     * 购物车/用户中心/商品详情获取 可能你还想要
     */
    public function getRecomProduct($type = 1){
        // 查询条件
        $where = [
            'channel'   => 2,
            'arcrank' => ['>', -1],
            'lang'    => self::$lang,
            'status'  => 1,
            'is_del'  => 0,
        ];
        // 数据排序
        if (1 == $type){
            //最新
            $order = [
                'aid' => 'desc',
            ];
        }elseif (2 == $type){
            //推荐
            $where['is_recom'] = 1;
            $order = [
                'sort_order' => 'asc',
                'aid' => 'desc',
            ];
        }elseif (3 == $type){
            //最热
            $order = [
                'click' => 'desc',
                'aid' => 'desc',
            ];
        }else {
            $order = [
                'sort_order' => 'asc',
                'aid' => 'desc',
            ];
        }

        // 分页处理
        $limit ='0,6';

        // 查询数据
        $ArchivesData = Db::name('archives')->where($where)->order($order)->limit($limit)->select();
        if (!empty($ArchivesData)) {
            foreach ($ArchivesData as $key => $value) {
                $ArchivesData[$key]['litpic'] = $this->get_default_pic($value['litpic'], true); // 默认封面图
            }
        } else {
            $ArchivesData = [];
        }

        return [
            'title' => '可能你还想要',
            'list'  => $ArchivesData,
        ];
    }

    //获取当前商品可用优惠券
    public function getCoupon($arcData = [],$users = [])
    {
        // 筛选条件
        $filter = [
            'status' => 1
        ];
        $filter['start_date'] = ['<=',getTime()];
        $filter['end_date'] = ['>=',getTime()];
        //先查出顶级栏目id
        $arctypeTopId = Db::name('arctype')->where('id',$arcData['typeid'])->value('topid');
        if (empty($arctypeTopId)) $arctypeTopId = $arcData['typeid'];

        $result = Db::name('shop_coupon')
            ->where($filter)
            ->where("coupon_type = '1' OR FIND_IN_SET('{$arctypeTopId}', arctype_id) OR FIND_IN_SET('{$arcData['aid']}', product_id)")
            ->order('sort_order asc,coupon_id desc')
            ->select();
        $coupon_ids = [];
        if (!empty($result)) {
            foreach ($result as $k => $v) {
                $coupon_ids[] = $v['coupon_id'];
                $v['coupon_form_name'] = '满减券';
                switch ($v['use_type']) {
                    case '1': // 固定期限有效
                        $v['start_date'] = date('Y/m/d', $v['start_date']);
                        $v['end_date'] = date('Y/m/d', $v['end_date']);
                        $v['use_type_name'] = $v['start_date'] . '-' . $v['end_date'];
                        break;
                    case '2'; // 领取当天开始有效
                        $v['use_type_name'] = '领取' . $v['valid_days'] . '天内有效';
                        break;
                    case '3'; // 领取次日开始有效
                        $v['use_type_name'] = '领取次日开始' . $v['valid_days'] . '天内有效';
                        break;
                }
                switch ($v['coupon_type']) {
                    case '1': // 未使用
                        $v['coupon_type_name'] = '全部商品';
                        break;
                    case '2'; // 已使用
                        $v['coupon_type_name'] = '指定商品';
                        break;
                    case '3'; // 已过期
                        $v['coupon_type_name'] = '指定分类';
                        break;
                }
                $result[$k] = $v;
            }
        }
        if (!empty($users)){
            $have_where['users_id'] = $users['users_id'];
            if (!empty($coupon_ids)) {
                $have_where['coupon_id'] = ['in',$coupon_ids];
            }
            $have_where['use_status'] = 0;
            $have = Db::name('shop_coupon_use')->where($have_where)->column('coupon_id');
            if (!empty($have)){
                foreach ($result as $k => $v ) {
                    if (in_array($v['coupon_id'],$have)){
                        $result[$k]['geted'] = 1;//当前还有已领取未使用的
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 获取秒杀商品详情
     */
    public function GetSharpGoods($aid = 0){
        $users = [];
        $aid = intval($aid);
        $args = [$aid, $users];
        $cacheKey = 'api-'.md5(__CLASS__.__FUNCTION__.json_encode($args));
        $result = cache($cacheKey);
        if (true || empty($result)) {
            $status = 0;
            $msg = 'OK';
            $detail = array();
            if (0 < $aid) {
                $detail = $this->getSharpInfo($aid, $users);
                if (!empty($detail)) {
                    if (0 == $detail['arcrank']) {
                        $status = 1;
                    } else if (0 > $detail['arcrank']) {
                        $msg = '文档审核中，无权查看！';
                    } else if (0 < $detail['arcrank']) {
                        $msg = '当前是游客，无权查看！';
                    }

                    $detail['add_time'] = date('Y-m-d H:i:s', $detail['add_time']); // 格式化发布时间
                    $detail['update_time'] = date('Y-m-d H:i:s', $detail['update_time']); // 格式化更新时间
                    $detail['content_1586404997'] = $this->html_httpimgurl($detail['content_1586404997'], true); // 转换内容图片为http路径
                    $detail['seo_description'] = '';


                } else {
                    $msg = '文档已删除！';
                }
            }

            $result = [
                'conf' => [
                    'status' => $status,
                    'msg'   => $msg,
                ],
                'detail' => !empty($detail) ? $detail : [],
            ];

            cache($cacheKey, $result, null, 'archives');
        }

        return $result;
    }

    /**
     * 获取秒杀商品记录
     * @author wengxianhu by 2017-7-26
     */
    private function getSharpInfo($aid,$users = [])
    {
        $where = [
            'a.status'    => 1,
            'a.is_del'    => 0,
            'a.aid'    => $aid,
        ];
        $result = array();
        $row = Db::name('sharp_goods')
            ->alias('a')
            ->field('a.sharp_goods_id,a.limit,a.seckill_stock,a.seckill_price,a.sales,a.virtual_sales,a.is_sku,b.sales_actual,c.*')
            ->where($where)
            ->join('sharp_active_goods b','a.aid = b.aid','left')
            ->join('archives c','a.aid = c.aid','left')
            ->find();
        $channeltype_row = \think\Cache::get('extra_global_channeltype');
        if (!empty($row)) {
            if (0 < $row['virtual_sales']){
                $row['sales_actual'] = $row['sales_actual']+$row['virtual_sales'];
            }
            $row['seo_title'] = $this->set_arcseotitle($row['title'], $row['seo_title']);
            /*封面图*/
            if (empty($row['litpic'])) {
                $row['is_litpic'] = 0; // 无封面图
            } else {
                $row['is_litpic'] = 1; // 有封面图
            }
            $row['litpic'] = $this->get_default_pic($row['litpic'], true); // 默认封面图

            // 模型处理
            $channeltypeInfo = !empty($channeltype_row[$row['channel']]) ? $channeltype_row[$row['channel']] : [];

            /*产品参数*/
            if (!empty($row['attrlist_id'])){
                $productAttrModel = new \app\home\model\ProductAttr();
                $attr_list = $productAttrModel->getProAttrNew($aid);
            }else{
                $productAttrModel = new \app\home\model\ProductAttr();
                $attr_list = $productAttrModel->getProAttr($aid);
            }
            $attr_list = !empty($attr_list[$aid]) ? $attr_list[$aid] : [];
            foreach ($attr_list as $key => $val) {
                $attr_list[$key]['attr_value'] = htmlspecialchars_decode($val['attr_value']);
            }
            $row['attr_list'] = $attr_list;

            /*规格数据*/
            $row['spec_attr'] = $this->getSpecAttr($aid, $users);
            /* END */

            // 产品相册
            $image_list = [];
            $productImgModel = new \app\home\model\ProductImg();
            $image_list_tmp = $productImgModel->getProImg($aid);
            if (!empty($image_list_tmp[$aid])) {
                foreach ($image_list_tmp[$aid] as $key => $val) {
                    $val['image_url'] = $this->get_default_pic($val['image_url'], true);
                    $image_list[$key] = $val;
                }
            }
            $row['image_list'] = $image_list;

            /*可控制的主表字段列表*/
            $row['ifcontrolRow_1586404997'] = Db::name('channelfield')->field('id,name')->where([
                'channel_id'    => $row['channel'],
                'ifmain'        => 1,
                'ifeditable'    => 1,
                'ifcontrol'     => 0,
                'status'        => 1,
            ])->getAllWithIndex('name');

            // 设置默认原价
            $row['old_price'] = $row['users_price'];
            $row['product_num'] = 1;
            $row['spec_value_id'] = '';

            // 获取自定义字段的数据
            $class = '\app\home\model\\'.$channeltypeInfo['ctl_name'];
            $model = new $class();
            $extFields = Db::name($channeltypeInfo['table'].'_content')->getTableFields();
            $extFields = array_flip($extFields);
            unset($extFields['id']);
            $rowExt = $model->getInfo($aid);
            $rowExt_new = [];
            foreach ($extFields as $key => $val) {
                $rowExt_new[$key] = $rowExt[$key];
            }
            $rowExt = array_diff_key($rowExt_new, $row);

            if (!empty($rowExt)) {
                $fieldLogic = new \app\home\logic\FieldLogic();
                $rowExt = $fieldLogic->getChannelFieldList($rowExt, $row['channel']); // 自定义字段的数据格式处理
            }
            /*--end*/

            // 浏览量
            Db::name('archives')->where(['aid'=>$aid])->setInc('click');
            $row['click'] = $row['click'] + 1;

            $result = array_merge($rowExt, $row);

            if (!empty($channeltypeInfo['ifsystem']) && 1 == $channeltypeInfo['ifsystem']) {
                $content = $result['content'];
                unset($result['content']);
            } else {
                /*获取第一个html类型的内容，作为文档的内容*/
                $contentField = Db::name('channelfield')->where([
                    'channel_id'    => $row['channel'],
                    'dtype'         => 'htmltext',
                ])->getField('name');
                $content = !empty($rowExt[$contentField]) ? $rowExt[$contentField] : '';
                /*--end*/
            }
            $result['content_1586404997'] = $content;
        }

        return $result;
    }

    /**
     * 获取某个场次的秒杀信息
     * @param $item
     */
    public function getSharp($active_time_id=0,$aid=0)
    {
        //获取当时时间的整点时间戳
        $time = strtotime(date('Y-m-d'));
        $hour = date('H');
        $where['a.status'] = 1;
        $where['a.active_time_id'] = $active_time_id;
        $where['b.is_del'] = 0;
        $where['b.status'] = 1;
        //获取秒杀场次信息
        $active = Db::name('sharp_active_time')
            ->alias('a')
            ->where($where)
            ->field('a.active_time_id,a.active_id,a.active_time,b.active_date,c.sales_actual')
            ->join('sharp_active b','a.active_id = b.active_id')
            ->join('sharp_active_goods c','a.active_time_id = c.active_time_id')
            ->find();
        //正在进行时
        if ($active['active_date'] == $time && $active['active_time']  ==  $hour){
            $active['status'] = 10;
        } else if (($active['active_date'] == $time && $active['active_time']  >  $hour) || $active['active_date'] > $time){
            //预告
            $active['status'] = 20;
        }else if (($active['active_date'] == $time && $active['active_time']  <  $hour) || $active['active_date'] < $time){
            //过期
            $active['status'] = 30;
        }
        $date = date('Y-m-d'); //2020-12-15
        if ($active['active_time'] < 10){
            $active['active_time'] = '0'.$active['active_time'].':00';
        }else{
            $active['active_time'] = $active['active_time'].':00'; // 00:00
        }
        $data_time = $date.' '.$active['active_time']; //2020-15-2-15 15:00
        $time_plus = strtotime($data_time . "+1 hours"); //2020-15-2-15 15:00
        $time_plus = date('Y-m-d H:i',$time_plus); //2020-15-2-15 16:00
        if ( 10 == $active['status'] ){
            $arr = [
                $time_plus,$time_plus,$data_time];
        }else if ( 20 == $active['status'] ){
            $arr = [$data_time,$time_plus,$data_time];
        }else if ( 30 == $active['status'] ){
            $arr = [false,$time_plus,$data_time];
        }
        $active['count_down_time'] = $arr[0];
        $active['end_time'] = $arr[1];
        $active['start_time'] = $arr[2];

        return $active;
    }

    /**
     * 获取优惠券数量
     */
    public function getCouponCount($user)
    {
        if (false == $user){
            return 0;
        }else{
            $where['users_id'] = $user['users_id'];
            $where['use_status'] =  0;
            $count = Db::name('shop_coupon_use')->where($where)->count();
            return $count;
        }
    }

    /**
     * 获取评论列表
     */
    public function getGoodsCommentList($param = [] )
    {
        $page =  empty($param['page']) ? 1: $param['page'];
        $type =  empty($param['type']) ? 'all': $param['type'];
        $pagesize =  empty($param['pagesize']) ? config('paginate.list_rows'): $param['pagesize'];
        $total_score =  empty($param['total_score']) ? '': $param['total_score'];
        $goods_id = $param['aid'];
        $field='a.*,u.nickname,u.head_pic';
        // 筛选条件
        $condition = [
            'a.product_id' => $goods_id,
            'a.is_show' => 1,
        ];
        if (!empty($param['total_score'])) $condition['a.total_score'] = $param['total_score'];
        if ('img' == $type) $condition['a.upload_img'] = ['neq',''];

        $args = [$goods_id,$page,$pagesize,$total_score];
        $cacheKey = 'api-'.md5(__CLASS__.__FUNCTION__.json_encode($args));
        if (true || empty($result)) {
            $paginate = array(
                'page'  => $page,
            );
            $pages = Db::name('shop_order_comment')
                ->field($field)
                ->alias('a')
                ->join('users u','a.users_id = u.users_id','left')
                ->where($condition)
                ->order('a.add_time desc')
                ->paginate($pagesize, false, $paginate);
            $result = $pages->toArray();

            foreach ($result['data'] as $key => $val) {
                $val['head_pic'] = get_default_pic($val['head_pic'],true);
                if (isset($val['add_time'])) {
                    $val['add_time'] = date('Y-m-d H:i:s', $val['add_time']);
                }
                if (!empty($val['upload_img'])){
                    $val['upload_img'] = unserialize($val['upload_img']);
                    $val['upload_img'] = explode(',',$val['upload_img']);
                    foreach ($val['upload_img'] as $k => $v){
                        $val['upload_img'][$k] = get_default_pic($v,true);
                    }
                }
                if (!empty($val['content'])){
                    $val['content'] = unserialize($val['content']);
                }
                $result['data'][$key] = $val;
            }
            $score_type = Db::name('shop_order_comment')
                ->where([
                    'product_id' => $goods_id,
                    'is_show' => 1,
                    'lang' => get_home_lang(),
                ])->field('count(*) as count,total_score')
                ->group('total_score')
                ->getAllWithIndex('total_score');
            $result['count']['goods'] = isset($score_type[1]) ? $score_type[1]['count'] : 0;
            $result['count']['middle'] = isset($score_type[2]) ? $score_type[2]['count'] : 0;
            $result['count']['bad'] = isset($score_type[3]) ? $score_type[3]['count'] : 0;
            $result['count']['all'] = $result['count']['goods']+$result['count']['middle']+$result['count']['bad'] ;

            cache($cacheKey, $result, null, 'getGoodsCommentList');
            $condition['a.upload_img'] = ['neq',''];
            $result['have_img_count'] = Db::name('shop_order_comment')->alias('a')->where($condition)->count();
        }
        return $result;
    }

    /**
     * 获取限时折扣商品详情
     */
    public function GetDiscountGoods($aid = 0,$users = []){
        $aid = intval($aid);
        $args = [$aid, $users];
        $cacheKey = 'api-'.md5(__CLASS__.__FUNCTION__.json_encode($args));
        $result = cache($cacheKey);
        if (true || empty($result)) {
            $detail = $this->getDiscountInfo($aid, $users);
            if (!empty($detail['detail'])) {
                if (0 <= $detail['detail']['arcrank']) { // 待审核稿件
                    $detail['detail']['title'] = htmlspecialchars_decode($detail['detail']['title']);
                    $detail['detail']['add_time'] = date('Y-m-d H:i:s', $detail['detail']['add_time']); // 格式化发布时间
                    $detail['detail']['content'] = $this->html_httpimgurl($detail['detail']['content'], true); // 转换内容图片为http路径
                } else {
                    $detail['detail'] = [
                        'arcrank'   => $detail['detail']['arcrank'],
                    ];
                }
            }

            $result = [
                'detail' => [
                    'data' => !empty($detail['detail']) ? $detail['detail'] : false,
                ],
                'product'=> !empty($detail['product']) ? $detail['product'] : [],
                'coupon_list'=> !empty($detail['coupon_list']) ? $detail['coupon_list'] : [],
            ];

            cache($cacheKey, $result, null, 'archives');
        }

        return $result;
    }

    /**
     * 获取限时折扣商品记录
     */
    private function getDiscountInfo($aid,$users = [])
    {
        $where = [
            'a.status'    => 1,
            'a.is_del'    => 0,
            'a.aid'    => $aid,
        ];
        $result = [];
        $detail = Db::name('discount_goods')
            ->alias('a')
            ->field('a.discount_gid,a.discount_stock,a.discount_price,a.sales,a.virtual_sales,a.is_sku,b.sales_actual,c.*')
            ->where($where)
            ->join('discount_active_goods b','a.aid = b.aid','left')
            ->join('archives c','a.aid = c.aid','left')
            ->find();

        if (!empty($detail)) {
            // 模型标题处理
            $channeltype_row = \think\Cache::get('extra_global_channeltype');
            $channeltypeInfo = !empty($channeltype_row[$detail['channel']]) ? $channeltype_row[$detail['channel']] : [];
            $detail['channel_ntitle'] = !empty($channeltypeInfo['ntitle']) ? $channeltypeInfo['ntitle'] : '文章';
            $detail['seo_title'] = $this->set_arcseotitle($detail['title'], $detail['seo_title']); // seo标题
            $detail['litpic'] = $this->get_default_pic($detail['litpic']); // 默认封面图
            $detail['content'] = '';

            unset($detail['users_free']);
            unset($detail['downcount']);

            /*产品参数*/
            if (!empty($detail['attrlist_id'])){ // 新版参数
                $productAttrModel = new \app\home\model\ProductAttr;
                $attr_list = $productAttrModel->getProAttrNew($aid, 'a.attr_id,a.attr_name,b.attr_value,b.aid');
            }else{ // 旧版参数
                $productAttrModel = new \app\home\model\ProductAttr;
                $attr_list = $productAttrModel->getProAttr($aid);
            }
            $attr_list = !empty($attr_list[$aid]) ? $attr_list[$aid] : [];
            foreach ($attr_list as $key => $val) {
                $val['attr_value'] = htmlspecialchars_decode($val['attr_value']);
                unset($val['aid']);
                $attr_list[$key] = $val;
            }
            $detail['attr_list'] = !empty($attr_list) ? $attr_list : false;


            /*规格数据*/
            $detail['spec_attr'] = $this->getSpecAttr($aid, $users,'discount_price asc,spec_price asc');
            /* END */

            // 产品相册
            $productImgModel = new \app\home\model\ProductImg;
            $image_list = $productImgModel->getProImg($aid, 'aid,image_url,intro');
            $image_list = !empty($image_list[$aid]) ? $image_list[$aid] : [];
            foreach ($image_list as $key => $val) {
                $val['image_url'] = $this->get_default_pic($val['image_url']);
                isset($val['intro']) && $val['intro'] = htmlspecialchars_decode($val['intro']);
                $image_list[$key] = $val;
            }

            $detail['image_list'] = !empty($image_list) ? $image_list : false;
            /*可控制的主表字段列表*/
            $detail['ifcontrolRow'] = Db::name('channelfield')->field('id,name')->where([
                'channel_id'    => $detail['channel'],
                'ifmain'        => 1,
                'ifeditable'    => 1,
                'ifcontrol'     => 0,
                'status'        => 1,
            ])->getAllWithIndex('name');

            // 设置默认原价
            $detail['old_price'] = $detail['users_price'];
            $detail['product_num'] = 1;
            $detail['spec_value_id'] = '';

            $result['product'] = $this->getRecomProduct();
            $result['coupon_list'] = $this->getCoupon($detail,$users);
            if ('v1.5.1' < getVersion()) {
                //总评论数
                $detail['comment_data_count'] = Db::name('shop_order_comment')->where(['product_id'=>$aid,'is_show'=>1])->count();
                $good_count = Db::name('shop_order_comment')->where(['product_id'=>$aid,'is_show'=>1,'total_score'=>1])->count();
                //好评率
                $detail['comment_good_per'] = !empty($detail['comment_data_count']) ? round($good_count/$detail['comment_data_count'],2)*100 : 0;
                $detail['comment_good_per'] = $detail['comment_good_per'].'%';
                if ($detail['comment_data_count'] > 0){
                    $detail['comment_data'] = Db::name('shop_order_comment')
                        ->alias('a')
                        ->field('a.*,b.nickname,b.head_pic')
                        ->join('users b','a.users_id = b.users_id')
                        ->where(['a.product_id'=>$aid,'a.is_show'=>1])
                        ->order('a.total_score asc')
                        ->limit(2)
                        ->select();
                    foreach ($detail['comment_data'] as $k => $v){
                        if (1 == $v['is_anonymous']){
                            $v['nickname'] = '匿名用户';
                        }
                        $v['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
                        $v['content'] = unserialize($v['content']);
                        $v['head_pic'] = get_default_pic($v['head_pic'],true);
                        $detail['comment_data'][$k] = $v;
                    }
                }
            }

            $detail['cart_total_num'] = 0;
            if (!empty($users['users_id'])){
                //购物车数量
                $detail['cart_total_num'] = Db::name('shop_cart')->where(['users_id' => $users['users_id']])->sum('product_num');
            }

            // 获取自定义字段的数据
            $customField = [];
            $detailExt = Db::name($channeltypeInfo['table'].'_content')->field('id,aid,add_time,update_time', true)->where('aid', $aid)->find();
            if (!empty($detailExt)) {
                $fieldLogic = new \app\home\logic\FieldLogic();
                $detailExt = $fieldLogic->getChannelFieldList($detailExt, $detail['channel']); // 自定义字段的数据格式处理
                if (empty($channeltypeInfo['ifsystem'])) { // 自定义模型
                    // 如果存在自定义字段content，默认作为文档的正文内容。
                    // 如果不存在，将获取第一个html类型的内容，作为文档的正文内容。
                    if (!isset($detailExt['content'])) {
                        $contentField = Db::name('channelfield')->where([
                            'channel_id'    => $detail['channel'],
                            'dtype'         => 'htmltext',
                        ])->getField('name');
                        $detailExt['content'] = !empty($detailExt[$contentField]) ? $detailExt[$contentField] : '';
                    }
                }
                $detail['content'] = $detailExt['content'];
                unset($detailExt['content']);

                if (!empty($detailExt)) {
                    $field = 'name, title, dtype';
                    $customField = Db::name('channelfield')->field($field)->where([
                        'name' => ['IN', array_keys($detailExt)],
                        'channel_id' => $detail['channel'],
                        'ifeditable' => 1
                    ])->getAllWithIndex('name');
                    if (!empty($customField)) {
                        foreach ($customField as $key => $value) {
                            if ('img' == $value['dtype']) {
                                $customField[$key]['value'] = $this->get_default_pic($detailExt[$key]);
                            } else if ('media' == $value['dtype']) {
                                $customField[$key]['value'] = $this->get_default_pic($detailExt[$key]);
                            } else if ('imgs' == $value['dtype']) {
                                foreach ($detailExt[$key] as $kk => $vv) {
                                    $detailExt[$key][$kk]['image_url'] = $this->get_default_pic($vv['image_url']);
                                }
                                $customField[$key]['value'] = $detailExt[$key];
                            } else {
                                $customField[$key]['value'] = $detailExt[$key];
                            }
                        }
                    }
                }
                $customField = array_values($customField);
            }
            $detail['customField'] = !empty($customField) ? $customField : false;

            // 浏览量
            Db::name('archives')->where(['aid'=>$aid])->setInc('click');
            $detail['click'] += 1;

            $result['detail'] = $detail;
        }

        return $result;
    }

    //添加文章评论
    public function addArticleComment($param = [],$users = [])
    {
        $users_level_id = !empty($users['level_id']) ? $users['level_id'] : 0;//0-游客
        $comment_level_data = Db::name('weapp_comment_level')->where('users_level_id',$users_level_id)->find();
        if (empty($comment_level_data['is_comment'])){
            return ['code'=>0, 'msg'=>'您没有评论权限'];
        }
        if (!empty($comment_level_data['is_review'])){
            $param['is_review'] = 0;
        }else{
            $param['is_review'] = 1;
        }
        $param['add_time'] = getTime();
        $param['update_time'] = getTime();
        $param['users_id'] = !empty($users['users_id']) ? $users['users_id'] : 0;
        $param['username'] = !empty($users['username']) ? $users['username'] : '游客';
        $param['provider'] = !empty($param['provider']) ? $param['provider'] : 'weixin';
        $param['users_ip'] = clientIP();
        $comment_id = Db::name('weapp_comment')->insertGetId($param);
        if (false !== $comment_id){
            $data = ['code'=>1, 'is_show'=>0];
            if (empty($param['is_review'])) {
                $msg = '评论成功，进入待审核中';
            } else {
                $msg = '评论成功';
                $comment = Db::name('weapp_comment')
                    ->alias('a')
                    ->field('a.*,b.head_pic,b.nickname,b.sex')
                    ->join('users b','a.users_id = b.users_id','left')
                    ->find($comment_id);
                $comment['head_pic'] = $this->get_head_pic($comment['head_pic'], false, $comment['sex']);
                $comment['add_time_format'] = $this->time_format($comment['add_time']);
                $comment['add_time'] = date('Y-m-d', $comment['add_time']);
                $data['comment'] = $comment;
                $data['is_show'] = 1;
            }
            $data['msg'] = $msg;

            return $data;
        }
        return ['code'=>0, 'msg'=>'添加评论失败'];
    }

}
