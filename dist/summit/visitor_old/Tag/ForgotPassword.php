<?php

namespace DevDemon\Visitor\Tag;

class ForgotPassword extends AbstractTag
{
    public function parse()
    {
        //location of the "choose new password" form
        $reset_url = (ee()->TMPL->fetch_param('reset_url') != '') ? ee()->functions->fetch_site_index(0, 0).ee()->TMPL->fetch_param('reset_url') : ee()->functions->fetch_current_uri();

        $return          = (ee()->TMPL->fetch_param('return') != '') ? ee()->TMPL->fetch_param('return') : '';
        $error_handling  = ee()->TMPL->fetch_param('error_handling', '');
        $is_ajax_request = ee()->TMPL->fetch_param('json', 'no');

        return $this->forgot_password(ee()->TMPL->tagdata, $return, $error_handling, $is_ajax_request, $reset_url);
    }

    function forgot_password($tagdata, $ret, $error_handling, $is_ajax_request = 'no', $reset_url = '')
    {

        $TMPL_cache = ee()->TMPL;

        $errors  = array();
        $vars    = array();
        $vars[0] = array('email'       => '',
                         'error:email' => '');

        if (isset($_POST['visitor_action']) && $_POST['visitor_action'] == 'forgot_password') {


            if (version_compare(APP_VER, '2.6.0', '>=')) {
                $result = $this->send_reset_token($reset_url);
            } else {
                $result = $this->_retrieve_password();
            }

            if (array_key_exists("success", $result)) {

                if ($ret != '') {
                    if (ee()->TMPL->fetch_param('secure_return') == 'yes') {
                        $ret = preg_replace('/^http:/', 'https:', $ret);
                    }
                    ee()->functions->redirect($ret);
                }


                if ($is_ajax_request == 'yes') {
                    $return = array(
                        'success' => 1,
                        'errors'  => array(),
                    );

                    ee()->output->send_ajax_response($return);
                }

                $tagdata = ee()->functions->prep_conditionals($tagdata, array('password_sent' => TRUE));
            } else {
                if ($is_ajax_request == 'yes') {
                    $return = array(
                        'success' => 0,
                        'errors'  => $result
                    );

                    ee()->output->send_ajax_response($return);
                }
                if ($error_handling != 'inline') {
                    ee()->output->show_user_error(FALSE, $result['email']);
                } else {
                    $result['email'] = $this->prep_errors(array($result['email']));
                    $vars[0]         = array('email'       => $_POST['email'],
                                             'error:email' => implode('<br/>', $result['email']));
                }

                $tagdata = ee()->functions->prep_conditionals($tagdata, array('password_sent' => FALSE));
            }

        } else {
            $tagdata = ee()->functions->prep_conditionals($tagdata, array('password_sent' => FALSE));
        }

        // Create form

        $data['action']        = ee()->TMPL->fetch_param('action', ee()->functions->create_url(ee()->uri->uri_string));
        $data['hidden_fields'] = array(
            'visitor_action' => 'forgot_password',
            'RET'            => $ret
        );

        if (ee()->TMPL->fetch_param('name') !== FALSE &&
            preg_match("#^[a-zA-Z0-9_\-]+$#i", ee()->TMPL->fetch_param('name'), $match)
        ) {
            $data['name'] = ee()->TMPL->fetch_param('name');
        }

        $data['id'] = ee()->TMPL->fetch_param('id', ee()->TMPL->fetch_param('form_id', ''));

        $data['class'] = ee()->TMPL->fetch_param('class', ee()->TMPL->fetch_param('form_class', ''));

        $form_declared = ee()->functions->form_declaration($data);

        $tagdata = ee()->TMPL->parse_variables($tagdata, $vars);

        if (ee()->TMPL->fetch_param('secure_action') == 'yes') {
            $form_declared = preg_replace('/(<form.*?action=")http:/', '\\1https:', $form_declared);
        }

        return $form_declared . $tagdata . '</form>';

    }

