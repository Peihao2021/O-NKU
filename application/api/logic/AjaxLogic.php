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

namespace app\api\logic;

use think\Model;
use think\Db;

/**
 * 逻辑定义
 * Class CatsLogic
 * @package api\Logic
 */
class AjaxLogic extends Model
{
    /**
     * 保存足迹
     */
    public function footprint_save($aid)
    {
        $users_id = (int)session('users_id');
        if (!empty($aid) && !empty($users_id)) {
            //查询标题模型缩略图信息
            $arc = Db::name('archives')
                ->field('aid,channel,typeid,title,litpic')
                ->find($aid);
            if (!empty($arc)) {
                $count = Db::name('users_footprint')->where([
                    'users_id' => $users_id,
                    'aid'      => $aid,
                ])->count();
                if (empty($count)) {
                    // 足迹记录条数限制
                    $user_footprint_limit = config('global.user_footprint_limit');
                    $user_footprint_record = Db::name('users_footprint')->where(['users_id'=>$users_id])->count("id");
                    if ($user_footprint_record == $user_footprint_limit) {
                        Db::name('users_footprint')->where(['users_id' => $users_id])->order("id ASC")->limit(1)->delete();
                    }elseif ($user_footprint_record > $user_footprint_limit) {
                        $del_count = $user_footprint_record-$user_footprint_limit+1;
                        $del_ids = Db::name('users_footprint')->field("id")->where(['users_id' => $this->users_id])->order("id ASC")->limit($del_count)->select();
                        $del_ids = get_arr_column($del_ids,'id');
                        Db::name('users_footprint')->where(['id' => ['IN',$del_ids]])->delete();
                    }

                    $arc['users_id']    = $users_id;
                    $arc['lang']        = get_home_lang();
                    $arc['add_time']    = getTime();
                    $arc['update_time'] = getTime();
                    Db::name('users_footprint')->add($arc);
                } else {
                    Db::name('users_footprint')->where([
                        'users_id' => $users_id,
                        'aid'      => $aid
                    ])->update([
                        'update_time' => getTime(),
                    ]);
                }
                return true;
            }
        } else if (IS_AJAX && !empty($aid) && empty($users_id)) {
            return true;
        }

        return false;
    }
}
