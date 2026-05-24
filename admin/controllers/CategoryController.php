<?php
require_once __DIR__ . '/BaseController.php';

class CategoryController extends BaseController {
    
    public function index() {
        $list = $this->db->fetchAll("SELECT * FROM tc_service_category ORDER BY sort ASC, id DESC");
        
        $categories = [];
        foreach ($list as $item) {
            if ($item['parent_id'] == 0) {
                $categories[$item['id']] = $item;
                $categories[$item['id']]['children'] = [];
            }
        }
        foreach ($list as $item) {
            if ($item['parent_id'] > 0 && isset($categories[$item['parent_id']])) {
                $categories[$item['parent_id']]['children'][] = $item;
            }
        }
        
        $this->layout('category/index', [
            'title' => '服务分类',
            'categories' => $categories,
            'list' => $list
        ]);
    }
    
    public function add() {
        if (isPost()) {
            $data = [
                'parent_id' => (int)input('parent_id', 0),
                'name' => input('name', ''),
                'icon' => input('icon', ''),
                'image' => input('image', ''),
                'sort' => (int)input('sort', 0),
                'status' => (int)input('status', 1),
                'created_at' => time(),
                'updated_at' => time()
            ];
            
            if (empty($data['name'])) {
                jsonError('请输入分类名称');
            }
            
            $id = $this->db->insert('tc_service_category', $data);
            if ($id) {
                jsonSuccess(['id' => $id], '添加成功');
            }
            jsonError('添加失败');
        }
        
        $parentCategories = $this->db->fetchAll("SELECT * FROM tc_service_category WHERE parent_id = 0 ORDER BY sort ASC");
        $this->layout('category/form', [
            'title' => '添加分类',
            'info' => null,
            'parentCategories' => $parentCategories
        ]);
    }
    
    public function edit() {
        $id = (int)input('id', 0);
        $info = $this->db->fetch("SELECT * FROM tc_service_category WHERE id = ?", [$id]);
        if (!$info) {
            die('分类不存在');
        }
        
        if (isPost()) {
            $data = [
                'parent_id' => (int)input('parent_id', 0),
                'name' => input('name', ''),
                'icon' => input('icon', ''),
                'image' => input('image', ''),
                'sort' => (int)input('sort', 0),
                'status' => (int)input('status', 1),
                'updated_at' => time()
            ];
            
            if (empty($data['name'])) {
                jsonError('请输入分类名称');
            }
            
            $this->db->update('tc_service_category', $data, 'id = :id', ['id' => $id]);
            jsonSuccess(null, '修改成功');
        }
        
        $parentCategories = $this->db->fetchAll("SELECT * FROM tc_service_category WHERE parent_id = 0 AND id != ? ORDER BY sort ASC", [$id]);
        $this->layout('category/form', [
            'title' => '编辑分类',
            'info' => $info,
            'parentCategories' => $parentCategories
        ]);
    }
    
    public function delete() {
        $id = (int)input('id', 0);
        $hasChild = $this->db->fetch("SELECT COUNT(*) as total FROM tc_service_category WHERE parent_id = ?", [$id])['total'];
        if ($hasChild > 0) {
            jsonError('该分类下有子分类，无法删除');
        }
        $hasService = $this->db->fetch("SELECT COUNT(*) as total FROM tc_service WHERE category_id = ?", [$id])['total'];
        if ($hasService > 0) {
            jsonError('该分类下有服务项目，无法删除');
        }
        $this->db->delete('tc_service_category', 'id = ?', [$id]);
        jsonSuccess(null, '删除成功');
    }
}
