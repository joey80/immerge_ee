<?php

namespace DevDemon\Visitor\Hook;

use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * CpMembersValidateMembers Hook Class
 *
 * @package         DevDemon_Visitor
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 */
class CpMembersValidateMembers extends AbstractHook
{

    /**
     * Additional processing after pending members are validated via the Control Panel.
     *
     * @return void
     */
    public function execute()
    {
        $lastSegment = end(ee()->uri->segments);

        $ids = array();

        if (isset($_POST['selection']) && ee()->input->post('bulk_action') == 'approve') {
            if (is_array($_POST['selection'])) {
                $ids = ee()->input->post('selection');
            }
        } else {
            if (ctype_digit($lastSegment)) {
                $ids[] = $lastSegment;
            }
        }

        foreach ($ids as $member_id) {
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

}

/* End of file CpMembersValidateMembers.php */
/* Location: ./system/user/addons/Visitor/Hook/CpMembersValidateMembers.php */