<?php

namespace app\modules\controllers;
use app\models\Category;
use app\models\Product;
use yii\web\Controller;
use Yii;
use yii\data\Pagination;
use crazyfd\qiniu\Qiniu;
use app\modules\controllers\CommonController;

class ProductController extends CommonController
{
    public function actionList()
    {
        $model = Product::find();
        $count = $model->count();
        $pageSize = Yii::$app->params['pageSize']['product'];
        $pager = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $products = $model->offset($pager->offset)->limit($pager->limit)->all();
        $this->layout = "layout1";
        return $this->render("products", ['pager' => $pager, 'products' => $products]);
    }

    public function actionAdd()//添加商品方法
    {
        $this->layout = "layout1";
        $model = new Product;
        $cate = new Category;
        $list = $cate->getOptions();
        unset($list[0]);
        
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $pics = $this->upload();
            if (!$pics) {
                $model->addError('cover', '封面不能为空');
            } else {
                $post['Product']['cover'] = $pics['cover'];
                $post['Product']['pics'] = $pics['pics'];
            }
            if ($pics && $model->add($post)) {
                Yii::$app->session->setFlash('info', '添加成功');
            } else {
                Yii::$app->session->setFlash('info', '添加失败');
            }

        }

        return $this->render("add", ['opts' => $list, 'model' => $model]);
    }


    private function upload()//上传文件到七牛
    {
        if ($_FILES['Product']['error']['cover'] > 0) {
            return false;
        }
        $qiniu = new Qiniu(Product::AK, Product::SK, Product::DOMAIN, Product::BUCKET);
        $key = uniqid();//在七牛上用key来定位图片
        $qiniu->uploadFile($_FILES['Product']['tmp_name']['cover'], $key);//上传图片
        $cover = $qiniu->getLink($key);//上传后图片的外链
        $pics = [];//附加图片，有多张，所以定义一个数组来存储
        foreach ($_FILES['Product']['tmp_name']['pics'] as $k => $file) {
            if ($_FILES['Product']['error']['pics'][$k] > 0) {//大于0代表有错
                continue;
            }
            $key = uniqid();
            $qiniu->uploadFile($file, $key);//上传每一个
            $pics[$key] = $qiniu->getLink($key);
        }
        return ['cover' => $cover, 'pics' => json_encode($pics)];
    }

    public function actionMod()//编辑商品方法
    {
        $this->layout = "layout1";
        $cate = new Category;
        $list = $cate->getOptions();//获得下拉菜单的选项
        unset($list[0]);

        $productid = Yii::$app->request->get("productid");//接受传递过来的商品ID
        $model = Product::find()->where('productid = :id', [':id' => $productid])->one();


        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $qiniu = new Qiniu(Product::AK, Product::SK, Product::DOMAIN, Product::BUCKET);
            $post['Product']['cover'] = $model->cover;
            //echo $_FILES['Product']['error']['cover']; die;
            if ($_FILES['Product']['error']['cover'] == 0) {//如果没有错误
                $key = uniqid();
                $qiniu->uploadFile($_FILES['Product']['tmp_name']['cover'], $key);
                $post['Product']['cover'] = $qiniu->getLink($key);//覆盖新的封面
                $qiniu->delete(basename($model->cover));//删除七牛中原来的封面照片

            }
            $pics = [];
            foreach($_FILES['Product']['tmp_name']['pics'] as $k => $file) {
                if ($_FILES['Product']['error']['pics'][$k] > 0) {//有错误，跳过
                    continue;
                }
                $key = uniqid();
                $qiniu->uploadfile($file, $key);//上传图片到七牛中
                $pics[$key] = $qiniu->getlink($key);//获取上传图片的KEY
            }
            $post['Product']['pics'] = json_encode(array_merge((array)json_decode($model->pics, true), $pics));
            if ($model->load($post) && $model->save()) {
                Yii::$app->session->setFlash('info', '修改成功');
            }

        }


        return $this->render('add', ['model' => $model, 'opts' => $list]);

    }

    public function actionRemovepic()//编辑商品下面的删除图片
    {
        $key = Yii::$app->request->get("key");//获取传递过来的要删除的照片的key
        $productid = Yii::$app->request->get("productid");//获取该商品的ID
        $model = Product::find()->where('productid = :pid', [':pid' => $productid])->one();
        //查询该商品的信息
        $qiniu = new Qiniu(Product::AK, Product::SK, Product::DOMAIN, Product::BUCKET);
        $qiniu->delete($key);//删除七牛中的图片
        $pics = json_decode($model->pics, true);//将json数据转换为数组
        unset($pics[$key]);
        Product::updateAll(['pics' => json_encode($pics)], 'productid = :pid', [':pid' => $productid]);//更新数据库数据
        return $this->redirect(['product/mod', 'productid' => $productid]);
    }

    public function actionDel()//删除该商品方法
    {
        $productid = Yii::$app->request->get("productid");
        $model = Product::find()->where('productid = :pid', [':pid' => $productid])->one();
        $key = basename($model->cover);
        $qiniu = new Qiniu(Product::AK, Product::SK, Product::DOMAIN, Product::BUCKET);
        $qiniu->delete($key);
        $pics = json_decode($model->pics, true);
        foreach($pics as $key=>$file) {
            $qiniu->delete($key);
        }
        Product::deleteAll('productid = :pid', [':pid' => $productid]);
        return $this->redirect(['product/list']);
    }

    public function actionOn()//商品上架操作
    {
        $productid = Yii::$app->request->get("productid");
        Product::updateAll(['ison' => '1'], 'productid = :pid', [':pid' => $productid]);
        return $this->redirect(['product/list']);
    }

    public function actionOff()//商品下架操作
    {
        $productid = Yii::$app->request->get("productid");
        Product::updateAll(['ison' => '0'], 'productid = :pid', [':pid' => $productid]);
        return $this->redirect(['product/list']);
    }









}
