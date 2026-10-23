<?php
/**
 * Public membership upgrades have been retired.
 * Keep existing links safe without processing GET or POST upgrade requests.
 */
wp_safe_redirect( home_url( '/members/' ), 303 );
exit;
