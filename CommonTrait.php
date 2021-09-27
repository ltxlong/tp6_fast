<?php

namespace app\common\traits;

use app\BaseLogic;
use app\BaseModel;
use app\common\lib\other\DataEdit;

/**
 * 公用的trait
 * @author longtaixuan
 * @email 416803647@qq.com
 */
trait CommonTrait
{
    /**
     * 获取DataEdit实例
     * @param array $options
     * @return DataEdit
     */
    public function dataEdit(array $options = []): DataEdit
    {
        return DataEdit::instance($options);
    }

    /**
     * 创建class实例
     * @param string $Class
     * @return mixed
     */
    public function class(string $Class)
    {
        return app($Class);
    }

    /**
     * 创建model实例
     * @param string $modelClass
     * @return BaseModel
     */
    public function model(string $modelClass)
    {
        return app($modelClass);
    }

    /**
     * 创建logic实例
     * @param string $logicClass
     * @return BaseLogic
     */
    public function logic(string $logicClass)
    {
        return app($logicClass);
    }

    /**
     * 获取参数转换
     * @param $arr
     * @param array|string $data 数据数组或者获取数据的方法名
     * @return array
     */
    public function buildParam($arr, $data = 'param')
    {
        $param = [];
        if (is_array($arr)) {
            $request = $this->request;
            $isDataArr = is_array($data);
            $reqParam = $isDataArr ? $data : $request->$data();

            foreach ($arr as $k => $v) {
                $vArr = explode('/', $v);
                if (isset($reqParam[$vArr[0]]) || strpos($v, '??') !== false) {
                    if (is_int($k)) {
                        $param[$vArr[0]] = $isDataArr ? $this->transformValue($reqParam[$vArr[0]], $vArr[1] ?? '') : $request->$data($v);
                    } else {
                        $param[$k] = $isDataArr ? $this->transformValue($reqParam[$vArr[0]], $vArr[1] ?? '') : $request->$data($v);
                    }
                }
            }
        }

        return $param;
    }

    /**
     * 变量类型转换
     * @param string $value 变量
     * @param string $toType 要转换的类型
     * @return array|bool|float|int|mixed|string
     */
    public function transformValue($value = '', $toType = '')
    {
        switch ($toType) {
            case 'string':
            case 's':
                $valueRes = (string)$value;
                break;
            case 'int':
            case 'd':
                $valueRes = (int)$value;
                break;
            case 'array':
            case 'a':
                $valueRes = (array)$value;
                break;
            case 'bool':
            case 'b':
                $valueRes = (bool)$value;
                break;
            case 'float':
            case 'f':
                $valueRes = (float)$value;
                break;
            default:
                $valueRes = $value;
        }

        return $valueRes;
    }

    /**
     * 获取列表（分页）
     * @param $model -- 模型路径|模型对象
     * @param array $where
     * @param string $field
     * @param string $order
     * @param int $perPage -- 每页数量
     * @return array
     */
    public function getListPage(
        $model,
        array $where = [],
        string $field = '*',
        string $order = 'id desc',
        int $perPage = 10
    ) {
        if (is_string($model)) {
            $model = app($model);
        }

        $query = $model->where($where)->field($field);

        $count = $query->count();

        return $query->order($order)
            ->paginate(
                $perPage,
                $count
            )->toArray();
    }

    /**
     * 获取列表（不分页）
     * @param $model -- 模型路径|模型对象
     * @param array $where
     * @param string $field
     * @param string $order
     * @return array
     */
    public function getListOnly(
        $model,
        array $where = [],
        string $field = '*',
        string $order = 'id desc'
    ) {
        if (is_string($model)) {
            $model = app($model);
        }

        $res = $model->where($where)->field($field)->order($order)->select();

        if ($res->isEmpty()) {
            return [];
        } else {
            return $res->toArray();
        }
    }

}
