<?php

namespace DevDemon\Visitor\Service;

class MembersSync
{
    public $site_id;
    public $channel_id;
    public $field_group_id;

    public function __construct($addon)
    {
        $this->setChannelAndFieldGroup();
        $this->site_id = ee()->config->item('site_id');
    }

    public function syncFields($fields, $customFields)
    {
        $this->syncStandardFields($fields);
        $this->syncCustomFields($customFields);
    }

    public function syncStandardFields($fields)
    {
        if (empty($fields)) return;

        ee()->lang->loadfile('myaccount');
        $syncFields = array();

        foreach ($fields as $field_name) {
            $mfield = array();
            $mfield['m_field_name']        = $field_name;
            $mfield['m_field_label']       = lang($field_name);
            $mfield['m_field_description'] = '';
            $mfield['m_field_ta_rows']     = '6';
            $mfield['m_field_required']    = 'n';
            $mfield['m_field_search']      = 'n';
            $mfield['m_field_fmt']         = 'none';

            if ($field_name == 'bday_y' || $field_name == 'bday_m' || $field_name == 'bday_d') {
                $list_items = '';

                $mfield['m_field_type'] = 'select';
                $mfield['m_field_maxl'] = '4';

                if ($field_name == 'bday_y') {
                    for ($i = date('Y', ee()->localize->now); $i >= 1904; $i--) {
                        $list_items .= $i;
                        if ($i > 1904) {
                            $list_items .= chr(10);
                        }
                    }
                }

                if ($field_name == 'bday_m') {

                    for ($i = 1; $i <= 12; $i++) {
                        $month = ($i < 10) ? "0" . $i : $i;
                        $list_items .= $month;
                        if ($i < 12) {
                            $list_items .= chr(10);
                        }
                    }

                }

                if ($field_name == 'bday_d') {
                    for ($i = 1; $i <= 31; $i++) {
                        $list_items .= $i;
                        if ($i < 31) {
                            $list_items .= chr(10);
                        }
                    }
                }

                $mfield['m_field_list_items'] = $list_items;
            } elseif ($field_name == 'bio' || $field_name == 'signature') {
                $mfield['m_field_type']       = 'text';
                $mfield['m_field_list_items'] = '';
            } elseif ($field_name == 'avatar_filename') {
                $mfield['m_field_type']       = 'file';
                $mfield['m_field_list_items'] = '';
            } elseif ($field_name == 'birthday') {
                $mfield['m_field_type']       = 'date';
                $mfield['m_field_list_items'] = '';
            } else {
                $mfield['m_field_type']       = 'text';
                $mfield['m_field_list_items'] = '';
            }

            $field = $this->createField($mfield);
            $syncFields[] = $field_name . ':' . $field->field_id;
        }

        // Save your selection
        $settings = ee('visitor:Settings')->settings;
        $settings['sync_standard_member_fields'] = implode('|', $syncFields);
        ee('visitor:Settings')->saveModuleSettings($settings);
    }

    public function syncCustomFields($fields)
    {
        if (empty($fields)) return;
        $syncFields = array();
        $customFields = ee('Model')->get('MemberField')->filter('m_field_id', 'IN', $fields)->order('m_field_order')->all();

        foreach ($customFields as $customField) {
            $field = $this->createField($customField->toArray());
            $syncFields[] = $customField->m_field_id . ':' . $field->field_id;
        }

        // Save your selection
        $settings = ee('visitor:Settings')->settings;
        $settings['sync_custom_member_fields'] = implode('|', $syncFields);
        ee('visitor:Settings')->saveModuleSettings($settings);
    }

    public function createField($mfield)
    {
        if (!$this->field_group_id) return;

        $site_id = ee()->config->item('site_id');
        $fieldName = 'mbr_' . $mfield['m_field_name'];
        $field = ee('Model')->get('ChannelField')->filter('field_name', $fieldName)->filter('site_id', $site_id)->first();

        if ($field) return $field;

        $fieldSettingsText = array('field_maxl' => '256', 'field_content_type' => 'all');
        $fieldSettingsRadio = array();
        $fieldSettingsDate = array();

        $field = ee('Model')->make('ChannelField');
        $field->site_id = $site_id;
        $field->group_id = $this->field_group_id;
        $field->field_name = $fieldName;
        $field->field_label = $mfield['m_field_label'];
        $field->field_type = $mfield['m_field_type'];
        $field->field_list_items = $mfield['m_field_list_items'];
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

        return $field;
    }

