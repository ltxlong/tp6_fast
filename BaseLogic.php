<?php

namespace app;


use app\common\traits\CommonTrait;

/**
 * 逻辑基类
 *
 * logic继承了这个基类后，在使用$this->logic()->的时候，让调用的方法可以ctrl+单击跳转了
 */
class BaseLogic
{
    use CommonTrait;
}