<?php

namespace app\controllers;
use app\controllers\CommonController;

use Yii;
use app\models\User;
use app\models\Address;

class AddressController extends CommonController
{
    public function actionAdd()//添加收货地址的方法
    {
        if (Yii::$app->session['isLogin'] != 1) {//判断是否登录
            return $this->redirect(['member/auth']);
        }
        $loginname = Yii::$app->session['loginname'];
        $userid = User::find()->where('username = :name or useremail = :email', [':name' => $loginname, ':email' => $loginname])->one()->userid;//获取用户ID
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $post['userid'] = $userid;
            $post['address'] = $post['address1'].$post['address2'];
            $data['Address'] = $post;
            $model = new Address;
            $model->load($data);
            $model->save();//添加收货地址进数据库
        }
        return $this->redirect($_SERVER['HTTP_REFERER']);//返回前一个页面的地址
    }

    public function actionDel()//删除收货地址方法
    {
        if (Yii::$app->session['isLogin'] != 1) {
            return $this->redirect(['member/auth']);
        }
        $loginname = Yii::$app->session['loginname'];
        $userid = User::find()->where('username = :name or useremail = :email', [':name' => $loginname, ':email' => $loginname])->one()->userid;
        $addressid = Yii::$app->request->get('addressid');
        if (!Address::find()->where('userid = :uid and addressid = :aid', [':uid' => $userid, ':aid' => $addressid])->one()) {
            return $this->redirect($_SERVER['HTTP_REFERER']);
        }//判断当前地址是不是属于这个用户
        Address::deleteAll('addressid = :aid', [':aid' => $addressid]);
        return $this->redirect($_SERVER['HTTP_REFERER']);
    }










}
