<?php

namespace DevDemon\Visitor\Tag;

class UpdateForm extends AbstractTag
{
    public function parse()
    {
        if (!$this->settings['member_channel_id']) {
            $this->log('No member channel has been specified, check your Visitor settings');
            return $this->noResultsConditional('no_results');
        }

        $channel = ee('Model')->get('Channel', $this->settings['member_channel_id'])->first();

        if (!$channel) {
            $this->log('The member channel specified in your Visitor settings does not exist.');
            return $this->noResultsConditional('no_results');
        }

        // allow membergroup to update channel entry
        ee()->db->where('channel_id', $this->settings['member_channel_id']);
        ee()->db->where('group_id', ee()->session->userdata['group_id']);
        $query_cmg = ee()->db->get('channel_member_groups');

        if ($query_cmg->num_rows() == 0) {
            ee()->db->set('group_id', ee()->session->userdata['group_id']);
            ee()->db->set('channel_id', $this->settings['member_channel_id']);
            ee()->db->insert('channel_member_groups');
        }

        //parse the captcha
        $this->tagdata = $this->parseCaptcha($this->tagdata);

        $require_password = $this->param('require_password', '');
        $member_id        = $this->param('member_id', 'current');
        $member_entry_id  = $this->param('member_entry_id', '');
        $username         = $this->param('username', '');

        if ($member_entry_id == '') {
            if ($username != '') {
                $entry_id = ee('visitor:Members')->getVisitorIdByUsername($username);
            } else {
                $entry_id = ee('visitor:Members')->getVisitorId($member_id);
            }
        }
        else {
            $entry_id = $member_entry_id;
        }

        // =========================
        // = Native members fields =
        // =========================
        ee()->db->select('member_id, group_id, username, screen_name, email, bday_y, bday_m, bday_d, url, location, occupation, interests, aol_im, icq, yahoo_im, msn_im, bio');
        ee()->db->from('members AS m');
        ee()->db->join('channel_titles AS ct', "ct.author_id = m.member_id", 'left');
        ee()->db->where('ct.entry_id', $entry_id);
        $query = ee()->db->get();
        $row = $query->row();

        if ($query->num_rows() == 0) {
            $this->log('Member Entry not found');
            return $this->noResultsConditional('no_results');
        }

        $member_id = $row->member_id;

        $vars = array();
        $vars['native:birthday_year']         = $this->birthdayYear($row->bday_y);
        $vars['native:birthday_month']        = $this->birthdayMonth($row->bday_m);
        $vars['native:birthday_day']          = $this->birthdayDay($row->bday_d);
        $vars['native:url']                   = ($row->url == '') ? 'http://' : $row->url;
        $vars['native:location']              = $row->location;
        $vars['native:occupation']            = $row->occupation;
        $vars['native:interests']             = $row->interests;
        $vars['native:aol_im']                = $row->aol_im;
        $vars['native:icq']                   = $row->icq;
        $vars['native:icq_im']                = $row->icq;
        $vars['native:yahoo_im']              = $row->yahoo_im;
        $vars['native:msn_im']                = $row->msn_im;
        $vars['native:bio']                   = $row->bio;
        $vars['username' ]                    = $row->username;
        $vars['screen_name' ]                 = $row->screen_name;
        $vars['email' ]                       = $row->email;
        $vars['member_group_id' ]             = $row->group_id;

        $this->tagdata = ee()->TMPL->parse_variables_row($this->tagdata, $vars);

        //wrap in channel form tags
        $form = '{exp:channel:form channel_id="' . $this->settings['member_channel_id'] . '" entry_id="' . $entry_id . '"  use_live_url="no" ' . $this->getChannelFormParams() . '}';

        //insert registration trigger
        $form .= '<input type="hidden" name="visitor_error_delimiters" value="' . htmlentities($this->param('error_delimiters', '')) . '">';
        $form .= '<input type="hidden" name="AG" value="' . ee('visitor:Helper')->encryptString($this->param('allowed_groups', '')) . '">';
        $form .= '<input type="hidden" name="visitor_action" id="visitor_action" value="update">';
        $form .= '<input type="hidden" name="title" id="EE_title" value="' . ee('visitor:Members')->getVisitorEntryTitle($member_id) . '">';
        $form .= '<input type="hidden" name="visitor_require_password" id="visitor_require_password" value="' . $require_password . '">';
        $form .= $this->tagdata;

        //wrap in channel form tags
        $form .= '{/exp:channel:form}';

        //if the form hasn't been submitted, remove error: fields (parse empty)
        return (count($_POST) == 0) ? ee()->TMPL->parse_variables_row($form, array($this->getChannelFormVars())) : $form;
    }
}

/* End of file UpdateForm.php */
/* Location: ./system/user/addons/Visitor/Tag/UpdateForm.php */