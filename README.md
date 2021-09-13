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
