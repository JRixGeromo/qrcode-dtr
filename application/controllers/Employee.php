<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class employee extends CI_Controller{

  public function __construct()
  {
      parent::__construct();
	  $this->load->helper('url');
      $this->load->model('employee_model');
  }

  public function index()
  {
      $this->load->view('employee/index');
  }

  public function ajax_list()
  {
      $list = $this->employee_model->get_datatables();
      $data = array();
      $no = $_POST['start'];
      foreach ($list as $employee) {
          $no++;
          $row = array();
          $row[] = '<input type="checkbox" class="data-check" value="'.$employee->emp_id.'" onclick="showBottomDelete()"/>';
          $row[] = $employee->fname;
          $row[] = $employee->lname;
          //$row[] = "<img src='".base_url()."qr-dtr/barcodes/".$employee->emp_id.".png'>";
          $row[] = "<img src='".base_url()."qr-dtr/qrcodes/".$employee->qr_code."' width=100px>";
          
          //add html for action
          $row[] = '<a class="btn btn-sm btn-primary" href="#" title="Edit" onclick="editemployee('."'".$employee->emp_id."'".')"><i class="glyphicon glyphicon-pencil"></i> Edit</a>
                <a class="btn btn-sm btn-danger" href="#" title="Delete" onclick="deleteemployee('."'".$employee->emp_id."'".')"><i class="glyphicon glyphicon-trash"></i> Delete</a>';
          $data[] = $row;
      }
      $output = array(
                      "draw" => $_POST['draw'],
                      "recordsTotal" => $this->employee_model->count_all(),
                      "recordsFiltered" => $this->employee_model->count_filtered(),
                      "data" => $data,
              );
      //output to json format
      echo json_encode($output);
  }

  public function ajax_edit($id)
  {
      $data = $this->employee_model->get_by_id($id);
      echo json_encode($data);
  }

  function generate_qrcode($id)
  {
      /* Load QR Code Library */
      $this->load->library('ciqrcode');
    
      /* Data */
      $save_name  = $id.'.png'; // this naming is temporary, this can still be secured by bin2hex($id) as an example

      $data = array(
        'qr_code' => $save_name,
        );
      $this->employee_model->update(array('emp_id' => $id), $data);

      /* QR Code File Directory Initialize */
      $dir = 'qrcodes/';
      if (!file_exists($dir)) {
            mkdir($dir, 0775, true);
      }

      /* QR Configuration  */
      $config['cacheable']    = true;
      $config['imagedir']     = $dir;
      $config['quality']      = true;
      $config['size']         = '1024';
      $config['black']        = array(255,255,255);
      $config['white']        = array(255,255,255);
      $this->ciqrcode->initialize($config);

      /* QR Data  */
      $params['data']     = $id;
      $params['level']    = 'L';
      $params['size']     = 10;
      $params['savename'] = FCPATH.$config['imagedir']. $save_name;
    
      $this->ciqrcode->generate($params);
  }

  public function ajax_add()
  {
      $this->_validate();

      $data = array(
              'fname' => $this->input->post('fname'),
              'lname' => $this->input->post('lname')
          );
      $insert = $this->employee_model->save($data);
      $id = $this->db->insert_id();

      $this->generate_qrcode($id);

      echo json_encode(array("status" => TRUE));
  }

  public function ajax_update()
  {
      $this->_validate();
      $data = array(
              'fname' => $this->input->post('fname'),
              'lname' => $this->input->post('lname'),
          );
      $this->employee_model->update(array('emp_id' => $this->input->post('emp_id')), $data);
      echo json_encode(array("status" => TRUE));
  }

  public function ajax_delete($id)
  {
      $this->employee_model->delete_by_id($id);
      unlink("qrcodes/".$id.'.png');
      echo json_encode(array("status" => TRUE));
  }

  public function ajax_list_delete()
   {
       $list_id = $this->input->post('id');
       foreach ($list_id as $id) {
           $this->employee_model->delete_by_id($id);
           unlink("qrcodes/".$id.'.png');
       }
       echo json_encode(array("status" => TRUE));
   }

  private function _validate()
  {
      $data = array();
      $data['error_string'] = array();
      $data['inputerror'] = array();
      $data['status'] = TRUE;

      if($this->input->post('fname') == '')
      {
          $data['inputerror'][] = 'fname';
          $data['error_string'][] = 'First name is required';
          $data['status'] = FALSE;
      }else{

        if(!$this->_validate_string($this->input->post('fname')))
        {
          $data['inputerror'][] = 'fname';
          $data['error_string'][] = 'Invalid value';
          $data['status'] = FALSE;
        }

      }

      if($this->input->post('lname') == '')
      {
          $data['inputerror'][] = 'lname';
          $data['error_string'][] = 'First lastname is required';
          $data['status'] = FALSE;
      }else{

        if(!$this->_validate_string($this->input->post('lname')))
        {
          $data['inputerror'][] = 'lname';
          $data['error_string'][] = 'Invalid value';
          $data['status'] = FALSE;
        }

      }

      if($data['status'] === FALSE)
      {
          echo json_encode($data);
          exit();
      }
  }

  private function _validate_string($string)
  {
      $allowed = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
      for ($i=0; $i<strlen($string); $i++)
      {
          if (strpos($allowed, substr($string,$i,1))===FALSE)
          {
              return FALSE;
          }
      }

     return TRUE;
  }

  private function _validate_number($string)
  {
      $allowed = "0123456789";
      for ($i=0; $i<strlen($string); $i++)
      {
          if (strpos($allowed, substr($string,$i,1))===FALSE)
          {
              return FALSE;
          }
      }

     return TRUE;
  }
}
