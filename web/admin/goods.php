<?php
/**
 * 商品信息管理页
 *
 * Created by PhpStorm.
 * User: hehongbo
 * Date: 2018/2/24
 * Time: 下午9:58
 */

include 'init.php';
$refer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/admin';
$TEMPLATE['refer'] = $refer;
$TEMPLATE['type']  = 'goods';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

$goods_model = new \model\Goods();

if('list' == $action)
{
    $page = getReqInt('page','get', 1);
    $size = 20;
    $offset = ($page -1) * $size;
    $data_list = $goods_model->getList($offset, $size);
    $TEMPLATE['data_list'] = $data_list;
    $TEMPLATE['pageTitle']  = '商品管理';
    include TEMPLATE_PATH . '/admin/goods_list.php';
}
else if('edit' == $action)
{
    $id = getReqInt('id', 'get', 0);
    if ($id) {
        $data = $goods_model->get($id);
    } else {
        $data = [
            'id' => 0,
            'active_id' => 0,
            'title' => '',
            'description' => '',
            'img' => '',
            'price_normal' => '0',
            'price_discount' => '0',
            'num_total' => 0,
            'num_user' => 0,
            'time_begin' => '',
            'time_end' => ''
        ];
    }

    $TEMPLATE['data'] = $data;
    $TEMPLATE['pageTitle'] = '编辑商品信息-商品管理';
    include TEMPLATE_PATH . '/admin/goods_edit.php';
}
else if('save' == $action)
{
    $info = $_POST['info'];
    $info['title']          = addslashes($info['title']);
    $info['description']    = addslashes($info['description']);
    $info['img']            = addslashes($info['img']);
    $info['price_normal']   = addslashes($info['price_normal']);
    $info['price_discount'] = intval($info['price_discount']);
    $info['num_total']      = $info['num_left'] = intval($info['num_total']);
    $info['num_user']       = intval($info['num_user']);

    foreach ($info as $k =>  $v)
    {
        $goods_model-> $k = $v;
    }

    if($info['id'])
    {
        $goods_model->sys_lastmodify = time();
		$ok = $goods_model->save();
        $id = $info['id'];
    }
    else
    {
        $active_model->sys_lastmodify = $active_model->sys_dateline  = time();
        $active_model->sys_ip = getClientIp();
        $ok = $active_model->create();
        $id = $ok;
    }
    if($ok)
    {
        // 将商品保存的Redis 数据缓存中 方便前端数据读取
        $redis_obj =  \common\Datasource::getRedis('instance1');
        // 设置Redis key  : miaosha:string:info_g_+  互动ID
        $redis_key = 'miaosha:string:info_g_'. $id;
        $info = json_encode($info);
        $redis_obj->set($redis_key, $info);
        redirect('goods.php');
    }
    else
    {
        echo '<script>alert("数据保存失败");history.go(-1);</script>';
    }
}
else if ('delete' == $action)
{
    $id = getReqInt('id','get',0);
    $ok = false;
    if($id)
    {
        $goods_model->id = $id;
        $goods_model->sys_status = 2;
        $goods_model->sys_lastmodify = time();
        $ok = $goods_model->save($id);

    }
    if($ok)
    {
        // 修改redis 缓存中的存储的商品数据
        // 将商品保存的Redis 数据缓存中 方便前端数据读取
        $redis_obj =  \common\Datasource::getRedis('instance1');
        // 设置Redis key  : miaosha:string:info_g_+  互动ID
        $redis_key = 'miaosha:string:info_g_'. $id;
        $info = $redis_obj->get($redis_key);
//        if($info)
//        {
            $info = json_decode($info,1);
            $info['sys_status'] = 2;
            $info['sys_lastmodify'] = time();
            $info = json_encode($info);
            $redis_obj->set($redis_key, $info);
//        }

        redirect($refer);
    }
    else
    {
        show_result("下线时候出现错误", $refer);
    }
} else if ('reset' == $action)
{
    // 数据恢复
    $id  = getReqInt('id','get',0);
    $ok = false;
    if($id)
    {
        $goods_model->id = $id;
        $goods_model->sys_status = 1;
        $goods_model->sys_lastmodify = time();
        $ok = $goods_model->save($id);
    }
    if($ok)
    {
        $data = $goods_model->get($id);
        // 修改redis 缓存中的存储的商品数据
        // 将商品保存的Redis 数据缓存中 方便前端数据读取
        $redis_obj =  \common\Datasource::getRedis('instance1');
        // 设置Redis key  : miaosha:string:info_g_+  互动ID
        $redis_key = 'miaosha:string:info_g_'. $id;
        $info = $redis_obj->get($redis_key);
//        if($info)
//        {
            $info = json_decode($info,1);
            $info['sys_status'] = 1;
            $info['num_user'] = $data['num_user'];
            $info['img'] = $data['img'];
            $info['title'] = $data['title'];
            $info['price_normal'] = $data['price_normal'];
            $info['price_discount'] = $data['price_discount'];
            $info['price_discount'] = $data['price_discount'];
            $info['sys_lastmodify'] = time();
            $info = json_encode($info);
            $redis_obj->set($redis_key, $info);
//        }

        redirect($refer);
    }
    else
    {
        show_result("上线时候出现错误", $refer);
    }
}
else
{
    echo 'error goods action';
}

