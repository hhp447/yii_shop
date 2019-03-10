<?php
namespace app\modules\controllers;

use yii\web\Controller;
use app\modules\models\Admin;
use Yii;

class PublicController extends Controller
{
    public function actionLogin()
    {
        $this->layout = false;//关闭布局
        $model = new Admin;
        if (Yii::$app->request->isPost) {//查看是否有post数据提交过来
            $post = Yii::$app->request->post();//接收数据
            if ($model->login($post)) {//执行登录方法
                $this->redirect(['default/index']);//跳转页面
                Yii::$app->end();//结束，不执行下面代码
            }
        }
        return $this->render("login", ['model' => $model]);
    }

    public function actionLogout()
    {
        Yii::$app->session->removeAll();
        if (!isset(Yii::$app->session['admin']['isLogin'])) {
            $this->redirect(['public/login']);
            Yii::$app->end();
        }
        $this->goback();
    }

    public function actionSeekpassword()
    {
        $this->layout = false;//关闭布局
        $model = new Admin;
        if (Yii::$app->request->isPost) {//查看是否有post数据提交过来
            $post = Yii::$app->request->post();//接受数据
            if ($model->seekPass($post)) {//执行找回密码发送邮件方法
                Yii::$app->session->setFlash('info', '电子邮件已经发送成功，请查收');//邮件发送成功提醒
            }
        }
        return $this->render("seekpassword", ['model' => $model]);//渲染模板
    }



}
