<?php
$loc = $_GET['loc'];
if($loc){
   $locale = 'en_US';
}else{
   $locale = get_locale();
}
/**
 * The template for displaying Archive pages
 */
get_header(); 
get_template_part(
    'story'
);
get_footer();?>