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
class CpMembersMemberCreate extends AbstractHook
{

    /**
     * Additional processing after a member is created via the control panel.
     * Executes after member is created, but before stats are recounted.
     *
     * @param  int   $member_id   New member’s ID
     * @param  array $data        New member’s data
     * @return void
     */
    public function execute($member_id, $data)
    {
        if (!$this->settings['member_channel_id']) return;
        if (ee()->router->class == 'publish') return; // If we are creating one already, return

        // Construct entry title
        $title = $data['email'];

        if ($this->settings['email_is_username'] != 'yes') {
            $title .= ' - ' . $data['username'];
        }

        if ($this->settings['use_screen_name'] != 'no') {
            $title .= ' - ' . $data['screen_name'];
        }

        $channel = ee('Model')->get('Channel', $this->settings['member_channel_id'])->first();
        if (!$channel) return;

        $entry = ee('Model')->make('ChannelEntry');
        $entry->Channel            = $channel;
        $entry->site_id            = $this->site_id;
        $entry->channel_id         = $channel->channel_id;
        $entry->author_id          = $member_id; // @todo double check if this is validated
        $entry->entry_date         = ee()->localize->now;
        $entry->edit_date          = ee()->localize->now;
        $entry->ip_address         = ee()->session->userdata['ip_address'];
        $entry->versioning_enabled = $channel->enable_versioning;
        $entry->sticky             = false;
        $entry->title              = $title;
        $entry->url_title          = url_title($title, ee()->config->item('word_separator'), true);

        // Set some defaults based on Channel Settings
        $entry->allow_comments = (isset($channel->deft_comments)) ? $channel->deft_comments : true;

        // Channel Default Status?
        if (isset($channel->deft_status)) {
            $entry->status = $channel->deft_status;
        }

        // Any Channel Default Categories
        if (isset($channel->deft_category)) {
            $cat = ee('Model')->get('Category', $channel->deft_category)->first();
            if ($cat) {
                $entry->Categories[] = $cat;
            }
        }

        $entry->save();

        // Sync the member status
        ee('visitor:Members')->syncMemberStatus($member_id, $entry->entry_id);
    }
}

/* End of file CpMembersMemberCreate.php */
/* Location: ./system/user/addons/Visitor/Hook/CpMembersMemberCreate.php */