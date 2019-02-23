<?php

namespace DevDemon\Visitor\Service;

class MembersRegister
{
    public function __construct($addon)
    {
        $this->site_id = ee()->config->item('site_id');
        $this->settings = ee('visitor:Settings')->settings;
    }

    public function registerSimple($data=false)
    {
        if (!$data) {
            $data = $_POST;
        }

        $member = ee('Model')->make('Member');
        $member->group_id        = (!$data['group_id']) ? 2 : $data['group_id'];
        $member->screen_name     = ($data['screen_name']) ? $data['screen_name'] : $data['username'];
        $member->username        = $data['username'];
        $member->password        = $data['password'];
        $member->email           = $data['email'];
        $member->ip_address      = ee()->input->ip_address();
        $member->join_date       = ee()->localize->now;
        $member->language        = ee()->config->item('deft_lang');
        $member->timezone        = ee()->config->item('default_site_timezone');
        $member->date_format     = ee()->config->item('date_format');
        $member->time_format     = ee()->config->item('time_format');
        $member->include_seconds = ee()->config->item('include_seconds');

        foreach ($member->getDisplay()->getFields() as $field) {
            if ($field->get('m_field_reg') == 'y' OR $field->isRequired()) {

                /*
                if (ee()->input->post('m_field_id_' . $row['m_field_id']) !== FALSE) {
                    $cust_fields['m_field_id_' . $row['m_field_id']] = $this->input->post('m_field_id_' . $row['m_field_id'], TRUE);
                }
                */
            }
        }

        // Now that we know the password is valid, hash it
        ee()->load->library('auth');
        $hashed_password = ee()->auth->hash_password($member->password);
        $member->password = $hashed_password['password'];
        $member->salt = $hashed_password['salt'];

        // -------------------------------------------
        // 'cp_members_member_create_start' hook.
        //  - Take over member creation when done through the CP
        //  - Added 1.4.2
        //
            ee()->extensions->call('cp_members_member_create_start');
            if (ee()->extensions->end_script === TRUE) return;
        //
        // -------------------------------------------

        $member->save();

        // -------------------------------------------
        // 'cp_members_member_create' hook.
        //  - Additional processing when a member is created through the CP
        //
            ee()->extensions->call('cp_members_member_create', $member->getId(), $member->getValues());
            if (ee()->extensions->end_script === TRUE) return;
        //
        // -------------------------------------------

        ee()->logger->log_action(lang('new_member_added').NBS.$member->username);
        ee()->stats->update_member_stats();

        return $member->getId();
    }

