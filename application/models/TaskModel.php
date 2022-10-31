<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TaskModel extends CI_Model{
  private function join_category(&$tasks){
    $this->load->model('taskCategoryModel');

    if(is_array($tasks)){
      $categories = $this->taskCategoryModel->all('*', null, [
        'ids' => array_map(fn($x) => $x->category_id ?? 0, $tasks)
      ]);
      for($i = 0; $i < count($tasks); $i++){
        $tasks[$i]->category = array_filter($categories, fn($x) => $x->id === $tasks[$i]->category_id)[0];
      }
      return;
    }

    $tasks->category = $this->taskCategoryModel->find($tasks->category_id ?? 0);
  }

  public function all($selects = null, $paginate = null, $joins = [], $filters = null){
    $this->db->start_cache();

    if($filters){
      extract($filters);

      if(isset($title)){
        $this->db->like('title', $title, 'both');
      }
  
      if(isset($after_start_date)){
        $this->db->where('start_date >=', $after_start_date);
      }
      
  
      if(isset($before_finish_date)){
        $this->db->where('finish_date <=', $before_finish_date);
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

      if($joins){
        if(in_array('category', $joins)){
          $this->join_category($data);
        }
      }

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

    if($joins && is_array($joins)){
      if(in_array('category', $joins)){
        $this->join_category($data);
      } 
    }

    return $data;
  }

  public function find($id, $selects = null, $joins = null){
    $data = $this->db->select($selects ?: '*')->where('id', $id)
      ->from('tasks')->get()->first_row();

    if($joins && is_array($joins)){
      if(in_array('category', $joins)){
        $this->join_category($data);
      }
    }

    return $data;
  }

  public function exists($id){
    return !!$this->db->select(['id'])->where('id', $id)
      ->from('tasks')->get()->first_row();
  }

  public function add($data){
    $this->db->insert("tasks", $data);
    return $this->find($this->db->insert_id());
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