<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Slider extends BackEndController {
    protected $add;
    protected $index_title;
    protected $title;
    protected $success_add;
    protected $success_update_order;
    protected $success_edit;
    protected $success_delete;

    public function __construct() {
        parent::__construct(__CLASS__);

        $this->index_title = lang('Slider');
        $this->add = lang('Add');
        $this->success_update_order = lang('You have successfully updated display order!');
        $this->success_add = lang('You have successfully added an object');
        $this->success_edit = lang('You have successfully updated the object');
        $this->success_delete = lang('You have successfully deleted the object');

        $this->data['title'] = $this->index_title;
        $this->data['add'] = $this->add;
        $this->load->model('slider_model');
    }

    public function index() {
        init_load_img($this->main_page);

        $objects = $this->slider_model->find();

        $this->data['inner_view'] = $this->index_view;
        $this->data['objects'] = $objects;

        $this->load->vars($this->data);
        $this->load->view($this->main_layout);
    }

    public function put() {
        check_if_POST();

        $post = array();

        init_load_img($this->main_page);

        try {
            foreach ($_POST as $index => $item) {
                $post[$index] = $this->input->post($index, TRUE);
            }

            foreach(language(true) as $lang){
                if (!empty($_FILES['img'.strtoupper($lang)]['name'])) {
                    $this->upload->do_upload('img'.strtoupper($lang));
                    $file_data = $this->upload->data();
                    $file = $file_data['file_name'];
                    if(verify_img_extension($file_data['file_ext']))  $post['img'.strtoupper($lang)] = $file;
                }
            }

            if (!$this->slider_model->put($post)) {
                throw new Exception(lang('Error writing data to table') . $this->main_page);
            }

            $_SESSION['success'] = $this->success_add;
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $errors[] = lang('Exception thrown') . $e->getMessage();
            $_SESSION['error'] = $errors;
        }

        redirect($this->path);
    }

    public function update_order() {
        check_if_POST();

        $this->load->model('slider_model');

        try {
            $post = $this->input->post('so');

            if (empty($post) || !is_array($post)) {
                throw new Exception(lang('Error in received data!'));
            }

            if (!$this->slider_model->update_sorder($post)) {
                throw new Exception(lang('Error writing data to table') . $this->main_page);
            }

            $_SESSION['success'] = $this->success_update_order;
        } catch (Exception $e) {
            $errors[] = lang('Exception thrown') . $e->getMessage();
            $_SESSION['error'] = $errors;
        }

        redirect($this->path);
    }

    public function item($id = 0) {
        $id = (int)$id;

        init_load_img($this->main_page);

        $item = $this->slider_model->find_first($id);

        if (empty($item)) throw_on_404();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                foreach ($_POST as $index => $post_data) {
                    $post[$index] = $this->input->post($index, TRUE);
                }

                foreach(language(true) as $lang) {
                    if (!empty($_FILES['img'.strtoupper($lang)]['name'])) {
                        unlink_files($this->main_page, $item->{'img'.strtoupper($lang)});
                        $this->upload->do_upload('img'.strtoupper($lang));
                        $file_data = $this->upload->data();
                        $file = $file_data['file_name'];
                        if (verify_img_extension($file_data['file_ext'])) $post['img'.strtoupper($lang)] = $file;
                    }
                }

                if (!$this->slider_model->update($post, $id)) {
                    throw new Exception(lang('Error writing data to table') . $this->main_page);
                }

                $_SESSION['success'] = $this->success_edit;
            } catch (Exception $e) {
                log_message('error', $e->getMessage());
                $errors[] = lang('Exception thrown') . $e->getMessage();
                $_SESSION['error'] = $errors;
            }

            $item = $this->slider_model->find_first($id);
        }

        $this->data['inner_view'] = $this->item_view;
        $this->data['title'] = lang('Edit') . $item->{'title'.get_language_for_admin(true)};
        $this->data['parent_url'] = $this->path;
        $this->data['parent_title'] = $this->index_title;
        $this->data['item'] = $item;

        $this->load->vars($this->data);
        $this->load->view($this->main_layout);
    }

    public function delete($id = false) {
        $id = (int)$id;

        $item = $this->slider_model->find_first($id);
        if (empty($item)) throw_on_404();

        foreach(language(true) as $lang){
            unlink_files($this->main_page, $item->{'img'.strtoupper($lang)});
        }

        try {
            if (!$this->slider_model->delete($id)) {
                throw new Exception(lang('Error deleting data from table') . $this->main_page);
            }

            $_SESSION['success'] = $this->success_delete;
        } catch (Exception $e) {
            $errors[] = lang('Exception thrown') . $e->getMessage();
            $_SESSION['error'] = $errors;
        }

        redirect($this->path);
    }
}
