<?php

namespace DevDemon\Visitor\Service;

class Members
{
    public $site_id;
    public $channel_id;
    public $field_group_id;

    public function __construct($addon)
    {
        $this->setChannelAndFieldGroup();
        $this->site_id = ee()->config->item('site_id');
    }

    public function getMemberId($entry_id)
    {
        if (!$entry_id) return false;

        $entry = ee('Model')->get('ChannelEntry', $entry_id)->fields('author_id')->first();

        if (!$entry) return false;

        // If the entry was found, check if the author exists
        $member = ee('Model')->get('Member', $entry->author_id)->fields('member_id')->first();

        if (!$member) return false;

        return $member->member_id;
    }

    public function getVisitorId($member_id='current')
    {
        if ($member_id == 'current' || $member_id == '') {
            if (!isset(ee()->session->userdata)) {
                //no session is available, this will handle trying to get the gloval var zoo_visitor_id when user is not logged in;
                $member_id = 0;
            } else {
                $member_id = ee()->session->userdata('member_id');
            }
        }

        if (!$member_id) return false;

        $channel_id = ee('visitor:Settings')->settings['member_channel_id'];
        if (!$channel_id) return false;

        $visitor_query = ee()->db->select('entry_id')->where('author_id', $member_id)->where('channel_id', $channel_id)->order_by('entry_id', 'desc')->limit(1)->get('channel_titles');

        if ($visitor_query->num_rows() == 0) {
            return false;
        }

        return $visitor_query->row()->entry_id;
    }

    public function getVisitorIdByUsername($username)
    {
        if (!$username) return false;

        $channel_id = ee('visitor:Settings')->settings['member_channel_id'];
        if (!$channel_id) return false;

        ee()->db->select('ct.entry_id');
        ee()->db->from('members AS m');
        ee()->db->join('channel_titles AS ct', "ct.author_id = m.member_id", 'left');
        ee()->db->where('m.username', $username);
        ee()->db->where('ct.channel_id', $channel_id);
        $query = ee()->db->get();

        if ($query->num_rows() == 0) return false;

        return $query->first_row()->entry_id;
    }

    public function getVisitorEntryTitle($member_id = 'current')
    {
        if ($member_id == 'current') {
            $member_id = ee()->session->userdata['member_id'];
        }

        if (!$member_id) return false;

        $channel_id = ee('visitor:Settings')->settings['member_channel_id'];
        if (!$channel_id) return false;

        ee()->db->select('title');
        ee()->db->where('author_id', $member_id);
        ee()->db->where('channel_id', $channel_id);
        ee()->db->order_by('entry_id', 'desc');
        ee()->db->limit(1);
        $visitor_query = ee()->db->get('channel_titles');

        if (!$visitor_query->num_rows()) return false;

        return $visitor_query->first_row()->title;
    }

    public function registerMemberSimple($data=false)
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

    public function getMemberGroups()
    {
        $memberGroups = array();
        $mgroups = ee('Model')->get('MemberGroup');

        // Member groups assignment
        if (ee()->session->userdata('group_id') != 1) {
            $mgroups->filter('is_locked', 'n');
        }

        $mgroups = $mgroups->fields('group_id', 'group_title')->all();

        foreach ($mgroups as $group) {
            // If the current user is not a Super Admin
            // we'll limit the member groups in the list
            if ($group->group_id == 1 && ee()->session->userdata('group_id') != 1) {
                continue;
            }

            $memberGroups[$group->group_id] = $group->group_title;
        }

        return $memberGroups;
    }

    public function syncMemberStatus($member_id, $entry_id=false)
    {
        if (!$member_id) return false;

        $member = ee('Model')->get('Member', $member_id)->first();
        if (!$member) return false;

        $group_id = $member->group_id;

        if (!$entry_id) {
            $entry_id = $this->getVisitorId($member_id);
        }

        if ($entry_id) {
            $this->updateMemberStatus($entry_id, $member_id, $group_id);
        }
    }

