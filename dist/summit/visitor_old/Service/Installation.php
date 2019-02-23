<?php

namespace DevDemon\Visitor\Service;

class Installation
{
    public static $site_id;
    public static $channel;
    public static $fieldGroup;

    public static function getSteps()
    {
        self::$site_id = $site_id = ee()->config->item('site_id');

        $steps = array();
        $steps['channel_installed'] = array('status' => 'pending', 'msg' => lang('v:channel_exists_no'));
        $steps['fieldtype_in_channel'] = array('status' => 'pending', 'msg' => lang('v:fieldtype_in_channel_no'));
        $steps['linked_with_members'] = array('status' => 'pending', 'msg' => lang('v:linked_with_members_no'));
        $steps['allow_member_registration'] = array('status' => 'pending', 'msg' => lang('v:allow_member_registration_no'));
        $steps['guest_member_posts'] = array('status' => 'pending', 'msg' => lang('v:guest_member_posts_allowed_no'));
        $steps['guest_member_created'] = array('status' => 'pending', 'msg' => lang('v:guest_member_created_no'));
        $steps['channel_form_author'] = array('status' => 'pending', 'msg' => lang('v:channel_form_author_no'));
        $steps['example_templategroup_exists'] = array('status' => 'pending', 'msg' => lang('v:example_templategroup_exists_no'));

        // =======================================
        // = Does Zoo Visitor channel exists =
        // =======================================
        $channel_id = 0;
        $field_group_id = 0;
        $channel = ee('Model')->get('Channel')
        ->filter('channel_name', 'IN', array('zoo_visitor', 'visitor'))
        ->filter('site_id', $site_id)
        ->first();

        if ($channel) {
            $steps['channel_installed']['status'] = 'success';
            $steps['channel_installed']['msg'] = lang('v:channel_exists_yes');

            $channel_id = $channel->channel_id;
            $field_group_id = $channel->field_group;
        }

        // =======================================
        // = Fieldtype linked to channel =
        // =======================================
        $fieldGroup = ee('Model')->get('ChannelField')
        ->filter('field_type', 'visitor')
        ->filter('group_id', $field_group_id)
        ->first();

        if ($fieldGroup) {
            $steps['fieldtype_in_channel']['status'] = 'success';
            $steps['fieldtype_in_channel']['msg'] = lang('v:fieldtype_in_channel_yes');
        }

        // =======================================
        // = linked_with_members =
        // =======================================
        if (ee('visitor:Settings')->settings['member_channel_id'] > 0) {
            $steps['linked_with_members']['status'] = 'success';
            $steps['linked_with_members']['msg'] = lang('v:linked_with_members_yes');
        }

        // =======================================
        // = allow_member_registration =
        // =======================================
        if (ee()->config->item('allow_member_registration') == 'y') {
            $steps['allow_member_registration']['status'] = 'success';
            $steps['allow_member_registration']['msg'] = lang('v:allow_member_registration_yes');
        }

        // =======================================
        // = guest_member_posts =
        // =======================================
        $memberGroup = ee('Model')->get('MemberGroup')->filter('site_id', $site_id)->filter('group_id', 3)->first();
        ee()->db->where('channel_id', $channel_id);
        ee()->db->where('group_id', '3');
        $query = ee()->db->get('channel_member_groups');

        if ($query->num_rows() > 0 && $memberGroup->can_create_entries && $memberGroup->can_edit_self_entries) {
            $steps['guest_member_posts']['status'] = 'success';
            $steps['guest_member_posts']['msg'] = lang('v:guest_member_posts_allowed_yes');
        }

        // =======================================
        // = guest_member_created =
        // =======================================
        if (ee('visitor:Settings')->settings['anonymous_member_id'] > 0 && ee('Model')->get('Member', ee('visitor:Settings')->settings['anonymous_member_id'])->first()) {
            $steps['guest_member_created']['status'] = 'success';
            $steps['guest_member_created']['msg'] = lang('v:guest_member_created_yes');

            // =======================================
            // = channel_form_author =
            // =======================================
            $form = ee('Model')->get('ChannelFormSettings')->filter('channel_id', ee('visitor:Settings')->settings['member_channel_id'])->first();
            if ($form && $form->default_author == ee('visitor:Settings')->settings['anonymous_member_id']) {
                $steps['channel_form_author']['status'] = 'success';
                $steps['channel_form_author']['msg'] = lang('v:channel_form_author_yes');
            }

        }


        // =======================================
        // = example_templategroup_exists =
        // =======================================
        $tmplGroup = ee('Model')->get('TemplateGroup')
        ->filter('group_name', 'IN', array('visitor_example', 'zoo_visitor_example'))
        ->first();

        if ($tmplGroup) {
            $steps['example_templategroup_exists']['status'] = 'success';
            $steps['example_templategroup_exists']['msg'] = lang('v:example_templategroup_exists_yes');
        }

        return $steps;
    }

