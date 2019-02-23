<?php

namespace DevDemon\Visitor\Tag;

class ResetPassword extends AbstractTag
{
    public function parse()
    {
        $return          = (ee()->TMPL->fetch_param('return') != '') ? ee()->TMPL->fetch_param('return') : '';
        $error_handling  = ee()->TMPL->fetch_param('error_handling', '');
        $is_ajax_request = ee()->TMPL->fetch_param('json', 'no');

        return $this->reset_password(ee()->TMPL->tagdata, $return, $error_handling, $is_ajax_request);
    }

    function reset_password($tagdata, $ret, $error_handling, $is_ajax_request = 'no')
    {

        $TMPL_cache = ee()->TMPL;

        $errors  = array();
        $vars    = array();
        $vars[0] = array('email'          => '',
                         'error:password' => '');

        if (isset($_POST['visitor_action']) && $_POST['visitor_action'] == 'reset_password') {

            $result = $this->process_reset_password();

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

                $tagdata = ee()->functions->prep_conditionals($tagdata, array('password_reset' => TRUE));
            } else {
                if ($is_ajax_request == 'yes') {
                    $return = array(
                        'success' => 0,
                        'errors'  => $result
                    );

                    ee()->output->send_ajax_response($return);
                }
                if ($error_handling != 'inline') {
                    ee()->output->show_user_error(FALSE, $result['password']);
                } else {
                    $result['password'] = $this->prep_errors(array($result['password']));
                    $vars[0]            = array('password'       => $_POST['password'],
                                                'error:password' => implode('<br/>', $result['password']));
                }

                $tagdata = ee()->functions->prep_conditionals($tagdata, array('password_reset' => FALSE));
            }

        } else {
            $tagdata = ee()->functions->prep_conditionals($tagdata, array('password_reset' => FALSE));
        }

        // if the use is logged in, then send them away
        if (ee()->session->userdata('member_id') !== 0) {
            return ee()->functions->redirect(ee()->functions->fetch_site_index());
        }
        // If the user is banned, send them away.
        if (ee()->session->userdata('is_banned') === TRUE) {
            return ee()->output->show_user_error('general', array(lang('not_authorized')));
        }

        // They didn't include their token.  Give em an error.
        if (!($resetcode = ee()->input->get_post('id'))) {
            return ee()->output->show_user_error('submission', array(lang('mbr_no_reset_id')));
        }

        $data = array();

        $data['action'] = ee()->TMPL->fetch_param('action', ee()->functions->create_url(ee()->uri->uri_string)) . '?&id=' . $resetcode;
        $data['id']     = ee()->TMPL->fetch_param('id', ee()->TMPL->fetch_param('form_id', ''));
        $data['class']  = ee()->TMPL->fetch_param('class', ee()->TMPL->fetch_param('form_class', ''));

        // Check to see whether we're in the forum or not.
        $in_forum                                = isset($_GET['r']) && $_GET['r'] == 'f';
        $data['hidden_fields']['from']           = ($in_forum == TRUE) ? 'forum' : '';
        $data['hidden_fields']['visitor_action'] = 'reset_password';
        $data['hidden_fields']['RET']            = $ret;
        $data['hidden_fields']['resetcode']      = $resetcode;

        $form_declared = ee()->functions->form_declaration($data);

        $tagdata = ee()->TMPL->parse_variables($tagdata, $vars);

        if (ee()->TMPL->fetch_param('secure_action') == 'yes') {
            $form_declared = preg_replace('/(<form.*?action=")http:/', '\\1https:', $form_declared);
        }

