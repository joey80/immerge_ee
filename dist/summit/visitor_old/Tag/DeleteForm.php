<?php

namespace DevDemon\Visitor\Tag;

class DeleteForm extends AbstractTag
{
    public function parse()
    {
        $return         = $this->param('return');
        $error_handling = $this->param('error_handling', '');

        return $this->delete_account(ee()->TMPL->tagdata, $return, $error_handling);
    }

    function delete_account($tagdata, $ret, $error_handling, $is_ajax_request = 'no')
    {

        $TMPL_cache = ee()->TMPL;

        $errors  = array();
        $vars    = array();
        $vars[0] = array('error:password' => '');

        if (isset($_POST['visitor_action']) && $_POST['visitor_action'] == 'delete_account') {


            /** -------------------------------------
             * /**  Validate submitted password
             * /** -------------------------------------*/
            if (!class_exists('EE_Validate')) {
                require APPPATH . 'libraries/Validate.php';
            }

            $VAL = new \EE_Validate(
                array(
                    'member_id'    => ee()->session->userdata('member_id'),
                    'cur_password' => $_POST['password']
                )
            );

            $VAL->password_safety_check();

            if (count($VAL->errors) == 0) {
                $result = $this->_member_delete();

                //IF SUCCESS, REDIRECT TO RETURN PARAM

                if (isset($result['success']) && $ret != '') {

                    if ($this->param('secure_return') == 'yes') {
                        $ret = preg_replace('/^http:/', 'https:', $ret);
                    }

                    /* -------------------------------------------
                     /* 'visitor_delete_end' hook.
                     */
                    $edata = ee()->extensions->call('visitor_delete_end');
                    if (ee()->extensions->end_script === TRUE) return;

                    ee()->functions->redirect($ret);
                }

                if (isset($result['error'])) {

                    //IF ERROR HANDLING INLINE, PREP ERRORS, OTHERWISE SHOW USER OUTPUT
                    if ($error_handling == "inline") {
                        $errors  = $this->prep_errors(array($result['error']));
                        $vars[0] = array('error:password' => $errors[0]);
                    } else {
                        ee()->output->show_user_error('submission', $result['error']);
                    }
                }


            } else {
                //IF ERROR HANDLING INLINE, PREP ERRORS, OTHERWISE SHOW USER OUTPUT
                if ($error_handling == "inline") {
                    $errors  = $this->prep_errors(array($VAL->errors[0]));
                    $vars[0] = array('error:password' => $errors[0]);
                } else {
                    ee()->output->show_user_error('submission', $VAL->errors[0]);
                }
            }

        }

        $data['id']            = 'delete_account_form';
        $data['action']        = ee()->functions->create_url(ee()->uri->uri_string);
        $data['hidden_fields'] = array(
            'visitor_action' => 'delete_account',
            'RET'            => $ret
        );

        $data = array_merge($data, ee()->TMPL->tagparams);

        $tagdata = ee()->TMPL->parse_variables($tagdata, $vars);

        $form_declared = ee()->functions->form_declaration($data);

        if ($this->param('secure_action') == 'yes') {
            $form_declared = preg_replace('/(<form.*?action=")http:/', '\\1https:', $form_declared);
        }

        return $form_declared . $tagdata . '</form>';

    }

