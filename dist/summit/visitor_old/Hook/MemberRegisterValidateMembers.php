<?php

namespace DevDemon\Visitor\Hook;

use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * MemberRegisterValidateMembers Hook Class
 *
 * @package         DevDemon_Visitor
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 */
class MemberRegisterValidateMembers extends AbstractHook
{

    /**
     * Additional processing when member(s) are self validated
     *
     * @param  int  $member_id    The ID of the member
     * @return void
     */
    public function execute($member_id)
    {
        ee()->db->select('group_id');
        $query = ee()->db->get_where('visitor_activation_membergroup', array('member_id' => $member_id));

        if ($query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                ee()->db->where('member_id', $member_id);
                ee()->db->update('members', array('group_id' => $row->group_id));

                // Delete the record
                ee()->db->delete('visitor_activation_membergroup', array('member_id' => $member_id));
            }
        }

        ee('visitor:Members')->syncMemberStatus($member_id);
    }

}

/* End of file MemberRegisterValidateMembers.php */
/* Location: ./system/user/addons/Visitor/Hook/MemberRegisterValidateMembers.php */