<?php

namespace Ad\Model;

use Think\Model;

class AdModel extends Model {

    protected $_validate = array(
        array('ad_name', 'require', '广告名称不能为空', 1),
        array('pos_id', 'require', '广告位id不能为空', 1),
        array('is_on', 'require', '是否启用不能为空', 1),
        array('ad_type', 'require', '广告类型不能为空', 1),
    );

    public function search() {
        $pages = 15;

        $where = 1;

        $count = $this->where($where)->count(); // 查询满足要求的总记录数
        $Page = new \Think\Page($count, $pages); // 实例化分页类 传入总记录数和每页显示的记录数(25)

        $data['show'] = $Page->show(); // 分页显示输出

        $data['list'] = $this->order('id')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //echo $this->getLastSql();die;
        return $data;
    }

    public function _before_insert(&$data, $options) {
        //var_dump($_FILES);die;
        if ($data['ad_type'] == '图片') {
            $upload = new \Think\Upload();
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = './Public/Uploads/';
            $upload->savePath = 'Goods/';
            $info = $upload->upload(array('img_url' => $_FILES["img_url"]));

            $img_path = $info['img_url']['savepath'] . $info['img_url']['savename'];
            $data['img_url'] = $img_path;
        }
        if ($data['is_on'] == '是') {
            $this->where('is_on="是" and pos_id='.$data['pos_id'])->setField('is_on', '否');
        }
    }

    public function Has_Img($img_temp) {
        if ($img_temp) {
            return true;
        }
        return false;
    }

    public function _after_insert($data, $options) {
        if ($data['ad_type'] == '动画') {
            if ($this->Has_Img($_FILES['cart_img']['tmp_name'])) {
                $ad_info = M('adInfo');
                $links = I('post.cart_link');
                $upload = new \Think\Upload();
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
                $upload->rootPath = './Public/Uploads/';
                $upload->savePath = 'Goods/';
                $info = $upload->upload(array('cart_img' => $_FILES['cart_img']));

                foreach ($info as $k => $i) {
                    $image_path = $i["savepath"] . $i["savename"];


                    //将地址保存到数据库中
                    $ad_info->img_url = $image_path;
                    $ad_info->link = $links[$k];
                    $ad_info->ad_id = $data['id'];


                    $ad_info->add();
                }
            }
        }
    }

    /**
      删除广告时,将广告的图片也删除掉
     */
    public function _before_delete($options) {
        //var_dump($options['where']['id']);die;
        if (is_array($options['where']['id'])) {
            $ads = $this->where('id in(' . $options['where']['id'][1] . ')')->select();
            $ad_info = M('adInfo');
            foreach ($ads as $ad) {
                if ($ad['ad_type'] == '图片') {

                    unlink(IMG_PATH2 . $ad['img_url']);
                } else {
                    $ad_infos = $ad_info->where('ad_id=' . $ad['id'])->select();
                    foreach ($ad_infos as $in) {
                        unlink(IMG_PATH2 . $in['img_url']);
                    }
                    $ad_info->where('ad_id=' . $ad['id'])->delete();
                }
            }
        } else {
            $ads = $this->where('id=' . $options['where']['id'])->find();
            if ($ads['ad_type'] == '图片') {
                unlink(IMG_PATH2 . $ads['img_url']);
            } else {
                $ad_info = M('adInfo');
                $ad_infos = $ad_info->where('ad_id=' . $options['where']['id'])->select();

                foreach ($ad_infos as $a) {
                    unlink(IMG_PATH2 . $a['img_url']);
                }
                $ad_info->where('ad_id=' . $options['where']['id'])->delete();
            }
        }
    }

    //修改图片类型
    public function _before_update(&$data, $options) {
        header("Content-type:text/html;charset=utf-8");

        if ($data['ad_type'] == '图片' && !empty($_FILES["img_url"]["name"])) {

            $img_path = I('post.file_path');

            unlink(IMG_PATH2 . $img_path);

            $upload = new \Think\Upload();
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = './Public/Uploads/';
            $upload->savePath = 'Goods/';
            $info = $upload->upload(array('img_url' => $_FILES["img_url"]));

            $i_path = $info["img_url"]["savepath"] . $info["img_url"]["savename"];
            $data['img_url'] = $i_path;
        }
        if ($data['is_on'] == '是') {
            $this->where('pos_id=' . $data['pos_id'] . ' and id <>' . $options['where']['id'])->setField('is_on', '否');
        }
    }

    //处理动画的修改
    public function _after_update($data, $options) {

        if ($data['ad_type'] == '动画') {

            if ($this->Has_Img($_FILES['cart_img']['tmp_name'])) {

                $ad_info = M('adInfo');
                $links = I('post.cart_link');

                $upload = new \Think\Upload();
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
                $upload->rootPath = './Public/Uploads/';
                $upload->savePath = 'Goods/';
                $info = $upload->upload(array('cart_img' => $_FILES['cart_img']));

                foreach ($info as $k => $i) {
                    $image_path = $i["savepath"] . $i["savename"];


                    //将地址保存到数据库中
                    $ad_info->img_url = $image_path;
                    $ad_info->link = $links[$k];
                    $ad_info->ad_id = $data['id'];
                    $ad_info->add();
                }
            }
            if ($this->Has_Img($_FILES['old_cart_img']['tmp_name'])) {


                //var_dump($links);die;
                $upload = new \Think\Upload();
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
                $upload->rootPath = './Public/Uploads/';
                $upload->savePath = 'Goods/';
                $info = $upload->upload(array('old_cart_img' => $_FILES['old_cart_img']));
            }

            $ad_info = M('adInfo');
            $links = I('post.old_cart_link');
            //var_dump($links);
            //var_dump($info);
            $ii=0;
            foreach ($links as $k => $i) {
              //var_dump($k);die;
                if (!empty($info[$ii])) {
                    $adInfo=M('adInfo');
                    $imgs=$adInfo->where('id='.$k)->getField('img_url');
                   
                    unlink(IMG_PATH2.$imgs);
                    //这里还没有修改完呢
                    $image_path = $info[$ii]["savepath"] . $info[$ii]["savename"];
                    //将地址保存到数据库中
                    $ad_info->img_url = $image_path;
                    $ad_info->link = $i;

                    $ad_info->where('id=' . $k)->save();
                   ++$ii;
                } else {//如果没有修改图片,只修了链接信息
                    $ad_info->link = $i;

                    $ad_info->where('id=' . $k)->save();
                    ++$ii;
                }
            }
        }
    }

}
