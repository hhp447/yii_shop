<?php

namespace app\models;

use yii\db\ActiveRecord;
use Yii;
use yii\helpers\ArrayHelper;

class Category extends ActiveRecord
{
    public static function tableName()
    {
        return "{{%category}}";
    }

    public function attributeLabels()
    {
        return [
            'parentid' => '上级分类',
            'title' => '分类名称'
        ];
    }

    public function rules()
    {
        return [
            ['parentid', 'required', 'message' => '上级分类不能为空'],
            ['title', 'required', 'message' => '标题名称不能为空'],
            ['createtime', 'safe']//确认add()中createtime数据是安全的
        ];
    }

    public function add($data)//添加分类
    {
        $data['Category']['createtime'] = time();
        if ($this->load($data) && $this->save()) {
            return true;
        }
        return false;
    }

    public function getData()//获取所有分类并转换为数组
    {
        $cates = self::find()->all();
        $cates = ArrayHelper::toArray($cates);
        return $cates;
    }

    public function getTree($cates, $pid = 0)//获取每个分类和她子分类的顺序结构
    {
        $tree = [];
        foreach($cates as $cate) {
            if ($cate['parentid'] == $pid) {
                $tree[] = $cate;//将此数组压进$tree中
                $tree = array_merge($tree, $this->getTree($cates, $cate['cateid']));
            }
        }
        return $tree;
    }

    public function setPrefix($data, $p = "|-----")//为各级分类设置前缀
    {
        $tree = [];//设置一个空数组
        $num = 1;//需要添加$p的个数
        $prefix = [0 => 1];//parentid对应需要添加$p的个数
        while($val = current($data)) {  //遍历$data
            $key = key($data);   //获取$data数组中当前值的键名
            if ($key > 0) {   //如果不是第一个值
                if ($data[$key - 1]['parentid'] != $val['parentid']) { //和上一个值做比较
                    $num ++;
                }
            }
            if (array_key_exists($val['parentid'], $prefix)) {//获取当前parentid需要增加$p的个数
                $num = $prefix[$val['parentid']];
            }
            $val['title'] = str_repeat($p, $num).$val['title'];//拼接
            $prefix[$val['parentid']] = $num;//写进$prefix中
            $tree[] = $val;//推进数组中
            next($data);//将数组指针指向下一个，不然造成死循环
        }
        return $tree;
    }

    public function getOptions()
    {
        $data = $this->getData();
        $tree = $this->getTree($data);
        $tree = $this->setPrefix($tree);
        $options = ['添加顶级分类'];
        foreach($tree as $cate) {
            $options[$cate['cateid']] = $cate['title'];
        }
        return $options;
    }

    public function getTreeList()
    {
        $data = $this->getData();
        $tree = $this->getTree($data);
        return $tree = $this->setPrefix($tree);
    }

    public static function getMenu()
    {
        $top = self::find()->where('parentid = :pid', [":pid" => 0])->limit(11)->orderby('createtime asc')->asArray()->all();//查询出顶级分类
        $data = [];
        foreach((array)$top as $k=>$cate) {  //根据顶级分类查询出二级分类
            $cate['children'] = self::find()->where("parentid = :pid", [":pid" => $cate['cateid']])->limit(10)->asArray()->all();
            $data[$k] = $cate;
        }
        return $data;
    }

}