    public function updateMemberStatus($entry_id, $member_id, $group_id)
    {
        if (ee('visitor:Settings')->settings['membergroup_as_status'] == 'no') return;
        $site_id = ee()->config->item('site_id');

        $group = ee('Model')->get('MemberGroup', $group_id)->first();
        if (!$group) return;

        // Check if the status group exists
        $statusGroup = ee('Model')->get('StatusGroup')
        ->filter('group_name', 'IN', array('Visitor Membergroup', 'Zoo Visitor Membergroup'))
        ->filter('site_id', $site_id)
        ->first();

        // Does the status itself exists?
        $statusLabel = $this->formatStatus($group->group_title, $group->group_id);
        $statusDb = ee('Model')->get('Status')->filter('status', $statusLabel)->filter('group_id', $statusGroup->group_id)->first();

        // Create it
        if (!$statusDb) {
            $statusDb = ee('Model')->make('Status');
            $statusDb->status = $statusLabel;
            $statusDb->group_id = $statusGroup->group_id;
            $statusDb->site_id = $site_id;
            $statusDb->save();
        }

        /* // Issues when doing this from sessions_end, for now let's use direct query
        $entry = ee('Model')->get('ChannelEntry')->filter('entry_id', $entry_id)->first();
        $entry->status = $statusLabel;
        $entry->save();
        */

        ee()->db->set('status', $statusLabel)->where('entry_id', $entry_id)->update('channel_titles');
    }

    public function updateScreenName($member_id='current', $entry_id=false)
    {
        if ($member_id == 'current') $member_id = ee()->session->userdata('member_id');
        if (!$member_id) return false;

        $member = ee('Model')->get('Member', $member_id)->first();
        if (!$member) return false;

        $group_id = $member->group_id;

        if (!$entry_id) {
            $entry_id = $this->getVisitorId($member_id);
        }

        if (!$entry_id) return false;

        $screen_name = '';
        ee()->db->where('entry_id', $entry_id);
        $query = ee()->db->get('channel_data');

        if ($query->num_rows() > 0) {
            $screen_name = ee('visitor:Settings')->settings['screen_name_override'];
            $fields      = array_reverse($query->row_array());

            foreach ($fields as $key => $val) {
                $screen_name = str_replace($key, $val, $screen_name);
            }
        }

        $screen_name_check = str_replace(' ', '', $screen_name);
        if (!$screen_name_check) return false;

        $member->screen_name = $screen_name;
        $member->save();

        return $screen_name;
    }

    public function syncBackToMember($entry_id, $member_id=false)
    {
        if (ee('visitor:Settings')->settings['sync_back_to_member'] != 'yes') return;

        if (!$member_id) {
            $entry = ee('Model')->get('ChannelEntry', $entry_id)->first();
            if (!$entry) return;

            $member_id = $entry->author_id;
        }

        // Get all entry data
        ee()->db->select('*');
        ee()->db->where('entry_id', $entry_id);
        $q = ee()->db->get('channel_data');

        if ($q->num_rows() == 0) return;

        $row = $q->row_array();
        $data = array();

        foreach ($sync_fields as $e_field_id => $m_field_id) {
            $data['m_field_id_' . $m_field_id] = $row['field_id_' . $e_field_id];
        }

        ee()->db->where('member_id', $member_id);
        ee()->db->update('member_data', $data);
    }

    public function updateEntryTitle($entry_id, $member_id=false)
    {
        if (is_object($entry_id)) {
            $entry = $entry_id;
            $entry_id = $entry->entry_id;
        } else {
            $entry = ee('Model')->get('ChannelEntry', $entry_id)->first();
        }

        $member = null;

        if ($member_id) {
            $member = ee('Model')->get('Member', $member_id)->first();
        } else {
            $member_id = ee('visitor:Members')->getMemberId($entry_id);
            if ($member_id) $member = ee('Model')->get('Member', $member_id)->first();
        }

        if (!$member) {
            return false;
        }

        if (ee('visitor:Settings')->settings['title_override'] != '') {
            $title = ee('visitor:Settings')->settings['title_override'];

            ee()->db->where('entry_id', $entry_id);
            //ee()->db->where('site_id',ee()->config->item('site_id'));
            $query = ee()->db->get('channel_data');

            if ($query->num_rows() > 0) {

                $fields = array_reverse($query->row_array());
                foreach ($fields as $key => $val) {
                    $title = str_replace($key, $val, $title);
                }
            }

            ee()->db->where('member_id', $member->member_id);
            $query_mem = ee()->db->get('members');

            if ($query_mem->num_rows() > 0) {

                $fields = $query_mem->row_array();
                foreach ($fields as $key => $val) {
                    $title = str_replace($key, $val, $title);
                }
            }

            // = if custom fields are empty, fall back to screenname  =
            $title = (str_replace(' ', '', $title) == "") ? $member->screen_name : $title;

        } else {
            // $title = $member->email;
            //
            // if(ee('visitor:Settings')->settings['email_is_username'] != 'yes')
            // {
            //  $title .= $member->username;
            // }
            // if(ee('visitor:Settings')->settings['use_screen_name'] != "no")
            // {
            $title = $member->screen_name;
            //}

        }

        $titlecheck = str_replace(' ', '', $title);

        if ($titlecheck != '') {

            $url_title = url_title($title, ee()->config->item('word_separator'), true);
            $url_title = $this->validateUrlTitle($url_title, $title, true, $entry_id, ee('visitor:Settings')->settings['member_channel_id']);

            // =================================================================================================
            // = there is a problem with converting the title to a volid url title (numbers, foreign chars...) =
            // =================================================================================================
            if (!$url_title) {
                $url_title = $member->screen_name;
                $url_title = url_title($url_title, ee()->config->item('word_separator'), true);
                $url_title = $this->validateUrlTitle($url_title, $url_title, true, $entry_id, ee('visitor:Settings')->settings['member_channel_id']);

                if (!$url_title) {
                    $url_title = $member->username;
                    $url_title = url_title($url_title, ee()->config->item('word_separator'), true);
                    $url_title = $this->validateUrlTitle($url_title, $url_title, true, $entry_id, ee('visitor:Settings')->settings['member_channel_id']);

                    if (!$url_title) {
                        $url_title = $member->email;
                        $url_title = url_title($url_title, ee()->config->item('word_separator'), true);
                        $url_title = $this->validateUrlTitle($url_title, $url_title, true, $entry_id, ee('visitor:Settings')->settings['member_channel_id']);
                    }
                }
            }
            //$url_title = $this->uniqueUrlTitle($url_title, $entry_id, ee('visitor:Settings')->settings['member_channel_id']);


            // //set channel entry title and url title
            if ($url_title != false) {
                ee()->db->update('channel_titles', array('title' => $title, 'url_title' => $url_title), 'entry_id = ' . $entry_id);
            }
        }
    }