    /**
     * E-mail Forgotten Password Reset Token to User
     *
     * Handler page for the forgotten password form.  Processes the e-mail
     * given us in the form, generates a token and then sends that token
     * to the given e-mail with a backlink to a location where the user
     * can set their password.  Expects to find the e-mail in `$_POST['email']`.
     *
     * @return void
     */
    public function send_reset_token($reset_url)
    {

        ee()->load->language("member");

        // if this user is logged in, then send them away.
        if (ee()->session->userdata('member_id') !== 0) {
            return ee()->functions->redirect(ee()->functions->fetch_site_index());
        }

        // Is user banned?
        if (ee()->session->userdata('is_banned') === TRUE) {
            //return ee()->output->show_user_error('general', array(lang('not_authorized')));
            return array('email' => lang('not_authorized'));
        }

        // Error trapping
        if (!$address = ee()->input->post('email')) {
            //return ee()->output->show_user_error('submission', array(lang('invalid_email_address')));
            return array('email' => lang('invalid_email_address'));
        }

        ee()->load->helper('email');
        if (!valid_email($address)) {
            //return ee()->output->show_user_error('submission', array(lang('invalid_email_address')));
            return array('email' => lang('invalid_email_address'));
        }

        $address = strip_tags($address);

        $memberQuery = ee()->db->select('member_id, username, screen_name')
            ->where('email', $address)
            ->get('members');

        if ($memberQuery->num_rows() == 0) {
            //return ee()->output->show_user_error('submission', array(lang('no_email_found')));
            return array('email' => lang('no_email_found'));
        }

        $member_id = $memberQuery->row('member_id');
        $username  = $memberQuery->row('username');
        $name  = ($memberQuery->row('screen_name') == '') ? $memberQuery->row('username') : $memberQuery->row('screen_name');

        // Kill old data from the reset_password field
        $a_day_ago = time() - (60 * 60 * 24);
        ee()->db->where('date <', $a_day_ago)
            ->or_where('member_id', $member_id)
            ->delete('reset_password');

        // Create a new DB record with the temporary reset code
        $rand = ee()->functions->random('alnum', 8);
        $data = array('member_id' => $member_id, 'resetcode' => $rand, 'date' => time());
        ee()->db->query(ee()->db->insert_string('exp_reset_password', $data));

        // Build the email message
        if (ee()->input->get_post('FROM') == 'forum') {
            if (ee()->input->get_post('board_id') !== FALSE &&
                is_numeric(ee()->input->get_post('board_id'))
            ) {
                $query = ee()->db->select('board_forum_url, board_id, board_label')
                    ->where('board_id', ee()->input->get_post('board_id'))
                    ->get('forum_boards');
            } else {
                $query = ee()->db->select('board_forum_url, board_id, board_label')
                    ->where('board_id', (int)1)
                    ->get('forum_boards');
            }

            $return    = $query->row('board_forum_url');
            $site_name = $query->row('board_label');
            $board_id  = $query->row('board_id');
        } else {
            $site_name = stripslashes(ee()->config->item('site_name'));
            $return    = ee()->config->item('site_url');
        }

        $forum_id = (ee()->input->get_post('FROM') == 'forum') ? '&r=f&board_id=' . $board_id : '';

        $reset_url = ($reset_url != '') ? $reset_url . QUERY_MARKER . '&id=' . $rand . $forum_id : ee()->functions->fetch_site_index(0, 0) . '/' . ee()->config->item('profile_trigger') . '/reset_password' . QUERY_MARKER . '&id=' . $rand . $forum_id;


        $swap = array(
            'username'  => $username,
            'name'      => $name,
            'reset_url' => $reset_url,
            'site_name' => $site_name,
            'site_url'  => $return
        );

        $template = ee()->functions->fetch_email_template('forgot_password_instructions');

        // _var_swap calls string replace on $template[] for each key in
        // $swap.  If the key doesn't exist then no swapping happens.
        $email_tit = $this->_var_swap($template['title'], $swap);
        $email_msg = $this->_var_swap($template['data'], $swap);

        // Instantiate the email class
        ee()->load->library('email');
        ee()->email->wordwrap = true;
        ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
        ee()->email->to($address);
        ee()->email->subject($email_tit);
        ee()->email->message($email_msg);

        if (!ee()->email->send()) {
            //return ee()->output->show_user_error('submission', array(lang('error_sending_email')));
            return array('email' => lang('error_sending_email'));
        }

        // Build success message
        $data = array(
            'title'   => lang('mbr_passwd_email_sent'),
            'heading' => lang('thank_you'),
            'content' => lang('forgotten_email_sent'),
            'link'    => array($return, $site_name)
        );

        //ee()->output->show_message($data);
        return array('success' => $data);
    }

