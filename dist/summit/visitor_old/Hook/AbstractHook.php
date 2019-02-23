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
abstract class AbstractHook
{
    protected $site_id;
    protected $settings = array();
    public $lastCall;
    public $endScript = false;

    public function __construct()
    {
        $this->site_id = ee()->config->item('site_id');
        $this->settings = ee('visitor:Settings')->settings;
    }

    // ********************************************************************************* //

    //abstract public function execute();

    // ********************************************************************************* //

}

/* End of file AbstractHook.php */
/* Location: ./system/user/addons/Visitor/Hook/AbstractHook.php */