<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TaskCategory extends CI_Controller{
  private $formValidationRules;

  public function __construct(){
    parent::__construct();
    $this->load->model("taskCategoryModel");
    $this->formValidationRules = [
      [
        'field' => 'name',
        'label' => 'Name',
        'rules' => 'required|max_length[255]',
        'errors' => [
          'required' => 'Kategori harus memiliki nama',
          'max_length' => 'Panjang nama kategori maksimal 255 karakter',
        ]
      ]
    ];
  }

  public function index(){
    $paginate = !$this->input->get('no_pagination') ?
      [
        'page' => +$this->input->get('page') ?: null,
        'limit' => +$this->input->get('limit') ?: null,
      ] : null;       

    echo view_json($this->taskCategoryModel->all('*', $paginate, [
      'name' => $this->input->get('name') ?: null
    ]));
  }

  public function show($id){
    $data = $this->taskCategoryModel->find($id);
    if(!$data){
      echo view_json(['message' => 'Not Found'], 404);
      return;
    }
    echo view_json($data);
  }
  
  public function create(){
    $data = (array) get_input_json();

    $this->form_validation->set_data($data);

    $this->form_validation->set_rules($this->formValidationRules);

    if(!$this->form_validation->run()){
      echo view_body_invalid($this->form_validation->error_array());
      return;
    }

    echo view_json($this->taskCategoryModel->add($data), 201);
  }

  public function update($id){
    $data = (array) get_input_json();

    if(!$this->taskCategoryModel->exists($id)){
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

    $this->taskCategoryModel->edit($id, $data);

    http_response_code(204);
  }

  public function edit($id){
    $data = (array) get_input_json();

    if(!$this->taskCategoryModel->exists($id)){
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

    $this->taskCategoryModel->edit($id, $data);

    http_response_code(204);
  }

  public function destroy($id){
    if(!$this->taskCategoryModel->exists($id)){
      echo view_json([
        'message' => 'Not Found'
      ], 404);
      return;
    }

    $this->taskCategoryModel->delete($id);
    http_response_code(204);
  }
}

?>