    protected function uniqueUrlTitle($url_title, $self_id, $type_id = '', $type = 'channel')
    {
        if ($type_id == '') {
            return false;
        }

        switch ($type) {
            case 'category'    :
                $table           = 'categories';
                $url_title_field = 'cat_url_title';
                $type_field      = 'group_id';
                $self_field      = 'category_id';
                break;
            default            :
                $table           = 'channel_titles';
                $url_title_field = 'url_title';
                $type_field      = 'channel_id';
                $self_field      = 'entry_id';
                break;
        }

        // Field is limited to 75 characters, so trim url_title before querying
        $url_title = substr($url_title, 0, 75);

        if ($self_id != '') {
            ee()->db->where(array($self_field . ' !=' => $self_id));
        }

        ee()->db->where(array($url_title_field => $url_title,
                                   $type_field      => $type_id));
        $count = ee()->db->count_all_results($table);

        if ($count > 0) {
            // We may need some room to add our numbers- trim url_title to 70 characters
            $url_title = substr($url_title, 0, 70);

            // Check again
            if ($self_id != '') {
                ee()->db->where(array($self_field . ' !=' => $self_id));
            }

            ee()->db->where(array($url_title_field => $url_title,
                                       $type_field      => $type_id));
            $count = ee()->db->count_all_results($table);

            if ($count > 0) {
                if ($self_id != '') {
                    ee()->db->where(array($self_field . ' !=' => $self_id));
                }

                ee()->db->select("{$url_title_field}, MID({$url_title_field}, " . (strlen($url_title) + 1) . ") + 1 AS next_suffix", false);
                ee()->db->where("{$url_title_field} REGEXP('" . preg_quote(ee()->db->escape_str($url_title)) . "[0-9]*$')");
                ee()->db->where(array($type_field => $type_id));
                ee()->db->order_by('next_suffix', 'DESC');
                ee()->db->limit(1);
                $query = ee()->db->get($table);

                // Did something go tragically wrong?  Is the appended number going to kick us over the 75 character limit?
                if ($query->num_rows() == 0 OR ($query->row('next_suffix') > 99999)) {
                    return false;
                }

                $url_title = $url_title . $query->row('next_suffix');

                // little double check for safety

                if ($self_id != '') {
                    ee()->db->where(array($self_field . ' !=' => $self_id));
                }

                ee()->db->where(array($url_title_field => $url_title,
                                           $type_field      => $type_id));
                $count = ee()->db->count_all_results($table);

                if ($count > 0) {
                    return false;
                }
            }
        }

        return $url_title;
    }

