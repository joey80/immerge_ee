<?php

namespace DevDemon\Visitor\Service;

class Helper
{
    protected $package_name = VISITOR_CLASS_NAME;
    protected $package_version = VISITOR_VERSION;
    protected $actionUrlCache = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->site_id = ee()->config->item('site_id');
    }

    public function getRouterUrl($type='url', $method='actionGeneralRouter')
    {
        // -----------------------------------------
        // Grab action_id
        // -----------------------------------------
        if (isset($this->actionUrlCache[$method]['action_id']) === false) {
            $action = ee('Model')->get('Action')
            ->filter('class', ucfirst($this->package_name))
            ->filter('method', $method)
            ->fields('action_id')
            ->first();

            if (!$action) {
                return false;
            }

            $action_id = $action->action_id;
        } else {
            $action_id = $this->actionUrlCache[$method]['action_id'];
        }

        // -----------------------------------------
        // Return FULL action URL
        // -----------------------------------------
        if ($type == 'url') {
            // Grab Site URL
            $url = ee()->functions->fetch_site_index(0, 0);

            if (defined('MASKED_CP') == false OR MASKED_CP == false) {
                // Replace site url domain with current working domain
                $server_host = (isset($_SERVER['HTTP_HOST']) == true && $_SERVER['HTTP_HOST'] != false) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
                $url = preg_replace('#http\://(([\w][\w\-\.]*)\.)?([\w][\w\-]+)(\.([\w][\w\.]*))?\/#', "http://{$server_host}/", $url);
            }

            // Create new URL
            $ajax_url = $url.QUERY_MARKER.'ACT=' . $action_id;

            // Config Override for action URLs?
            $config = ee()->config->item($this->package_name);
            $over = isset($config['action_url']) ? $config['action_url'] : array();

            if (is_array($over) === true && isset($over[$method]) === true) {
                $url = $over[$method];
            }

            // Protocol Relative URL
            $ajax_url = str_replace(array('https://', 'http://'), '//', $ajax_url);

            return $ajax_url;
        }

        return $action_id;
    }

    public function getThemeUrl($root=false)
    {
        if (defined('URL_THIRD_THEMES') === true) {
            $theme_url = URL_THIRD_THEMES;
        } else {
            $theme_url = ee()->config->slash_item('theme_folder_url').'third_party/';
        }

        $theme_url = str_replace(array('http://','https://'), '//', $theme_url);

        if ($root) return $theme_url;

        $theme_url .= $this->package_name . '/';

        return $theme_url;
    }

    public function encryptString($string)
    {
        ee()->load->library('encrypt');

        $key = '0XOyqwQ6Nli1iPIKgR7Bx9YZOQ30HCEWWGDUhPbRAmaVa9m7H9GNHusX8A9191t7';
        if (ee()->config->item('encryption_key')) $key = ee()->config->item('encryption_key');

        $string = ee()->encrypt->encode($string, substr(sha1(base64_encode($key)),0, 56));

        return $string;
    }

    public function decodeString($string)
    {
        ee()->load->library('encrypt');

        $key = '0XOyqwQ6Nli1iPIKgR7Bx9YZOQ30HCEWWGDUhPbRAmaVa9m7H9GNHusX8A9191t7';
        if (ee()->config->item('encryption_key')) $key = ee()->config->item('encryption_key');

        $string = ee()->encrypt->decode($string, substr(sha1(base64_encode($key)),0, 56));

        return $string;
    }

    /**
     * Just like trim, but also removes non-breaking spaces
     *
     * @param string $string The string to trim
     * @return string The trimmed string
     */
    public function trimNbs($string)
    {
        $string = trim($string);
        return trim($string, " \t\n\r\0\xB\xA0".chr(0xC2).chr(0xA0));
    }

    public function studlyCase($str)
    {
        $str = ucwords(str_replace(array('-', '_'), ' ', $str));
        return str_replace(' ', '', $str);
    }

    public function mcpAssets($type, $name=null, $dir=null, $addon=false)
    {
        $ajaxUrl  = $this->getRouterUrl('url');
        $mcpAjaxUrl  = ee('CP/URL', 'addons/settings/' . $this->package_name . '/ajax')->setQueryStringVariable('site_id', $this->site_id)->compile();
        $themeUrl = $this->getThemeUrl();
        $addon = $this->package_name ?: 'devdemon';

        // -----------------------------------------
        // CSS
        // -----------------------------------------
        if ($type == 'css' && !ee()->session->cache($addon, $name)) {
            $url = $dir ? "{$themeUrl}{$dir}/{$name}" : "{$themeUrl}css/{$name}";
            ee()->cp->add_to_head('<link rel="stylesheet" href="' . $url . '?v='.$this->package_version.'" type="text/css" media="print, projection, screen" />');
            ee()->session->set_cache($addon, $name, 'yes');
        }

        // -----------------------------------------
        // Javascript
        // -----------------------------------------
        if ($type == 'js' && !ee()->session->cache($addon, $name)) {
            $url = $dir ? "{$themeUrl}{$dir}/{$name}" : "{$themeUrl}js/{$name}";
            ee()->cp->add_to_foot('<script src="' . $url . '?v='.$this->package_version.'" type="text/javascript"></script>');
            ee()->session->set_cache($addon, $name, 'yes');
        }

        // -----------------------------------------
        // Global Inline Javascript
        // -----------------------------------------
        if ($type == 'gjs') {
            if (!ee()->session->cache($addon, 'gjs.' . $this->package_name)) {

                $js = " var VISITOR = VISITOR ? VISITOR : {};
                        VISITOR.AJAX_URL = '{$ajaxUrl}&site_id={$this->site_id}';
                        VISITOR.MCP_AJAX_URL = '{$mcpAjaxUrl}';
                        VISITOR.THEME_URL = '{$themeUrl}';
                        VISITOR.site_id = '{$this->site_id}';
                ";

                ee()->cp->add_to_foot('<script type="text/javascript">' . $js . '</script>');
                ee()->session->set_cache($addon, 'gjs.' . $this->package_name, 'yes');
            }
        }
    }

}

/* End of file Helper.php */
/* Location: ./system/user/addons/visitor/Service/Helper.php */