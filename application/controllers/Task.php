<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Traits\CustomValidators;

class Task extends CI_Controller{
  use CustomValidators;

  private $formValidationRules;

  public function __construct(){
    parent::__construct();
    $this->load->model('taskModel');

    $this->formValidationRules = [
      [
        'field' => 'category_id',
        'name' => 'Category ID',
        'rules' => 'required|callback_exists[task_categories.id]',
        'errors' => [
          'required' => 'Harap pilih kategori',
          'exists' => 'Kategori tidak ditemukan'
        ]
      ],
      [
        'field' => 'title',
        'name' => 'Title',
        'rules' => 'required|max_length[255]',
        'errors' => [
          'required' => 'Harap masukkan judul',
          'max_length' => 'Panjang judul maksimal 255 karakter'
        ]
      ],
      [
        'field' => 'description',
        'name' => 'Description',
        'rules' => 'max_length[65535]',
        'errors' => [
          'max_length' => 'Panjang deskripsi maksimal 65535 karakter'
        ]
      ],
      [
        'field' => 'start_date',
        'name' => 'Start Date',
        'rules' => 'required|callback_valid_date',
        'errors' => [
          'required' => 'Harap masukkan tanggal mulai',
          'valid_date' => 'Harap masukkan format tanggal sesuai ISO, YYYY-MM-DD'
        ]
      ],
      [
        'field' => 'finish_date',
        'name' => 'Finish Date',
        'rules' => 'required|callback_valid_date|callback_after_date_field[start_date]',
        'errors' => [
          'required' => 'Harap masukkan tanggal selesai',
          'valid_date' => 'Harap masukkan format tanggal sesuai ISO, YYYY-MM-DD',
          'after_date_field' => 'Tugas tidak dapat selesai sebelum dimulai'
        ]
      ],
      [
        'field' => 'status',
        'name' => 'Status',
        'rules' => 'required|in_list[New,On Progress,Finish]',
        'errors' => [
          'required' => 'Harap tentukan status tugas',
          'in_list' => 'Status hanya dapat diisi dengan "New", "On Progress", dan "Finish"',
        ]
      ]
    ];
  }

  public function index(){
    $status = $this->input->get('status');

    if($status){
      $status = array_filter($this->input->get('status'), fn($x) => in_array($x, ['New', 'On Progress', 'Finish']));
    }

    $paginate = !$this->input->get('no_pagination') ?
      [
        'page' => +$this->input->get('page') ?: null,
        'limit' => +$this->input->get('limit') ?: null,
      ] : null;


    echo view_json($this->taskModel->all('*', $paginate, $this->input->get('joins') ?: null, [
      'title' => $this->input->get('title') ?: null,
      'after_start_date' => $this->input->get('after_start_date') ?: null,
      'before_finish_date' => $this->input->get('before_finish_date') ?: null,
      'status' => $status
    ]));
  }

  public function show($id){
    $data = $this->taskModel->find($id, '*', $this->input->get('joins') ?: null);
    if(!$data){
      echo view_json(['message' => 'Not Found'], 404);
      return;
    }
    echo view_json($data);
  }

  public function create(){
    $data = (array) get_input_json();

    $this->form_validation->set_data($data);

    $this->form_validation->set_rules(array_filter($this->formValidationRules, fn($x) => $x['field'] !== 'status'));

    $filename = null;

    if(array_key_exists('doc', $_FILES)){
      $filename = add_hash_filename($_FILES['doc']['name']);
    }

    $this->load->library('upload', [
      'upload_path' => './upload/tasks',
      'allowed_types' => 'pdf|docx|doc|ppt|pptx|odt|xls|xlsx|html',
      'file_name' => $filename,
      'max_size' => 10240,
      'remove_spaces' => FALSE
    ]);

    if(!$this->form_validation->run() || !$this->upload->do_upload('doc')){
      $errors = $this->form_validation->error_array();
      if($this->upload->error_msg){
        $errors['doc'] = $this->upload->error_msg[0];
      }
      echo view_body_invalid($errors);
      return;
    }

    $data['doc_url'] = base_url('upload/tasks/' . rawurlencode($filename));

    echo view_json($this->taskModel->add($data), 201);
  }

