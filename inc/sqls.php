<?php
if(!defined('IN_SYSTEM')) {
    exit('Access Denied');
}

class Database {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // 获取条目列表，支持分页和筛选
    public function getEntries($page = 1, $limit = 10, $search = [], $order = []) {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];
        $bindParams = [];
        
        if (!empty($search)) {
            foreach ($search as $key => $value) {
                if ($value !== '' && in_array($key, ['template_id', 'username', 'email', 'submit_ip'])) {
                    $paramName = ":" . $key;
                    $where[] = "`$key` LIKE $paramName";
                    $bindParams[$paramName] = "%$value%";
                }
            }
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // 安全处理排序字段
        $allowedFields = ['id', 'template_id', 'username', 'email', 'submit_date', 'submit_time', 'submit_ip', 'status'];
        $orderField = in_array($order['field'] ?? 'id', $allowedFields) ? $order['field'] : 'id';
        $orderDirection = isset($order['direction']) && strtoupper($order['direction']) === 'ASC' ? 'ASC' : 'DESC';
        $orderClause = "ORDER BY `$orderField` $orderDirection";
        
        // 获取总数
        $countSql = "SELECT COUNT(*) as total FROM `entries` $whereClause";
        $stmt = $this->pdo->prepare($countSql);
        foreach ($bindParams as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        $total = $stmt->fetch()['total'];
        
        // 获取数据
        $sql = "SELECT * FROM `entries` $whereClause $orderClause LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($bindParams as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll();
        
        return [
            'total' => $total,
            'data' => $data,
            'pages' => ceil($total / $limit)
        ];
    }
    
    // 获取单个条目及其附件
    public function getEntry($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM `entries` WHERE `id` = ?");
        $stmt->execute([$id]);
        $entry = $stmt->fetch();
        
        if ($entry) {
            // 获取附件信息
            $stmt = $this->pdo->prepare("
                SELECT `id`, `field_name`, `field_index`, `file_path`, `upload_time` 
                FROM `attachments` 
                WHERE `entry_id` = ? 
                ORDER BY `field_name`, `field_index` ASC
            ");
            $stmt->execute([$id]);
            $entry['attachments'] = $stmt->fetchAll();
            
            // 解析JSON字段
            if (isset($entry['idesc'])) {
                $entry['idesc'] = json_decode($entry['idesc'], true) ?: [];
            }
        }
        
        return $entry;
    }
    
    // 删除条目及其附件
    public function deleteEntry($id) {
        try {
            $this->pdo->beginTransaction();
            
            // 获取附件信息
            $stmt = $this->pdo->prepare("SELECT `file_path` FROM `attachments` WHERE `entry_id` = ?");
            $stmt->execute([$id]);
            $attachments = $stmt->fetchAll();
            
            // 删除物理文件
            foreach ($attachments as $attachment) {
                $filepath = $attachment['file_path'];
                if (file_exists($filepath) && is_file($filepath)) {
                    unlink($filepath);
                    
                    // 尝试删除空目录
                    $dir = dirname($filepath);
                    if (is_dir($dir) && count(glob("$dir/*")) === 0) {
                        rmdir($dir);
                    }
                }
            }
            
            // 删除附件记录
            $stmt = $this->pdo->prepare("DELETE FROM `attachments` WHERE `entry_id` = ?");
            $stmt->execute([$id]);
            
            // 删除主记录
            $stmt = $this->pdo->prepare("DELETE FROM `entries` WHERE `id` = ?");
            $stmt->execute([$id]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    // 更新条目状态
    public function updateEntryStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE `entries` SET `status` = ? WHERE `id` = ?");
        return $stmt->execute([(int)$status, $id]);
    }
    
    // 获取统计数据
    public function getStats() {
        $stats = [];
        
        // 总提交数
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM `entries`");
        $stats['total'] = $stmt->fetch()['total'];
        
        // 按模板统计
        $stmt = $this->pdo->query("
            SELECT `template_id`, COUNT(*) as count 
            FROM `entries` 
            GROUP BY `template_id`
        ");
        $stats['by_template'] = $stmt->fetchAll();
        
        // 按状态统计
        $stmt = $this->pdo->query("
            SELECT `status`, COUNT(*) as count 
            FROM `entries` 
            GROUP BY `status`
        ");
        $stats['by_status'] = $stmt->fetchAll();
        
        return $stats;
    }
}
