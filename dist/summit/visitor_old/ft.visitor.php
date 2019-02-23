<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Channel Polls Module FieldType
 *
 * @package         DevDemon_Visitor
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com/visitor/
 * @see             http://expressionengine.com/user_guide/development/fieldtypes.html
 */
class Visitor_ft extends EE_Fieldtype
{

    /**
     * Field info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'name'      => VISITOR_NAME,
        'version'   => VISITOR_VERSION,
    );

    static protected $userFormShown = false;

    /**
     * Constructor
     *
     * @access public
     *
     * Calls the parent constructor
     */
    public function __construct()
    {
        $this->site_id = ee()->config->item('site_id');

        ee()->lang->loadfile('visitor');
    }

    // ********************************************************************************* //

    /**
     * Check if the fieldtype will accept a certain content type
     *
     * For backward compatiblity, all fieldtypes will initially only
     * support the channel content type. Override this method for more
     * control.
     *
     * @param string  The name of the content type
     * @return bool   Supports content type?
     */
    public function accepts_content_type($name)
    {
        return ($name == 'channel');
    }

    // ********************************************************************************* //

    /**
     * Display the field in the publish form
     *
     * @access public
     * @param $data String Contains the current field data. Blank for new entries.
     * @return String The custom field HTML
     */
    public function display_field($data)
    {
        $settings = ee('visitor:Settings')->getFieldtypeSettings($this->field_id, $this->settings);

        //ee('visitor:Helper')->mcpAssets('gjs');
        ee('visitor:Helper')->mcpAssets('css', 'addon_pbf.css', null, true);
        //ee('visitor:Helper')->mcpAssets('js', 'addon_pbf.js', null, true);

        if (!ee()->cp->allowed_group('can_edit_members')) {
            return '<div class="alert inline warn">' . lang('v:error_can_admin_members') . '</div>';
        }

        $entry_id = $this->content_id();

        // New Entry?
        if (!$entry_id) {
            if ($settings['show'] == 'both') $settings['show'] = 'form';

            // Hide this field if we are showing info only?
            if ($settings['show'] == 'info') {

                ee()->cp->add_to_foot("<script type='text/javascript'>
                $('.visitor-new_member').closest('.col-group').hide();
                </script>");

                return '<div class="alert inline warn visitor-new_member">' . lang('v:error_info_new_member') . '</div>';
            }

            return $this->createForm();
        }

        $member_id = ee('visitor:Members')->getMemberId($entry_id);

        if (!$member_id) {
            return '<div class="alert inline issue">' . lang('v:error_member_link_missing') . '</div>';
        }

        $member = ee('Model')->get('Member', $member_id)->first();

        if ($settings['show'] == 'info') {
            return $this->showUserInfo($member);
        }

        if ($settings['show'] == 'form') {
            return $this->editForm($member);
        }

        $form = $this->editForm($member);
        $info = $this->showUserInfo($member);

        $ret = '<div class="col w-8">' . $form . '</div>';
        $ret .= '<div class="col w-8 last">' . $info . '</div>';

        return $ret;
    }

    // ********************************************************************************* //

    protected function showUserInfo($member)
    {
        $vdata['member'] = $member;
        $vdata['fieldname'] = $fieldname = $this->field_name;
        $vdata['settings'] = array();
        $vdata['name'] = null;

        $vdata['show_email_member'] = ($member->member_id != ee()->session->userdata('member_id'));
        $vdata['show_login_as_member'] = (ee()->session->userdata('group_id') == 1 && $member->member_id != ee()->session->userdata('member_id'));
        $vdata['show_resend_activation'] = ($member->member_id != ee()->session->userdata('member_id') && ee()->config->item('req_mbr_activation') == 'email');
        $vdata['show_delete_member'] = false;

        if (ee()->cp->allowed_group('can_delete_members') AND $member->member_id != ee()->session->userdata('member_id')) {
            if ($member->group_id == '1' AND ee()->session->userdata('group_id') != '1') {

            } else {
                $vdata['show_delete_member'] = true;
            }
        }

        $vdata['actionsHtml'] = ee('View')->make('visitor:pbf/info_actions')->render($vdata);

        $vdata['settings'][] = array(
            'title'  => 'v:join_date',
            'fields' => array(
                $fieldname.'[join_date]' => array(
                    'type'    => 'html',
                    'content'   => ee()->localize->human_time($member->join_date),
                )
            )
        );

        $vdata['settings'][] = array(
            'title'  => 'v:last_visit',
            'fields' => array(
                $fieldname.'[join_date]' => array(
                    'type'    => 'html',
                    'content' => ($member->last_visit) ? ee()->localize->human_time($member->last_visit) : '--',
                )
            )
        );

        $vdata['infoHtml'] = ee('View')->make('_shared/form/section')->render($vdata);


        return ee('View')->make('visitor:pbf/info')->render($vdata);
    }

    // ********************************************************************************* //

    protected function createForm()
    {
        if (self::$userFormShown) {
            return '<div class="alert inline issue">' . lang('v:error_user_form_shown') . '</div>';
        }

        self::$userFormShown = true;

        //use when publishing new entry or when member hasn't been linked with entry
        //group, username, screen_name, email, password, confirm password
        //OR dropdown with members
        //validate to see if the user hasn't been linked yet.
        //current member_id

        $vdata = array();
        $vdata['member'] = ee('Model')->make('Member');
        $vdata['fieldname'] = $fieldname = $this->field_name;
        $vdata['settings'] = array();
        $vdata['name'] = null;

        $canEditGroups = false;
        if (ee()->cp->allowed_group('can_admin_mbr_groups')) $canEditGroups = true;

        if ($canEditGroups) {
            $mgroups = ee('visitor:Members')->getMemberGroups();

            $vdata['settings'][] = array(
                'title'  => 'member_group',
                'fields' => array(
                    $fieldname.'[group_id]' => array(
                        'type'    => 'select',
                        'value'   => ee()->config->item('default_member_group'),
                        'choices' => $mgroups,
                    )
                )
            );
        }


        $vdata['settings'][] = array(
            'title'  => 'email',
            'fields' => array(
                $fieldname.'[email]' => array(
                    'type'  => 'text',
                    'value' => null,
                )
            )
        );

        if (ee('visitor:Settings')->settings['use_screen_name'] == 'yes') {
            $vdata['settings'][] = array(
                'title'  => 'screen_name',
                'fields' => array(
                    $fieldname.'[screen_name]' => array(
                        'type'  => 'text',
                        'value' => null,
                    )
                )
            );
        }

        if (ee('visitor:Settings')->settings['email_is_username'] == 'yes') {
            $vdata['settings'][] = array(
                'title'  => 'username',
                'fields' => array(
                    $fieldname.'[username]' => array(
                        'type'  => 'text',
                        'value' => null,
                    )
                )
            );
        }

        $vdata['settings'][] = array(
            'title'  => 'v:new_password',
            'fields' => array(
                $fieldname.'[password]' => array(
                    'type'  => 'password',
                ),
            )
        );

        $vdata['settings'][] = array(
            'title'  => 'v:password_confirm',
            'fields' => array(
                $fieldname.'[password_confirm]' => array(
                    'type'  => 'password',
                ),
            )
        );

        // Render the normal Form
        $vdata['form_html'] = ee('View')->make('_shared/form/section')->render($vdata);

        if (REQ == 'CP') {
            $rand = time();
            ee()->cp->add_to_foot("<script type='text/javascript'>
            $(':input[name=title]').val('visitor-temp-{$rand}').closest('.col-group').hide();
            $(':input[name=url_title]').val('visitor-temp-{$rand}').closest('.col-group').hide();
            $(':input[name=author_id]').closest('.col-group').hide();
            $(':input[name=sticky]').closest('.col-group').hide();
            $(':input[name=channel_id]').closest('.col-group').hide();

            var visitorForm = $('.visitor-form').closest('.col-group');
            visitorForm.find('.setting-txt, .setting-field').removeClass('w-16').addClass('w-8');
            </script>");

            // $("select[name=\'author_id\']").append(\'<option val="'.$member->member_id.'" selected>Member account linked with this Zoo Visitor entry</option>\');});';

        }


        return ee('View')->make('visitor:pbf/form')->render($vdata);

         /*
        if ((isset($this->zoo_settings['hide_link_to_existing_member']) && $this->zoo_settings['hide_link_to_existing_member'] != 'yes') || ee()->session->userdata('group_id') == '1') {
            $anon_mem = (isset($this->zoo_settings['anonymous_member_id'])) ? $this->zoo_settings['anonymous_member_id'] : 0;

            //Get all member_id's
            $sql         = "SELECT mem.member_id, mem.screen_name, mem.email FROM exp_members mem WHERE mem.member_id != '" . $anon_mem . "'";
            $q_mem       = ee()->db->query($sql);
            $all_members = array();

            foreach ($q_mem->result_array() as $member) {
                $all_members[$member['member_id']] = $member['screen_name'] . ' (' . $member['email'] . ')';
            }

            //get members who already have a Visitor profile
            $sql          = "SELECT ct.author_id FROM exp_channel_titles ct WHERE ct.channel_id = '" . $this->zoo_settings['member_channel_id'] . "'";
            $q_visitor    = ee()->db->query($sql);
            $all_visitors = array();

            foreach ($q_visitor->result_array() as $entry) {
                $all_visitors[$entry['author_id']] = $entry;
            }

            $memberData[''] = 'Select a member';
            $memberData += array_diff_key($all_members, $all_visitors);

            $members = (count($memberData) > 1) ? '<b>OR Link an existing member:</b><br/><br/>' . form_dropdown('EE_existing_member_id', $memberData, '') . '</b>' : '';

        } else {
            $members = '';
        }
        */
    }

    // ********************************************************************************* //

    protected function editForm($member)
    {
        if (self::$userFormShown) {
            return '<div class="alert inline issue">' . lang('v:error_user_form_shown') . '</div>';
        }

        self::$userFormShown = true;

        $vdata = array();
        $vdata['member'] = $member;
        $vdata['fieldname'] = $fieldname = $this->field_name;
        $vdata['settings'] = array();
        $vdata['name'] = null;


        $vdata['settings'][] = array(
            'title'  => 'email',
            'fields' => array(
                $fieldname.'[email]' => array(
                    'type'  => 'text',
                    'value' => $member->email,
                ),
            )
        );

        if (ee('visitor:Settings')->settings['use_screen_name'] == 'yes') {
            $vdata['settings'][] = array(
                'title'  => 'screen_name',
                'fields' => array(
                    $fieldname.'[screen_name]' => array(
                        'type'  => 'text',
                        'value' => $member->screen_name,
                    ),
                )
            );
        }

        if (ee('visitor:Settings')->settings['email_is_username'] == 'no') {
            $vdata['settings'][] = array(
                'title'  => 'username',
                'fields' => array(
                    $fieldname.'[username]' => array(
                        'type'  => 'text',
                        'value' => $member->username,
                    ),
                )
            );
        }

        $canEditGroups = false;
        if (ee()->cp->allowed_group('can_admin_mbr_groups')) $canEditGroups = true;

        // Only Super Admins can edit other Super Admins
        if ($member->group_id == 1 && ee()->session->userdata('group_id') != 1) $canEditGroups = false;

        if ($canEditGroups) {
            $mgroups = ee('visitor:Members')->getMemberGroups();

            // Is the current group in the list?
            if (!in_array($member->group_id, $mgroups)) {
                $currentGroup = ee('Model')->get('MemberGroup', $member->group_id)->fields('group_id', 'group_title')->first();
                $mgroups[$currentGroup->group_id] = $currentGroup->group_title;
            }

            $vdata['settings'][] = array(
                'title'  => 'member_group',
                'fields' => array(
                    $fieldname.'[group_id]' => array(
                        'type'    => 'select',
                        'value'   => $member->group_id,
                        'choices' => $mgroups,
                    ),
                )
            );
        }

        $vdata['settings'][] = array(
            'title'  => 'v:new_password',
            'desc'   => 'v:password_blank',
            'fields' => array(
                $fieldname.'[new_password]' => array(
                    'type'  => 'password',
                ),
            )
        );

        $vdata['settings'][] = array(
            'title'  => 'v:password_confirm',
            'fields' => array(
                $fieldname.'[new_password_confirm]' => array(
                    'type'  => 'password',
                ),
            )
        );

        // Render the normal Form
        $vdata['form_html'] = ee('View')->make('_shared/form/section')->render($vdata);

        if (REQ == 'CP') {
            ee()->cp->add_to_foot("<script type='text/javascript'>
            $(':input[name=title]').closest('.col-group').hide();
            $(':input[name=url_title]').closest('.col-group').hide();
            $(':input[name=author_id]').closest('.col-group').hide();
            $(':input[name=sticky]').closest('.col-group').hide();
            $(':input[name=channel_id]').closest('.col-group').hide();
            </script>");

            // $("select[name=\'author_id\']").append(\'<option val="'.$member->member_id.'" selected>Member account linked with this Zoo Visitor entry</option>\');});';

        }


        return ee('View')->make('visitor:pbf/form')->render($vdata);
    }

    // ********************************************************************************* //

    /**
     * Validates the field input
     *
     * @param $data Contains the submitted field data.
     * @return mixed Must return true or an error message
     */
    public function validate($data, $returnData=false)
    {
        // Request comes from frontend, validation is done in extension
        if (REQ != 'CP') return true;
        if (!is_array($data)) return true;

        $entry_id = $this->content_id();

        if (!$entry_id) {
            $member = ee('Model')->make('Member');
        } else {
            $member_id = ee('visitor:Members')->getMemberId($entry_id);

            if (!$member_id) {
                return 'Error: Member link could not be found!';
            }

            $member = ee('Model')->get('Member', $member_id)->first();

            if (!$member) {
                return 'Error: Member could not be found!';
            }
        }

        $data['member_id'] = $member->member_id;

        // Securely trim
        $data = array_map(function($val){
            if (!is_string($val)) return $val;

            return ee('visitor:Helper')->trimNbs($val);
        }, $data);

        //$this->prepare_post();

        //----------------------------------------
        // Check Email
        //----------------------------------------
        if ($data['email'] != $member->email) {

            // Valid Email?
            if ($data['email'] != filter_var($data['email'], FILTER_SANITIZE_EMAIL) OR ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return lang('v:invalid_email_address');
            }

            // Email already exists?
            $tempMember = ee('Model')->get('Member')->filter('email', $data['email'])->first();
            if ($tempMember) {
                return lang('v:email_taken');
            }
        }

        //----------------------------------------
        // Check Username
        //----------------------------------------

        // Email is username?
        if (ee('visitor:Settings')->settings['email_is_username'] == 'yes') {
            $data['username'] = $data['email'];
        }

        // Check Username
        if ($data['username'] != $member->username) {
            // Validate username
            $res = $member->validateUsername('username', $data['username']);
            if ($res !== true) {
                return lang('v:' . $res);
            }

            // Unique username?
            $tempMember = ee('Model')->get('Member')->filter('username', $data['username'])->first();
            if ($tempMember) {
                return lang('v:username_taken');
            }
        }

        //----------------------------------------
        // Check Screen Name
        //----------------------------------------

        // Check screen name
        if (ee('visitor:Settings')->settings['use_screen_name'] == 'no') {

            // Screen Name Override?
            if (ee('visitor:Settings')->settings['screen_name_override'] != '') {
                $data['screen_name'] = ee('visitor:Settings')->settings['screen_name_override'];
                $fields = array_reverse($_POST);

                foreach ($fields as $key => $val) {
                    if (is_string($val)) $data['screen_name'] = str_replace($key, $val, $data['screen_name']);
                }
            } else {
                $data['screen_name'] = $data['username'];
            }

        }

        if (preg_match('/[\{\}<>]/', $data['screen_name'])) {
            return lang('v:disallowed_screen_chars');
        }

        // Ban Check
        if (ee()->session->ban_check('screen_name', $data['screen_name']) OR trim(preg_replace("/&nbsp;*/", '', $data['screen_name'])) == '') {
            return lang('v:screen_name_taken');
        }

        //----------------------------------------
        // Check Password
        //----------------------------------------
        if (isset($data['new_password']) && $data['new_password'] != '') {
            if ($data['new_password'] != $data['new_password_confirm']) {
                return lang('v:missmatched_passwords');
            }

            $res = $member->validatePassword('password', $data['new_password']);
            if ($res !== true) {
                return lang('v:' . $res);
            }
        }

        if ($returnData) {
            return $data;
        }

        return true;
    }

    // ********************************************************************************* //

    /**
     * Preps the data for saving
     *
     * @param $data Contains the submitted field data.
     * @return string Data to be saved
     */
    public function save($data)
    {
        $data = $this->validate($data, true);

        if (is_array($data)) {
            ee()->session->set_cache('visitor', 'field-'.$this->field_id, $data);
        } else {
            ee()->session->set_cache('visitor', 'field-'.$this->field_id, false);
        }

        return;
    }

    // ********************************************************************************* //

    /**
     * Handles any custom logic after an entry is saved.
     * Called after an entry is added or updated.
     * Available data is identical to save, but the settings array includes an entry_id.
     *
     * @param $data Contains the submitted field data. (Returned by save())
     * @access public
     * @return void
     */
    public function post_save($data)
    {
        //request comes from frontend SafeCracker, validation is done in extension
        if (REQ != 'CP' && REQ != 'PAGE') return;

        // Any data?
        $data = ee()->session->cache('visitor', 'field-'.$this->field_id);
        if (!$data) return;

        $entry_id = $this->content_id();
        $member_id = $data['member_id'];
        $entry = ee('Model')->get('ChannelEntry', $entry_id)->first();

        $action = 'new';

        //----------------------------------------
        // Update Member
        //----------------------------------------
        if ($member_id > 0) {
            $action = 'update';
            $member = ee('Model')->get('Member', $member_id)->first();

            // Let's update the author_id real quick!
            $entry->author_id = $member_id;
            $entry->save();

            if ($member->email != $data['email']) {
                $member->email = $data['email'];
            }

            if ($member->username != $data['username']) {
                $member->username = $data['username'];
            }

            if (isset($data['new_password']) && $data['new_password'] != '') {
                // Now that we know the password is valid, hash it
                ee()->load->library('auth');
                $hashed_password = ee()->auth->hash_password($data['new_password']);
                $member->password = $hashed_password['password'];
                $member->salt = $hashed_password['salt'];
            }

            if ($member->group_id != $data['group_id']) {
                $member->group_id = $data['group_id'];
                ee('visitor:Members')->updateMemberStatus($entry_id, $member_id, $data['group_id']);
            }

            $member->save();
        }

        //----------------------------------------
        // New Member?
        //----------------------------------------
        else {
            $member_id = ee('visitor:MembersRegister')->registerSimple($data);
        }

        // Sync Member Status
        ee('visitor:Members')->syncMemberStatus($member_id, $entry_id);

        // Update visitor field to contain the member_id
        // Save the author_id real quick
        $entry->author_id = $member_id;
        $entry->setProperty('field_id_' . $this->field_id, $member_id);
        $entry->save();

        // Sync the screen_name based on the provided override fields
        if (ee('visitor:Settings')->settings['use_screen_name'] == 'no' && ee('visitor:Settings')->settings['screen_name_override'] != '') {
            ee('visitor:Members')->updateScreenName($member_id, $entry_id);
        }

        ee('visitor:Members')->updateEntryTitle($entry_id, $member_id);
        ee('visitor:Members')->updateNativeMemberFields($member_id, $entry_id);

        // Sync fields back to original member data
        ee('visitor:Members')->syncBackToMember($entry_id, $member_id);

        if ($action == 'new') {
            $edata = ee()->extensions->call('visitor_cp_register_end', $_POST, $member_id);
            if (ee()->extensions->end_script === TRUE) return;
        } else {
            $edata = ee()->extensions->call('visitor_cp_update_end', $_POST, $member_id);
            if (ee()->extensions->end_script === TRUE) return;
        }

        return;
    }

    /**
     * Display the settings page. The default ExpressionEngine rows can be created using built in methods.
     * All of these take the current $data and the fieltype name as parameters:
     *
     * @param $data array
     * @access public
     * @return void
     */
    public function display_settings($data)
    {
        $settings = ee('visitor:Settings')->getFieldtypeSettings($this->field_id);

        $fields = array(
            array(
                'title'  => lang('v:ft_show'),
                'desc'   => lang('v:ft_show_desc'),
                'fields' => array(
                    'visitor[show]' => array(
                        'type'  => 'inline_radio',
                        'choices' => array(
                            'both' => lang('v:both'),
                            'form' => lang('v:user_form'),
                            'info' => lang('v:user_info'),
                        ),
                        'value' => $settings['show'],
                    )
                )
            ),
        );

        return array('field_options_visitor' => array(
            'label'    => 'field_options',
            'group'    => 'visitor',
            'settings' => $fields
        ));
    }

    // ********************************************************************************* //
    /**
     * Save the fieldtype settings.
     *
     * @param $data array Contains the submitted settings for this field.
     * @access public
     * @return array
     */
    public function save_settings($data)
    {
        $settings = ee('Request')->post('visitor');

        if ($settings['show'] == 'both') {
            $settings['field_wide'] = true;
        }

        return $settings;
    }

    // ********************************************************************************* //
}

/* End of file ft.visitor.php */
/* Location: ./system/user/addons/visitor/ft.visitor.php */