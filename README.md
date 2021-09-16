# tp6_fast
tp6的进一步封装，让代码优雅而简洁

文件各自放的位置，在文件的命名空间上

在控制器的时候，不用new Model/Logic/Service了，直接$this->xxxModel/xxxLogic/xxxService就可以调用相应的实例

在逻辑类的时候，不用new Model/Service了，直接$this->xxxModel/xxxService就可以调用相应的实例

想要代码提示（方法提示和跳转），就要在前面添加相应的注释：

```
use app\admin\logic\AlbumLogic;


/**
* @var AlbumLogic $albumLogic
*/
$albumLogic = $this->albumLogic;

$res = $albumLogic->add($data);

```

```
public function listPage()
    {

        $param = $this->request->only(
            [
                'name',
                'begin_time',
                'end_time',
                'folder',
                'page',
                'per_page'
            ]
        );

        $where = [];
        if (!empty($param['name'])) {
            $where[] = ['name', 'like', '%' . $param['name'] . '%'];
        }
        if (!empty($param['folder'])) {
            $where[] = ['folder', '=', $param['folder']];
        }
        if (!empty($param['begin_time'])) {
            $where[] = ['created_at', '>=', strtotime($param['begin_time'])];
        }
        if (!empty($param['end_time'])) {
            $where[] = ['created_at', '<=', strtotime($param['end_time'])];
        }
        $where[] = ['is_del', '=', 0];

        $list = $this->getListPage($this->albumModel, $where, '*', 'id desc', $param['per_page'] ?? 10);

        return retJson('', 0, $list);
    }
    
```


### __get()魔术方法的封装
- BaseController
- BaseLogic

#### 效果：

        可以在继承了BaseController/BaseLogic的类里，直接用$this->来实例化Model、Logic、Service、Validate

        必须带相应的后缀，如模型类必须带Model后缀
    
        如果想实例化common文件夹的，就要带上common前缀
    
        用驼峰命名法
    
        实例规则：如果带了common前缀就去common文件夹找；如果不带common前缀就在当前模块找

        底层是app()函数，不用担心在同一个函数里多次这样调用会浪费内存，因为获取到的是同一个实例

```injectablephp
    $this->loginModel // 获得当前应用的login模型实例
    
    $this->commonLogicModel // 获得common的login模型实例
    
    $this->loginLogic // 获得当前应用的login逻辑实例
    
    $this->commonLoginLogic // 获得common的login逻辑实例

    // 这样就不必要在类的构造器里一堆的new了

    // 小的缺点：这样获取的实例，不能够直接的获得代码提示，直接的代码提示只有BaseModel和BaseLogic的方法

    // 解决这个小的缺点：添加变量注释，下面的实例
    /**
    * @var \app\api\logic\LoginLogic $loginLogic 
    */
    $loginLogic = $this->loginLogic;
    // 这样用$loginLogic->就有正常的代码提示了
    // 上面的 @var \app\api\logic\LoginLogic $loginLogic 在代码里会是@var LoginLogic $loginLogic，并且添加use，并且可以自定义命名
    
    /**
    * @var \app\common\logic\ProjectLogic as commonProjectLogic $commonProjectLogic 
    */
    $commonProjectLogic = $this->commonProjectLogic;
    // 这样用$commonProjectLogic->就有正常的代码提示了
    // 上面的 @var \app\api\logic\ProjectLogic as commonProjectLogic $commonProjectLogic 在代码里会是@var commonProjectLogic $commonProjectLogic，并且添加use，并且可以自定义命名

```
### commonTrait的封装
- dataEdit() 获取DataEdit类实例
- class() 获取类的实例
- model() 获取模型类的实例
- logic() 获取逻辑类的实例
- buildParam() 构建参数
- getListPage() 获取分页列表
- getListOnly() 获取非分页列表

在BaseController和BaseLogic都use commonTrait，可以直接在继承了BaseController和BaseLogic的类或者use commonTrait的地方，用$this->来调用以上方法