        return $form_declared . $tagdata . '</form>';

    }

    /**
     * Reset Password Processing Action
     *
     * Processing action to process a reset password.  Sent here by the form presented
     * to the user in `Member_auth::reset_password()`.  Process the form and return
     * the user to the appropriate login page.  Expects to find the contents of the
     * form in `$_POST`.
     *
     * @since 2.6
     */
    public function process_reset_password()
    {
        ee()->load->language("member");

        // if the user is logged in, then send them away
        if (ee()->session->userdata('member_id') !== 0) {
            return ee()->functions->redirect(ee()->functions->fetch_site_index());
        }

        // If the user is banned, send them away.
        if (ee()->session->userdata('is_banned') === TRUE) {
            //return ee()->output->show_user_error('general', array(lang('not_authorized')));
            return array('password' => lang('not_authorized'));
        }

        if (!($resetcode = ee()->input->get_post('resetcode'))) {
            //return ee()->output->show_user_error('submission', array(lang('mbr_no_reset_id')));
            return array('password' => lang('mbr_no_reset_id'));
        }

        // We'll use this in a couple of places to determine whether a token is still valid
        // or not.  Tokens expire after exactly 1 day.
        $a_day_ago = time() - (60 * 60 * 24);

        // Make sure the token is valid and belongs to a member.
        $member_id_query = ee()->db->select('member_id')
            ->where('resetcode', $resetcode)
            ->where('date >', $a_day_ago)
            ->get('reset_password');

        if ($member_id_query->num_rows() === 0) {
            //return ee()->output->show_user_error('submission', array(lang('mbr_id_not_found')));
            return array('password' => lang('mbr_id_not_found'));
        }

        // Ensure the passwords match.
        if (!($password = ee()->input->get_post('password'))) {
            //return ee()->output->show_user_error('submission', array(lang('mbr_missing_password')));
            return array('password' => lang('mbr_missing_password'));
        }

        if (!($password_confirm = ee()->input->get_post('password_confirm'))) {
            //return ee()->output->show_user_error('submission', array(lang('mbr_missing_confirm')));
            return array('password' => lang('mbr_missing_confirm'));
        }

        // Validate the password, using EE_Validate. This will also
        // handle checking whether the password and its confirmation
        // match.
        if (!class_exists('EE_Validate')) {
            require APPPATH . 'libraries/Validate.php';
        }

        $VAL = new \EE_Validate(array(
            'password'         => $password,
            'password_confirm' => $password_confirm,
        ));

        $VAL->validate_password();
        if (count($VAL->errors) > 0) {
            //return ee()->output->show_user_error('submission', $VAL->errors);
            return array('password' => implode('<br/>', $VAL->errors));
        }

        // Update the database with the new password.  Apply the appropriate salt first.
        ee()->load->library('auth');
        ee()->auth->update_password(
            $member_id_query->row('member_id'),
            $password
        );

        // Invalidate the old token.  While we're at it, may as well wipe out expired
        // tokens too, just to keep them from building up.
        ee()->db->where('date <', $a_day_ago)
            ->or_where('member_id', $member_id_query->row('member_id'))
            ->delete('reset_password');


        // If we can get their last URL from the tracker,
        // then we'll use it.
        if (isset(ee()->session->tracker[3])) {
            $site_name = stripslashes(ee()->config->item('site_name'));
            $return    = ee()->functions->fetch_site_index() . '/' . ee()->session->tracker[3];
        }
        // Otherwise, it's entirely possible they are clicking the e-mail link after
        // their session has expired.  In that case, the only information we have
        // about where they came from is in the POST data (where it came from the GET data).
        // Use it to get them as close as possible to where they started.
        else if (ee()->input->get_post('FROM') == 'forum') {
            $board_id = ee()->input->get_post('board_id');
            $board_id = ($board_id === FALSE OR !is_numeric($board_id)) ? 1 : $board_id;

            $forum_query = ee()->db->select('board_forum_url, board_label')
                ->where('board_id', (int)$board_id)
                ->get('forum_boards');

            $site_name = $forum_query->row('board_label');
            $return    = $forum_query->row('board_forum_url');
        } else {
            $site_name = stripslashes(ee()->config->item('site_name'));
            $return    = ee()->functions->fetch_site_index();
        }

        // Build the success message that we'll show to the user.
        $data = array(
            'title'    => lang('mbr_password_changed'),
            'heading'  => lang('mbr_password_changed'),
            'content'  => lang('mbr_successfully_changed_password'),
            'link'     => array($return, $site_name), // The link to show them. In the form of (URL, Name)
            'redirect' => $return, // Redirect them to this URL...
            'rate'     => '5' // ...after 5 seconds.

        );

        //ee()->output->show_message($data);
        return array('success' => $data);
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

/* End of file ResetPassword.php */
/* Location: ./system/user/addons/Visitor/Tag/ResetPassword.php */