    public function register($doRegister=true, $error_handling = '')
    {
        ee()->load->helper('security');

        $inline_errors = array();

        //ee()->load->language("member");
        /** -------------------------------------
         * /**  Do we allow new member registrations?
         * /** ------------------------------------*/

        if (ee()->config->item('allow_member_registration') == 'n') {
            return array('general', array(ee()->lang->line('member_registrations_not_allowed')));;
        }

        /** ----------------------------------------
         * /**  Is user banned?
         * /** ----------------------------------------*/

        if (ee()->session->userdata['is_banned'] == TRUE) {
            return array('general', array(ee()->lang->line('not_authorized')));
        }

        /** ----------------------------------------
         * /**  Blacklist/Whitelist Check
         * /** ----------------------------------------*/

        if (ee()->blacklist->blacklisted == 'y' && ee()->blacklist->whitelisted == 'n') {
            return array('general', array(ee()->lang->line('not_authorized')));
        }

        ee()->load->helper('url');

        /* -------------------------------------------
                 /* 'member_member_register_start' hook.
                 /*  - Take control of member registration routine
                 /*  - Added EE 1.4.2
                 */
        $edata = ee()->extensions->call('member_member_register_start');
        if (ee()->extensions->end_script === TRUE) return;
        /*
                            /* -------------------------------------------*/


        /** ----------------------------------------
         * /**  Set the default globals
         * /** ----------------------------------------*/

        $default = array('username', 'password', 'password_confirm', 'email', 'screen_name', 'url', 'location');

        foreach ($default as $val) {
            if (!isset($_POST[$val])) $_POST[$val] = '';
        }

        if ($_POST['screen_name'] == '')
            $_POST['screen_name'] = $_POST['username'];

        /** -------------------------------------
         * /**  Instantiate validation class
         * /** -------------------------------------*/
        if (!class_exists('EE_Validate')) {
            require APPPATH.'libraries/Validate.php';
        }

        $VAL = new \EE_Validate(
            array(
                'member_id'        => '',
                'val_type'         => 'new', // new or update
                'fetch_lang'       => TRUE,
                'require_cpw'      => FALSE,
                'enable_log'       => FALSE,
                'username'         => $_POST['username'],
                'cur_username'     => '',
                'screen_name'      => $_POST['screen_name'],
                'cur_screen_name'  => '',
                'password'         => $_POST['password'],
                'password_confirm' => $_POST['password_confirm'],
                'cur_password'     => '',
                'email'            => $_POST['email'],
                'cur_email'        => ''
            )
        );

        // load the language file
        ee()->lang->loadfile('visitor');


        $VAL->validate_email();
        $inline_errors["email"] = $VAL->errors;
        $offset                 = count($VAL->errors);

        /** -------------------------------------
         * /**  Zoo Visitor conditional checking
         * /** -------------------------------------*/

        if ($this->settings['email_is_username'] != 'yes') {
            $VAL->validate_username();

            $inline_errors["username"] = array_slice($VAL->errors, $offset);
            $offset                    = count($VAL->errors);
        }


        if ($this->settings['use_screen_name'] != "no") {
            $VAL->validate_screen_name();
            $inline_errors["screen_name"] = array_slice($VAL->errors, $offset);
            $offset                       = count($VAL->errors);
        }


        $VAL->validate_password();
        $inline_errors["password"] = array_slice($VAL->errors, $offset);
        $offset                    = count($VAL->errors);

        /** -------------------------------------
         * /**  Do we have any custom fields?
         * /** -------------------------------------*/

        $query = ee()->db->query("SELECT m_field_id, m_field_name, m_field_label, m_field_required FROM exp_member_fields");

        $cust_errors = array();
        $cust_fields = array();

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                if ($row['m_field_required'] == 'y' && (!isset($_POST['m_field_id_' . $row['m_field_id']]) OR $_POST['m_field_id_' . $row['m_field_id']] == '')) {
                    $cust_errors[]                       = ee()->lang->line('mbr_field_required') . '&nbsp;' . $row['m_field_label'];
                    $inline_errors[$row['m_field_name']] = array(ee()->lang->line('mbr_field_required') . '&nbsp;' . $row['m_field_label']);
                } elseif (isset($_POST['m_field_id_' . $row['m_field_id']])) {
                    $cust_fields['m_field_id_' . $row['m_field_id']] = ee()->security->xss_clean($_POST['m_field_id_' . $row['m_field_id']]);
                }
            }
        }

        if (isset($_POST['email_confirm']) && $_POST['email'] != $_POST['email_confirm']) {
            $cust_errors[]                  = ee()->lang->line('mbr_emails_not_match');
            $inline_errors["email_confirm"] = array(ee()->lang->line('mbr_emails_not_match'));
        }

        if (ee()->config->item('use_membership_captcha') == 'y') {

            if (!isset($_POST['captcha']) OR $_POST['captcha'] == '') {
                $cust_errors[]            = ee()->lang->line('captcha_required');
                $inline_errors["captcha"] = array(ee()->lang->line('captcha_required'));
            }
        }

        /** ----------------------------------------
         * /**  Do we require captcha?
         * /** ----------------------------------------*/

        if (ee()->config->item('use_membership_captcha') == 'y') {
            $query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_captcha WHERE word='" . ee()->db->escape_str($_POST['captcha']) . "' AND ip_address = '" . ee()->input->ip_address() . "' AND date > UNIX_TIMESTAMP()-7200");

            if ($query->row('count') == 0) {
                $cust_errors[]            = ee()->lang->line('captcha_incorrect');
                $inline_errors["captcha"] = array(ee()->lang->line('captcha_incorrect'));

            }

            //ee()->db->query("DELETE FROM exp_captcha WHERE (word='" . ee()->db->escape_str($_POST['captcha']) . "' AND ip_address = '" . ee()->input->ip_address() . "') OR date < UNIX_TIMESTAMP()-7200");
        }

        if (ee()->config->item('require_terms_of_service') == 'y') {
            if (!isset($_POST['accept_terms'])) {
                $cust_errors[]                 = ee()->lang->line('mbr_terms_of_service_required');
                $inline_errors["accept_terms"] = array(ee()->lang->line('mbr_terms_of_service_required'));
            }
        }

        $errors = array_merge($VAL->errors, $cust_errors);

        // ===========================
        // = Set default membergroup =
        // ===========================
        if (ee()->config->item('req_mbr_activation') == 'manual' OR ee()->config->item('req_mbr_activation') == 'email') {
            $data['group_id'] = 4; // Pending
        } else {
            if (ee()->config->item('default_member_group') == '') {
                $data['group_id'] = 4; // Pending
            } else {
                $data['group_id'] = ee()->config->item('default_member_group');
            }
        }

        // ============================================
        // = Check if there is a membergroup selected =
        // ============================================
        $selected_group_id = ee('visitor:Members')->checkMemberGroupChange($data);

        /** -------------------------------------
         * /**  Display error is there are any
         * /** -------------------------------------*/
        if (count($errors) > 0) {
            return array('submission', $inline_errors);
            //return array('submission', $errors);
        }

        if (!$doRegister) {
            return TRUE;
        }

        /** -------------------------------------
         * /**  Assign the base query data
         * /** -------------------------------------*/

        $data['username'] = $_POST['username'];

        if (version_compare(APP_VER, '2.8.0', '>=')) {
            $data['password'] = sha1($_POST['password']);
        } elseif (version_compare(APP_VER, '2.6.0', '<')) {
            $data['password'] = ee()->functions->hash(stripslashes($_POST['password']));
        } else {
            $data['password'] = do_hash(stripslashes($_POST['password']));
        }

        $data['ip_address']  = ee()->input->ip_address();
        $data['unique_id']   = ee()->functions->random('encrypt');
        $data['join_date']   = ee()->localize->now;
        $data['email']       = $_POST['email'];
        $data['screen_name'] = $_POST['screen_name'];
        $data['url']         = prep_url($_POST['url']);
        $data['location']    = $_POST['location'];
        // overridden below if used as optional fields
        $data['language']    = (ee()->config->item('deft_lang')) ? ee()->config->item('deft_lang') : 'english';
        $data['time_format'] = (ee()->config->item('time_format')) ? ee()->config->item('time_format') : 'us';
        $data['timezone']    = (ee()->config->item('default_site_timezone') && ee()->config->item('default_site_timezone') != '') ? ee()->config->item('default_site_timezone') : ee()->config->item('server_timezone');

        if (version_compare(APP_VER, '2.6.0', '<')) {
            $data['daylight_savings'] = (ee()->config->item('default_site_dst') && ee()->config->item('default_site_dst') != '') ? ee()->config->item('default_site_dst') : ee()->config->item('daylight_savings');
        }

        // ==========================
        // = Standard member fields =
        // ==========================

        $fields = array('bday_y',
            'bday_m',
            'bday_d',
            'url',
            'location',
            'occupation',
            'interests',
            'aol_im',
            'icq',
            'yahoo_im',
            'msn_im',
            'bio'
        );

        foreach ($fields as $val) {
            if (ee()->input->post($val)) {
                $data[$val] = (isset($_POST[$val])) ? ee()->security->xss_clean($_POST[$val]) : '';
                unset($_POST[$val]);
            }
        }

        if (isset($data['bday_d']) && is_numeric($data['bday_d']) && is_numeric($data['bday_m'])) {
            $year  = ($data['bday_y'] != '') ? $data['bday_y'] : date('Y');
            $mdays = ee()->localize->fetch_days_in_month($data['bday_m'], $year);

            if ($data['bday_d'] > $mdays) {
                $data['bday_d'] = $mdays;
            }
        }

        // Optional Fields
        $optional = array('bio'         => 'bio',
                          'language'    => 'deft_lang',
                          'timezone'    => 'server_timezone',
                          'time_format' => 'time_format');

        foreach ($optional as $key => $value) {
            if (isset($_POST[$value])) {
                $data[$key] = $_POST[$value];
            }
        }

        /*
        if (ee()->input->post('daylight_savings') == 'y') {
            $data['daylight_savings'] = 'y';
        }
        elseif (ee()->input->post('daylight_savings') == 'n') {
            $data['daylight_savings'] = 'n';
        }
        */
        // We generate an authorization code if the member needs to self-activate

        if (ee()->config->item('req_mbr_activation') == 'email') {
            $data['authcode'] = ee()->functions->random('alnum', 10);
        }

        /** -------------------------------------
         * /**  Insert basic member data
         * /** -------------------------------------*/
        ee()->db->query(ee()->db->insert_string('exp_members', $data));

        $member_id = ee()->db->insert_id();


        // =============================================
        // = Override the screenname for use in emails =
        // =============================================
        $screen_name_overriden = $this->get_override_screen_name();
        $data['screen_name']   = ($screen_name_overriden !== FALSE) ? $screen_name_overriden : $data['screen_name'];


        // =========================================================================================
        // = Store the selected membergroup if it is defined in the form AND activation is required =
        // ==========================================================================================

        if (isset($selected_group_id) AND is_numeric($selected_group_id) AND $selected_group_id != '1') {
            if (ee()->config->item('req_mbr_activation') == 'email' || ee()->config->item('req_mbr_activation') == 'manual') {
                $activation_data              = array();
                $activation_data['member_id'] = $member_id;
                $activation_data['group_id']  = $selected_group_id;

                ee()->db->insert('visitor_activation_membergroup', $activation_data);
            }
        }

        // =====================
        // = HASH THE PASSWORD =
        // =====================
        ee()->load->library('auth');
        $hashed_pair = ee()->auth->hash_password($_POST['password']);

        if ($hashed_pair === FALSE) {

        } else {
            ee()->db->where('member_id', (int)$member_id);
            ee()->db->update('members', $hashed_pair);
        }


        /** -------------------------------------
         * /**  Insert custom fields
         * /** -------------------------------------*/
        $cust_fields['member_id'] = $member_id;

        ee()->db->query(ee()->db->insert_string('exp_member_data', $cust_fields));


        /** -------------------------------------
         * /**  Create a record in the member homepage table
         * /** -------------------------------------*/
        // This is only necessary if the user gains CP access, but we'll add the record anyway.

        ee()->db->query(ee()->db->insert_string('exp_member_homepage', array('member_id' => $member_id)));

        /** -------------------------------------
         * /**  Mailinglist Subscribe
         * /** -------------------------------------*/

        $mailinglist_subscribe = FALSE;

        if (isset($_POST['mailinglist_subscribe']) && is_numeric($_POST['mailinglist_subscribe'])) {
            // Kill duplicate emails from authorizatin queue.
            ee()->db->query("DELETE FROM exp_mailing_list_queue WHERE email = '" . ee()->db->escape_str($_POST['email']) . "'");

            // Validate Mailing List ID
            $query = ee()->db->query("SELECT COUNT(*) AS count
                                 FROM exp_mailing_lists
                                 WHERE list_id = '" . ee()->db->escape_str($_POST['mailinglist_subscribe']) . "'");

            // Email Not Already in Mailing List
            $results = ee()->db->query("SELECT count(*) AS count
                                    FROM exp_mailing_list
                                    WHERE email = '" . ee()->db->escape_str($_POST['email']) . "'
                                    AND list_id = '" . ee()->db->escape_str($_POST['mailinglist_subscribe']) . "'");

            /** -------------------------------------
             * /**  INSERT Email
             * /** -------------------------------------*/

            if ($query->row('count') > 0 && $results->row('count') == 0) {
                $mailinglist_subscribe = TRUE;

                $code = ee()->functions->random('alnum', 10);

                if (ee()->config->item('req_mbr_activation') == 'email') {
                    // Activated When Membership Activated
                    ee()->db->query("INSERT INTO exp_mailing_list_queue (email, list_id, authcode, date)
                                VALUES ('" . ee()->db->escape_str($_POST['email']) . "', '" . ee()->db->escape_str($_POST['mailinglist_subscribe']) . "', '" . $code . "', '" . time() . "')");
                } elseif (ee()->config->item('req_mbr_activation') == 'manual') {
                    // Mailing List Subscribe Email
                    ee()->db->query("INSERT INTO exp_mailing_list_queue (email, list_id, authcode, date)
                                VALUES ('" . ee()->db->escape_str($_POST['email']) . "', '" . ee()->db->escape_str($_POST['mailinglist_subscribe']) . "', '" . $code . "', '" . time() . "')");

                    ee()->lang->loadfile('mailinglist');
                    $action_id = ee()->functions->fetch_action_id('Mailinglist', 'authorize_email');

                    $swap = array(
                        'activation_url' => ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id . '&id=' . $code,
                        'site_name'      => stripslashes(ee()->config->item('site_name')),
                        'site_url'       => ee()->config->item('site_url')
                    );

                    $template  = ee()->functions->fetch_email_template('mailinglist_activation_instructions');
                    $email_tit = ee()->functions->var_swap($template['title'], $swap);
                    $email_msg = ee()->functions->var_swap($template['data'], $swap);

                    /** ----------------------------
                     * /**  Send email
                     * /** ----------------------------*/

                    ee()->load->library('email');
                    ee()->email->wordwrap = true;
                    ee()->email->mailtype = 'plain';
                    ee()->email->priority = '3';

                    ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
                    ee()->email->to($_POST['email']);
                    ee()->email->subject($email_tit);
                    ee()->email->message($email_msg);
                    ee()->email->send();
                } else {
                    // Automatically Accepted
                    ee()->db->query("INSERT INTO exp_mailing_list (list_id, authcode, email, ip_address)
                                          VALUES ('" . ee()->db->escape_str($_POST['mailinglist_subscribe']) . "', '" . $code . "', '" . ee()->db->escape_str($_POST['email']) . "', '" . ee()->db->escape_str(ee()->input->ip_address()) . "')");
                }
            }
        }

        /** -------------------------------------
         * /**  Update
         * /** -------------------------------------*/

        if (ee()->config->item('req_mbr_activation') == 'none') {
            ee()->stats->update_member_stats();
        }


        /** -------------------------------------
         * /**  Send admin notifications
         * /** -------------------------------------*/
        if (ee()->config->item('new_member_notification') == 'y' AND ee()->config->item('mbr_notification_emails') != '') {
            $name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

            $swap = array(
                'name'              => $name,
                'site_name'         => stripslashes(ee()->config->item('site_name')),
                'control_panel_url' => ee()->config->item('cp_url'),
                'username'          => $data['username'],
                'email'             => $data['email']
            );

            $template  = ee()->functions->fetch_email_template('admin_notify_reg');
            $email_tit = $this->_var_swap($template['title'], $swap);
            $email_msg = $this->_var_swap($template['data'], $swap);

            ee()->load->helper('string');
            // Remove multiple commas
            $notify_address = reduce_multiples(ee()->config->item('mbr_notification_emails'), ',', TRUE);

            /** ----------------------------
             * /**  Send email
             * /** ----------------------------*/

            // Load the text helper
            ee()->load->helper('text');

            ee()->load->library('email');
            ee()->email->wordwrap = true;
            ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
            ee()->email->to($notify_address);
            ee()->email->subject($email_tit);
            ee()->email->message(entities_to_ascii($email_msg));
            ee()->email->Send();
        }

        // -------------------------------------------
        // 'member_member_register' hook.
        //  - Additional processing when a member is created through the User Side
        //  - $member_id added in 2.0.1
        //
        $edata = ee()->extensions->call('member_member_register', $data, $member_id);
        if (ee()->extensions->end_script === TRUE) return;
        //
        // -------------------------------------------

        /** -------------------------------------
         * /**  Zoo Visitor assignment
         * /** -------------------------------------*/

        $member_data              = $data;
        $member_data["member_id"] = $member_id;

        /** -------------------------------------
         * /**  Send user notifications
         * /** -------------------------------------*/
        if (ee()->config->item('req_mbr_activation') == 'email') {
            $action_id = ee()->functions->fetch_action_id('Member', 'activate_member');

            $name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

            $board_id = (ee()->input->get_post('board_id') !== FALSE && is_numeric(ee()->input->get_post('board_id'))) ? ee()->input->get_post('board_id') : 1;

            $forum_id = (ee()->input->get_post('FROM') == 'forum') ? '&r=f&board_id=' . $board_id : '';

            $add = ($mailinglist_subscribe !== TRUE) ? '' : '&mailinglist=' . $_POST['mailinglist_subscribe'];

            $swap = array(
                'name'           => $name,
                'activation_url' => ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id . '&id=' . $data['authcode'] . $forum_id . $add,
                'site_name'      => stripslashes(ee()->config->item('site_name')),
                'site_url'       => ee()->config->item('site_url'),
                'username'       => $data['username'],
                'email'          => $data['email']
            );

            $template  = ee()->functions->fetch_email_template('mbr_activation_instructions');
            $email_tit = $this->_var_swap($template['title'], $swap);
            $email_msg = $this->_var_swap($template['data'], $swap);

            /** ----------------------------
             * /**  Send email
             * /** ----------------------------*/

            // Load the text helper
            ee()->load->helper('text');

            ee()->load->library('email');
            ee()->email->wordwrap = true;
            ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
            ee()->email->to($data['email']);
            ee()->email->subject($email_tit);
            ee()->email->message(entities_to_ascii($email_msg));
            ee()->email->Send();

            $message = ee()->lang->line('mbr_membership_instructions_email');
        } elseif (ee()->config->item('req_mbr_activation') == 'manual') {
            $message = ee()->lang->line('mbr_admin_will_activate');
        } else {
            /** ----------------------------------------
             * /**  Log user is handled at the end of the extension
             * /** ----------------------------------------*/

        }


        /** ----------------------------------------
         * /**  Build the message
         * /** ----------------------------------------*/

        if (ee()->input->get_post('FROM') == 'forum') {
            if (ee()->input->get_post('board_id') !== FALSE && is_numeric(ee()->input->get_post('board_id'))) {
                $query = ee()->db->query("SELECT board_forum_url, board_id, board_label FROM exp_forum_boards WHERE board_id = '" . ee()->db->escape_str(ee()->input->get_post('board_id')) . "'");
            } else {
                $query = ee()->db->query("SELECT board_forum_url, board_id, board_label FROM exp_forum_boards WHERE board_id = '1'");
            }

            $site_name = $query->row('board_label');
            $return    = $query->row('board_forum_url');
        } else {
            $site_name = (ee()->config->item('site_name') == '') ? ee()->lang->line('back') : stripslashes(ee()->config->item('site_name'));
            $return    = ee()->config->item('site_url');
        }

        $data = array('title'       => ee()->lang->line('mbr_registration_complete'),
                      'heading'     => ee()->lang->line('thank_you'),
                      'content'     => ee()->lang->line('mbr_registration_completed'),
                      'redirect'    => '',
                      'link'        => array($return, $site_name),
                      'result'      => 'registration_complete',
                      'member_data' => $member_data
        );

        //ee()->output->show_message($data);
        return $data;
    }

    public function updateProfile($do_update=true)
    {
        $errors = array();

        //PASSWORD CHANGE
        if (array_key_exists('new_password', $_POST) && $_POST['new_password'] != '') {
            $_POST['password']         = $_POST['new_password'];
            $_POST['password_confirm'] = $_POST['new_password_confirm'];
        } else {
            $_POST['password']         = '';
            $_POST['password_confirm'] = '';
        }

        ////////////////////////

        if (array_key_exists('password', $_POST) || array_key_exists('username', $_POST) || array_key_exists('screen_name', $_POST)) {

            if ((isset($_POST['username']) && $_POST['username'] != ee()->session->userdata('username')) ||
                (isset($_POST['screen_name']) && $_POST['screen_name'] != ee()->session->userdata('screen_name')) ||
                (isset($_POST['new_password']) && $_POST['new_password'] != '')
            ) {

                if (!isset($_POST['username'])) {
                    $query             = ee()->db->select('username, screen_name')->from('members')->where('member_id', ee()->input->post('author_id'))->get();
                    $_POST['username'] = $query->row('username'); //ee()->session->userdata('username');
                }
                if (!isset($_POST['screen_name'])) {
                    //$_POST['screen_name'] = ee()->session->userdata('screen_name');
                    $query                = ee()->db->select('username, screen_name')->from('members')->where('member_id', ee()->input->post('author_id'))->get();
                    $_POST['screen_name'] = $query->row('screen_name');
                }
                if (!isset($_POST['password'])) {
                    $_POST['password'] = $_POST['current_password'];
                }

                $errors = array_merge($errors, $this->updateUserPass($do_update));
            }
        }

        if (array_key_exists('email', $_POST) && $_POST['email'] != ee()->session->userdata('email')) {
            //current password in this function is just "password"
            $_POST['password'] = $_POST['current_password'];
            $errors = array_merge($errors, $this->updateEmail($do_update, ee()->input->post('author_id')));
        }

        return $errors;
    }

    public function updateUserPass($do_update = TRUE, $error_handling = '')
    {

        if (ee()->input->post('author_id')) {
            $errors = array();

            if (!isset($_POST['current_password'])) {
                $errors['invalid_action'] = ee()->lang->line('invalid_action');
            }

            $query = ee()->db->query("SELECT username, screen_name FROM exp_members WHERE member_id = '" . ee()->input->post('author_id') . "'");

            if ($query->num_rows() == 0) {
                return FALSE;
            }

            if (ee()->config->item('allow_username_change') != 'y') {
                $_POST['username'] = $query->row('username');
            }

            // If the screen name field is empty, we'll assign is
            // from the username field.

            if ($_POST['screen_name'] == '')
                $_POST['screen_name'] = $_POST['username'];

            if (!isset($_POST['username']))
                $_POST['username'] = '';

            /** -------------------------------------
             * /**  Validate submitted data
             * /** -------------------------------------*/
            if (!class_exists('EE_Validate')) {
                require APPPATH.'libraries/Validate.php';
            }

            $VAL = new \EE_Validate(
                array(
                    'member_id'        => ee()->input->post('author_id'),
                    'val_type'         => 'update', // new or update
                    'fetch_lang'       => TRUE,
                    'require_cpw'      => TRUE,//FALSE,
                    'enable_log'       => FALSE,
                    'username'         => $_POST['username'],
                    'cur_username'     => $query->row('username'),
                    'screen_name'      => $_POST['screen_name'],
                    'cur_screen_name'  => $query->row('screen_name'),
                    'password'         => $_POST['password'],
                    'password_confirm' => $_POST['password_confirm'],
                    'cur_password'     => $_POST['current_password']
                )
            );

            // load the language file
            ee()->lang->loadfile('visitor');

            $errors['current_password'] = $VAL->errors;
            $offset                     = count($VAL->errors);

            $VAL->validate_screen_name();
            $errors['screen_name'] = array_slice($VAL->errors, $offset);
            $offset                = count($VAL->errors);


            if (ee()->config->item('allow_username_change') == 'y') {
                $VAL->validate_username();
                $errors['username'] = array_slice($VAL->errors, $offset);
                $offset             = count($VAL->errors);
            }

            if ($_POST['password'] != '') {
                $VAL->validate_password();
                $errors['password'] = array_slice($VAL->errors, $offset);
                $offset             = count($VAL->errors);
            }
            /** -------------------------------------
             * /**  Display error is there are any
             * /** -------------------------------------*/

            if (count($VAL->errors) > 0) {
                return $errors;
            }

            //Just validate, no update
            if (!$do_update) {
                return array();
            }

            /** -------------------------------------
             * /**  Update "last post" forum info if needed
             * /** -------------------------------------*/

            if ($query->row('screen_name') != $_POST['screen_name'] AND ee()->config->item('forum_is_installed') == "y") {
                ee()->db->query("UPDATE exp_forums SET forum_last_post_author = '" . ee()->db->escape_str($_POST['screen_name']) . "' WHERE forum_last_post_author_id = '" . ee()->input->post('author_id') . "'");
                ee()->db->query("UPDATE exp_forum_moderators SET mod_member_name = '" . ee()->db->escape_str($_POST['screen_name']) . "' WHERE mod_member_id = '" . ee()->input->post('author_id') . "'");
            }

            /** -------------------------------------
             * /**  Assign the query data
             * /** -------------------------------------*/
            $data['screen_name'] = $_POST['screen_name'];

            if (ee()->config->item('allow_username_change') == 'y') {
                $data['username'] = $_POST['username'];
            }

            // Was a password submitted?

            $pw_change = '';

            if ($_POST['password'] != '') {
                //$data['password'] = ee()->functions->hash(stripslashes($_POST['password']));

                ee()->load->library('auth');
                ee()->auth->update_password(ee()->input->post('author_id'), $_POST['password']);
            }

            ee()->db->query(ee()->db->update_string('exp_members', $data, "member_id = '" . ee()->input->post('author_id') . "'"));

            /** -------------------------------------
             * /**  Update comments if screen name has changed
             * /** -------------------------------------*/
            if ($query->row('screen_name') != $_POST['screen_name']) {
                ee()->db->select('module_id');
                ee()->db->where('module_name', 'Comment');
                $module_query = ee()->db->get('modules');

                if ($module_query->num_rows() > 0) {
                    ee()->db->query(ee()->db->update_string('exp_comments', array('name' => $_POST['screen_name']), "author_id = '" . ee()->input->post('author_id') . "'"));
                }

                ee()->session->userdata['screen_name'] = stripslashes($_POST['screen_name']);
            }

            return array('success');
        }
    }

    public function updateEmail($do_update=true, $member_id)
    {
        $errors = array();

        if (!isset($_POST['email'])) {
            $errors['invalid_actions'] = ee()->lang->line('invalid_action');
            return $errors;
        }

        /** ----------------------------------------
         * /**  Blacklist/Whitelist Check
         * /** ----------------------------------------*/
        if (ee()->blacklist->blacklisted == 'y' && ee()->blacklist->whitelisted == 'n') {
            $errors['not_authorized'] = ee()->lang->line('not_authorized');
            return $errors;
        }

        /** -------------------------------------
         * /**  Validate submitted data
         * /** -------------------------------------*/
        if (!class_exists('EE_Validate')) {
            require APPPATH.'libraries/Validate.php';
        }

        $query = ee()->db->select('email, password')->from('members')->where('member_id', $member_id)->get();

        $VAL = new \EE_Validate(
            array(
                'member_id'    => $member_id,
                'val_type'     => 'update', // new or update
                'fetch_lang'   => TRUE,
                'require_cpw'  => TRUE,//FALSE, //ADDED IN ZOO VISITOR
                'enable_log'   => FALSE,
                'email'        => $_POST['email'],
                'cur_email'    => $query->row('email'),
                'cur_password' => $_POST['current_password']
            )
        );

        // load the language file
        ee()->lang->loadfile('visitor');

        $errors['current_password'] = $VAL->errors;
        $offset                     = count($VAL->errors);

        $VAL->validate_email();
        $errors['email'] = array_slice($VAL->errors, $offset);
        $offset          = count($VAL->errors);

        if (count($VAL->errors) > 0) {
            return $errors;
        }

        if (!$do_update) {
            return array();
        }

        /** -------------------------------------
         * /**  Assign the query data
         * /** -------------------------------------*/

        $data = array(
            'email'               => $_POST['email'],
            'accept_admin_email'  => (isset($_POST['accept_admin_email'])) ? 'y' : 'n',
            'accept_user_email'   => (isset($_POST['accept_user_email'])) ? 'y' : 'n',
            'notify_by_default'   => (isset($_POST['notify_by_default'])) ? 'y' : 'n',
            'notify_of_pm'        => (isset($_POST['notify_of_pm'])) ? 'y' : 'n',
            'smart_notifications' => (isset($_POST['smart_notifications'])) ? 'y' : 'n'
        );

        ee()->db->query(ee()->db->update_string('exp_members', $data, "member_id = '" . $member_id . "'"));

        /** -------------------------------------
         * /**  Update comments and log email change
         * /** -------------------------------------*/
        if ($query->row('email') != $_POST['email']) {
            ee()->db->select('module_id');
            ee()->db->where('module_name', 'Comment');
            $module_query = ee()->db->get('modules');

            if ($module_query->num_rows() > 0) {
                ee()->db->query(ee()->db->update_string('exp_comments', array('email' => $_POST['email']), "author_id = '" . $member_id . "'"));
            }
        }

        return array();
    }

    function get_override_screen_name()
    {

        //replace screen_name_override with field names
        ee()->db->select('field_name, field_id');
        ee()->db->where('site_id', ee()->config->item('site_id'));
        ee()->db->order_by('field_id', 'desc');

        $query = ee()->db->get('channel_fields');
        if ($query->num_rows() > 0) {
            $screen_name = $this->settings['screen_name_override'];
            foreach ($query->result_array() as $row) {
                /**
                 * @author  Stephen Lewis
                 *
                 * Additional check to ensure that $_POST data is a string. Ensures ZV
                 * doesn't choke on DropDate, or any other "array" fields.
                 */

                $field_name = $row['field_name'];

                $value = (isset($_POST[$field_name]) && is_string($_POST[$field_name]))
                    ? $_POST[$field_name] : '';

                /* End of modifications. */

                $screen_name = str_replace('field_id_' . $row['field_id'], $value,
                    $screen_name);
            }

            return (str_replace(' ', '', $screen_name) != '') ? $screen_name : FALSE;

        } else {
            return FALSE;
        }


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
}

/* End of file MembersRegister.php */
/* Location: ./system/user/addons/visitor/Service/MembersRegister.php */