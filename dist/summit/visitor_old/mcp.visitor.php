<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Zoo Visitor Control Panel Class
 *
 * @package   Zoo Visitor
 * @author    ExpressionEngine Zoo <info@eezoo.com>
 * @copyright Copyright (c) 2011 ExpressionEngine Zoo (http://eezoo.com)
 */
class Visitor_mcp
{
    public $module_name = VISITOR_NAME;
    public $class_name  = VISITOR_CLASS_NAME;
    public $settings    = null;

    /**
     * Views Data
     * @var array
     * @access protected
     */
    protected $vdata = array();

    /**
     * Base URI
     * @var string
     * @access protected
     */
    protected $baseUri = 'addons/settings/visitor/';

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->baseUrl = ee('CP/URL', $this->baseUri);
        $this->site_id = ee()->config->item('site_id');
        $this->vdata['baseUri'] = $this->baseUri;
        $this->vdata['baseUrl'] = $this->baseUrl->compile();

        // Generate the Sidebar & JS/CSS
        $this->generateSidebar();
        $this->addMcpJsCss();

        ee()->view->header = array(
            'title' => lang('visitor'),
            'toolbar_items' => array(
                'settings'  => array(
                    'href'  => ee('CP/URL', $this->baseUri . 'settings_mace'),
                    'title' => lang('v:settings')
                )
            )
        );
    }

    // ********************************************************************************* //

    public function index()
    {
        ee()->functions->redirect(ee('CP/URL', $this->baseUri . 'settings_mace'));

        return array(
            'heading'    => lang('v:dashboard'),
            'body'       => ee('View')->make('visitor:mcp/index')->render($this->vdata),
            'breadcrumb' => array(
                $this->baseUrl->compile() => lang('visitor')
            ),
        );
    }

    // ********************************************************************************* //

    public function installation()
    {
        $this->vdata['steps'] = \DevDemon\Visitor\Service\Installation::getSteps();

        return array(
            'heading'    => lang('v:installation'),
            'body'       => ee('View')->make('visitor:mcp/installation')->render($this->vdata),
            'breadcrumb' => array(
                $this->baseUrl->compile() => lang('visitor')
            ),
        );
    }

    // ********************************************************************************* //

    public function install()
    {
        $res = \DevDemon\Visitor\Service\Installation::install();

        ee()->functions->redirect(ee('CP/URL', $this->baseUri . 'installation'));
    }

    // ********************************************************************************* //

    public function sync()
    {
        $this->checkInstallation();

        // ==========================
        // = standard member fields =
        // ==========================
        $this->vdata['fields']         = array('url', 'location', 'occupation', 'interests', 'aol_im', 'yahoo_im', 'msn_im', 'icq', 'bio', 'bday_y', 'bday_m', 'bday_d', 'birthday', 'signature', 'timezone');
        $this->vdata['fields_checked'] = array();
        $checkedFields          = explode('|', ee('visitor:Settings')->settings['sync_standard_member_fields']);
        foreach ($checkedFields as $field) {
            $parts = explode(':', $field);
            $this->vdata['fields_checked'][] = $parts[0];
        }

        // ========================
        // = custom member fields =
        // ========================
        $customFields = ee('Model')->get('MemberField')->order('m_field_order')->all();
        $customFields = $customFields->getDictionary('m_field_id', 'm_field_label');

        $this->vdata['custom_fields']         = $customFields;
        $this->vdata['custom_fields_checked'] = array();
        $custom_member_fields                 = explode('|', ee('visitor:Settings')->settings['sync_custom_member_fields']);
        foreach ($custom_member_fields as $field) {
            $parts                                  = explode(':', $field);
            $this->vdata['custom_fields_checked'][] = $parts[0];
        }

        return array(
            'heading'    => lang('v:sync_mdata'),
            'body'       => ee('View')->make('visitor:mcp/sync')->render($this->vdata),
            'breadcrumb' => array(
                $this->baseUrl->compile() => lang('visitor')
            ),
        );
    }

    // ********************************************************************************* //

    public function syncMembers()
    {
        $fields = ee('Request')->post('fields', array());
        $customFields = ee('Request')->post('custom_fields', array());

        // Sync
        $res = ee('visitor:MembersSync')->syncFields($fields, $customFields);
        $res = ee('visitor:MembersSync')->syncMemberData();

        ee('CP/Alert')->makeInline('shared-form')
        ->asSuccess()
        ->withTitle(lang('v:sync_done'))
        ->defer();

        ee()->functions->redirect(ee('CP/URL', $this->baseUri . 'sync'));
    }

    // ********************************************************************************* //

    public function settings()
    {
        $settings = ee('visitor:Settings')->settings;

        // Form definition array
        $this->vdata['sections'] = array(
            array(
                array(
                    'title'  => 'v:email_is_username',
                    'fields' => array(
                        'settings[email_is_username]' => array(
                            'type'    => 'inline_radio',
                            'value'   => $settings['email_is_username'],
                            'choices' => array(
                                'yes' => lang('yes'),
                                'no'  => lang('no'),
                            )
                        )
                    )
                ),
                array(
                    'title'  => 'v:email_confirmation',
                    'desc'   => 'v:email_confirmation_desc',
                    'fields' => array(
                        'settings[email_confirmation]' => array(
                            'type'    => 'inline_radio',
                            'value'   => $settings['email_confirmation'],
                            'choices' => array(
                                'yes' => lang('yes'),
                                'no'  => lang('no'),
                            )
                        )
                    )
                ),
                array(
                    'title'  => 'v:password_confirmation',
                    'desc'   => 'v:password_confirmation_desc',
                    'fields' => array(
                        'settings[password_confirmation]' => array(
                            'type'    => 'inline_radio',
                            'value'   => $settings['password_confirmation'],
                            'choices' => array(
                                'yes' => lang('yes'),
                                'no'  => lang('no'),
                            )
                        )
                    )
                ),
            ),
        );

        // Final view variables we need to render the form
        $this->vdata += array(
            'base_url'      => ee('CP/URL', $this->baseUri . 'save-settings')->compile(),
            'cp_page_title' => lang('v:settings'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
        );

        return array(
            'heading'    => lang('v:settings'),
            'body'       => ee('View')->make('visitor:mcp/settings')->render($this->vdata),
            'breadcrumb' => array(
                $this->baseUrl->compile() => lang('visitor')
            ),
        );
    }

    // ********************************************************************************* //

    public function settings_mace()
    {
        $this->checkInstallation();

        $settings = ee('visitor:Settings')->settings;

        // Replace screen_name_override with field names
        $channelFields = ee('Model')->get('ChannelField')->filter('site_id', $this->site_id)->order('field_id', 'desc')->all();
        foreach ($channelFields as $field) {
            $settings['screen_name_override'] = str_replace('field_id_' . $field->field_id, '{' . $field->field_name . '}', $settings['screen_name_override']);
            $settings['title_override'] = str_replace('field_id_' . $field->field_id, '{' . $field->field_name . '}', $settings['title_override']);
        }

        // Get Channels
        $channels = ee('Model')->get('Channel')->order('channel_title', 'asc')->filter('site_id', $this->site_id)->all();
        $channels = array('---') + $channels->getDictionary('channel_id', 'channel_title');

        // Get All Guest Members
        $guestMembers = ee('Model')->get('Member')->order('email', 'asc')->filter('group_id', 3)->all();
        $guestMembers = array('---') + $guestMembers->getDictionary('member_id', 'email');


        // Form definition array
        $this->vdata['sections'] = array(
            array(
                array(
                    'title'  => 'v:email_is_username',
                    'fields' => array(
                        'settings[email_is_username]' => array(
                            'type'    => 'inline_radio',
                            'value'   => $settings['email_is_username'],
                            'choices' => array(
                                'yes' => lang('yes'),
                                'no'  => lang('no'),
                            )
                        )
                    )
                ),
                array(
                    'title'  => 'v:email_confirmation',
                    'desc'   => 'v:email_confirmation_desc',
                    'fields' => array(
                        'settings[email_confirmation]' => array(
                            'type'    => 'inline_radio',
                            'value'   => $settings['email_confirmation'],
                            'choices' => array(
                                'yes' => lang('yes'),
                                'no'  => lang('no'),
                            )
                        )
                    )
                ),
                array(
                    'title'  => 'v:password_confirmation',
                    'desc'   => 'v:password_confirmation_desc',
                    'fields' => array(
                        'settings[password_confirmation]' => array(
                            'type'    => 'inline_radio',
                            'value'   => $settings['password_confirmation'],
                            'choices' => array(
                                'yes' => lang('yes'),
                                'no'  => lang('no'),
                            )
                        )
                    )
                ),

                array(
                    'title'  => 'v:title_override',
                    'desc'   => 'v:title_override_desc',
                    'fields' => array(
                        'settings[title_override]' => array(
                            'type'    => 'text',
                            'value'   => $settings['title_override'],
                        )
                    )
                ),
                array(
                    'title'  => 'v:use_screen_name',
                    'desc'   => 'v:use_screen_name_desc',
                    'fields' => array(
                        'settings[use_screen_name]' => array(
                            'type'    => 'inline_radio',
                            'value'   => $settings['use_screen_name'],
                            'choices' => array(
                                'yes' => lang('yes'),
                                'no'  => lang('no'),
                            )
                        )
                    )
                ),
                array(
                    'title'  => 'v:screen_name_override',
                    'desc'   => 'v:screen_name_override_desc',
                    'fields' => array(
                        'settings[screen_name_override]' => array(
                            'type'    => 'text',
                            'value'   => $settings['screen_name_override'],
                        )
                    )
                ),
            ),
            'v:general_settings' => array(
                array(
                    'title'  => 'v:redirect_view_all_members',
                    'fields' => array(
                        'settings[redirect_view_all_members]' => array(
                            'type'    => 'inline_radio',
                            'value'   => $settings['redirect_view_all_members'],
                            'choices' => array(
                                'yes' => lang('yes'),
                                'no'  => lang('no'),
                            )
                        )
                    )
                ),
                array(
                    'title'  => 'v:redirect_member_edit_profile_to_edit_channel_entry',
                    'fields' => array(
                        'settings[redirect_member_edit_profile_to_edit_channel_entry]' => array(
                            'type'    => 'inline_radio',
                            'value'   => $settings['redirect_member_edit_profile_to_edit_channel_entry'],
                            'choices' => array(
                                'yes' => lang('yes'),
                                'no'  => lang('no'),
                            )
                        )
                    )
                ),
                array(
                    'title'  => 'v:membergroup_as_status',
                    'fields' => array(
                        'settings[membergroup_as_status]' => array(
                            'type'    => 'inline_radio',
                            'value'   => $settings['membergroup_as_status'],
                            'choices' => array(
                                'yes' => lang('yes'),
                                'no'  => lang('no'),
                            )
                        )
                    )
                ),
            ),
            'v:fieldtype_settings' => array(
                array(
                    'title'  => 'v:hide_link_to_existing_member',
                    'fields' => array(
                        'settings[hide_link_to_existing_member]' => array(
                            'type'    => 'inline_radio',
                            'value'   => $settings['hide_link_to_existing_member'],
                            'choices' => array(
                                'yes' => lang('yes'),
                                'no'  => lang('no'),
                            )
                        )
                    )
                ),
            ),
            'v:member_channel_settings' => array(
                array(
                    'title'  => 'v:member_channel_id',
                    'fields' => array(
                        'settings[member_channel_id]' => array(
                            'type'    => 'select',
                            'value'   => $settings['member_channel_id'],
                            'choices' => $channels,
                        )
                    )
                ),
                array(
                    'title'  => 'v:anonymous_member_id',
                    'fields' => array(
                        'settings[anonymous_member_id]' => array(
                            'type'    => 'select',
                            'value'   => $settings['anonymous_member_id'],
                            'choices' => $guestMembers,
                        )
                    )
                ),
                array(
                    'title'  => 'v:delete_member_when_deleting_entry',
                    'desc'   => 'v:delete_member_when_deleting_entry_desc',
                    'caution' => TRUE,
                    'fields' => array(
                        'settings[delete_member_when_deleting_entry]' => array(
                            'type'    => 'inline_radio',
                            'value'   => $settings['delete_member_when_deleting_entry'],
                            'choices' => array(
                                'yes' => lang('yes'),
                                'no'  => lang('no'),
                            )
                        )
                    )
                ),
            ),
        );

        // Final view variables we need to render the form
        $this->vdata += array(
            'base_url'      => ee('CP/URL', $this->baseUri . 'save-settings')->setQueryStringVariable('mace', 'yes')->compile(),
            'cp_page_title' => lang('v:settings'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
        );

        return array(
            'heading'    => lang('v:settings'),
            'body'       => ee('View')->make('visitor:mcp/settings')->render($this->vdata),
            'breadcrumb' => array(
                $this->baseUrl->compile() => lang('visitor')
            ),
        );
    }

    // ********************************************************************************* //

    public function saveSettings()
    {
        $settings = ee('visitor:Settings')->settings;
        $postedSettings = ee('Request')->post('settings');
        $finalSettings = array_merge($settings, $postedSettings);

        $saveSettings = true;

        foreach ($postedSettings as $setting => $val) {
            if (in_array($setting, array('screen_name_override', 'title_override')) == false) continue;

            $ret = $this->convertFieldVarsToIds($finalSettings[$setting]);

            if (is_array($ret)) {

                ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('v:fields_not_exists'))
                ->addToBody(lang('v:fields_not_exists_desc') . ' ' . implode($ret))
                ->defer();

                $saveSettings = false;
                break;
            }

            $finalSettings[$setting] = trim($ret);
        }

        if ($saveSettings) {
            ee('visitor:Settings')->saveModuleSettings($finalSettings);

            ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('settings_saved'))
            ->addToBody(sprintf(lang('settings_saved_desc'), FORMS_NAME))
            ->defer();
        }

        // Check to see if the title_override has changed && is not empty
        if (isset($postedSettings['title_override'])
         && $finalSettings['title_override'] != $settings['title_override']
         && $finalSettings['title_override'] != '') {

            ee()->db->select('entry_id');
            //ee()->db->where('site_id', ee()->config->item('site_id'));
            ee()->db->where('channel_id', ee('visitor:Settings')->settings['member_channel_id']);
            ee()->db->order_by('entry_id', 'asc');

            $query = ee()->db->get('channel_titles');

            if ($query->num_rows()) {
                foreach ($query->result() as $row) {
                    ee('visitor:SyncMembers')->updateEntryTitle($row->entry_id);
                }
            }
        }

        // Redirect to Members as Channel Entries specific settings page
        if (ee('Request')->get('mace') == 'yes') {
            ee()->functions->redirect(ee('CP/URL', $this->baseUri . 'settings_mace'));
        }

        ee()->functions->redirect(ee('CP/URL', $this->baseUri . 'settings'));
    }

    // ********************************************************************************* //

    protected function convertFieldVarsToIds($str)
    {
        $pattern = "/{(.*?)}/";
        preg_match_all($pattern, $str, $matches);

        $override_fields      = (isset($matches[1])) ? $matches[1] : array();
        $screen_name_override = '';
        $errors               = array();

        if (!count($override_fields) || !$str) {
            return;
        }

        foreach ($override_fields as $fieldName) {
            $field = ee('Model')->get('ChannelField')->filter('field_name', $fieldName)->first();

            if (!$field) {
                $errors[] = '{' . $fieldName . '}';
                continue;
            }

            $str = str_replace('{' . $fieldName . '}', 'field_id_' . $field->field_id, $str);
        }

        if (count($errors) > 0) return $errors;
        else return $str;
    }

    // ********************************************************************************* //

    protected function generateSidebar()
    {
        $sidebar = ee('CP/Sidebar')->make();

        // Dashboard
        //$this->navDash = $sidebar->addHeader(lang('v:dashboard'), ee('CP/URL', rtrim($this->baseUri, '/')));

        // General Settings
        //$this->navSettings = $sidebar->addHeader(lang('v:general_settings'), ee('CP/URL', $this->baseUri . 'settings'));

        // Members as Entries
        $this->navMemberAsEntries = $sidebar->addHeader(lang('v:members_as_entries'));
        $membersAsEntries = $this->navMemberAsEntries->addBasicList();

        // Installation
        $this->navInstall = $membersAsEntries->addItem(lang('v:installation'), ee('CP/URL', $this->baseUri . 'installation'));

        // Sync Member Data
        $this->navSync = $membersAsEntries->addItem(lang('v:sync_mdata'), ee('CP/URL', $this->baseUri . 'sync'));

        // Settings
        $this->navSettingsMace = $membersAsEntries->addItem(lang('v:settings'), ee('CP/URL', $this->baseUri . 'settings_mace'));

        return $sidebar;
    }

    // ********************************************************************************* //

    protected function addMcpJsCss()
    {
        ee('visitor:Helper')->mcpAssets('gjs');
        ee('visitor:Helper')->mcpAssets('css', 'addon_mcp.css', null, true);
        ee('visitor:Helper')->mcpAssets('js', 'addon_mcp.js', null, true);
    }

    // ********************************************************************************* //

    private function checkInstallation()
    {
        if (ee('visitor:Settings')->settings['installed'] == 'no') {
            ee()->functions->redirect(ee('CP/URL', $this->baseUri . 'installation'));
        }
    }

} // END CLASS

/* End of file mcp.visitor.php */
/* Location: ./system/user/addons/visitor/mcp.visitor.php */