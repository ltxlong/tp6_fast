<?php

namespace app\common\lib\other;

use app\common\lib\exception\ApiException;
use think\facade\Request;
use think\facade\Validate;

/**
$paramList = [
"my_username" => "username/s",
"my_address" => "address/s",
"desc" => "desc/s",
"content/s",
"test"
];
//$validateRule = "app\common\UserValidate"; //验证器也行
$validateRule = ['username' => 'require'];
$modelName = 'app\common\model\Login'; // 为了维护方便，可以在类的属性写个变量，并且类的路径获取用::class而不是像这样直接写字符串，推荐更简洁的写法 $modelName = $this->commonLoginModel
$res = DataEdit::instance() // 推荐继承在框架的控制器基类里，直接$this->dataEdit()->来进行调用
->setParam($paramList)
->setAppend(["append" => "this is append"])
->setValidate($validateRule)
->setModel($modelName)
->save();

return $res ? retJson('success', 0) : retJson('fail', 1);
 */

/**
 * 数据编辑快捷类
 * @author longtaixuan
 * @email 416803647@qq.com
 */

class DataEdit
{
    protected static $instance;
    protected $request;
    protected $param;
    protected $paramMethod = 'param';
    protected $append;
    protected $validate;
    protected $data;
    protected $saveData;
    protected $useSetData  = false;
    protected $model = '';
    protected $modelClass = null;
    protected $updateMap;
    protected $isUpdate;
    protected $pk;
    protected $allowField = [];
    protected $result;
    protected $error;

    public function __construct($options = [])
    {
        $this->request = Request::instance();
        if (isset($options['data'])) {
            $this->data = $options['data'];
        }
        if (isset($options['validate'])) {
            $this->validate = $options['validate'];
        }
        if (isset($options['model'])) {
            $this->model = $options['model'];
        }
    }

    /**
     * 静态初始化
     * @param array $options
     * @return static
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    /**
     * 设置获取参数
     * @param array $param
     * @param string $paramMethod
     * @return $this
     * @throws ApiException
     */
    public function setParam($param = [], $paramMethod = 'param')
    {
        if (!empty($param) && is_array($param)) {
            $this->param = $param;
            if (is_string($paramMethod)) {
                $this->paramMethod = $paramMethod;
            }

            return $this;
        } else {
            throw new ApiException([100, 'setParam设置错误！']);
        }
    }

    /**
     * 添加单个获取参数
     * @param $saveName
     * @param string $inputName
     * @param string $dataType
     * @return $this
     * @throws ApiException
     */
    public function addParam($saveName, $inputName = '', $dataType = '')
    {
        if (empty($saveName) || !is_string($saveName)) {
            throw new ApiException([100, 'addParam设置错误！']);
        }
        if (empty($inputName)) {
            $inputName = $saveName;
        }
        switch ($dataType) {
            case 'string':
            case 's':
                $dataType = '/s';
                break;
            case 'int':
            case 'd':
                $dataType = '/d';
                break;
            case 'array':
            case 'a':
                $dataType = '/a';
                break;
            case 'bool':
            case 'b':
                $dataType = '/b';
                break;
            case 'float':
            case 'f':
                $dataType = '/f';
                break;
            default:
                $dataType = '';

        }
        $this->param[$saveName] = $inputName . $dataType;

        return $this;
    }

    /**
     * 设置追加参数
     * @param array $param
     * @return $this
     * @throws ApiException
     */
    public function setAppend($param = [])
    {
        if (!empty($param) && is_array($param)) {
            $this->append = $param;

            return $this;
        } else {
            throw new ApiException([100, 'setAppend设置错误！']);
        }
    }

    /**
     * 添加单个附加数据
     * @param $saveName
     * @param $saveValue
     * @return $this
     * @throws ApiException
     */
    public function addAppend($saveName, $saveValue)
    {
        if (!empty($saveName) && is_string($saveName)) {
            $this->append[$saveName] = $saveValue;

            return $this;
        } else {
            throw new ApiException([100, 'addAppendData设置错误！']);
        }
    }

