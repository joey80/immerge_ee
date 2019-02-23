<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Visitor Module Extension File
 *
 * @package         DevDemon_Visitor
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 * @see             https://docs.expressionengine.com/latest/development/extensions.html
 */
class Visitor_ext
{
    public $version         = VISITOR_VERSION;
    public $name            = VISITOR_NAME;
    public $description     = 'Supports the Visitor Module in various functions.';
    public $docs_url        = 'http://www.devdemon.com';
    public $settings_exist  = false;
    public $settings        = array();
    public $hooks           = array(
        'sessions_end',
        'after_member_update',
        'member_register_validate_members',
        'cp_members_member_create', 'cp_members_validate_members',
        'before_channel_entry_delete', 'after_channel_entry_delete',
        'channel_form_submit_entry_start', 'channel_form_submit_entry_end',
    );

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {

    }

    public function __call($name, $args)
    {
        $class = '\\DevDemon\\Visitor\\Hook\\' . ee('visitor:Helper')->studlyCase($name);

        if (class_exists($class) === false) {
            $error = 'Hook Class not found: ' . str_replace('\\', '&#x5C;', $class);
            return ee()->output->fatal_error($error);
        }

        $hook = new $class();

        if (ee()->extensions->last_call !== false) {
            $hook->lastCall = ee()->extensions->last_call;
        }

        $ret = call_user_func_array(array($hook, 'execute'), $args);

        if ($hook->endScript == true) {
            ee()->extensions->end_script = true;
        }

        return $ret;
    }

    /**
     * Called by ExpressionEngine when the user activates the extension.
     *
     * @access      public
     * @return      void
     **/
    public function activate_extension()
    {
        foreach ($this->hooks as $hook) {
             $data = array(
                'class'     =>  __CLASS__,
                'method'    =>  $hook,
                'hook'      =>  $hook,
                'settings'  =>  serialize($this->settings),
                'priority'  =>  100,
                'version'   =>  $this->version,
                'enabled'   =>  'y'
            );

            // insert in database
            ee()->db->insert('extensions', $data);
        }
    }

    /**
     * Called by ExpressionEngine when the user disables the extension.
     *
     * @access      public
     * @return      void
     **/
    public function disable_extension()
    {
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('extensions');
    }

    /**
     * Called by ExpressionEngine updates the extension
     *
     * @access public
     * @return void
     **/
    public function update_extension($current=false)
    {
        if ($current == $this->version) return false;

        // Get all existing ones
        $dbexts = array();
        $query = ee()->db->select('*')->from('extensions')->where('class', __CLASS__)->get();

        foreach ($query->result() as $row) {
            $dbexts[$row->hook] = $row;
        }

        // Add the new ones
        foreach ($this->hooks as $hook) {
            if (isset($dbexts[$hook]) === true) continue;

            $data = array(
                'class'     =>  __CLASS__,
                'method'    =>  $hook,
                'hook'      =>  $hook,
                'settings'  =>  serialize($this->settings),
                'priority'  =>  100,
                'version'   =>  $this->version,
                'enabled'   =>  'y'
            );

            // insert in database
            ee()->db->insert('extensions', $data);
        }

        // Delete old ones
        foreach ($dbexts as $hook => $ext) {
            if (in_array($hook, $this->hooks) === true) continue;

            ee()->db->where('hook', $hook);
            ee()->db->where('class', __CLASS__);
            ee()->db->delete('extensions');
        }

        // Update the version number for all remaining hooks
        ee()->db->where('class', __CLASS__)->update('extensions', array('version' => $this->version));

    }

} // END CLASS

/* End of file ext.visitor.php */
/* Location: ./system/user/addons/visitor/ext.visitor.php */