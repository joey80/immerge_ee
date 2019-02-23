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
class BeforeChannelEntryDelete extends AbstractHook
{

    /**
     * Called after the channel entry object is deleted.
     *
     * @param  object  $member   Current ChannelEntry model object
     * @param  array   $values   The ChannelEntry model object data as an array
     * @return void
     */
    public function execute($entry, $values)
    {
        if ($entry->channel_id != $this->settings['member_channel_id']) return;
        if ($this->settings['delete_member_when_deleting_entry'] != 'yes') return;

        // Get the member_id
        $member = ee('Model')->get('Member', $entry->author_id)->fields('member_id', 'group_id')->first();
        if (!$member) return;

        // Verify the member is allowed to delete
        if ( ! ee()->cp->allowed_group('can_delete_members')) {
            show_error(lang('unauthorized_access'));
        }

        if (ee()->session->userdata['member_id'] == $member->member_id) {
            show_error(lang('can_not_delete_self'));
        }

        $this->_super_admin_delete_check($member->member_id);

        // EE removes author_id when onAfterDelete() runs,
        // so we need to cache it.
        ee()->session->set_cache('visitor', 'author-' . $entry->entry_id, $entry->author_id);
    }

    /**
     * Check to see if the members being deleted are super admins. If they are
     * we need to make sure that the deleting user is a super admin and that
     * there is at least one more super admin remaining.
     *
     * @param  Array  $member_ids Array of member_ids being deleted
     * @return void
     */
    private function _super_admin_delete_check($member_ids)
    {
        if ( ! is_array($member_ids))
        {
            $member_ids = array($member_ids);
        }

        $super_admins = ee('Model')->get('Member')
            ->filter('group_id', 1)
            ->filter('member_id', 'IN', $member_ids)
            ->count();

        if ($super_admins > 0)
        {
            // You must be a Super Admin to delete a Super Admin

            if (ee()->session->userdata['group_id'] != 1)
            {
                show_error(lang('must_be_superadmin_to_delete_one'));
            }

            // You can't delete the only Super Admin
            $total_super_admins = ee('Model')->get('Member')
                ->filter('group_id', 1)
                ->count();

            if ($super_admins >= $total_super_admins)
            {
                show_error(lang('cannot_delete_super_admin'));
            }
        }
    }
}

/* End of file BeforeChannelEntryDelete.php */
/* Location: ./system/user/addons/Visitor/Hook/BeforeChannelEntryDelete.php */