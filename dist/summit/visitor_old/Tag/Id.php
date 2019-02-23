<?php

namespace DevDemon\Visitor\Tag;

class Id extends AbstractTag
{
    // =====================================================================
    // = Gets entry ids of the current logged in member OR specific member
    // = OR piped ids of all members of specific member group =
    // =====================================================================
    public function parse()
    {
        $member_id    = ($this->param('member_id') != '') ? $this->param('member_id') : 'current';
        $member_group = ($this->param('member_group') != '') ? $this->param('member_group') : '';

        if ($member_group != '') {

            $ids       = implode("','", explode('|', trim($member_group)));
            $query_str = "SELECT tit.entry_id FROM exp_members mem, exp_channel_titles tit WHERE tit.author_id = mem.member_id AND tit.channel_id = '" . $this->settings['member_channel_id'] . "' AND mem.group_id IN ('" . $ids . "') GROUP BY mem.member_id";
            $query     = ee()->db->query($query_str);

            if ($query->num_rows() > 0) {
                $entry_ids = '0|';
                foreach ($query->result() as $row) {
                    $entry_ids .= $row->entry_id . '|';
                }

                return $entry_ids;
            } else {
                return '0|';
            }

        } else {
            return ee('visitor:Members')->getVisitorId($member_id);
        }
    }
}

/* End of file Id.php */
/* Location: ./system/user/addons/Visitor/Tag/Id.php */