    /**
     * 设置保存的值
     * @param array $data -- 不用前端的时候，就用这个
     * @return $this
     * @throws ApiException
     */
    public function setData($data = [])
    {
        if (!empty($data) && is_array($data)) {
            $this->data = $data;
            $this->useSetData = true;

            return $this;
        } else {
            throw new ApiException([100, 'setData设置错误！']);
        }
    }

    /**
     * 设置validate
     * @param $validate -- rule或者验证器路径
     * @return $this
     * @throws ApiException
     */
    public function setValidate($validate)
    {
        if (!empty($validate) && (is_array($validate) || is_string($validate) || is_object($validate))) {
            $this->validate = $validate;

            return $this;
        } else {
            throw new ApiException([100, 'setValidate设置错误！']);
        }
    }

    /**
     * 设置model
     * @param $model -- model路径|实例
     * @return $this
     * @throws ApiException
     */
    public function setModel($model)
    {
        if (!empty($model)) {
            if (is_string($model)) {
                $this->model = $model;
            } else {
                $this->modelClass = $model;
            }

            return $this;
        } else {
            throw new ApiException([100, 'setModel设置错误！']);
        }
    }

    /**
     * 设置允许字段
     * @param array $allowField -- 允许写入的字段
     * @return $this
     * @throws ApiException
     */
    public function setAllowField($allowField = [])
    {
        if (!empty($allowField) && is_array($allowField)) {
            $this->allowField = $allowField;

            return $this;
        } else {
            throw new ApiException([100, 'setAllowField设置错误！']);
        }
    }

    /**
     * 构建参数
     * @param array $arr
     * @param array|string $data
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
                if (isset($reqParam[$vArr[0]])) {
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
     * 存储数据，自动判断新增和更新
     * @return false
     */
    public function save()
    {
        try {
            if (!$this->actionHandle()) {
                throw new ApiException([100, $this->getError()]);
            }
            $this->checkPk();
            if ($this->updateMap && isset($this->saveData[$this->pk])) {
                if (!isset($this->updateMap[$this->pk])) {
                    unset($this->saveData[$this->pk]);
                }
            }
            if (empty($this->updateMap) && isset($this->saveData[$this->pk])) {
                $this->updateMap[$this->pk] = $this->saveData[$this->pk];
                unset($this->saveData[$this->pk]);
            }
            if ($this->updateMap) {
                $this->result = $this->modelClass->allowField($this->allowField)->save($this->saveData, $this->updateMap);
            } else {
                $this->result = $this->modelClass->allowField($this->allowField)->save($this->saveData);
                $this->isUpdate = false;
            }

            return $this->result;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            lo($e->getMessage());

            return false;
        }
    }