    function _member_delete()
    {

        ee()->load->language("member");

        // No sneakiness - we'll do this in case the site administrator
        // has foolishly turned off secure forms and some monkey is
        // trying to delete their account from an off-site form or
        // after logging out.

        if (ee()->session->userdata('member_id') == 0 OR
            ee()->session->userdata('can_delete_self') !== 'y'
        ) {
            return array('error' => ee()->lang->line('not_authorized'));
        }

        // If the user is a SuperAdmin, then no deletion
        if (ee()->session->userdata('group_id') == 1) {
            return array('error' => ee()->lang->line('cannot_delete_super_admin'));
        }

        // Is IP and User Agent required for login?  Then, same here.
        if (ee()->config->item('require_ip_for_login') == 'y') {
            if (ee()->session->userdata('ip_address') == '' OR
                ee()->session->userdata('user_agent') == ''
            ) {
                return array('error' => ee()->lang->line('unauthorized_request'));
            }
        }

        // Check password lockout status
        if (ee()->session->check_password_lockout(ee()->session->userdata('username')) === TRUE) {
            ee()->lang->loadfile('login');

            return array('error' =>
                             sprintf(lang('password_lockout_in_effect'), ee()->config->item('password_lockout_interval'))
            );
        }

        /** -------------------------------------
         * /**  Validate submitted password
         * /** -------------------------------------*/
        if (!class_exists('EE_Validate')) {
            require APPPATH . 'libraries/Validate.php';
        }

        $VAL = new \EE_Validate(
            array(
                'member_id'    => ee()->session->userdata('member_id'),
                'cur_password' => $_POST['password']
            )
        );

        $VAL->password_safety_check();

        if (isset($VAL->errors) && count($VAL->errors) > 0) {
            ee()->session->save_password_lockout(ee()->session->userdata('username'));

            return array('error' => ee()->lang->line('invalid_pw'));
        }
        // Are you who you say you are, or someone sitting at someone
        // else's computer being mean?!
        //      $query = ee()->db->select('password')
        //                            ->where('member_id', ee()->session->userdata('member_id'))
        //                            ->get('members');
        //
        //      $password = ee()->functions->hash(stripslashes($_POST['password']));
        // echo '<br/>'.$query->row('password') .'<br/>'. $password;
        //      if ($query->row('password') != $password)
        //      {
        //          ee()->session->save_password_lockout(ee()->session->userdata('username'));
        //
        //          return array('error' => ee()->lang->line('invalid_pw'));
        //      }

        // No turning back, get to deletin'!
        $id = ee()->session->userdata('member_id');

        ee()->db->where('member_id', (int)$id)->delete('members');
        ee()->db->where('member_id', (int)$id)->delete('member_data');
        ee()->db->where('member_id', (int)$id)->delete('member_homepage');
        ee()->db->where('sender_id', (int)$id)->delete('message_copies');
        ee()->db->where('sender_id', (int)$id)->delete('message_data');
        ee()->db->where('member_id', (int)$id)->delete('message_folders');
        ee()->db->where('member_id', (int)$id)->delete('message_listed');

        $message_query = ee()->db->query("SELECT DISTINCT recipient_id FROM exp_message_copies WHERE sender_id = '{$id}' AND message_read = 'n'");

        if ($message_query->num_rows() > 0) {
            foreach ($message_query->result_array() as $row) {
                $count_query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_message_copies WHERE recipient_id = '" . $row['recipient_id'] . "' AND message_read = 'n'");
                ee()->db->query(ee()->db->update_string('exp_members', array('private_messages' => $count_query->row('count')), "member_id = '" . $row['recipient_id'] . "'"));
            }
        }

        // Delete Forum Posts
        if (ee()->config->item('forum_is_installed') == "y") {
            ee()->db->where('member_id', (int)$id)->delete('forum_subscriptions');
            ee()->db->where('member_id', (int)$id)->delete('forum_pollvotes');
            ee()->db->where('author_id', (int)$id)->delete('forum_topics');
            ee()->db->where('admin_member_id', (int)$id)->delete('forum_administrators');
            ee()->db->where('mod_member_id', (int)$id)->delete('forum_moderators');

            // Snag the affected topic id's before deleting the member for the update afterwards
            $query = ee()->db->query("SELECT topic_id FROM exp_forum_posts WHERE author_id = '{$id}'");

            if ($query->num_rows() > 0) {
                $topic_ids = array();

                foreach ($query->result_array() as $row) {
                    $topic_ids[] = $row['topic_id'];
                }

                $topic_ids = array_unique($topic_ids);
            }

            ee()->db->where('author_id', (int)$id)->delete('forum_posts');
            ee()->db->where('author_id', (int)$id)->delete('forum_polls');

            // Kill any attachments
            $query = ee()->db->query("SELECT attachment_id, filehash, extension, board_id FROM exp_forum_attachments WHERE member_id = '{$id}'");

            if ($query->num_rows() > 0) {
                // Grab the upload path
                $res = ee()->db->query('SELECT board_id, board_upload_path FROM exp_forum_boards');

                $paths = array();
                foreach ($res->result_array() as $row) {
                    $paths[$row['board_id']] = $row['board_upload_path'];
                }

                foreach ($query->result_array() as $row) {
                    if (!isset($paths[$row['board_id']])) {
                        continue;
                    }

                    $file  = $paths[$row['board_id']] . $row['filehash'] . $row['extension'];
                    $thumb = $paths[$row['board_id']] . $row['filehash'] . '_t' . $row['extension'];

                    @unlink($file);
                    @unlink($thumb);

                    ee()->db->where('attachment_id', (int)$row['attachment_id'])
                        ->delete('forum_attachments');
                }
            }

            // Update the forum stats
            $query = ee()->db->query("SELECT forum_id FROM exp_forums WHERE forum_is_cat = 'n'");

            if (!class_exists('Forum')) {
                require PATH_MOD . 'forum/mod.forum.php';
                require PATH_MOD . 'forum/mod.forum_core.php';
            }

            $FRM = new Forum_Core;

            foreach ($query->result_array() as $row) {
                $FRM->_update_post_stats($row['forum_id']);
            }

            if (isset($topic_ids)) {
                foreach ($topic_ids as $topic_id) {
                    $FRM->_update_topic_stats($topic_id);
                }
            }
        }

        // Va-poo-rize Channel Entries and Comments
        $entry_ids   = array();
        $channel_ids = array();
        $recount_ids = array();

        // Find Entry IDs and Channel IDs, then delete
        $query = ee()->db->query("SELECT entry_id, channel_id FROM exp_channel_titles WHERE author_id = '{$id}'");

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $entry_ids[]   = $row['entry_id'];
                $channel_ids[] = $row['channel_id'];
            }

