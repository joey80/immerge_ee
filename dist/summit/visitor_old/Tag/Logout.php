<?php

namespace DevDemon\Visitor\Tag;

class Logout extends AbstractTag
{
    public function parse()
    {
        // Kill the session and cookies
        ee()->db->where('site_id', ee()->config->item('site_id'));
        ee()->db->where('ip_address', ee()->input->ip_address());
        ee()->db->where('member_id', ee()->session->userdata('member_id'));
        ee()->db->delete('online_users');

        ee()->session->destroy();

        ee()->input->set_cookie('read_topics');

        /* -------------------------------------------
        /* 'member_member_logout' hook.
        /*  - Perform additional actions after logout
        /*  - Added EE 1.6.1
        */
        $edata = ee()->extensions->call('member_member_logout');
        if (ee()->extensions->end_script === TRUE) return;
        /*
        /* -------------------------------------------*/

        // Is this a forum redirect?
        $name = '';
        unset($url);

        if (ee()->input->get_post('FROM') == 'forum') {
            if (ee()->input->get_post('board_id') !== FALSE &&
                is_numeric(ee()->input->get_post('board_id'))
            ) {
                $query = ee()->db->select("board_forum_url, board_label")
                    ->where('board_id', ee()->input->get_post('board_id'))
                    ->get('forum_boards');
            } else {
                $query = ee()->db->select('board_forum_url, board_label')
                    ->where('board_id', (int)1)
                    ->get('forum_boards');
            }

            $url  = $query->row('board_forum_url');
            $name = $query->row('board_label');
        }


        if (($return = ee()->TMPL->fetch_param('return')) !== FALSE) {
            ee()->functions->redirect(ee()->functions->create_url($return));
        } else {
            // return to most recent page
            ee()->functions->redirect(ee()->functions->form_backtrack(1));
        }
    }
}

/* End of file Logout.php */
/* Location: ./system/user/addons/Visitor/Tag/Logout.php */