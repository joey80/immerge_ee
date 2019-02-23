<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Module File
 *
 * @package         DevDemon_Visitor
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 * @see             https://ellislab.com/expressionengine/user-guide/development/modules.html
 */
class Visitor
{
    /**
     * Module Constructor
     */
    public function __construct()
    {

    }

    public function __call($name, $args)
    {
        $class = '\\DevDemon\\Visitor\\Tag\\' . ee('visitor:Helper')->studlyCase($name);

        if (class_exists($class) === false) {
            ee()->TMPL->log_item("Tag Not Processed: Method Inexistent or Module Not Installed");

            $error  = ee()->lang->line('error_tag_module_processing');
            $error .= '<br /><br />';
            $error .= htmlspecialchars(LD);
            $error .= 'exp:visitor:'.$name;
            $error .= htmlspecialchars(RD);
            $error .= '<br /><br />';
            $error .= str_replace('%x', 'visitor', str_replace('%y', $name, ee()->lang->line('error_fix_module_processing')));
            ee()->output->fatal_error($error);
        }

        if (empty($args)) {
            $tag = new $class(ee()->TMPL->tagdata, ee()->TMPL->tagparams);
        } else {
            $tag = new $class($args[0], $args[1]);
        }

        return $tag->parse();
    }

} // END CLASS

/* End of file mod.visitor.php */
/* Location: ./system/user/addons/visitor/mod.visitor.php */