其中，class()、model()、logic()方法的参数是类的路径，如 LoginLogic::class
```injectablephp
    $this->logic(\app\api\logic\LoginLogic::class)
    // 如果在一个类上使用，一般是设置私有属性来保存累的路径：
    private $commonProjectLogic = \app\common\logic\ProjectLogic::class;
    $this->logic($this->commonProjectLogic)
    // 或者
    $this->class($this->commonProjectLogic)
    // class()和model()、logic()的区别是，model()实例化的有BaseModel的方法提示，logic()实例化的有BaseLogic的方法提示
    
    // 分页列表快速获取：
    $list = $this->getListPage($this->commonProjectModel, $where, '*', 'id desc', $param['per_page'] ?? 10);
    // 因为tp6的分页会自动获取$param['page']参数，所以不用管这个page参数，但是，前端要传page参数，不传的话，默认page为1；per_page为每页数量
    return retJson('', 0, $list);
```

个人推荐，能用$this->来实例化，就不用$this->class()/model()/logic()，
毕竟$this->写法更加的简洁，不仅不用新增private属性，并且可以有代码提示

buildParam()的第二个参数，可以是现成的数组，也可以是接收数据的方法名，如param/post/get，会直接调用$this->request->post()来后去数据，默认param()

示例： buildParam($paramList, 'post')、buildParam($paramList, $data)

### DataEdit类的封装
- 简化代码
- 快速开发

```injectablephp
    /**
     * 添加分类
     * @return false
     * @throws \app\common\lib\exception\ApiException
     */
    public function add()
    {
        $param = $this->request->only(
            [
                'name'
            ]
        );

        $validate = [
            'name' => 'require'
        ];

        return $this->dataEdit()
            ->setData($param)
            ->setValidate($validate)
            ->setModel($this->commonProjectClassModel)
            ->saveAll();
    }

    // 或者
    
    /**
     * 添加分类
     * @return false
     * @throws \app\common\lib\exception\ApiException
     */
    public function add()
    {
        $paramList = [
            'name/s'
        ];

        $validate = [
            'name' => 'require'
        ];

        return $this->dataEdit()
            ->setParam($paramList)
            ->setValidate($validate)
            ->setModel($this->commonProjectClassModel)
            ->saveAll();
    }
```

```injectablephp
    参数设置：
    setParam()方法配合 $paramList数组
    $paramList = [
        'name' => 'my_name/s',
        'age' => 'my_age/d',
        'desc/s'
    ];
    $paramList的key是允许接收的，并且要用的参数名；value是前端传过来的参数名，并且可以添加类型限制，是tp6的
    
    setParam()的第二个参数是接收数据的方法名，如param/post/get，会直接调用$this->request->post()来后去数据，默认param()
    示例：setParam($paramList, 'post')
    
    如果不需要参数名转换，不需要强制类型限制，那么直接用：
    setData()方法配合 $this->request->only([]); 比较快和简洁
    
    如果要追加格外的参数，可以用setAppend([])方法，直接链式调用
    
    还有单个参数相关的方法：addParam()、addAppend()，具体看源码注释
    
```

```injectablephp
    参数校验
    setValidate()
    这个方法的参数，可以是Validate校验器的路径，也可以是rule数组
```

```injectablephp
    模型设置
    setModel()
    这个方法的参数，可以是模型实例，也可以是模型类的路径
```

```injectablephp
    模型操作
    save() -- 自动识别新增、更新
    allowField([]) -- 允许保存/更新的参数
    add() -- 新增
    update() -- 更新
    setUpdateMap([]) -- 更新条件
    saveAll() -- 自动识别新增、更新
    execModelAction('') -- 执行模型的方法，参数是方法名；而这个模型的方法的参数是设置的数据数组
    
    关于saveAll()，无论是配合用setParam()还是setData()，都会自动判断是否数据数组
```

- 结果返回：可以直接用if来判断成功和失败

