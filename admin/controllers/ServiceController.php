<?php
require_once __DIR__ . '/BaseController.php';

class ServiceController extends BaseController {
    
    public function index() {
        $page = max(1, (int)input('page', 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        
        $where = '1=1';
        $params = [];
        
        $keyword = input('keyword', '');
        if ($keyword) {
            $where .= ' AND s.name LIKE ?';
            $params[] = "%$keyword%";
        }
        
        $category_id = (int)input('category_id', 0);
        if ($category_id > 0) {
            $where .= ' AND s.category_id = ?';
            $params[] = $category_id;
        }
        
        $status = input('status', '');
        if ($status !== '') {
            $where .= ' AND s.status = ?';
            $params[] = $status;
        }
        
        $total = $this->db->fetch("SELECT COUNT(*) as total FROM tc_service s WHERE $where", $params)['total'];
        $list = $this->db->fetchAll(
            "SELECT s.*, sc.name as category_name 
             FROM tc_service s 
             LEFT JOIN tc_service_category sc ON s.category_id = sc.id 
             WHERE $where 
             ORDER BY s.sort ASC, s.id DESC 
             LIMIT $offset, $pageSize",
            $params
        );
        
        $categories = $this->db->fetchAll("SELECT * FROM tc_service_category WHERE parent_id = 0 ORDER BY sort ASC");
        
        $this->layout('service/index', [
            'title' => '服务项目',
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'keyword' => $keyword,
            'category_id' => $category_id,
            'status' => $status,
            'categories' => $categories
        ]);
    }
    
    public function add() {
        if (isPost()) {
            $images = input('images', '');
            $imagesArray = array_filter(explode(',', $images));
            
            $data = [
                'category_id' => (int)input('category_id', 0),
                'merchant_id' => (int)input('merchant_id', 0),
                'name' => input('name', ''),
                'cover' => input('cover', ''),
                'images' => json_encode($imagesArray),
                'price' => (float)input('price', 0),
                'duration' => (int)input('duration', 0),
                'description' => input('description', ''),
                'notice' => input('notice', ''),
                'is_hot' => (int)input('is_hot', 0),
                'is_recommend' => (int)input('is_recommend', 0),
                'service_type' => (int)input('service_type', 3),
                'dispatch_type' => (int)input('dispatch_type', 3),
                'sort' => (int)input('sort', 0),
                'status' => (int)input('status', 1),
                'created_at' => time(),
                'updated_at' => time()
            ];
            
            if (empty($data['name'])) {
                jsonError('请输入服务名称');
            }
            if ($data['category_id'] <= 0) {
                jsonError('请选择服务分类');
            }
            
            $id = $this->db->insert('tc_service', $data);
            if ($id) {
                $specNames = input('spec_name/a', []);
                $specPrices = input('spec_price/a', []);
                $specOriginalPrices = input('spec_original_price/a', []);
                $specDurations = input('spec_duration/a', []);
                
                if (!empty($specNames)) {
                    foreach ($specNames as $k => $name) {
                        if (!empty($name)) {
                            $this->db->insert('tc_service_spec', [
                                'service_id' => $id,
                                'name' => $name,
                                'price' => (float)($specPrices[$k] ?? 0),
                                'original_price' => (float)($specOriginalPrices[$k] ?? 0),
                                'duration' => (int)($specDurations[$k] ?? 0),
                                'sort' => $k,
                                'status' => 1,
                                'created_at' => time()
                            ]);
                        }
                    }
                }
                
                $cityIds = input('city_ids/a', []);
                if (!empty($cityIds)) {
                    foreach ($cityIds as $cityId) {
                        $this->db->insert('tc_city_service', [
                            'city_id' => (int)$cityId,
                            'service_id' => $id,
                            'sort' => 0,
                            'created_at' => time()
                        ]);
                    }
                }
                
                jsonSuccess(['id' => $id], '添加成功');
            }
            jsonError('添加失败');
        }
        
        $categories = $this->db->fetchAll("SELECT * FROM tc_service_category WHERE parent_id = 0 ORDER BY sort ASC");
        $subCategories = $this->db->fetchAll("SELECT * FROM tc_service_category WHERE parent_id > 0 ORDER BY sort ASC");
        $cities = $this->db->fetchAll("SELECT * FROM tc_city WHERE status = 1 ORDER BY sort ASC");
        $merchants = $this->db->fetchAll("SELECT * FROM tc_merchant WHERE status = 1 ORDER BY id DESC");
        
        $this->layout('service/form', [
            'title' => '添加服务',
            'info' => null,
            'specs' => [],
            'serviceCities' => [],
            'categories' => $categories,
            'subCategories' => $subCategories,
            'cities' => $cities,
            'merchants' => $merchants
        ]);
    }
    
    public function edit() {
        $id = (int)input('id', 0);
        $info = $this->db->fetch("SELECT * FROM tc_service WHERE id = ?", [$id]);
        if (!$info) {
            die('服务不存在');
        }
        
        if (isPost()) {
            $images = input('images', '');
            $imagesArray = array_filter(explode(',', $images));
            
            $data = [
                'category_id' => (int)input('category_id', 0),
                'merchant_id' => (int)input('merchant_id', 0),
                'name' => input('name', ''),
                'cover' => input('cover', ''),
                'images' => json_encode($imagesArray),
                'price' => (float)input('price', 0),
                'duration' => (int)input('duration', 0),
                'description' => input('description', ''),
                'notice' => input('notice', ''),
                'is_hot' => (int)input('is_hot', 0),
                'is_recommend' => (int)input('is_recommend', 0),
                'service_type' => (int)input('service_type', 3),
                'dispatch_type' => (int)input('dispatch_type', 3),
                'sort' => (int)input('sort', 0),
                'status' => (int)input('status', 1),
                'updated_at' => time()
            ];
            
            if (empty($data['name'])) {
                jsonError('请输入服务名称');
            }
            
            $this->db->update('tc_service', $data, 'id = :id', ['id' => $id]);
            
            $this->db->delete('tc_service_spec', 'service_id = ?', [$id]);
            $specNames = input('spec_name/a', []);
            $specPrices = input('spec_price/a', []);
            $specOriginalPrices = input('spec_original_price/a', []);
            $specDurations = input('spec_duration/a', []);
            
            if (!empty($specNames)) {
                foreach ($specNames as $k => $name) {
                    if (!empty($name)) {
                        $this->db->insert('tc_service_spec', [
                            'service_id' => $id,
                            'name' => $name,
                            'price' => (float)($specPrices[$k] ?? 0),
                            'original_price' => (float)($specOriginalPrices[$k] ?? 0),
                            'duration' => (int)($specDurations[$k] ?? 0),
                            'sort' => $k,
                            'status' => 1,
                            'created_at' => time()
                        ]);
                    }
                }
            }
            
            $this->db->delete('tc_city_service', 'service_id = ?', [$id]);
            $cityIds = input('city_ids/a', []);
            if (!empty($cityIds)) {
                foreach ($cityIds as $cityId) {
                    $this->db->insert('tc_city_service', [
                        'city_id' => (int)$cityId,
                        'service_id' => $id,
                        'sort' => 0,
                        'created_at' => time()
                    ]);
                }
            }
            
            jsonSuccess(null, '修改成功');
        }
        
        $categories = $this->db->fetchAll("SELECT * FROM tc_service_category WHERE parent_id = 0 ORDER BY sort ASC");
        $subCategories = $this->db->fetchAll("SELECT * FROM tc_service_category WHERE parent_id > 0 ORDER BY sort ASC");
        $cities = $this->db->fetchAll("SELECT * FROM tc_city WHERE status = 1 ORDER BY sort ASC");
        $merchants = $this->db->fetchAll("SELECT * FROM tc_merchant WHERE status = 1 ORDER BY id DESC");
        $specs = $this->db->fetchAll("SELECT * FROM tc_service_spec WHERE service_id = ? ORDER BY sort ASC", [$id]);
        $serviceCities = $this->db->fetchAll("SELECT city_id FROM tc_city_service WHERE service_id = ?", [$id]);
        $serviceCityIds = array_column($serviceCities, 'city_id');
        
        $this->layout('service/form', [
            'title' => '编辑服务',
            'info' => $info,
            'specs' => $specs,
            'serviceCityIds' => $serviceCityIds,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'cities' => $cities,
            'merchants' => $merchants
        ]);
    }
    
    public function delete() {
        $id = (int)input('id', 0);
        $this->db->delete('tc_service', 'id = ?', [$id]);
        $this->db->delete('tc_service_spec', 'service_id = ?', [$id]);
        $this->db->delete('tc_city_service', 'service_id = ?', [$id]);
        jsonSuccess(null, '删除成功');
    }
}
