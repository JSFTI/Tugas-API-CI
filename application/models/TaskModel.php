<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TaskModel extends CI_Model{
  public function all($selects = null, $paginate = null, $filters = null){
    $this->db->start_cache();

    if($filters){
      extract($filters);

      if(isset($title)){
        $this->db->like('title', $title, 'both');
      }
  
      if(isset($after_start_date)){
        $this->db->where('start_date >=', $after_start_date);
      }
      
  
      if(isset($finish_date)){
        $this->db->where('finish_date <=', $finish_date);
      }
  
      if(isset($status)){
        if(!is_array($status)){
          $status = [$status];
        }
  
        $this->db->where_in('status', $status);      
      }
    }

    $this->db->stop_cache();

    if($paginate && is_array($paginate)){
      $page = $paginate['page'] ?: 1;
      $limit = $paginate['limit'] ?: 10;
      
      $allCount = $this->db->count_all_results('tasks');

      $this->db->select($selects ?? '*')->from('tasks');

      $this->db->limit($limit)->offset(($page - 1) * $limit);

      $data = $this->db->get()->result();
    
      $this->db->flush_cache();

      return (object) [
        'current_page' => $page,
        'count_data' => $allCount,
        'last_page' => ceil($allCount / $limit),
        'data' => $data,
      ];
    }

    $this->db->select($selects ?: '*')->from('tasks');

    $data = $this->db->get()->result();

    $this->db->flush_cache();

    return $data;
  }

  public function find($id, $selects = null){
    return $this->db->select($selects ?: '*')->where('id', $id)
      ->from('tasks')->get()->first_row();
  }

  public function exists($id){
    return !!$this->db->select(['id'])->where('id', $id)
      ->from('tasks')->get()->first_row();
  }

  public function add($data){
    $this->db->insert("tasks", $data);
    $data['id'] = $this->db->insert_id();
    return $data;
  }

  public function edit($id, $data){
    $this->db->set($data);
    $this->db->where('id', $id);
    $this->db->update('tasks');
  }

  public function delete($ids){
    if(!is_array($ids)){
      $ids = [$ids];
    }
    $this->db->where_in('id', $ids);
    $this->db->delete('tasks');
  }
}

?>