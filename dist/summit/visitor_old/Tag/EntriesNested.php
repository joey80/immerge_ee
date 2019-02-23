<?php

namespace DevDemon\Visitor\Tag;

class EntriesNested extends AbstractTag
{

    public function parse()
    {
        $tagdata = $this->tagdata;
        $tagdata = str_replace("nested:", "", $tagdata);
        $tagdata = str_replace("/nested:", "/", $tagdata);

        $entries_tag = '{exp:channel:entries ';

        foreach ($this->params as $key => $value) {
            $entries_tag .= $key . '="' . $value . '" ';
        }

        $entries_tag .= '}' . $tagdata . '{/exp:channel:entries}';

        return $entries_tag;
    }
}

/* End of file EntriesNested.php */
/* Location: ./system/user/addons/Visitor/Tag/EntriesNested.php */