    function _retrieve_password()
    {
        ee()->load->language("member");
        // Is user banned?
        if (ee()->session->userdata('is_banned') === TRUE) {
            return array('email' => lang('not_authorized'));
        }

        // Error trapping
        if (!$address = ee()->input->post('email')) {
            return array('email' => lang('invalid_email_address'));
        }

        ee()->load->helper('email');

        if (!valid_email($address)) {
            return array('email' => lang('invalid_email_address'));
        }

        $address = strip_tags($address);

        // Fetch user data
        $query = ee()->db->select('member_id, username')
            ->where('email', $address)
            ->get('members');

        if ($query->num_rows() == 0) {
            return array('email' => lang('no_email_found'));
        }

        $member_id = $query->row('member_id');
        $username  = $query->row('username');

        // Kill old data from the reset_password field

        $time = time() - (60 * 60 * 24);

        ee()->db->where('date <', $time)
            ->or_where('member_id', $member_id)
            ->delete('reset_password');

        // Create a new DB record with the temporary reset code
        $rand = ee()->functions->random('alnum', 8);

        $data = array('member_id' => $member_id,
                      'resetcode' => $rand,
                      'date'      => time());

        ee()->db->query(ee()->db->insert_string('exp_reset_password', $data));

        // Buid the email message

        if (ee()->input->get_post('FROM') == 'forum') {
            if (ee()->input->get_post('board_id') !== FALSE &&
                is_numeric(ee()->input->get_post('board_id'))
            ) {
                $query = ee()->db->select('board_forum_url, board_id, board_label')
                    ->where('board_id', ee()->input->get_post('board_id'))
                    ->get('forum_boards');
            } else {
                $query = ee()->db->select('board_forum_url, board_id, board_label')
                    ->where('board_id', (int)1)
                    ->get('forum_boards');
            }

            $return    = $query->row('board_forum_url');
            $site_name = $query->row('board_label');
            $board_id  = $query->row('board_id');
        } else {
            $site_name = stripslashes(ee()->config->item('site_name'));
            $return    = ee()->config->item('site_url');
        }

        $forum_id = (ee()->input->get_post('FROM') == 'forum') ? '&r=f&board_id=' . $board_id : '';

        $swap = array(
            'name'      => $username,
            'reset_url' => ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . ee()->functions->fetch_action_id('Member', 'reset_password') . '&id=' . $rand . $forum_id,
            'site_name' => $site_name,
            'site_url'  => $return
        );

        $template  = ee()->functions->fetch_email_template('forgot_password_instructions');
        $email_tit = $this->_var_swap($template['title'], $swap);
        $email_msg = $this->_var_swap($template['data'], $swap);

        // Instantiate the email class

        ee()->load->library('email');
        ee()->email->wordwrap = true;
        ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
        ee()->email->to($address);
        ee()->email->subject($email_tit);
        ee()->email->message($email_msg);

        if (!ee()->email->send()) {
            return array('email' => lang('error_sending_email'));
        }

        // Build success message
        $data = array('title'   => lang('mbr_passwd_email_sent'),
                      'heading' => lang('thank_you'),
                      'content' => lang('forgotten_email_sent'),
                      'link'    => array($return, $site_name)
        );

        return array('success' => $data);
    }

    function _var_swap($str, $data)
    {
        if (!is_array($data)) {
            return FALSE;
        }

        foreach ($data as $key => $val) {
            $str = str_replace('{' . $key . '}', $val, $str);
        }

        return $str;
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

/* End of file ForgotPassword.php */
/* Location: ./system/user/addons/Visitor/Tag/ForgotPassword.php */