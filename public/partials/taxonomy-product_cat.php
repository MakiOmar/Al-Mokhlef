<?php
defined('ABSPATH') || die();
get_header();
$mokhlif = new Mokhlef_Public('mokhlef', MOKHLEF_VERSION );
$mokhlif->taxonomy_product_cat_output();
get_footer();