    public function syncMemberData()
    {
        if (!$this->field_group_id) return;
        if (!$this->channel_id) return;
        if (!ee('visitor:Settings')->settings['anonymous_member_id']) return;

        // Loop though members and build array based on the post fields
        $query = ee()->db->select('mem.member_id, mem.join_date, ct.author_id, ct.entry_id')
        ->from('exp_members mem')
        ->join('exp_channel_titles ct', 'ct.author_id = mem.member_id AND ct.channel_id = "' . ee('visitor:Settings')->settings['anonymous_member_id'] . '"', 'left')
        ->where('ct.entry_id IS NULL', null, false)
        ->where('mem.member_id !=', ee('visitor:Settings')->settings['anonymous_member_id'])
        ->get();

        if ($query->num_rows() == 0) return;

        foreach ($query->result_array() as $row) {

            //create entry, channel_title -> author_id  = member_id
            $entry = $this->createMemberEntry($row);

            if ($entry) {
                // run title update
                ee('visitor:Members')->updateEntryTitle($entry->entry_id, $row['member_id']);

                // set membergroup status
                ee('visitor:Members')->syncMemberStatus($row['member_id'], $entry->entry_id);

                // sync the screen_name based on the provided override fields
                if (ee('visitor:Settings')->settings['use_screen_name'] == 'no' && ee('visitor:Settings')->settings['screen_name_override'] != '') {
                    ee('visitor:Members')->updateScreenName($row['member_id'], $entry->entry_id);
                }
            }
        }
    }

    public function createMemberEntry($data)
    {
        // Find the existing channel entry if it exists
        $entry = ee('Model')->get('ChannelEntry')
        ->filter('channel_id', $this->channel_id)
        ->filter('author_id', $data['member_id'])
        ->first();

        if ($entry) {
            return false; // Means we are not creating a new entry, it already exists
        }

        $channel = ee('Model')->get('Channel', $this->channel_id)->first();
        $member = ee('Model')->get('Member', $data['member_id'])->first();

        $entry = ee('Model')->make('ChannelEntry');
        $entry->Channel            = $channel;
        $entry->site_id            = $this->site_id;
        $entry->channel_id         = $this->channel_id;
        $entry->author_id          = $data['member_id']; // @todo double check if this is validated
        $entry->entry_date         = $data['join_date'];
        $entry->edit_date          = ee()->localize->now;
        $entry->ip_address         = ee()->session->userdata['ip_address'];
        $entry->versioning_enabled = $channel->enable_versioning;
        $entry->sticky             = false;
        $entry->title              = 'temp-sync';
        $entry->url_title          = 'temp-sync';

        // Set some defaults based on Channel Settings
        $entry->allow_comments = (isset($channel->deft_comments)) ? $channel->deft_comments : true;

        // Channel Default Status?
        if (isset($channel->deft_status)) {
            $entry->status = $channel->deft_status;
        }

        // Any Channel Default Categories
        if (isset($channel->deft_category)) {
            $cat = ee('Model')->get('Category', $channel->deft_category)->first();
            if ($cat) {
                $entry->Categories[] = $cat;
            }
        }

        // Create the entry
        $entry->save();

        // =====================================
        // = loop through standard member fields =
        // =====================================
        if (ee('visitor:Settings')->settings['sync_standard_member_fields']) {
            $settingsFields = explode('|', ee('visitor:Settings')->settings['sync_standard_member_fields']);
            $fields = array();

            foreach ($settingsFields as $field) {
                $parts    = explode(':', $field);
                $field_id = $parts[1];

                // Let's make sure it actually exists
                if (ee()->db->field_exists('field_id_' . $field_id, 'channel_data')) {
                    $fields[] = $field;
                }
            }

            foreach ($fields as $field) {
                $parts      = explode(':', $field);
                $field_name = $parts[0]; // Member Field Name
                $field_id   = $parts[1]; // EE Channel Field ID

                // Check if the property exists first, then set the corresponding channel entry field data
                if ($member->hasProperty($field_name)) {
                    $entry->setProperty('field_id_' . $field_id, $member->getProperty($field_name));
                    //$entry->setProperty('field_ft_' . $field_id, 'none');
                }
            }
        }

        // =====================================
        // = loop through custom member fields =
        // =====================================

        if (ee('visitor:Settings')->settings['sync_custom_member_fields']) {
            $settingsFields = explode('|', ee('visitor:Settings')->settings['sync_custom_member_fields']);
            $fields = array();

            foreach ($settingsFields as $field) {
                $parts    = explode(':', $field);
                $field_id = $parts[1];

                // Let's make sure it actually exists
                if (ee()->db->field_exists('field_id_' . $field_id, 'channel_data')) {
                    $fields[] = $field;
                }
            }

            foreach ($fields as $field) {
                $parts     = explode(':', $field);
                $mfield_id = $parts[0]; // Member Field ID
                $field_id  = $parts[1]; // EE Channel Field ID

                // Check if the property exists first, then set the corresponding channel entry field data
                if ($member->hasProperty('m_field_id_' . $mfield_id)) {
                    $entry->setProperty('field_id_' . $field_id, $member->getProperty('m_field_id_' . $mfield_id));
                    //$entry->setProperty('field_ft_' . $field_id, 'none');
                }
            }
        }

        // Save the entry again
        $entry->save();

        return $entry;
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
}

/* End of file MembersSync.php */
/* Location: ./system/user/addons/visitor/Service/MembersSync.php */