    public function validateUrlTitle($url_title = '', $title = '', $update = false, $entry_id, $channel_id)
    {
        $word_separator = ee()->config->item('word_separator');

        ee()->load->helper('url');

        if (!trim($url_title)) {
            $url_title = url_title($title, $word_separator, true);
        }

        // Remove extraneous characters

        if ($update) {
            ee()->db->select('url_title');
            $url_query = ee()->db->get_where('channel_titles', array('entry_id' => $entry_id));

            if ($url_query->row('url_title') != $url_title) {
                $url_title = url_title($url_title, $word_separator);
            }
        } else {
            $url_title = url_title($url_title, $word_separator);
        }

        // URL title cannot be a number

        if (is_numeric($url_title)) {
            return false;
            //$this->_set_error('url_title_is_numeric', 'url_title');
        }

        // It also cannot be empty

        if (!trim($url_title)) {
            return false;
            //$this->_set_error('unable_to_create_url_title', 'url_title');
        }

        // And now we need to make sure it's unique

        if ($update) {
            $url_title = $this->uniqueUrlTitle($url_title, $entry_id, $channel_id);
        } else {
            $url_title = $this->uniqueUrlTitle($url_title, '', $channel_id);
        }

        // One more safety

        if (!$url_title) {
            return false;
            //$this->_set_error('unable_to_create_url_title', 'url_title');
        }

        // And lastly, we prevent this potentially problematic case

        if ($url_title == 'index') {
            return false;
            //$this->_set_error('url_title_is_index', 'url_title');
        }

        return $url_title;
    }

    public function updateNativeMemberFields($member_id, $entry_id=false)
    {
        // TODO: Rewrite this...

        if (!$entry_id) {
            $entry_id = $this->getVisitorId($member_id);
        }

        if (!$entry_id) return false;
        $member = ee('Model')->get('Member', $member_id)->first();

        // ===============================
        // = Custom member fields =
        // ===============================
        $custom_member_data = array();

        $sql = "SELECT mf.m_field_id AS member_field_id,
            mf.m_field_name,
            cf.field_id AS channel_field_id,
            cf.field_name
            FROM exp_member_fields mf, exp_channel_fields cf
            WHERE cf.field_name = CONCAT('member_', mf.m_field_name)
        ";
        $query = ee()->db->query($sql);

        // Get data from channel entry instead from post array
        $data_query         = ee()->db->select('*')->from('channel_data')->where('entry_id', $entry_id)->get();
        $entry_channel_data = $data_query->first_row('array');

        if ($query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $custom_member_data['m_field_id_' . $row->member_field_id] = $entry_channel_data['field_id_' . $row->channel_field_id];
            }
        }

        if (count($custom_member_data) > 0) {
            $member->set($custom_member_data);
            $member->save();
        }

        // ==========================
        // = Standard member fields =
        // ==========================

        //deprecated method for saving native fields based on name m_field_id_x
        $native_member_fields_data = $this->contains_native_member_fields();

        if ($native_member_fields_data !== FALSE) {
            ee()->db->where('member_id', $author_id);
            ee()->db->update('member_data', $native_member_fields_data);
        }


        //new method for saving native fields
        $data   = array();
        $fields = array('bday_y', 'bday_m', 'bday_d', 'birthday', 'url', 'location', 'occupation', 'interests', 'aol_im', 'icq', 'yahoo_im', 'msn_im', 'bio', 'signature', 'avatar', 'photo', 'timezone', 'time_format', 'language');

        //get the channel field ids based on the name of the member fields
        $member_fields = array();
        foreach ($fields as $field) {
            $member_fields[] = 'member_' . $field;
        }

        $channelFields = ee('Model')->get('ChannelField')->filter('field_name', 'IN', $member_fields)->all();

        //place the fields in an array field_name => field_id, we need this because fields are in post array based on field_id
        $field_map = array();
        if ($channelFields) {
            foreach ($channelFields as $row) {
                $field_map[str_replace('member_', '', $row->field_name)] = $row->field_id;
            }

            foreach ($fields as $val) {

                if (isset($field_map[$val])) {

                    if (isset($entry_channel_data['field_id_' . $field_map[$val]])) {

                        $field_value = $entry_channel_data['field_id_' . $field_map[$val]];

                        if (strpos($field_value, '}')) {
                            $field_value = substr($field_value, strpos($field_value, '}') + 1);
                        }

                        $data[$val] = $field_value;
                    }
                }
            }

            if (isset($data['birthday'])) {

                //dropdate passes date as an array
                if (is_array($data['birthday'])) {
                    $data['bday_d'] = $data['birthday'][0];
                    $data['bday_m'] = $data['birthday'][1];
                    $data['bday_y'] = $data['birthday'][2];
                } else {
                    $data['bday_d'] = date('j', strtotime($data['birthday']));
                    $data['bday_m'] = date('n', strtotime($data['birthday']));
                    $data['bday_y'] = date('Y', strtotime($data['birthday']));
                }
                unset($data['birthday']);

            }
            if (isset($data['bday_d']) && isset($data['bday_m']) && is_numeric($data['bday_d']) AND is_numeric($data['bday_m'])) {
                $year = (isset($data['bday_y']) && $data['bday_y'] != '') ? $data['bday_y'] : date('Y');

                ee()->load->helper('date');
                $mdays = days_in_month($data['bday_m'], $year);

                if ($data['bday_d'] > $mdays) {
                    $data['bday_d'] = $mdays;
                }
            }
        }


