<?php

namespace DevDemon\Visitor\Tag;

/**
 * Abstract Tag Class
 *
 * @package         DevDemon_Visitor
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 */
abstract class AbstractTag
{
    protected $tagdata;
    protected $params;
    protected $member_id;
    protected $site_id;
    protected $settings = array();
    protected $prefix = 'visitor:';

    public function __construct($tagdata, $params)
    {
        $this->tagdata = $tagdata;
        $this->params = $params ?: array();
        $this->member_id = ee()->session->userdata('member_id');
        $this->site_id = ee()->config->item('site_id');
        $this->settings = ee('visitor:Settings')->settings;

        if (isset($this->params['prefix']) === true) {
            if ($this->params['prefix'] === '') {
                $this->prefix = '';
            } else {
                $this->prefix = $this->params['prefix'] . ':';
            }
        }
    }

    // ********************************************************************************* //

    abstract public function parse();

    // ********************************************************************************* //

    public function param($key, $default=false)
    {
        if (isset($this->params[$key])) {

            // Consistent yes/no parameters
            switch ($this->params[$key]) {
                case 'yes':
                case 'y':
                case 'on':
                    return 'yes';
                break;

                case 'no':
                case 'n':
                case 'off':
                    return 'no';
                break;
            }

            return $this->params[$key];
        }

        // Do we have a default value?
        if ($default) {
            return $default;
        }

        // Not set
        return false;
    }

    // ********************************************************************************* //

    public function log($msg)
    {
        ee()->TMPL->log_item('SUBS: ' . $msg);
    }

    // ********************************************************************************* //

    /**
     * Custom No_Result conditional
     *
     * Same as {if no_result} but with your own conditional.
     *
     * @param string $cond_name
     * @param string $source
     * @param string $return_source
     * @return unknown
     */
    public function noResultsConditional($cond_name, $source=false, $return_source=false)
    {
        $cond_name = $this->prefix . $cond_name;
        if ($source === false) $source = $this->tagdata;

        if (strpos($source, LD."if {$cond_name}".RD) !== false)
        {
            if (preg_match('/'.LD."if {$cond_name}".RD.'(.*?)'.LD.'\/'.'if'.RD.'/s', $source, $cond))
            {
                return $cond[1];
            }

        }

        if ($return_source !== false) {
            return $source;
        }
    }

    // ********************************************************************************* //

    /**
     * Fetch data between var pairs
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $source - Source
     * @return string
     */
    public function fetchVarPairData($varname='', $source = '')
    {
        if ( ! preg_match('/'.LD.($varname).RD.'(.*?)'.LD.'\/'.$varname.RD.'/s', $source, $match))
               return;

        return $match['1'];
    }

    // ********************************************************************************* //

    /**
     * Fetch data between var pairs
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $source - Source
     * @return string
     */
    public function fetchVarPairDataAll($varname='', $source = '')
    {
        if ( ! preg_match_all('/'.LD.($varname).RD.'(.*?)'.LD.'\/'.$varname.RD.'/s', $source, $matches))
               return;

        return $matches;
    }

    // ********************************************************************************* //

    /**
     * Fetch data between var pairs (including optional parameters)
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $source - Source
     * @return string
     */
    public function fetchVarPairDataWithParams($open='', $close='', $source = '')
    {
        if ( ! preg_match('/'.LD.preg_quote($open).'.*?'.RD.'(.*?)'.LD.'\/'.$close.RD.'/s', $source, $match))
               return;

        return $match['1'];
    }

    // ********************************************************************************* //

    /**
     * Replace var_pair with final value
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $replacement - Replacement
     * @param string $source - Source
     * @return string
     */
    public function swapVarPair($varname = '', $replacement = '\\1', $source = '')
    {
        $replacement = str_replace('$', '\\$', $replacement);
        return preg_replace("/".LD.$varname.RD."(.*?)".LD.'\/'.$varname.RD."/s", $replacement, $source);
    }

    // ********************************************************************************* //

    /**
     * Replace var_pair with final value (including optional parameters)
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $replacement - Replacement
     * @param string $source - Source
     * @return string
     */
    public function swapVarPairWithParams($open = '', $close = '', $replacement = '\\1', $source = '')
    {
        $replacement = str_replace('$', '\\$', $replacement);
        return preg_replace("/".LD.preg_quote($open).RD."(.*?)".LD.'\/'.$close.RD."/s", $replacement, $source);
    }

    // ********************************************************************************* //

    protected function parseCaptcha($reg_form)
    {
        //parse the captcha
        if (preg_match("/{if captcha}(.+?){\/if}/s", $reg_form, $match)) {
            if (ee()->config->item('use_membership_captcha') == 'y') {
                $reg_form = preg_replace("/{if captcha}.+?{\/if}/s", $match['1'], $reg_form);

                // Bug fix.  Deprecate this later..
                $reg_form = str_replace('{captcha_word}', '', $reg_form);

                if (!class_exists('Template')) {
                    $reg_form = preg_replace("/{captcha}/", ee()->functions->create_captcha(), $reg_form);
                }
            }
            else {
                $reg_form = preg_replace("/{if captcha}.+?{\/if}/s", "", $reg_form);
            }
        }

        return $reg_form;
    }

