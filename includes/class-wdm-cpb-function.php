<?php

namespace cpbFunctions;

if (! class_exists('WdmCpbFunctions')) {
    class WdmCpbFunctions
    {
        public function cpbGetTemplate($file, $data, $empty, $path)
        {
            return wc_get_template($file, $data, $empty, $path);
        }

        protected function allowPrefillProducts()
        {
            $allowPrefillProducts = get_post_meta(get_the_ID(), 'wdm_prefilled_box', true);
            if ($allowPrefillProducts == 'yes') {
                return true;
            }
            return false;
        }
    }
}