        // ============================================
        // = Check if there is a membergroup selected =
        // ============================================
        $this->checkMemberGroupChange($data);

        if (isset($data['avatar'])) {
            $data['avatar_filename'] = 'uploads/' . $data['avatar'];
            unset($data['avatar']);
        }

        if (isset($data['photo'])) {
            $data['photo_filename'] = $data['photo'];
            unset($data['photo']);
        }

        if (count($data) > 0) {
            $member->set($data);
            $member->save();
        }

    }

    public function validateCurrentPassword($member_id, $current_password)
    {
        ee()->lang->loadfile('myaccount');

        if (ee()->session->userdata('group_id') == 1) {
            //return;
        }

        if ($current_password == '') {
            return ee()->lang->line('missing_current_password');
        }

        ee()->load->library('auth');

        // Get the users current password
        $pq = ee()->db->select('password, salt')
            ->get_where('members', array(
                    'member_id' => (int)$member_id)
            );


        if (!$pq->num_rows()) {
            return ee()->lang->line('invalid_password');
        }

        $passwd = ee()->auth->hash_password($current_password, $pq->row('salt'));

        if (!isset($passwd['salt']) OR ($passwd['password'] != $pq->row('password'))) {
            return ee()->lang->line('invalid_password');
        }

        return 'valid';
    }

    public function checkMemberGroupChange(&$data)
    {

        $selected_group_id = '';

        // ============================================
        // = Check if there is a membergroup selected =
        // ============================================

        $allowed_groups = (isset($_POST['AG'])) ? ee('visitor:Helper')->decodeString($_POST['AG']) : '';

        if (isset($_POST['AG']) && isset($_POST['group_id']) && ctype_digit($_POST['group_id']) && $allowed_groups !== '') {
            $sql = "SELECT DISTINCT group_id FROM exp_member_groups WHERE group_id NOT IN (1,2,3,4) AND group_id = '" . ee()->db->escape_str($_POST['group_id']) . "'" . ee()->functions->sql_andor_string($allowed_groups, 'group_id');

            $query = ee()->db->query($sql);
            if ($query->num_rows() > 0) {
                $selected_group_id = $query->row('group_id');
            }

            if (isset($_POST['entry_id']) && $_POST['entry_id'] != '0') {
                $data['group_id'] = $selected_group_id;
            } else {
                if (isset($selected_group_id) && is_numeric($selected_group_id)) {

                    if (ee()->config->item('req_mbr_activation') == 'manual' OR ee()->config->item('req_mbr_activation') == 'email') {
                        $data['group_id'] = 4; // Pending
                    } else {
                        $data['group_id'] = $selected_group_id;
                    }

                }
            }


        }

        return $selected_group_id;
    }

    protected function setChannelAndFieldGroup()
    {
        $channel_id = 0;
        $field_group_id = 0;

        if (ee('visitor:Settings')->settings['member_channel_id']) {
            $channel = ee('Model')->get('Channel', ee('visitor:Settings')->settings['member_channel_id'])->first();
        } else {
            $channel = ee('Model')->get('Channel')
            ->filter('channel_name', 'IN', array('visitor', 'zoo_visitor'))
            ->filter('site_id', $this->site_id)
            ->first();
        }

        if ($channel) {
            $this->channel_id = $channel->channel_id;
            $this->field_group_id = $channel->field_group;
            return true;
        }

        return false;
    }

    public function formatStatus($group_title, $group_id)
    {
        return preg_replace("/[^a-z0-9_]/i", '_', $group_title) . '-id' . $group_id;
    }

    // deprecated method for saving native fields based on name m_field_id_x
    public function contains_native_member_fields()
    {
        $native_member_fields      = FALSE;
        $native_member_fields_data = array();
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'm_field_id_') !== FALSE) {
                $native_member_fields_data[$key] = $value;
                $native_member_fields            = TRUE;
            }
        }

        return ($native_member_fields) ? $native_member_fields_data : FALSE;
    }
}

/* End of file Members.php */
/* Location: ./system/user/addons/visitor/Service/Members.php */