<?php

namespace DevDemon\Visitor\Hook;

/**
 * ChannelFormSubmitEntryEnd Hook Class
 *
 * @package         DevDemon_Visitor
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 */
class ChannelFormSubmitEntryEnd extends AbstractHook
{
    /**
     * [channel_form_submit_entry_end description]
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

        $entry_id = $formObj->entry('entry_id');
        $member_id = 0;

        // = Get stored field errors =
        $is_valid = TRUE;
        $formObj->field_errors = array_merge($formObj->field_errors, ee()->session->cache['visitor_field_errors']);
        ee()->session->cache['visitor_field_errors'] = array();

        /** ----------------------------------------
         * /**  Zoo visitor action is set to register
         * /** ----------------------------------------*/


        // =========================
        // = create native profile =
        // =========================
        if ($_POST['visitor_action'] == 'register') {

            // -------------------------------------------
            // 'visitor_register_validation_start' hook.
            //  - Additional processing when a member is being validated through the registration form tag
            //

            $field_errors = ee()->extensions->call('visitor_register_validation_start', $formObj->field_errors);
            if ($field_errors) $formObj->field_errors = $field_errors;
            if (ee()->extensions->end_script === TRUE) return;

            //wrap errors if there is an error delimiter
            $formObj->field_errors = $this->prep_errors($formObj->field_errors);

            if ((is_array($formObj->errors) && count($formObj->errors) > 0) || (is_array($formObj->field_errors) && count($formObj->field_errors) > 0)) {

                ee()->db->where('entry_id', $entry_id);
                ee()->db->delete('channel_titles');
                ee()->db->where('entry_id', $entry_id);
                ee()->db->delete('channel_data');

                $is_valid = FALSE;
                //do nothing. let safecracker handle the error reporting

            } else {

                // -------------------------------------------
                // 'visitor_register_start' hook.
                //  - Additional processing before a member is registered
                //
                $edata = ee()->extensions->call('visitor_register_start', $_POST);
                if (ee()->extensions->end_script === TRUE) return;

                /** ----------------------------------------
                 * /** No Safecracker errors, register EE member
                 * /** ----------------------------------------*/
                $reg_result = ee('visitor:MembersRegister')->register(true);

                //EE member registration is complete, check result
                if (isset($reg_result['result']) && $reg_result['result'] == "registration_complete") {

                    $member_id = $reg_result['member_data']['member_id'];

                    //registration successfull, set author_id in channel entry
                    ee()->db->update('channel_titles', array('author_id' => $member_id), 'entry_id = ' . $entry_id);

                    //sync the screen_name based on the provided override fields
                    if ($this->settings['use_screen_name'] == "no" && $this->settings['screen_name_override'] != '') {
                        ee('visitor:Members')->updateScreenName($member_id, $entry_id);
                    }

                    if (!isset($_POST['use_dynamic_title'])) {
                        ee('visitor:Members')->updateEntryTitle($entry_id, $member_id);
                    }

                    ee('visitor:Members')->updateMemberStatus($entry_id, $member_id, $reg_result['member_data']['group_id']);
                    ee('visitor:Members')->updateNativeMemberFields($member_id, $entry_id);
                } else {

                    /** ----------------------------------------
                     * /** EE member registration failed
                     * /** ----------------------------------------*/

                    ee()->extensions->end_script = TRUE;

                    //EE registration failed, remove member channel entry
                    $entry = ee('Model')->get('ChannelEntry', $entry_id)->first();
                    $entry->delete();

                    $errors = array();
                    foreach ($reg_result[1] as $key => $value) {
                        if (count($value) > 0) {
                            if (is_array($value))
                                $errors[$key] = implode('<br/>', $value);
                            else
                                $errors[$key] = $value;
                        }
                    }

                    ee()->output->show_user_error($reg_result[0], $errors);
                }
            }
        }

        // =========================
        // = Native profile update =
        // =========================
        if ($_POST['visitor_action'] == 'update_profile' || $_POST['visitor_action'] == 'update') {

            $member_id = ee()->input->post('author_id');

            $formObj->field_errors = $this->prep_errors($formObj->field_errors);

            //check for safecracker errors
            if ((is_array($formObj->errors) && count($formObj->errors) > 0) || (is_array($formObj->field_errors) && count($formObj->field_errors) > 0)) {

                //do nothing. let safecracker handle the error reporting

                $is_valid = FALSE;

            } else {

                // -------------------------------------------
                // 'visitor_update_start' hook.
                //  - Additional processing before a member is updated through the update form tag
                //
                $edata = ee()->extensions->call('visitor_update_start', $_POST);
                if (ee()->extensions->end_script === TRUE) return;


                //do update
                ee('visitor:MembersRegister')->updateProfile(TRUE);
                ee('visitor:Members')->updateNativeMemberFields($member_id, $entry_id);
            }
        }