    public static function install()
    {
        $site_id = ee()->config->item('site_id');
        $errors  = array();
        $success = array();

        // =======================================
        // = Is Zoo Visitor fieldtype installed? =
        // =======================================
        $fieldtype = ee('Model')->get('Fieldtype')->filter('name', 'visitor')->first();

        if (!$fieldtype) {
            ee()->session->set_flashdata('errors', 'fieldtype_not_installed');
            return;
        }

        // ======================
        // =Create fieldgroup =
        // ======================
        $fieldGroup = ee('Model')->get('ChannelFieldGroup')
        ->filter('group_name', 'IN', array('Visitor Fields', 'Zoo Visitor Fields'))
        ->filter('site_id', $site_id)
        ->first();

        if (!$fieldGroup) {
            $fieldGroup = ee('Model')->make('ChannelFieldGroup');
            $fieldGroup->site_id = $site_id;
            $fieldGroup->group_name = 'Visitor Fields';
            $fieldGroup->save();
        }

        self::$fieldGroup = $fieldGroup;

        /// ==================
        // = Create channel =
        // ==================
        $channel = ee('Model')->get('Channel')
        ->filter('channel_name', 'IN', array('zoo_visitor', 'visitor'))
        ->filter('site_id', $site_id)
        ->first();

        if ($channel) {
            $channel->field_group   = $fieldGroup->group_id;
            $channel->save();
        } else {
            $channel = ee('Model')->make('Channel');
            $channel->site_id       = $site_id;
            $channel->channel_name  = 'visitor';
            $channel->channel_title = 'Visitor Members';
            $channel->channel_url   = '';
            $channel->channel_lang  = ee()->config->item('xml_lang');
            $channel->field_group   = $fieldGroup->group_id;
            $channel->status_group  = 1;
            $channel->save();
        }

        self::$channel = $channel;

        // =============================================================
        // = Allow guests to post in this channel, member registration =
        // =============================================================
        ee()->db->where('channel_id', $channel->channel_id);
        ee()->db->where('group_id', '3');
        $query = ee()->db->get('channel_member_groups');

        if ($query->num_rows() == 0) {
            ee()->db->set('group_id', 3);
            ee()->db->set('channel_id', $channel->channel_id);
            ee()->db->insert('channel_member_groups');
        }

        // And allow create/edit entires
        $memberGroup = ee('Model')->get('MemberGroup')->filter('site_id', $site_id)->filter('group_id', 3)->first();
        $memberGroup->can_create_entries = true;
        $memberGroup->can_edit_self_entries = true;
        $memberGroup->save();

        // ======================
        // = Create statusgroup =
        // ======================
        $statusGroup = ee('Model')->get('StatusGroup')
        ->filter('group_name', 'IN', array('Visitor Membergroup', 'Zoo Visitor Membergroup'))
        ->filter('site_id', $site_id)
        ->first();

        if (!$statusGroup) {
            $statusGroup = ee('Model')->make('StatusGroup');
            $statusGroup->site_id    = $site_id;
            $statusGroup->group_name = 'Visitor Membergroup';
            $statusGroup->save();

            $memberGroups  = ee('Model')->get('MemberGroup')->filter('site_id', $site_id)->all();

            foreach ($memberGroups as $memberGroup) {
                $status = ee('Model')->make('Status');
                $status->status       = ee('visitor:Members')->formatStatus($memberGroup->group_title, $memberGroup->group_id);
                $status->site_id      = $site_id;
                $status->group_id     = $statusGroup->group_id;
                $status->status_order = '1';
                $status->highlight = '';
                $status->save();
            }
        }

        $channel->status_group = $statusGroup->group_id;
        $channel->save();

        // =================================
        // = create anonymous guest member =
        // =================================
        $member = ee('Model')->get('Member')->filter('username', 'IN', array('visitor_guest', 'zoo_visitor_guest'))->first();

        if (!$member) {
            ee()->load->library('auth');

            $member = ee('Model')->make('Member');
            $member->group_id    = 3; // Guests
            $member->username    = 'visitor_guest';
            $member->ip_address  = ee()->input->ip_address();
            $member->unique_id   = ee()->functions->random('encrypt');
            $member->join_date   = ee()->localize->now;
            $member->email       = 'visitor@yourdomain.tld';
            $member->screen_name = 'visitor_guest';
            $member->language    = (ee()->config->item('deft_lang')) ? ee()->config->item('deft_lang') : 'english';
            //$data['time_format'] = (ee()->config->item('time_format')) ? ee()->config->item('time_format') : 'us';
            //$data['timezone'] = (ee()->config->item('default_site_timezone') && ee()->config->item('default_site_timezone') != '') ? ee()->config->item('default_site_timezone') : ee()->config->item('server_timezone');

            $hashed_password = ee()->auth->hash_password(md5('visitor_login'));
            $member->password = $hashed_password['password'];
            $member->salt = $hashed_password['salt'];

            $member->save();
        }

        // =================================
        // = Set default author =
        // =================================
        $form = ee('Model')->get('ChannelFormSettings')->filter('channel_id', $channel->channel_id)->first();
        if ($form) {
            $form->default_author = $member->member_id;
            $form->save();
        } else {
            $form = ee('Model')->make('ChannelFormSettings');
            $form->site_id = $site_id;
            $form->channel_id = $channel->channel_id;
            $form->default_author = $member->member_id;
            $form->save();
        }

        // =======================
        // = Create template group  =
        // =======================
        $tmplGroupName = 'visitor_example';
        $tmplGroup = ee('Model')->get('TemplateGroup')
        ->filter('site_id', $site_id)
        ->filter('group_name', $tmplGroupName)
        ->first();

        if (!$tmplGroup) {
            $tmplGroup = ee('Model')->make('TemplateGroup');
            $tmplGroup->site_id = $site_id;
            $tmplGroup->group_name = $tmplGroupName;
            $tmplGroup->group_order = 100;
            $tmplGroup->is_site_default = 'n';
            $tmplGroup->save();
        }

        // ====================
        // = Create templates =
        // ====================
        $path = ee('Addon')->get('visitor')->getPath();
        $files = scandir($path.'/View/templates');

        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($file == '.' || $file == '.' || $file == '.DS_Store') continue;
            if (!in_array($ext, array('html', 'css', 'js', 'xml'))) continue;

            $name = basename($file, '.'.$ext);
            $contents = file_get_contents($path . '/View/templates/' . $file);

            $template = ee('Model')->get('Template')
            ->filter('group_id', $tmplGroup->group_id)
            ->filter('template_name', $name)
            ->first();

            if ($template) continue;

            $template = ee('Model')->make('Template');
            $template->group_id      = $tmplGroup->group_id;
            $template->template_name = $name;
            $template->template_data = $contents;
            $template->site_id       = $site_id;
            $template->template_type = 'webpage';
            $template->edit_date     = ee()->localize->now;
            $template->save();
        }

