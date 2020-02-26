<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product extends CI_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->model('Category_model', 'categories');
        $this->load->model('Product_model', 'products');
    }

	public function index(){

        $data = [
            'title' => 'Produtos | CodeIgniter 3.1.11',
            'h1' => 'Produtos',
            'products' => $this->products->getAll()->result(),
        ];

        if(isset($_SESSION["redirect_data"]))
        {
            $data['msg_type'] = $_SESSION["redirect_data"]['msg_type'];
            $data['msg_label'] = $_SESSION["redirect_data"]['msg_label'];
            unset($_SESSION["redirect_data"]);
        }
        else 
        {
            $data['msg_type'] = false;
            $data['msg_label'] = false;
        }
        
        $this->load->view('header', $data);
        $this->load->view('list_product', $data);
        $this->load->view('footer');
    }

    public function delete()
    {
        $id = $this->uri->segment(3);
        $rtn = $this->products->delete($id);

        $data = [];

        if($rtn){
            $data['msg_type'] = 'alert-success';
            $data['msg_label'] = "Produto ID: {$id} excluído com sucesso!";
        }
        else{
            $data['msg_type'] = 'alert-danger';
            $data['msg_label'] = "Problemas ao excluir o produto com ID: {$id}!";
        }

        $_SESSION["redirect_data"] = $data;
        
        redirect('/product', 'refresh');
    }

    public function upsert()
    {
        $data = [
            'form_status' => false,
            'form_alert' => '',
            'form_msg' => '',
            'categories' => $this->categories->getAll()->result(),
            'upt' => (object)['id_product' => '', 'id_category' => '', 'name' => '', 'url' => '', 'price' => '', 'description' => ''],
        ];
        
        // Update
        $id = $this->uri->segment(3);
        if($id)
        {
            $data['title'] = 'Atualizar Produto | CodeIgniter 3.1.11';
            $data['h1'] = 'Atualizar Produto';
            $data['btn'] = 'Atualizar';

            $upt = $this->products->getOne("WHERE id_product = '{$id}'")->result();

            if(!$upt)
            {
                $data['msg_type'] = 'alert-danger';
                $data['msg_label'] = "Não existe o produto com ID: {$id}!";

                $_SESSION["redirect_data"] = $data;
            
                redirect('/product', 'refresh');
            }
            else
            {
                $data['upt'] = $upt[0];
            }
        }
        // Insert
        else
        {
            $data['title'] = 'Cadastrar Produto | CodeIgniter 3.1.11';
            $data['h1'] = 'Cadastrar Produto';
            $data['btn'] = 'Cadastrar';
        }
        
        $this->load->helper(['form','funcoes_helper']);
        $this->load->library('form_validation');
        $this->form_validation->set_rules('id_category', 'ID Categoria', 'trim|required');
        $this->form_validation->set_rules('name', 'Nome Produto', 'trim|required');
        $this->form_validation->set_rules('price', 'Preço', 'trim|required');

        $is_valid = $this->form_validation->run();

        if($is_valid)
        {
            $dados_form = $this->input->post();

            if(!!trim($dados_form['url'])):
                $dados_form['url'] = urlSlug($dados_form['url']);
            else:
                $dados_form['url'] = urlSlug($dados_form['name']);
            endif;

            if(!!$this->products->getOne("WHERE url = '".$dados_form['url']."'" . ($id ? " AND id_product <> {$id}" : ""))->result())
            {
                $data['form_status'] = true;
                $data['form_alert'] = 'alert-danger';
                $data['form_msg'] = 'URL já encontra-se registrado na base de dados!';
            }
            else
            {
                if($id)
                {
                    $dados_form['id_product'] = $id;
                    
                    if($this->products->update($dados_form))
                    {
                        $data['msg_type'] = 'alert-success';
                        $data['msg_label'] = "Produto ID: {$id} atualizado com sucesso!";
        
                        $_SESSION["redirect_data"] = $data;
                    
                        redirect('/product', 'refresh');
                    }
                    else
                    {
                        $data['form_status'] = true;
                        $data['form_alert'] = 'alert-danger';
                        $data['form_msg'] = 'Falha ao cadastrar produto, contate o administrador!';
                    }
                }
                else
                {
                    if($this->products->insert($dados_form))
                    {
                        $data['form_status'] = true;
                        $data['form_alert'] = 'alert-success';
                        $data['form_msg'] = 'Produto cadastrado com sucesso!';
                    }
                    else
                    {
                        $data['form_status'] = true;
                        $data['form_alert'] = 'alert-danger';
                        $data['form_msg'] = 'Falha ao cadastrar produto, contate o administrador!';
                    }
                }
            }
        }
        
        $this->load->view('header', $data);
        $this->load->view('upsert_product', $data);
        $this->load->view('footer');
    }
}
