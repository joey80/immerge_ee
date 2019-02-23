<?php

namespace DevDemon\Visitor\Tag;

class Members extends AbstractTag
{
    public function parse()
    {
        $tagdata = ee()->TMPL->tagdata;

        if (!$tagdata) return;

        // =======================
        // = GET ONLY ONE MEMBER =
        // =======================
        $member_id       = ee()->TMPL->fetch_param('member_id', ''); //=> current for current logged in user
        $member_entry_id = ee()->TMPL->fetch_param('member_entry_id', '');
        $username        = ee()->TMPL->fetch_param('username', '');

        if ($member_entry_id != '') {
            unset(ee()->TMPL->tagparams['member_entry_id']);
            $entry_id = $member_entry_id;
        }
        elseif ($username != '') {
            $entry_id = ee('visitor:Members')->getVisitorIdByUsername($username);
        }
        elseif ($member_id != '') {
            $entry_id = ee('visitor:Members')->getVisitorId($member_id);
        }

        if ($member_id == 'current' || $member_entry_id == 'current') {
            $entry_id = ee('visitor:Members')->getVisitorId();
        }

        //JUST GET SELECTED MEMBERS
        if (isset($entry_id)) {
            ee()->TMPL->tagparams['entry_id'] = $entry_id;
        }

        ee()->TMPL->tagparams['channel_id']       = $this->settings['member_channel_id'];
        ee()->TMPL->tagparams['dynamic']       = 'no';
        ee()->TMPL->tagparams['status']        = ee()->TMPL->fetch_param('status', 'not closed');
        ee()->TMPL->tagparams['show_expired']  = 'yes';
        ee()->TMPL->tagparams['require_entry'] = 'no'; //(isset($entry_id)) ? 'yes' : 'no';
        ee()->TMPL->tagparams['orderby']       = ee()->TMPL->fetch_param('orderby', 'date');
        ee()->TMPL->tagparams['sort']          = ee()->TMPL->fetch_param('sort', 'desc');
        ee()->TMPL->tagparams['limit']         = ee()->TMPL->fetch_param('limit', '1000');
        ee()->TMPL->tagparams['disable']       = ee()->TMPL->fetch_param('disable', '');

        ee()->TMPL->tagparams['group_id'] = ee()->TMPL->fetch_param('member_group', '');

        $tagdata = str_replace("visitor:", "", $tagdata);
        $tagdata = str_replace("/visitor:", "/", $tagdata);


        ee()->TMPL->tagdata = $tagdata;

        $vars = ee()->functions->assign_variables($tagdata);
        ee()->TMPL->var_single = $vars['var_single'];
        ee()->TMPL->var_pair   = $vars['var_pair'];

        if (!class_exists('Channel')) {
            require PATH_MOD . 'channel/mod.channel.php';
        }

        // create a new Channel object and run entries()
        $Channel = new \Channel();
        return $Channel->entries();
    }
}

/* End of file Members.php */
/* Location: ./system/user/addons/Visitor/Tag/Members.php */