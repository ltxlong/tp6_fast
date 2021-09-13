<?php
declare (strict_types = 1);

namespace app;

use app\common\traits\CommonTrait;
use think\App;
use think\exception\ValidateException;
use think\Validate;


/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * @param $name
     * @return BaseModel|BaseLogic|App
     * @author longtaixuan
     * @email 416803647@qq.com
     */
    public function __get($name)
    {
        // 判断类别Model、Logic、Service
        // 先看common前缀，再看后缀
        // 有common的就去common找，非common的就先找当前应用
        // 非common的要首字母大写转换
        // 默认：
        // 存放的文件夹是model、logic、service
        // 除了model文件，logic和service文件名分别添加后缀Logic和Service

        $isCommon = false;
        if (strpos($name, 'common') === 0) {
            $isCommon = true;
        }
        switch ($name) {
            case strstr($name, 'Model') === 'Model':
                $suffix = 'Model';
                break;
            case strstr($name, 'Logic') === 'Logic':
                $suffix = 'Logic';
                break;
            case strstr($name, 'Service') === 'Service':
                $suffix = 'Service';
                break;
            default:
                $suffix = 'Logic';
        }
        if ($isCommon) {
            $name = str_replace(['common', $suffix], '', $name);
            $classPath = 'app\common\\' . lcfirst($suffix) . '\\' . $name;
        } else {
            $name = str_replace($suffix, '', $name);
            $name = ucfirst($name);
            $module = app('http')->getName();
            $classPath = 'app\\' . $module . '\\' . lcfirst($suffix) . '\\' . $name;
        }

        if ($suffix !== 'Model') {
            $classPath .= $suffix;
        }

        return app($classPath);
    }

    use CommonTrait;
}
