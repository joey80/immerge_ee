<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class VisitorUpdate_30000
{
    public function update()
    {
        // Action: actionGeneralRouter
        $action = ee('Model')->get('Action')->filter('class', 'Visitor')->filter('method', 'actionGeneralRouter')->first();
        if (!$action) {
            $action = ee('Model')->make('Action');
            $action->class = 'Visitor';
            $action->method = 'actionGeneralRouter';
            $action->csrf_exempt = 0;
            $action->save();
        }

        if (ee()->db->table_exists('zoo_visitor_settings')) {
            $sites = array();
            $settings = ee()->db->select('*')->from('zoo_visitor_settings')->get();

            foreach ($settings->result() as $val) {
                if (!$val->site_id) continue;
                $sites[$val->site_id][$val->var] = $val->var_value;
            }

            // Loop over all sites and merge & save the settings
            foreach ($sites as $site_id => $siteSettings) {
                $settings = ee('visitor:Settings')->getModuleSettings($site_id);
                $settings = array_merge($settings, $siteSettings);
                ee('visitor:Settings')->saveModuleSettings($settings, $site_id);
            }
        }

        // Rename all zoo visitor fields to visitor
        $fields = ee('Model')->get('ChannelField')->filter('field_type', 'zoo_visitor')->all();
        foreach ($fields as $field) {
            $field->field_type = 'visitor';
            $field->save();
        }
    }
}

/* End of file 3_00_00.php */
/* Location: ./system/user/addons/visitor/Updates/3_00_00.php */