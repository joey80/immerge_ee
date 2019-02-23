<?php if (!defined('BASEPATH')) die('No direct script access allowed');

$lang = array(

'visitor'           => 'Visitor',
'v:dashboard'       => 'Dashboard',
'v:members_as_entries' => 'Members as Channel Entries',
'v:settings'        => 'Settings',
'v:installation'    => 'Installation',
'v:sync_mdata'      => 'Transfer Member Data',

// Fieldtype
'v:ft_show'          => 'Fieldtype Content',
'v:ft_show_desc'     => 'A wider field will be used when showing both User form and info. You can have two Visitor fieldtypes in a fieldgroup, each should show another type of content.',
'v:both'             => 'Both',
'v:user_form'        => 'User Form',
'v:user_form_header' => 'User Account Form',
'v:user_info'        => 'Info & Links',
'v:user_info_header' => 'User Info & Links',
'v:warning_own_account' => 'Warning: This is your own account!',
'v:new_password'     => 'New Password',
'v:password_confirm' => 'Confirm Password',
'v:password_blank'   => 'Leave the fields blank if you do not wish to change the password.',

'v:email_member'    => 'Email Member',
'v:login_as_member' => 'Login as Member',
'v:join_date'       => 'Join Date',
'v:last_visit'      => 'Last Visit',

// Settings
'v:email_is_username'          => 'Set username as email (login is email)',
'v:use_screen_name'            => 'Require screen name',
'v:use_screen_name_desc'       => 'When set to no, you will be able to compose the screen_name out of custom fields',
'v:screen_name_override'       => 'Set screen_name as a combination of the following fields',
'v:screen_name_override_desc'  => 'Leave blank to use username',
'v:title_override'             => 'Set the member entry title as a combination of the following fields',
'v:title_override_desc'        => 'Leave blank to use username',
'v:email_confirmation'         => 'Require email confirmation',
'v:email_confirmation_desc'    => 'Do not forget to add email_confirm input in the form',
'v:password_confirmation'      => 'Require password confirmation when registering',
'v:password_confirmation_desc' => 'do not forget to add password_confirm input in the form when set to yes',

'v:general_settings'          => 'General Settings',
'v:redirect_view_all_members' => 'Redirect the "View all members" link to the channel entries overview',
'v:redirect_member_edit_profile_to_edit_channel_entry' => 'Redirect the Account "edit profile" link to the corresponding member channel entry page',
'v:membergroup_as_status'     => 'Show membergroup in channel entry status',

'v:fieldtype_settings'           => 'Fieldtype Settings',
'v:hide_link_to_existing_member' => 'Hide "Link an existing member" for non super-admins',

'v:member_channel_settings' => "Member channel settings - Advanced use (do not change if you're not sure about the functionality)",
'v:member_channel_id'       => 'Channel which you would like to use as members channel',
'v:anonymous_member_id'     => 'Anonymous guest member used for registration',
'v:delete_member_when_deleting_entry' => 'Delete member when deleting entry',
'v:delete_member_when_deleting_entry_desc' => 'When the linked entry is deleted, this will force the member account also to be deleted. Be careful when bulk deleting entries.',

'v:fields_not_exists'       => 'Field(s) does not exists',
'v:fields_not_exists_desc'  => 'The following variables cannot be matched to an existing field:',

// Installations
'v:install_exp_1'            => 'The Visitor addon can be used to turn your members into Channel Entries and use any fieldtype etc you want.',
'v:install_exp_2'            => 'But it can also be used without enabling this feature, you can use the template tags to register/view/edit/etc members.',
'v:channel_installed'        => 'Does Visitor channel exists?',
'v:channel_exists_yes'       => 'Channel exists',
'v:channel_exists_no'        => 'Channel does not exist',
'v:fieldtype_in_channel'     => 'Does the Visitor fieldtype exist in the channel fields?',
'v:fieldtype_in_channel_yes' => 'Fieldtype is present',
'v:fieldtype_in_channel_no'  => 'Fieldtype has not been added to the channel fields',
'v:linked_with_members'       => 'Visitor channel will be configured to be used as Members channel',
'v:linked_with_members_yes'   => 'Channel has been assigned',
'v:linked_with_members_no'    => 'No channel has been assigned',
'v:allow_member_registration'         => 'New ExpressionEngine member registrations are allowed?',
'v:allow_member_registration_yes'     => 'New member registrations are allowed',
'v:allow_member_registration_no'      => 'EE new member registrations are not allowed, go to "System Settings" -> "Members" and set the "Allow Registrations" option to "yes".',
'v:guest_member_posts'                => 'Guest membergroup can create entries in Visitor Member channel?',
'v:guest_member_posts_allowed_yes'    => 'Guests can post',
'v:guest_member_posts_allowed_no'     => 'Registrations are not possible, go to "Members" -> "Member Groups" -> Edit: "Guests". Set "Allowed actions" to Create & Edit own Entries. Thick "Visitor Members" in the "Allowed Channels" section.',
'v:guest_member_created'      => 'Create anonymous Visitor Guest member (required to use registration form)',
'v:guest_member_created_yes'  => 'An anonymous Guest member exists',
'v:guest_member_created_no'   => 'An anonymous Guest member does not exist or is not linked with Visitor, go to the Visitor settings and select a member you want to use as anonymous Guest member.',
'v:channel_form_author'    => 'Visitor Guest member set as default Channel Form author',
'v:channel_form_author_no'    => 'Visitor Guest member is not the default author',
'v:channel_form_author_yes'    => 'Visitor Guest member is the default author',
'v:example_templategroup_exists'      => 'Install example login/register/profile/password_change/login change templates which serve as a starting point',
'v:example_templategroup_exists_yes'  => 'Examples exist. They can be located at your_site_root/visitor_example/profile',
'v:example_templategroup_exists_no'   => 'Examples do not exist',

'v:sync_desc_0' => 'This method can be used to transfer existing members into your Visitor channel.',
'v:sync_desc_1' => 'Select the fields you want to transfer to your member channel. Submit the form.',
'v:sync_desc_2' => 'Corresponding channel fields will automatically be created in the Visitor channel field group. The created fieldnames will use "mbr_" as a prefix.',
'v:sync_desc_3' => 'Member entries will be created for those who don\'t have a corresponding channel entry yet. Selected field data will be transferred.',
'v:sync_desc_4' => 'When using {exp:visitor:sync} in your templates, the same fields as checked here will be used. This sync tag can be used whenever a new member has been registered through another add-on and you want to transfer this data directly into th extended Visitor profile. Can be used in combination with Membrr registrations, Solspace Facebook Connect, etc... Just place this tag on the registration success page of these add-ons.',
'v:sync_desc_5' => 'These selected fields will also be used when using the sync tag.',
'v:sync_done' => 'Members have been transferred.',

'v:error_can_admin_members' => 'You need to be assigned rights to administrate member accounts.',
'v:error_info_new_member'   => 'Info & Stats are only available for existing members.',
'v:error_member_link_missing'=> 'This entry has no member account linked to it. Members are linked via entry author. Entry author is not set or the associated member does not exists.',
'v:error_user_form_shown'   => 'User form can only be shown once per field group.',

//----------------------------------------
// Errors
//----------------------------------------

// Email
'v:invalid_email_address'          => 'You did not submit a valid email address',
'v:email_taken'                    => 'The email you chose is not available',

// Username
'v:username_taken'                 => 'The username you chose is not available',
'v:username_too_short'             => 'Your username must be at least %x characters long',
'v:username_too_long'              => 'Your username cannot be over 50 characters in length',
'v:invalid_characters_in_username' => 'Your username cannot use the following characters: | " \' ! < > { }',

// Screen Name
'v:screen_name_taken'              => 'The screen name you chose is not available',
'v:disallowed_screen_chars'        => 'Screen Name contains illegal characters',

// Passwords
'v:missmatched_passwords'          => 'The password and password confirmation do not match',
'v:password_too_short'             => 'Your password must be at least %x characters long',
'v:password_too_long'              => 'Your password cannot be over '.PASSWORD_MAX_LENGTH.' characters in length',
'v:password_based_on_username'     => 'The password cannot be based on the username',
'v:password_in_dictionary'         => 'You are not allowed to use a word found in a dictionary as a password',
'v:not_secure_password'            => 'Password must contain at least one uppercase character, one lowercase character and one number',


// To be checked
'v:valid_user_email'               => 'The email you chose is not valid',
'v:missing_password'               => 'You must submit a password',
'v:missing_current_password'       => 'In order to make changes you must submit the current password',
'v:invalid_password'               => 'The password you submitted was not correct',
'v:missing_email'                  => 'You must submit an email address',
'v:banned_email'                   => 'The email address you submitted is banned',



// END
''=>''
);

/* End of file visitor_lang.php */
/* Location: ./system/user/addons/visitor/language/english/visitor_lang.php */