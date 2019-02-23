<?php

namespace DevDemon\Visitor\Tag;

class RegistrationForm extends AbstractTag
{

    public function parse()
    {
        if (!$this->settings['member_channel_id']) {
            $this->log('Visitor: No member channel has been specified, check your Visitor settings');
            return $this->noResultsConditional('no_results');
        }

        $channel = ee('Model')->get('Channel', $this->settings['member_channel_id'])->first();

        if (!$channel) {
            $this->log('Visitor: The member channel specified in your Visitor settings does not exist.');
            return $this->noResultsConditional('no_results');
        }

        if (!$this->settings['anonymous_member_id']) {
            $this->log('Visitor: anonymous_member_id does not exist.');
            return $this->noResultsConditional('no_results');
        }

        $anonMember = ee('Model')->get('Member', $this->settings['anonymous_member_id'])->first();
        if (!$anonMember) {
            $this->log('Visitor: Anonymous Member does not exist (Member: Visitor Guest).');
            return $this->noResultsConditional('no_results');
        }

        //parse the captcha
        $this->tagdata = $this->parseCaptcha($this->tagdata);

        // =========================
        // = Native members fields =
        // =========================
        $query = ee()->db->query("SELECT bday_y, bday_m, bday_d, url, location, occupation, interests, aol_im, icq, yahoo_im, msn_im, bio FROM exp_members WHERE member_id = '" . ee()->session->userdata('member_id') . "'");

        $vars = array();
        $vars['native:birthday_year']         = $this->birthdayYear($query->row('bday_y'));
        $vars['native:birthday_month']        = $this->birthdayMonth($query->row('bday_m'));
        $vars['native:birthday_day']          = $this->birthdayDay($query->row('bday_d'));

        //include group_id as tag variable for error handling after posting incomplete forms
        if (ee()->input->post('group_id')) {
            $vars['group_id'] = ee()->input->post('group_id');
        }

        $this->tagdata = ee()->TMPL->parse_variables_row($this->tagdata, $vars);

        //wrap in channel form tags
        $form = '{exp:channel:form channel_id="' . $this->settings['member_channel_id'] . '" logged_out_member_id="'.$this->settings['anonymous_member_id'].'" ' . $this->getChannelFormParams() . ' }';

        //insert registration trigger
        $form .= '<input type="hidden" name="visitor_error_delimiters" value="' . htmlentities($this->param('error_delimiters', '')) . '">';

        //set a hidden field if dynamic title parameter is being used, this parameter is not reachable anymore in extension hooks as it has been moved to a protected variable in Channel Form
        if ($this->param('dynamic_title', false)){
            $form .= '<input type="hidden" name="use_dynamic_title" value="yes" />';
        }

        $form .= '<input type="hidden" name="AG" value="' . ee('visitor:Helper')->encryptString($this->param('allowed_groups', '')) . '">';
        $form .= '<input type="hidden" name="autologin" value="' . $this->param('autologin', '') . '">';
        $form .= '<input type="hidden" name="visitor_action" id="visitor_action" value="register">' . $this->tagdata;

        //wrap in channel form tags
        $form .= '{/exp:channel:form}';

        //if the form hasn't been submitted, remove error: fields (parse empty)
        return (count($_POST) == 0) ? ee()->TMPL->parse_variables($form, array($this->getChannelFormVars())) : $form;
    }
}

/* End of file RegistrationForm.php */
/* Location: ./system/user/addons/Visitor/Tag/RegistrationForm.php */