        //check if is has passed validation, if not, block the further processing
        if ($is_valid) {
            //sync the screen_name based on the provided override fields
            if ($this->settings['use_screen_name'] == "no" && $this->settings['screen_name_override'] != '') {
                ee('visitor:Members')->updateScreenName($member_id, $entry_id);
            }

            if (!isset($_POST['use_dynamic_title'])) {
                ee('visitor:Members')->updateEntryTitle($entry_id, $member_id);
            }

            //set membergroup status
            ee('visitor:Members')->syncMemberStatus($member_id, $entry_id);


            // ===================
            // = Post processing =
            // ===================
            if (isset($reg_result['result']) && $reg_result['result'] == "registration_complete") {
                // -------------------------------------------
                // 'visitor_register' hook.
                //  - Additional processing when a member is created through the registration form tag
                //  Still present for backward compatibility with other add-ons using the old register hook
                //
                $edata = ee()->extensions->call('visitor_register', array_merge($reg_result['member_data'], $_POST), $reg_result['member_data']['member_id']);
                if (ee()->extensions->end_script === TRUE) return;

                // -------------------------------------------
                // 'visitor_register_end' hook.
                //  - Additional processing when a member is created through the registration form tag
                //
                $edata = ee()->extensions->call('visitor_register_end', array_merge($reg_result['member_data'], $_POST), $reg_result['member_data']['member_id']);
                if (ee()->extensions->end_script === TRUE) return;


                //check activation method, if none, auto-login
                if (ee()->config->item('req_mbr_activation') == 'none') {

                    if (isset($_POST['autologin']) && $_POST['autologin'] == 'no') {
                    } else {
                        $this->autologin($reg_result['member_data'], $member_id);
                    }

                    //is redirect set?
                    if ($this->settings['redirect_after_activation'] == "yes") {

                        ee()->extensions->end_script = TRUE;

                        //sync the screen_name based on the provided override fields before the redirect
                        if ($this->settings['use_screen_name'] == "no" && $this->settings['screen_name_override'] != '') {
                            ee('visitor:Members')->updateScreenName($member_id, $entry_id);
                        }

                        //$this->redirect();

                    }
                }

                // ==============================================
                // = Send JSON RESPONSE WITH MEMBER ID INCLUDED =
                // ==============================================
                if ($formObj->json) {
                    if (is_array($formObj->errors)) {
                        //add the field name to custom_field_empty errors
                        foreach ($formObj->errors as $field_name => $error) {
                            if ($error == ee()->lang->line('custom_field_empty')) {
                                $formObj->errors[$field_name] = $error . ' ' . $field_name;
                            }
                        }
                    }

                    return $formObj->send_ajax_response(
                        array(
                            'success'      => (empty($formObj->errors) && empty($formObj->field_errors)) ? 1 : 0,
                            'errors'       => (empty($formObj->errors)) ? array() : $formObj->errors,
                            'field_errors' => (empty($formObj->field_errors)) ? array() : $formObj->field_errors,
                            'entry_id'     => $entry_id,
                            'member_id'    => $member_id,
                            'url_title'    => $formObj->entry('url_title'),
                            'channel_id'   => $formObj->entry('channel_id'),
                        )
                    );
                }
            }

            if ($_POST['visitor_action'] == 'update_profile' || $_POST['visitor_action'] == 'update') {
                // -------------------------------------------
                // 'visitor_update_end' hook.
                //  - Additional processing when a member is update through the update form tag
                //
                $edata = ee()->extensions->call('visitor_update_end', $_POST, $member_id);
                if (ee()->extensions->end_script === TRUE) return;
            }
        }
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

    private function autologin($data, $member_id)
    {
        // Log user in (the extra query is a little annoying)
        ee()->load->library('auth');
        $member_data_q = ee()->db->get_where('members', array('member_id' => $member_id));

        $incoming = new \Auth_result($member_data_q->row());
        $incoming->remember_me(60 * 60 * 24 * 182);
        $incoming->start_session();

        $message = lang('mbr_your_are_logged_in');
    }

}

/* End of file ChannelFormSubmitEntryEnd.php */
/* Location: ./system/user/addons/Visitor/Hook/ChannelFormSubmitEntryEnd.php */