  public function edit($id){
    $data = (array) get_input_json();

    if(!$this->taskModel->exists($id)){
      echo view_json([
        'message' => 'Not Found'
      ], 404);
      return;
    }

    $this->form_validation->set_data($data);

    $this->form_validation->set_rules(array_filter($this->formValidationRules, fn($x) => array_key_exists($x['field'], $data)));

    if(!$this->form_validation->run()){
      echo view_body_invalid($this->form_validation->error_array());
      return;
    }

    if(array_key_exists('doc', $_FILES)){
      $oldFileUrl = $this->taskModel->find($id, ['doc_url'])->doc_url;
      $explodedOldFileUrl = explode('/', $oldFileUrl);
      $filename = array_pop($explodedOldFileUrl);

      if(file_exists("./upload/tasks/$filename")){
        unlink("./upload/tasks/$filename");
      }

      $newFilename = null;

      if(array_key_exists('doc', $_FILES)){
        $newFilename = add_hash_filename($_FILES['doc']['name']);
      }

      $this->load->library('upload', [
        'upload_path' => './upload/tasks',
        'allowed_types' => 'pdf|docx|doc|ppt|pptx|odt|xls|xlsx|html',
        'file_name' => $newFilename,
        'max_size' => 10240,
        'remove_spaces' => FALSE
      ]);

      if(!$this->upload->do_upload('doc')){
        echo view_body_invalid([
          'doc' => $this->upload->error_msg[0]
        ]);
        return;
      }

      $data['doc_url'] = base_url('upload/tasks/' . rawurlencode($newFilename));
    }

    $this->taskModel->edit($id, $data);

    http_response_code(204);
  }

  public function update($id){
    $data = (array) get_input_json();

    if(!$this->taskModel->exists($id)){
      echo view_json([
        'message' => 'Not Found'
      ], 404);
      return;
    }

    $this->form_validation->set_data($data);
   
    $this->form_validation->set_rules($this->formValidationRules);

    if(!$this->form_validation->run()){
      echo view_body_invalid($this->form_validation->error_array());
      return;
    }

    $oldFileUrl = $this->taskModel->find($id, ['doc_url'])->doc_url;
    $explodedOldFileUrl = explode('/', $oldFileUrl);
    $filename = array_pop($explodedOldFileUrl);

    $newFilename = null;

    if(array_key_exists('doc', $_FILES)){
      $newFilename = add_hash_filename($_FILES['doc']['name']);
    }

    if(file_exists("./upload/tasks/$filename")){
      unlink("./upload/tasks/$filename");
    }

    $this->load->library('upload', [
      'upload_path' => './upload/tasks',
      'allowed_types' => 'pdf|docx|doc|ppt|pptx|odt|xls|xlsx|html',
      'file_name' => $newFilename,
      'max_size' => 10240,
      'remove_spaces' => FALSE
    ]);

    if(!$this->upload->do_upload('doc')){
      echo view_body_invalid([
        'doc' => $this->upload->error_msg[0]
      ]);
      return;
    }

    $data['doc_url'] = base_url('upload/tasks/' . rawurlencode($newFilename));

    $this->taskModel->edit($id, $data);

    http_response_code(204);
  }

  public function destroy($id){
    $task = $this->taskModel->find($id, ['doc_url']);

    if(!$task){
      echo view_json([
        'message' => 'Not Found'
      ], 404);
      return;
    }

    $explodedOldFileUrl = explode('/', $task->doc_url);
    $filename = array_pop($explodedOldFileUrl);

    if(file_exists("./upload/tasks/$filename")){
      unlink("./upload/tasks/$filename");
    }
      
    $this->taskModel->delete($id);
    http_response_code(204);
  }
}

?>