    /**
     * 更新数据
     * @return false
     */
    public function update()
    {
        try {
            if (!$this->actionHandle()) {
                throw new ApiException([100, $this->getError()]);
            }
            $this->checkPk();
            if ($this->updateMap && isset($this->saveData[$this->pk])) {
                if (!isset($this->updateMap[$this->pk])) {
                    unset($this->saveData[$this->pk]);
                }
            }
            if (empty($this->updateMap) && !isset($this->saveData[$this->pk])) {
                throw new ApiException([100, '缺少更新条件']);
            } elseif (empty($this->updateMap) && isset($this->saveData[$this->pk])) {
                $this->updateMap[$this->pk] = $this->saveData[$this->pk];
                unset($this->saveData[$this->pk]);
            }
            if ($this->updateMap) {
                $this->result = $this->modelClass->allowField($this->allowField)->save($this->saveData, $this->updateMap);

                return $this->result;
            } else {
                throw new ApiException([100, '缺少更新条件']);
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            lo($e->getMessage());

            return false;
        }
    }

    /**
     * 新增数据
     * @return false
     */
    public function add()
    {
        try {
            if (!$this->actionHandle()) {
                throw new ApiException([100, $this->getError()]);
            }
            $this->checkPk();
            if ($this->isUpdate && isset($this->saveData[$this->pk])) {
                unset($this->saveData[$this->pk]);
                $this->isUpdate = false;
            }
            $this->result = $this->modelClass->allowField($this->allowField)->save($this->saveData);

            return $this->result;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            lo($e->getMessage());

            return false;
        }
    }

    /**
     * saveAll，自动判断新增和更新
     * @return false
     */
    public function saveAll()
    {
        try {
            if (!$this->actionHandle()) {
                throw new ApiException([100, $this->getError()]);
            }
            $saveData = $this->saveData;
            if ($this->useSetData) {
                if ($this->isAssoc($saveData)) {
                    $saveData = [$saveData];
                }
            } else {
                $saveData  = [$saveData];
            }

            $res = $this->modelClass->saveAll($saveData);
            if ($res) {
                $this->result = $res;
            } else {
                $this->result = false;
            }

            return $this->result;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            lo($e->getMessage());

            return false;
        }
    }

    /**
     * 执行model方法
     * @param $actionName
     * @return false
     */
    public function execModelAction($actionName)
    {
        try {
            if (!$this->actionHandle()) {
                throw new ApiException([100, $this->error]);
            }
            if (!method_exists($this->modelClass, $actionName)) {
                throw new ApiException([100, '自定义的模型方法不存在']);
            }
            $this->result = $this->modelClass->$actionName($this->saveData);

            return $this->result;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 操作执行前处理
     * @return bool
     */
    protected function actionHandle()
    {
        try {
            $this->buildSaveData();
            if (!$this->checkSaveDate()) {
                return false;
            }

            if (!empty($this->model)) {
                $this->modelClass = app($this->model);
            }

            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 构建保存值数组 $saveData
     * @throws ApiException
     */
    protected function buildSaveData()
    {
        switch (true) {
            case (empty($this->param) && empty($this->data)):
                $this->saveData = $this->request->param();
                break;
            case (!empty($this->param) && !empty($this->data)):
                throw new ApiException([100, 'setParam和setData不可同时设置，请使用append方法']);
                break;
            case ($this->param):
                $this->saveData = $this->buildParam($this->param, $this->paramMethod);
                break;
            case ($this->data):
                $this->saveData = $this->data;
                break;
            default:
                ;
        }
        if ($this->append) {
            $this->saveData = array_merge($this->saveData, $this->append);
        }
    }

    /**
     * 校验参数
     * @return bool
     */
    public function checkSaveDate()
    {
        $flag = true;
        if (empty($this->saveData)) {
            $flag = false;
            $this->error = '要保存的数据为空';
        }
        if (is_array($this->validate)) {
            $flag = Validate::rule($this->validate)->check($this->saveData);
            $this->error = Validate::getError();
        } elseif (is_string($this->validate)) {
            app($this->validate)->goCheck();
        } elseif (is_object($this->validate)) {
            $this->validate->goCheck();
        }

        return $flag;
    }

    /**
     * 设置更新条件
     * @param $map
     * @return $this
     * @throws ApiException
     */
    public function setUpdateMap($map)
    {
        if (is_array($map)) {
            $this->updateMap = $map;
            $this->isUpdate = true;

            return $this;
        } else {
            throw new ApiException([100, 'setUpdateMap设置错误']);
        }
    }

    /**
     * 设置pk
     * @param $pk
     * @return $this
     * @throws ApiException
     */
    public function setPk($pk)
    {
        if (is_string($pk)) {
            $this->pk = $pk;

            return $this;
        } else {
            throw new ApiException([100, 'setPk设置错误']);
        }
    }

    /**
     * 检查pk
     */
    protected function checkPk()
    {
        if (empty($this->pk)) {
            $this->pk = $this->modelClass->getPk();
        }
        if (isset($this->saveData[$this->pk])) {
            $this->isUpdate = true;
        }
    }

    /**
     * 获取model
     * @return mixed
     */
    public function getModel()
    {
        return $this->modelClass;
    }

    /**
     * 获取错误信息
     * @return false|mixed|string
     */
    public function getError()
    {
        switch (true) {
            case (empty($this->error)):
                return '';
                break;
            case (is_string($this->error)):
                return $this->error;
                break;
            case (is_array($this->error) && isset($this->error['msg'])):
                return $this->error['msg'];
                break;
            case (is_array($this->error)):
                return json_encode($this->error);
                break;
            default:
                return json_encode($this->error);
        }
    }

    /**
     * 判断是否关联数组
     * @param array $array
     * @return bool
     */
    protected function isAssoc($array = [])
    {
        $keys = array_keys($array);

        return $keys != array_keys($keys);
    }
}