    // ********************************************************************************* //

    /**
     * Create the "year" pull-down menu
     */
    protected function birthdayYear($year = '')
    {
        $r = "<select name='bday_y' class='select'>\n";

        $selected = ($year == '') ? " selected='selected'" : '';

        $r .= "<option value=''{$selected}>" . ee()->lang->line('year') . "</option>\n";

        for ($i = date('Y', ee()->localize->now); $i > 1904; $i--) {
            $selected = ($year == $i) ? " selected='selected'" : '';

            $r .= "<option value='{$i}'{$selected}>" . $i . "</option>\n";
        }

        $r .= "</select>\n";

        return $r;
    }

    // ********************************************************************************* //

    /**
     * Create the "month" pull-down menu
     */
    protected function birthdayMonth($month = '')
    {
        $months = array('01' => 'January',
                        '02' => 'February',
                        '03' => 'March',
                        '04' => 'April',
                        '05' => 'May',
                        '06' => 'June',
                        '07' => 'July',
                        '08' => 'August',
                        '09' => 'September',
                        '10' => 'October',
                        '11' => 'November',
                        '12' => 'December');

        $r = "<select name='bday_m' class='select'>\n";

        $selected = ($month == '') ? " selected='selected'" : '';

        $r .= "<option value=''{$selected}>" . ee()->lang->line('month') . "</option>\n";

        for ($i = 1; $i < 13; $i++) {
            if (strlen($i) == 1)
                $i = '0' . $i;

            $selected = ($month == $i) ? " selected='selected'" : '';

            $r .= "<option value='{$i}'{$selected}>" . ee()->lang->line($months[$i]) . "</option>\n";
        }

        $r .= "</select>\n";

        return $r;
    }

    // ********************************************************************************* //

    /**
     * Create the "day" pull-down menu
     */
    protected function birthdayDay($day = '')
    {
        $r = "<select name='bday_d' class='select'>\n";

        $selected = ($day == '') ? " selected='selected'" : '';

        $r .= "<option value=''{$selected}>" . ee()->lang->line('day') . "</option>\n";

        for ($i = 1; $i <= 31; $i++) {
            $selected = ($day == $i) ? " selected='selected'" : '';

            $r .= "<option value='{$i}'{$selected}>" . $i . "</option>\n";
        }

        $r .= "</select>\n";

        return $r;
    }

    // ********************************************************************************* //

    protected function getChannelFormVars()
    {
        $vars = array();
        $vars['error:username']         = '';
        $vars['error:screen_name']      = '';
        $vars['error:email']            = '';
        $vars['error:email_confirm']    = '';
        $vars['error:password']         = '';
        $vars['error:current_password'] = '';
        $vars['error:captcha']          = '';
        $vars['error:accept_terms']     = '';

        return $vars;
    }

    protected function getChannelFormParams()
    {

        //param name / default value / include even if not provided

        $params   = array();
        $params[] = array('include_jquery', 'yes', true);
        $params[] = array('include_assets', 'yes', true);
        $params[] = array('preserve_checkboxes', 'yes', true);
        $params[] = array('json', 'yes', false);
        $params[] = array('datepicker', 'no', false);
        $params[] = array('secure_action', 'yes', false);
        $params[] = array('secure_return', 'yes', false);
        $params[] = array('error_handling', 'inline', false);
        $params[] = array('return', 'site', false);
        $params[] = array('return_X', 'site/thanks', false);
        $params[] = array('class', 'visitor_form', true);
        $params[] = array('id', 'visitor_form', true);
        $params[] = array('site', 'default_site', false);
        $params[] = array('dynamic_title', '', false);
        $params[] = array('rte_toolset_id', '', false);
        $params[] = array('rte_selector', '', false);

        // Save all used params
        $included_params = array();

        $param_str = '';
        foreach ($params as $param) {

            $include_if_not_provided = $param[2];
            $param_name              = $param[0];
            $param_value             = $param[1];

            $included_params[] = $param_name;

            $fetched_param = ee()->TMPL->fetch_param($param_name);

            //param is not set, see if we need to include it by FORCE!
            if (!$fetched_param && $include_if_not_provided) {
                $param_str .= $param_name . '="' . $param_value . '" ';
            }

            //param is set -> include it
            if ($fetched_param != FALSE) {
                $param_str .= $param_name . '="' . $fetched_param . '" ';
            }
        }

        foreach (ee()->TMPL->tagparams as $key => $value) {
            // If the param was included in the tag make sure it is used
            if (!in_array($key, $included_params)) {
                $param_str .= $key . '="' . $value . '" ';
            }

//          if (preg_match('/^rules:(.+)/', $key, $match)) {
//              $param_str .= " rules:" . $match[1] . '="' . $value . '"';
//          }

        }

        return $param_str;
    }

    // ********************************************************************************* //

}

/* End of file AbstractTag.php */
/* Location: ./system/user/addons/Visitor/Tag/AbstractTag.php */