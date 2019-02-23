<?php

namespace DevDemon\Visitor\Hook;

/**
 * Abstract Hook Class
 *
 * @package         DevDemon_Visitor
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 */
class AfterMemberUpdate extends AbstractHook
{

    /**
     * Called after the member object is updated. Changes made to the object will not be saved automatically.
     * Calling save may fire additional hooks.
     *
     * @param  object  $member   Current Member model object
     * @param  array   $values   The Member model object data as an array
     * @param  array   $modified An array of all the old values that were changed
     * @return void
     */
    public function execute($member, $values, $modified)
    {
        if (REQ == 'CP') {
            $uri = ee()->uri->uri_string();

            // When member group changes, make sure we also change it in their entry profile
            if ($uri == 'cp/members/profile/group' && isset($modified['group_id'])) {
                $entry_id = ee('visitor:Members')->getVisitorId($member->member_id);

                if ($entry_id) {
                    ee('visitor:Members')->updateMemberStatus($entry_id, $member->member_id, $member->group_id);
                }
            }
        }
    }
}

/* End of file AfterMemberUpdate.php */
/* Location: ./system/user/addons/Visitor/Hook/AfterMemberUpdate.php */