        // Create Basic Custom Fields
        self::createBasicCustomFields();

        // ====================
        // = Update Setting: screen_name_override
        // ====================
        $override_val = 'member_firstname member_lastname';
        $screen_name_override = null;
        $fields = ee('Model')->get('ChannelField')
        ->filter('field_name', 'IN', array('member_firstname', 'member_lastname'))
        ->filter('site_id', $site_id)
        ->all();

        if ($fields->count() > 0) {
            foreach ($fields as $field) {
                $override_val = str_replace($field->field_name, 'field_id_' . $field->field_id, $override_val);
            }

            $screen_name_override = $override_val;
        }


        // ====================
        // = Save Settings =
        // ====================
        $settings = ee('visitor:Settings')->settings;
        $settings['installed'] = 'yes';
        $settings['member_channel_id'] = $channel->channel_id;
        $settings['anonymous_member_id'] = $member->member_id;
        if ($screen_name_override) $settings['screen_name_override'] = $screen_name_override;
        ee('visitor:Settings')->saveModuleSettings($settings);
    }

    public static function createBasicCustomFields()
    {
        $field_order = 1;
        $site_id = ee()->config->item('site_id');
        $fieldSettingsText = array('field_maxl' => '256', 'field_content_type' => 'all');
        $fieldSettingsRadio = array();
        $fieldSettingsDate = array();

        $fields   = array();
        $fields[] = array('Member account', 'member_account', 'visitor', null);
        $fields[] = array('Firstname', 'member_firstname', 'text', null);
        $fields[] = array('Lastname', 'member_lastname', 'text', null);
        $fields[] = array('Gender', 'member_gender', 'radio', "Male\nFemale");
        $fields[] = array('Birthday', 'member_birthday', 'date', null);

        foreach ($fields as $theField) {
            $field_order++;

            $field = ee('Model')->get('ChannelField')->filter('field_name', $theField[1])->filter('site_id', $site_id)->first();
            if ($field) continue;

            $field = ee('Model')->make('ChannelField');
            $field->site_id = $site_id;
            $field->group_id = self::$fieldGroup->group_id;
            $field->field_name = $theField[1];
            $field->field_label = $theField[0];
            $field->field_type = $theField[2];
            $field->field_list_items = $theField[3];
            $field->field_order = $field_order;
            $field->field_fmt = 'none';
            $field->field_show_fmt = 'n';

            if ($field->field_type == 'text') {
                $field->field_maxl = 256;
                $field->field_content_type = 'all';
                $field->field_settings = $fieldSettingsText;
            } elseif ($field->field_type == 'radio') {
                $field->field_content_type = 'any';
                $field->field_settings = $fieldSettingsRadio;
            } elseif ($field->field_type == 'date') {
                $field->field_content_type = 'any';
                $field->field_settings = $fieldSettingsDate;
            }

            $field->save();
        }
    }
}

/* End of file Installation.php */
/* Location: ./system/user/addons/visitor/Service/Installation.php */