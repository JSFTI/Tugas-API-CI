<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TaskCategoryModel extends CI_Model{
  public function all($selects, $paginate = null, $filters = null){
    $this->db->start_cache();

    if($filters){
      extract($filters);

      if(isset($name)){
        $this->db->like('name', $name, 'both');
      }

      if(isset($ids)){
        $this->db->where_in('id', $ids);
      }
    }

    $this->db->stop_cache();

    if($paginate && is_array($paginate)){
      $page = $paginate['page'] ?: 1;
      $limit = $paginate['limit'] ?: 10;
      
      $allCount = $this->db->count_all_results('task_categories');

      $this->db->select($selects ?? '*')->from('task_categories');

      $this->db->limit($limit)->offset(($page - 1) * $limit);
    
      $data = $this->db->get()->result();

      $this->db->flush_cache();

      return (object) [
        'current_page' => $page,
        'count' => $allCount,
        'last_page' => ceil($allCount / $limit),
        'data' => $data,
      ];
    }

    $this->db->select($selects ?: '*')->from('task_categories');

    $data = $this->db->get()->result();

    $this->db->flush_cache();

    return $data;
  }

  public function find($id, $selects = null){
    return $this->db->select($selects ?: '*')->where('id', $id)
      ->from('task_categories')->get()->first_row();
  }

  public function exists($id){
    return !!$this->db->select(['id'])->where('id', $id)
      ->from('task_categories')->get()->first_row();
  }

  public function add($data){
    $this->db->insert("task_categories", $data);
    return $this->find($this->db->insert_id());
  }

  public function edit($id, $data){
    $this->db->set($data);
    $this->db->where('id', $id);
    $this->db->update('task_categories');
  }

  public function delete($ids){
    if(!is_array($ids)){
      $ids = [$ids];
    }
    $this->db->where_in('id', $ids);
    $this->db->delete('task_categories');
  }
}
?>