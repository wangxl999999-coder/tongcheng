<?php
require_once __DIR__ . '/BaseController.php';

class CityController extends BaseController {
    
    public function index() {
        $page = max(1, (int)input('page', 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        
        $where = '1=1';
        $params = [];
        
        $keyword = input('keyword', '');
        if ($keyword) {
            $where .= ' AND name LIKE ?';
            $params[] = "%$keyword%";
        }
        
        $status = input('status', '');
        if ($status !== '') {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        
        $total = $this->db->fetch("SELECT COUNT(*) as total FROM tc_city WHERE $where", $params)['total'];
        $list = $this->db->fetchAll("SELECT * FROM tc_city WHERE $where ORDER BY sort ASC, id DESC LIMIT $offset, $pageSize", $params);
        
        $this->layout('city/index', [
            'title' => '城市管理',
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'keyword' => $keyword,
            'status' => $status
        ]);
    }
    
    public function add() {
        if (isPost()) {
            $data = [
                'name' => input('name', ''),
                'pinyin' => input('pinyin', ''),
                'letter' => input('letter', ''),
                'sort' => (int)input('sort', 0),
                'is_hot' => (int)input('is_hot', 0),
                'status' => (int)input('status', 1),
                'created_at' => time(),
                'updated_at' => time()
            ];
            
            if (empty($data['name'])) {
                jsonError('请输入城市名称');
            }
            
            $id = $this->db->insert('tc_city', $data);
            if ($id) {
                jsonSuccess(['id' => $id], '添加成功');
            }
            jsonError('添加失败');
        }
        $this->layout('city/form', ['title' => '添加城市', 'info' => null]);
    }
    
    public function edit() {
        $id = (int)input('id', 0);
        $info = $this->db->fetch("SELECT * FROM tc_city WHERE id = ?", [$id]);
        if (!$info) {
            die('城市不存在');
        }
        
        if (isPost()) {
            $data = [
                'name' => input('name', ''),
                'pinyin' => input('pinyin', ''),
                'letter' => input('letter', ''),
                'sort' => (int)input('sort', 0),
                'is_hot' => (int)input('is_hot', 0),
                'status' => (int)input('status', 1),
                'updated_at' => time()
            ];
            
            if (empty($data['name'])) {
                jsonError('请输入城市名称');
            }
            
            $this->db->update('tc_city', $data, 'id = :id', ['id' => $id]);
            jsonSuccess(null, '修改成功');
        }
        
        $this->layout('city/form', ['title' => '编辑城市', 'info' => $info]);
    }
    
    public function delete() {
        $id = (int)input('id', 0);
        $this->db->delete('tc_city', 'id = ?', [$id]);
        jsonSuccess(null, '删除成功');
    }
}
