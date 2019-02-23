<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Install / Uninstall and updates the modules
 *
 * @package         DevDemon_Visitor
 * @author          DevDemon <http://www.devdemon.com>
 * @copyright       Copyright (c) 2007-2016 DevDemon <http://www.devdemon.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com/visitor/
 * @see             http://expressionengine.com/user_guide/development/module_tutorial.html#update_file
 */
class Visitor_upd
{
    /**
     * Module version
     *
     * @var string
     * @access public
     */
    public $version     =   VISITOR_VERSION;

    /**
     * Module Short Name
     *
     * @var string
     * @access private
     */
    private $module_name    =   VISITOR_NAME;

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        ee()->load->dbforge();
    }

    // ********************************************************************************* //

    /**
     * Installs the module
     *
     * Installs the module, adding a record to the exp_modules table,
     * creates and populates and necessary database tables,
     * adds any necessary records to the exp_actions table,
     * and if custom tabs are to be used, adds those fields to any saved publish layouts
     *
     * @access public
     * @return boolean
     **/
    public function install()
    {
        //----------------------------------------
        // EXP_MODULES
        //----------------------------------------
        $module = ee('Model')->make('Module');
        $module->module_name = ucfirst($this->module_name);
        $module->module_version = $this->version;
        $module->has_cp_backend = 'y';
        $module->has_publish_fields = 'n';
        $module->save();

        //----------------------------------------
        // EXP_ACTIONS
        //----------------------------------------
        $action = ee('Model')->make('Action');
        $action->class = ucfirst($this->module_name);
        $action->method = 'actionGeneralRouter';
        $action->csrf_exempt = 0;
        $action->save();

        //----------------------------------------
        // VISITOR_ACTIVATION_MEMBERGROUP
        //----------------------------------------
        $fields = array(
            'member_id' => array('type' => 'INT', 'unsigned' => TRUE, 'default' => 0),
            'group_id'  => array('type' => 'INT', 'unsigned' => TRUE, 'default' => 0),
        );

        ee()->dbforge->add_field($fields);
        ee()->dbforge->add_key('member_id');
        ee()->dbforge->create_table('visitor_activation_membergroup', TRUE);

        //----------------------------------------
        // EXP_MODULES
        // The settings column, Ellislab should have put this one in long ago.
        // No need for a seperate preferences table for each module.
        //----------------------------------------
        if (ee()->db->field_exists('settings', 'modules') == false) {
            ee()->dbforge->add_column('modules', array('settings' => array('type' => 'TEXT') ) );
        }

        return true;
    }

    // ********************************************************************************* //

    /**
     * Uninstalls the module
     *
     * @access public
     * @return Boolean false if uninstall failed, true if it was successful
     **/
    public function uninstall()
    {
        // Remove
        ee()->dbforge->drop_table('visitor_activation_membergroup');

        ee('Model')->get('Action')->filter('class', ucfirst($this->module_name))->all()->delete();
        ee('Model')->get('Module')->filter('module_name', ucfirst($this->module_name))->all()->delete();

        return true;
    }

    // ********************************************************************************* //

    /**
     * Updates the module
     *
     * This function is checked on any visit to the module's control panel,
     * and compares the current version number in the file to
     * the recorded version in the database.
     * This allows you to easily make database or
     * other changes as new versions of the module come out.
     *
     * @access public
     * @return Boolean FALSE if no update is necessary, TRUE if it is.
     **/
    public function update($current = '')
    {
        // Are they the same?
        if (version_compare($current, $this->version) >= 0) {
            return false;
        }

        // Two Digits? (needs to be 3)
        if (strlen($current) == 2) $current .= '0';

        $update_dir = PATH_THIRD.strtolower($this->module_name).'/Updates/';

        // Does our folder exist?
        if (@is_dir($update_dir) === true) {
            // Loop over all files
            $files = @scandir($update_dir);

            if (is_array($files) == true) {
                foreach ($files as $file) {
                    if (strpos($file, '.php') === false) continue;
                    if (strpos($file, '_') === false) continue; // For legacy: XXX.php
                    if ($file == '.' OR $file == '..' OR strtolower($file) == '.ds_store') continue;

                    // Get the version number
                    $ver = substr($file, 0, -4);
                    $ver = str_replace('_', '.', $ver);

                    // We only want greater ones
                    if (version_compare($current, $ver) >= 0) continue;

                    require $update_dir . $file;
                    $class = 'VisitorUpdate_' . str_replace('.', '', $ver);
                    $UPD = new $class();
                    $UPD->update();
                }
            }
        }

        // Upgrade The Module
        $module = ee('Model')->get('Module')->filter('module_name', ucfirst($this->module_name))->first();
        $module->module_version = $this->version;
        $module->save();

        // Upgrade The Fieldtype
        $fieldtype = ee('Model')->get('Fieldtype')->filter('name', $this->module_name)->first();
        $fieldtype->version = $this->version;
        $fieldtype->save();

        return true;
    }

} // END CLASS

/* End of file upd.visitor.php */
/* Location: ./system/user/addons/visitor/upd.visitor.php */