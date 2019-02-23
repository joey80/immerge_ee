<?php

if (!defined('VISITOR_NAME')){
    define('VISITOR_NAME',         'Visitor');
    define('VISITOR_CLASS_NAME',   'visitor');
    define('VISITOR_VERSION',      '3.0.1');
}

if ( ! function_exists('dd')) {
    function dd()
    {
        array_map(function($x) { var_dump($x); }, func_get_args()); die;
    }
}

return array(
    'author'         => 'DevDemon',
    'author_url'     => 'http://www.devdemon.com/',
    'docs_url'       => 'http://www.devdemon.com/docs/',
    'name'           => VISITOR_NAME,
    'description'    => '',
    'version'        => VISITOR_VERSION,
    'namespace'      => 'DevDemon\Visitor',
    'settings_exist' => true,
    'fieldtypes' => array(
        'visitor' => array(
            'name' => 'Visitor',
            'compatibility' => null,
        ),
    ),
    'models' => array(
        //'File' => 'Model\File',
    ),
    'services'       => array(),
    'services.singletons' => array(
        'Settings' => function($addon) {
            return new DevDemon\Visitor\Service\Settings($addon);
        },
        'Helper' => function($addon) {
            return new DevDemon\Visitor\Service\Helper($addon);
        },
        'Members' => function($addon) {
            return new DevDemon\Visitor\Service\Members($addon);
        },
        'MembersAuth' => function($addon) {
            return new DevDemon\Visitor\Service\MembersAuth($addon);
        },
        'MembersRegister' => function($addon) {
            return new DevDemon\Visitor\Service\MembersRegister($addon);
        },
        'MembersSync' => function($addon) {
            return new DevDemon\Visitor\Service\MembersSync($addon);
        },
    ),

    //----------------------------------------
    // Default Module Settings
    //----------------------------------------
    'settings_module' => array(
        'installed'                    => 'no',

        'use_screen_name'              => 'no',
        'screen_name_override'         => '',
        'title_override'               => '',
        'email_is_username'            => 'yes',
        'email_confirmation'           => 'no',
        'password_confirmation'        => 'yes',
        'new_entry_status'             => 'incomplete_profile',
        'incomplete_status'            => 'incomplete_profile',
        'hide_link_to_existing_member' => 'no',
        'membergroup_as_status'        => 'yes',
        'delete_member_when_deleting_entry' => 'no',
        'redirect_after_activation'    => 'no',
        'redirect_location'            => '',
        'redirect_view_all_members'    => 'no',
        'redirect_member_edit_profile_to_edit_channel_entry' => 'no',

        // Member
        'member_channel_id'            => '',
        'anonymous_member_id'          => '',

        // Sync specific settings
        'sync_standard_member_fields'  => '',
        'sync_custom_member_fields'    => '',

        // Not being used
        'sync_back_to_member' => 'no',
        'sync_back_fields'    => 'yes',
    ),

    //----------------------------------------
    // Default Fieldtype Settings
    //----------------------------------------
    'settings_fieldtype' => array(
        'show' => 'both',
    ),
);

/* End of file addons.setup.php */
/* Location: ./system/user/addons/visitor/addons.setup.php */