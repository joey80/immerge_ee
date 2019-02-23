<?php

namespace DevDemon\Visitor\Tag;

class LoginForm extends AbstractTag
{
    public function parse()
    {
        $return          = ($this->param('return') != '') ? $this->param('return') : ee()->functions->fetch_current_uri();
        $error_handling  = $this->param('error_handling', '');
        $is_ajax_request = $this->param('json', 'no');

        $vars    = array();
        $vars[0] = array('username'    => '',
                         'password'    => '',
                         'error:login' => '');

        // ===============================
        // = Process the submitted login =
        // ===============================
        if (isset($_POST['visitor_action']) && $_POST['visitor_action'] == 'login') {

            /* -------------------------------------------
            /* 'visitor_login_start' hook.
            /*  - Take control of member login routine
            /*  - Added EE 1.4.2
            */
            $edata = ee()->extensions->call('visitor_login_start');
            if (ee()->extensions->end_script === TRUE) return;
            /*
            /* -------------------------------------------*/

            $result = ee('visitor:MembersAuth')->member_login();

            if (array_key_exists("success", $result)) {
                if ($is_ajax_request == 'yes') {
                    $return = array(
                        'success' => 1,
                        'errors'  => array(),
                        'return'  => $return
                    );

                    ee()->output->send_ajax_response($return);
                }

                $this->tagdata = ee()->functions->prep_conditionals($this->tagdata, array('success' => TRUE));

                $redirect = ee()->input->post('RET');

                if ($redirect) {
                    if ($this->param('secure_return') == 'yes') {
                        $return = preg_replace('/^http:/', 'https:', $redirect);
                    }
                    ee()->functions->redirect($redirect);
                }
            } else {
                if ($is_ajax_request == 'yes') {
                    $return = array(
                        'success' => 0,
                        'errors'  => $result
                    );

                    ee()->output->send_ajax_response($return);
                }
                if ($error_handling != 'inline') {
                    ee()->output->show_user_error(FALSE, $result['login']);
                } else {
                    $result['login'] = $this->prep_errors(array($result['login']));
                    $vars[0]         = array('username'    => ee()->input->post('username'),
                                             'password'    => ee()->input->post('password'),
                                             'error:login' => implode('<br/>', $result['login']));
                }

                $this->tagdata = ee()->functions->prep_conditionals($this->tagdata, array('success' => FALSE));
            }

        }

        $this->tagdata = ee()->TMPL->parse_variables($this->tagdata, $vars);

        if (ee()->config->item('user_session_type') != 'c') {
            $this->tagdata = preg_replace("/{if\s+auto_login}.*?{" . '\/' . "if}/s", '', $this->tagdata);
        } else {
            $this->tagdata = preg_replace("/{if\s+auto_login}(.*?){" . '\/' . "if}/s", "\\1", $this->tagdata);
        }

        // Create form

        $data['action']        = $this->param('form_action', ee()->functions->create_url(ee()->uri->uri_string));
        $data['hidden_fields'] = array(
            'visitor_action' => 'login',
            'RET'            => $return
        );

        if ($this->param('name') !== FALSE &&
            preg_match("#^[a-zA-Z0-9_\-]+$#i", $this->param('name'), $match)
        ) {
            $data['name'] = $this->param('name');
        }

        $data['id'] = $this->param('id', $this->param('form_id', ''));

        $data['class'] = $this->param('class', $this->param('form_class', ''));

        $res = ee()->functions->form_declaration($data);

        $res .= stripslashes($this->tagdata);

        $res .= "</form>";

        if ($this->param('secure_action') == 'yes') {
            $res = preg_replace('/(<form.*?action=")http:/', '\\1https:', $res);
        }

        return $res;
    }

    private function get_error_delimiters()
    {

        $delimiter_param = (isset($_POST) && isset($_POST['visitor_error_delimiters'])) ? $_POST['visitor_error_delimiters'] : ee()->TMPL->fetch_param('error_delimiters', '');

        $delimiter = explode('|', $delimiter_param);
        $delimiter = (count($delimiter) == 2) ? $delimiter : array('', '');
        return $delimiter;
    }

    private function prep_errors($errors)
    {
        $error_delimiters = $this->get_error_delimiters();
        $prepped_errors   = array();
        if (isset($errors) && count($errors) > 0) {
            foreach ($errors as $key => $error) {
                $prepped_errors[$key] = $error_delimiters[0] . $error . $error_delimiters[1];
            }
        }
        return $prepped_errors;
    }
}

/* End of file LoginForm.php */
/* Location: ./system/user/addons/Visitor/Tag/LoginForm.php */