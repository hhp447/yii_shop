<?php
namespace app\controllers;
use app\controllers\CommonController;
use app\models\User;
use app\models\Cart;
use app\models\Product;
use Yii;

class CartController extends CommonController
{
    public function actionIndex()//购物车的遍历方法
    {
        if (Yii::$app->session['isLogin'] != 1) {
            return $this->redirect(['member/auth']);
        }
        $userid = User::find()->where('username = :name', [':name' => Yii::$app->session['loginname']])->one()->userid;
        $cart = Cart::find()->where('userid = :uid', [':uid' => $userid])->asArray()->all();
        //找到该用户购物车下的所有商品
        $data = [];
        foreach ($cart as $k=>$pro) {//找出各个商品的详细信息
            $product = Product::find()->where('productid = :pid', [':pid' => $pro['productid']])->one();
            $data[$k]['cover'] = $product->cover;
            $data[$k]['title'] = $product->title;
            $data[$k]['productnum'] = $pro['productnum'];
            $data[$k]['price'] = $pro['price'];
            $data[$k]['productid'] = $pro['productid'];
            $data[$k]['cartid'] = $pro['cartid'];
        }
        $this->layout = 'layout1';
        return $this->render("index", ['data' => $data]);
    }

    public function actionAdd()//加入购物车方法
    {
        if (Yii::$app->session['isLogin'] != 1) {//判断用户是否登录
            return $this->redirect(['member/auth']);//跳转到登录界面
        }
        $userid = User::find()->where('username = :name', [':name' => Yii::$app->session['loginname']])->one()->userid;//查询该用户信息
        if (Yii::$app->request->isPost) {//post提交数据，为商品详情页的加入购物车按钮
            $post = Yii::$app->request->post();//获取传递来的post数据
            $num = Yii::$app->request->post()['productnum'];//获取加入数量
            $data['Cart'] = $post;
            $data['Cart']['userid'] = $userid;
        }
        if (Yii::$app->request->isGet) {//为首页的加入购物车按钮
            $productid = Yii::$app->request->get("productid");
            $model = Product::find()->where('productid = :pid', [':pid' => $productid])->one();
            $price = $model->issale ? $model->saleprice : $model->price;//获取价格
            $num = 1;
            $data['Cart'] = ['productid' => $productid, 'productnum' => $num, 'price' => $price, 'userid' => $userid];
        }
        if (!$model = Cart::find()->where('productid = :pid and userid = :uid', [':pid' => $data['Cart']['productid'], ':uid' => $data['Cart']['userid']])->one()) {
            $model = new Cart;
            //判断该用户的购物车上是否拥有该商品，如果有，则数量加1，如果没有，则插入一条数据进购物车
        } else {
            $data['Cart']['productnum'] = $model->productnum + $num;
        }
        $data['Cart']['createtime'] = time();
        $model->load($data);
        $model->save();
        return $this->redirect(['cart/index']);
    }

    public function actionMod()//修改购物车商品的数量方法
    {
        $cartid = Yii::$app->request->get("cartid");
        $productnum = Yii::$app->request->get("productnum");
        Cart::updateAll(['productnum' => $productnum], 'cartid = :cid', [':cid' => $cartid]);
    }

    public function actionDel()//购物车中删除部分商品方法
    {
        $cartid = Yii::$app->request->get("cartid");
        Cart::deleteAll('cartid = :cid', [':cid' => $cartid]);
        return $this->redirect(['cart/index']);
    }

}





