<?php

namespace DevDemon\Visitor\Tag;

class Details extends AbstractTag
{
    // =====================================================================
    // = Gets entry ids of the current logged in member OR specific member
    // = OR piped ids of all members of specific member group =
    // =====================================================================
    public function parse()
    {
        $TMPL_cache = ee()->TMPL;
        $tagdata    = ee()->TMPL->tagdata;

        if (!$tagdata) return;

        $member_id       = ee()->TMPL->fetch_param('member_id', 'current');
        $member_entry_id = ee()->TMPL->fetch_param('member_entry_id', '');
        $username        = ee()->TMPL->fetch_param('username', '');
        $url_title       = ee()->TMPL->fetch_param('url_title', '');

        if (!$member_id && !$member_entry_id) return;

        if ($member_entry_id == '') {
            if ($username != '') {
                $entry_id = ee('visitor:Members')->getVisitorIdByUsername($username);
            }
            else {
                $entry_id = ee('visitor:Members')->getVisitorId($member_id);
            }
        }
        else {
            $entry_id = $member_entry_id;
        }

        $entry_id = (!$entry_id) ? "-1" : $entry_id;

        $tagdata = str_replace("{visitor:", "{", $tagdata);
        $tagdata = str_replace("{/visitor:", "{/", $tagdata);
        $tagdata = str_replace("{if visitor:", "{if ", $tagdata);

        $tagdata = str_replace("member:", "", $tagdata);
        $tagdata = str_replace("/member:", "/", $tagdata);

        ee()->TMPL->tagdata = $tagdata;

        if ($url_title == '') ee()->TMPL->tagparams['entry_id'] = $entry_id;
        if ($url_title != '') ee()->TMPL->tagparams['url_title'] = $url_title;

        ee()->TMPL->tagparams['channel_id']       = $this->settings['member_channel_id'];
        ee()->TMPL->tagparams['dynamic']       = 'no';
        ee()->TMPL->tagparams['status']        = 'not closed';
        ee()->TMPL->tagparams['show_expired']  = 'yes';
        ee()->TMPL->tagparams['require_entry'] = ($entry_id == '' || $entry_id == FALSE) ? 'yes' : 'no';

        if (!isset(ee()->TMPL->tagparams['disable'])) {
            ee()->TMPL->tagparams['disable'] = ''; //categories|category_fields|member_data|pagination';
        }

        $vars  = ee()->functions->assign_variables($tagdata);
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

/* End of file Details.php */
/* Location: ./system/user/addons/Visitor/Tag/Details.php */