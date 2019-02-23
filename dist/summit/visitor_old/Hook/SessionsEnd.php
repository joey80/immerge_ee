<?php

namespace DevDemon\Visitor\Hook;

use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * Abstract Hook Class
 *
 * @package         DevDemon_Visitor
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 */
class SessionsEnd extends AbstractHook
{

    /**
     * 'sessions_end' hook.
     * - Modify the user's session/member data.
     * - Additional Session or Login methods (ex: log in to other system)
     *
     * @param  obj $session The session object
     * @return void
     */
    public function execute($session)
    {
        if (REQ == 'CP') {
            $class  = ee()->router->class;
            $method = ee()->router->method;
            $uri = ee()->uri->uri_string();

            //dd($class, $method, $uri, $_GET, $_POST);

            // = Delete member entry if member is deleted =
            if ($class == 'members' && $method == 'delete') {
                // For now, when you delete a member it, it will also delete their entries, always!
                unset($_POST['heir_action']);
            }

            if ($uri == 'cp/members/groups/delete' && isset($_POST['replacement'])) {
                $this->_before_member_group_delete();
            }

            // Redirect "View All Members"
            if ($class == 'members' && $method == 'index' && $this->settings['redirect_view_all_members'] == 'yes') {
                $url = $this->getUrlFactory('cp/publish/edit', $session)->setQueryStringVariable('filter_by_channel', $this->settings['member_channel_id']);
                header("Location: {$url}");
                return;
            }

            // Redirect "Edit Member Profile"
            if ($class == 'profile' && $method == 'index' && $this->settings['redirect_member_edit_profile_to_edit_channel_entry'] == 'yes') {
                $entry_id = ee('visitor:Members')->getVisitorId(ee()->input->get('id'));

                if ($entry_id) {
                    $url = $this->getUrlFactory('cp/publish/edit/entry/'.$entry_id, $session);
                    header("Location: {$url}");
                    return;
                }
            }

            return;
        }

        //---
        // From this point forward it's only for PAGE/ACTION requests
        //---

        $globalVars =& ee()->config->_global_vars;
        $globalVars['current_uri_string'] = ee()->uri->uri_string;

        //----------------------------------------
        // Logged in?
        //----------------------------------------
        if (isset($session->sdata['member_id']) && $session->sdata['member_id'] > 0) {
            $member_id = $session->sdata['member_id'];
            $globalVars['visitor_member_id'] = $member_id;
            $globalVars['zoo_member_id'] = $member_id;

            // Set the visitor id
            $visitor_id = ee('visitor:Members')->getVisitorId($member_id);
            $globalVars['visitor_id'] = $visitor_id;
            $globalVars['zoo_visitor_id'] = $visitor_id;

            // = GET THE MEMBER DATA AS GLOBAL VARS? =
            if ($visitor_id) {
                $fieldq = ee()->db->query('SELECT ch.field_group, cf.field_id, cf.field_name FROM exp_channels ch, exp_channel_fields cf WHERE ch.channel_id = "' . $this->settings['member_channel_id'] . '" AND cf.group_id = ch.field_group');

                if ($fieldq->num_rows() > 0) {
                    $field_ids   = array();
                    $field_names = array();

                    foreach ($fieldq->result() as $row) {
                        array_push($field_ids, 'field_id_' . $row->field_id);
                        $field_names[$row->field_id] = $row->field_name;
                    }

                    $fields = implode(',', $field_ids);
                    $dataq  = ee()->db->query('SELECT ct.url_title, ct.expiration_date, ' . $fields . ' FROM exp_channel_data cd, exp_channel_titles ct WHERE cd.entry_id = "' . $visitor_id . '" AND ct.entry_id  = "' . $visitor_id . '"');

                    if ($dataq->num_rows() > 0) {
                        $values = $dataq->row_array();

                        foreach ($field_names as $field_id => $field_name) {
                            $globalVars["visitor:global:{$field_name}"]        = $values['field_id_' . $field_id];
                            $globalVars["visitor:global:field_id_{$field_id}"] = $values['field_id_' . $field_id];
                        }

                        $globalVars['visitor:global:url_title']       = $values['url_title'];
                        $globalVars['visitor:global:expiration_date'] = $values['expiration_date'];
                    }

                    $globalVars['visitor:global:categories_piped'] = '';

                    $data_cats = ee()->db->query('SELECT cp.cat_id FROM exp_category_posts cp WHERE cp.entry_id  = "' . $visitor_id . '"');

                    if ($data_cats->num_rows() > 0) {

                        $visitor_cats = array();
                        foreach ($data_cats->result_array() as $key => $cat) {
                            $visitor_cats[] = $cat['cat_id'];
                        }

                        $globalVars['visitor:global:categories_piped'] = implode('|', $visitor_cats);
                    }
                }

                $uploads = ee()->db->select('*')->from('upload_prefs')->get();
                if (isset($uploads) && $uploads->num_rows() > 0) {

                    foreach ($uploads->result() as $row) {
                        $globalVars["filedir_{$row->id}"] = $row->url;
                    }
                }
            }
        }

        // Set the visitor channel name
        if (!isset($this->settings['member_channel_name']) || !$this->settings['member_channel_name']) {
            $this->settings['member_channel_name'] = '';

            $q = ee()->db->select('channel_name')->from('channels')->where('channel_id', $this->settings['member_channel_id'])->get();
            if ($q->num_rows() > 0) {
                $this->settings['member_channel_name'] = $q->row('channel_name');
            }
        }

        $globalVars['visitor_channel_name'] = $this->settings['member_channel_name'];
        $globalVars['zoo_visitor_channel_name'] = $this->settings['member_channel_name'];

    }

    private function _before_member_group_delete()
    {
        $mgroupId = (ee()->input->post('replacement') == 'delete') ? 3 : $_POST['replacement'];

        foreach ($_POST['selection'] as $group_id) {
            // Grab all members with that group id
            $q = ee()->db->select('member_id')->from('members')->where('group_id', $group_id)->get();

            $ids = array();
            foreach ($q->result() as $row) {
                $ids[] = $row->member_id;
            }

            if (empty($ids)) continue;

            foreach ($ids as $member_id) {
                $entry_id = ee('visitor:Members')->getVisitorId($member_id);

                if ($entry_id) {
                    ee('visitor:Members')->updateMemberStatus($entry_id, $member_id, $mgroupId);
                }
            }
        }
    }

    private function getUrlFactory($path, $session)
    {
        $sessionType = ee()->config->item('cp_session_type');

        //just to prevent any errors
        if (!defined('BASE')) {
            $s = ($sessionType != 'c') ? $session->sdata['session_id'] : 0;
            define('BASE', SELF.'?S='.$s.'&amp;D=cp');
        }

        $session_id = ($sessionType == 'cs') ? $session->sdata['fingerprint'] : $session->sdata['session_id'];
        $factory = new URL($path, $session_id);

        return $factory;
    }

}

/* End of file SessionsEnd.php */
/* Location: ./system/user/addons/Visitor/Hook/SessionsEnd.php */