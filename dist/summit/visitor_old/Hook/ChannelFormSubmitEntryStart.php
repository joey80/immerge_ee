<?php

namespace DevDemon\Visitor\Hook;

/**
 * ChannelFormSubmitEntryStart Hook Class
 *
 * @package         DevDemon_Visitor
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 */
class ChannelFormSubmitEntryStart extends AbstractHook
{
    /**
     * [channel_form_submit_entry_start description]
     *
     * @param  object  $formObj Active channel form object
     * @return void
     */
    public function execute($formObj)
    {
        if (ee('Request')->post('visitor_action') != 'register'
            && ee('Request')->post('visitor_action') != 'update'
            && ee('Request')->post('visitor_action') != 'update_profile'
        ) return;

        $this->addAdditionalRules();
        ee()->session->cache['visitor_field_errors'] = array();

        //----------------------------------------
        // Is password required to update the regular channel fields?
        //----------------------------------------
        if (ee('Request')->post('visitor_require_password') == 'yes') {
            $current_password = ee('Request')->post('current_password');
            $member_id = ee('visitor:Members')->getMemberId(ee('Request')->post('entry_id'));
            $member_id = $member_id ? $member_id : ee()->session->userdata('member_id');

            $current_password_errors = ee('visitor:Members')->validateCurrentPassword($member_id, $current_password);

            if ($current_password_errors != 'valid') {
                $formObj->field_errors['current_password'] = $current_password_errors;
                ee()->session->cache['visitor_field_errors']['current_password'] = $current_password_errors;
            }
        }

        //----------------------------------------
        // Extra merging of categories, can be used to split up categories for validation
        //----------------------------------------
        if (isset($_POST['category_to_be_merged']) && !empty($_POST['category_to_be_merged'])) {
            $category = (isset($_POST['category'])) ? $_POST['category'] : array();

            foreach ($_POST['category_to_be_merged'] as $key => $value) {
                $category = array_merge($category, $value);
            }

            $_POST['category'] = $category;
        }

        //----------------------------------------
        // Make sure we are updating!
        //----------------------------------------
        if (!isset($_POST['username'])
            && !isset($_POST['screen_name'])
            && !isset($_POST['password'])
            && !isset($_POST['email'])
            && !isset($_POST['new_password'])
            && !isset($_POST['group_id'])) {

            return;

        }

        $profileFields = array('username', 'screen_name', 'password', 'email', 'new_password');

        // Remove all empty fields
        foreach ($profileFields as $name) {
            if (!ee('Request')->post($name)) {
                unset($_POST[$name]);
            }
        }

        // This is a member profile update or registration
        $_POST['visitor_action'] = (ee('Request')->post('visitor_action') == 'register') ? 'register' : 'update_profile';

        $title = '';
        $email = trim(ee('Request')->post('email'));
        $username = trim(ee('Request')->post('username'));
        $password = trim(ee('Request')->post('password'));

        // Set the channel entry title, because this field is required
        if ($this->settings['email_is_username'] == 'yes' && $email) {
            $_POST['username'] = $email;
        }

        if ($this->settings['email_is_username'] == 'yes' && $username) {
            $_POST['email'] = $username;
        }

        if ($this->settings['use_screen_name'] == 'no' && $username) {
            $_POST['screen_name'] = $username;
        }

        if ($this->settings['password_confirmation'] == 'no' && $password) {
            $_POST['password_confirm'] = $password;
        }

        if ($this->settings['email_confirmation'] == 'no' && $email) {
            $_POST['email_confirm'] = $email;
        }

        // The title will be synced when entry is submitted
        if (!isset($_POST['use_dynamic_title'])) {
            $_POST['title'] = ee('Request')->post('EE_title') ?: 'no_title';
            $_POST['title'] = ee('Request')->post('username') ?: $_POST['title'];
            $_POST['title'] = ee('Request')->post('email') ?: $_POST['title'];
            $_POST['title'] = (!$_POST['title']) ? 'no_title' : $_POST['title'];
        }

        // ==========================
        // = grab the update errors =
        // ==========================
        if ($_POST['visitor_action'] == 'update_profile') {

            // Validate update
            $validate_errors = ee('visitor:MembersRegister')->updateProfile(false);

            if (count($validate_errors) > 0) {
                foreach ($validate_errors as $key => $value) {
                    if (count($value) > 0) {
                        $formObj->field_errors[$key] = implode('<br/>', $value);
                        ee()->session->cache['visitor_field_errors'][$key] = implode('<br/>', $value);
                    }

                }
            }
        }

        // ================================
        // = grab the registration errors =
        // ================================
        if ($_POST['visitor_action'] == 'register') {

            $reg_result = ee('visitor:MembersRegister')->register(false);

            if ($reg_result[0] == 'submission' && count($reg_result[1]) > 0) {
                foreach ($reg_result[1] as $key => $value) {
                    if (count($value) > 0) {
                        $formObj->field_errors[$key]                                    = implode('<br/>', $value);
                        ee()->session->cache['visitor_field_errors'][$key] = implode('<br/>', $value);
                    }
                }
            }
        }
    }

    private function addAdditionalRules()
    {
        // Validation rules based on the "rules" parameter
        $additional_rule_fields = array('screen_name', 'username', 'email', 'password', 'current_password', 'new_password', 'new_password_confirm');

        $meta = $_POST['meta'];
		
        ee()->load->library('encrypt');
        $meta = ee()->encrypt->decode($meta, ee()->db->username . ee()->db->password);
        $meta = @unserialize($meta);

        if (isset($meta['rules'])) {
            foreach ($additional_rule_fields as $additional_rule) {
                if (array_key_exists($additional_rule, $meta['rules'])) {
                    ee()->form_validation->set_rules($additional_rule, ee()->lang->line($additional_rule), $meta['rules'][$additional_rule]);
                }
            }
        }
    }

}

/* End of file ChannelFormSubmitEntryStart.php */
/* Location: ./system/user/addons/Visitor/Hook/ChannelFormSubmitEntryStart.php */