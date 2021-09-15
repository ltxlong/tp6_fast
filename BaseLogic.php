<?php

namespace app;


use app\common\traits\CommonTrait;
use think\App;

/**
 * 逻辑基类
 *
 * logic继承了这个基类后，在使用$this->logic()->的时候，让调用的方法可以ctrl+单击跳转了
 */
class BaseLogic
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @access public
     * @var App $app 应用对象
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        // 逻辑初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * @param $name
     * @return BaseModel|\think\App
     * @author longtaixuan
     * @email 416803647@qq.com
     */
    public function __get($name)
    {
        // 判断类别Model、Logic、Service、Validate
        // 先看common前缀，再看后缀
        // 有common的就去common找，非common的就先找当前应用
        // 非common的要首字母大写转换
        // 默认：
        // 存放的文件夹是model、Logic、service、validate
        // 除了model文件，Logic、service、Validate文件名添加后缀Logic、Service、Validate

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
            case strstr($name, 'Validate') === 'Validate':
                $suffix = 'Validate';
                break;
            default:
                $suffix = 'Model';
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