            ee()->db->query("DELETE FROM exp_channel_titles WHERE author_id = '{$id}'");
            ee()->db->query("DELETE FROM exp_channel_data WHERE entry_id IN ('" . implode("','", $entry_ids) . "')");
            ee()->db->query("DELETE FROM exp_comments WHERE entry_id IN ('" . implode("','", $entry_ids) . "')");
        }

        // Find the affected entries AND channel ids for author's comments
        $query = ee()->db->query("SELECT DISTINCT(entry_id), channel_id FROM exp_comments WHERE author_id = '{$id}'");

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $recount_ids[] = $row['entry_id'];
                $channel_ids[] = $row['channel_id'];
            }

            $recount_ids = array_diff($recount_ids, $entry_ids);
        }

        // Delete comments by member
        ee()->db->query("DELETE FROM exp_comments WHERE author_id = '{$id}'");

        // Update stats on channel entries that were NOT deleted AND had comments by author

        if (count($recount_ids) > 0) {
            foreach (array_unique($recount_ids) as $entry_id) {
                $query = ee()->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '" . ee()->db->escape_str($entry_id) . "'");

                $comment_date = ($query->num_rows() == 0 OR !is_numeric($query->row('max_date'))) ? 0 : $query->row('max_date');

                $query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '{$entry_id}' AND status = 'o'");

                ee()->db->query("UPDATE exp_channel_titles SET comment_total = '" . ee()->db->escape_str($query->row('count')) . "', recent_comment_date = '$comment_date' WHERE entry_id = '{$entry_id}'");
            }
        }

        if (count($channel_ids) > 0) {
            foreach (array_unique($channel_ids) as $channel_id) {
                ee()->stats->update_channel_stats($channel_id);
                ee()->stats->update_comment_stats($channel_id);
            }
        }

        // Email notification recipients
        if (ee()->session->userdata('mbr_delete_notify_emails') != '') {

            $notify_address = ee()->session->userdata('mbr_delete_notify_emails');

            $swap = array(
                'name'      => ee()->session->userdata('screen_name'),
                'email'     => ee()->session->userdata('email'),
                'site_name' => stripslashes(ee()->config->item('site_name'))
            );

            $email_tit = ee()->functions->var_swap(ee()->lang->line('mbr_delete_notify_title'), $swap);
            $email_msg = ee()->functions->var_swap(ee()->lang->line('mbr_delete_notify_message'), $swap);

            // No notification for the user themselves, if they're in the list
            if (strpos($notify_address, ee()->session->userdata('email')) !== FALSE) {
                $notify_address = str_replace(ee()->session->userdata('email'), "", $notify_address);
            }

            ee()->load->helper('string');
            // Remove multiple commas
            $notify_address = reduce_multiples($notify_address, ',', TRUE);

            if ($notify_address != '') {
                // Send email
                ee()->load->library('email');

                // Load the text helper
                ee()->load->helper('text');

                foreach (explode(',', $notify_address) as $addy) {
                    ee()->email->EE_initialize();
                    ee()->email->wordwrap = FALSE;
                    ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
                    ee()->email->to($addy);
                    ee()->email->reply_to(ee()->config->item('webmaster_email'));
                    ee()->email->subject($email_tit);
                    ee()->email->message(entities_to_ascii($email_msg));
                    ee()->email->send();
                }
            }
        }

        // Trash the Session and cookies
        ee()->db->where('site_id', ee()->config->item('site_id'))
            ->where('ip_address', ee()->input->ip_address())
            ->where('member_id', (int)$id)
            ->delete('online_users');

        ee()->db->where('session_id', ee()->session->userdata('session_id'))
            ->delete('sessions');

        ee()->input->set_cookie(ee()->session->c_session);
        ee()->input->set_cookie(ee()->session->c_expire);
        ee()->input->set_cookie(ee()->session->c_anon);
        ee()->input->set_cookie('read_topics');
        ee()->input->set_cookie('tracker');

        // Update
        ee()->stats->update_member_stats();

        // Build Success Message
        $url  = ee()->config->item('site_url');
        $name = stripslashes(ee()->config->item('site_name'));

        $data = array('title'    => ee()->lang->line('mbr_delete'),
                      'heading'  => ee()->lang->line('thank_you'),
                      'content'  => ee()->lang->line('mbr_account_deleted'),
                      'redirect' => '',
                      'link'     => array($url, $name)
        );

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

/* End of file DeleteForm.php */
/* Location: ./system/user/addons/Visitor/Tag/DeleteForm.php */