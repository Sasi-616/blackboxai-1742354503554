<?php
require_once __DIR__ . '/../db.php';

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = true;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Find a record by its primary key
    public function find($id) {
        try {
            return $this->db->select($this->table, '*', "{$this->primaryKey} = ?", [$id])->fetch();
        } catch (Exception $e) {
            $this->handleError('Find failed', $e);
            return false;
        }
    }

    // Get all records
    public function all($orderBy = null) {
        try {
            $order = $orderBy ?: "{$this->primaryKey} DESC";
            return $this->db->select($this->table, '*', '', [], $order)->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get all failed', $e);
            return [];
        }
    }

    // Create a new record
    public function create(array $data) {
        try {
            $data = $this->filterFillable($data);
            
            if ($this->timestamps) {
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
            }

            $id = $this->db->insert($this->table, $data);
            return $this->find($id);
        } catch (Exception $e) {
            $this->handleError('Create failed', $e);
            return false;
        }
    }

    // Update a record
    public function update($id, array $data) {
        try {
            $data = $this->filterFillable($data);
            
            if ($this->timestamps) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }

            $affected = $this->db->update(
                $this->table,
                $data,
                "{$this->primaryKey} = ?",
                [$id]
            );

            return $affected > 0;
        } catch (Exception $e) {
            $this->handleError('Update failed', $e);
            return false;
        }
    }

    // Delete a record
    public function delete($id) {
        try {
            return $this->db->delete($this->table, "{$this->primaryKey} = ?", [$id]) > 0;
        } catch (Exception $e) {
            $this->handleError('Delete failed', $e);
            return false;
        }
    }

    // Find records by a where clause
    public function where($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        try {
            return $this->db->select(
                $this->table,
                '*',
                "$column $operator ?",
                [$value]
            )->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Where query failed', $e);
            return [];
        }
    }

    // Find first record by a where clause
    public function firstWhere($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        try {
            return $this->db->select(
                $this->table,
                '*',
                "$column $operator ?",
                [$value],
                '',
                '1'
            )->fetch();
        } catch (Exception $e) {
            $this->handleError('First where query failed', $e);
            return false;
        }
    }

    // Count records
    public function count($where = '', $params = []) {
        try {
            $result = $this->db->select(
                $this->table,
                'COUNT(*) as count',
                $where,
                $params
            )->fetch();
            return (int) $result['count'];
        } catch (Exception $e) {
            $this->handleError('Count failed', $e);
            return 0;
        }
    }

    // Paginate results
    public function paginate($page = 1, $perPage = 10, $where = '', $params = []) {
        try {
            $offset = ($page - 1) * $perPage;
            $total = $this->count($where, $params);
            
            $items = $this->db->select(
                $this->table,
                '*',
                $where,
                $params,
                "{$this->primaryKey} DESC",
                "$offset, $perPage"
            )->fetchAll();

            return [
                'data' => $items,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage)
            ];
        } catch (Exception $e) {
            $this->handleError('Pagination failed', $e);
            return [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1
            ];
        }
    }

    // Filter data to only include fillable fields
    protected function filterFillable(array $data) {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    // Handle errors
    protected function handleError($message, Exception $e) {
        error_log("$message: " . $e->getMessage());
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            throw $e;
        }
    }

    // Get table name
    public function getTable() {
        return $this->table;
    }

    // Get primary key
    public function getPrimaryKey() {
        return $this->primaryKey;
    }

    // Get fillable fields
    public function getFillable() {
        return $this->fillable;
    }
}
