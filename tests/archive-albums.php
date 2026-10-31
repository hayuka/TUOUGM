<?php
/** Development-only transactional fixtures. No production ACF/DB configuration changes. */
$_SERVER['HTTP_HOST'] = 'localhost:8088';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
require getenv('TUOUGM_WP_LOAD') ?: '/var/www/html/wp-load.php';
global $wpdb;
$engines = $wpdb->get_col($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE %s', $wpdb->esc_like($wpdb->prefix).'%'));
if (!$engines || array_filter($engines, static function($engine){return strtoupper($engine)!=='INNODB';})) throw new Exception('Use an InnoDB development database.');
if ($wpdb->query('START TRANSACTION')===false) throw new Exception('Cannot start test transaction.');
function archive_check($condition,$name){if(!$condition)throw new Exception($name);echo "PASS $name\n";}
function archive_fixture($type,$title,$order=0,$status='publish') {return wp_insert_post(array('post_type'=>$type,'post_title'=>$title,'post_status'=>$status,'menu_order'=>$order));}
try {
 // Request-local field definitions model the confirmed Text/True-False types.
 acf_add_local_field_group(array('key'=>'group_archive_test','title'=>'Archive test','fields'=>array(
  array('key'=>'field_archive_test_artist','name'=>'artist_name','type'=>'text'),
  array('key'=>'field_archive_test_flag','name'=>'archive_only','type'=>'true_false'),
 ),'location'=>array(array(array('param'=>'post_type','operator'=>'==','value'=>'music_album')))));
 $track=archive_fixture('music_track','ArchiveTest Track');update_post_meta($track,'track_audio','https://example.invalid/archive-test.mp3');
 $normal=archive_fixture('music_album','ArchiveTest Normal',-99998);
 $archive=archive_fixture('music_album','ArchiveTest Hidden',-99999);
 $restricted=archive_fixture('music_album','ArchiveTest Restricted',-99997);
 $legacy=archive_fixture('music_album','ArchiveTest Legacy');
 foreach(array($normal,$archive,$restricted,$legacy) as $id)update_post_meta($id,'album_tracks',array($track));
 update_field('field_archive_test_flag',true,$archive);update_field('field_archive_test_flag',true,$restricted);
 update_field('field_archive_test_flag',false,$normal);update_field('field_archive_test_artist','  LOST FIGHTS  ',$archive);
 update_field('field_archive_test_artist','   ',$normal);update_post_meta($restricted,'required_level',2);
 $a=tuougm_get_album_data($archive);$n=tuougm_get_album_data($normal);$l=tuougm_get_album_data($legacy);
 archive_check($a['archive_only']===true&&$n['archive_only']===false&&$l['archive_only']===false,'true/false/unset JSON boolean contract');
 archive_check(strpos(wp_json_encode($a),'"archive_only":true')!==false,'JSON boolean is not string');
 archive_check($a['artist_name']==='LOST FIGHTS','artist trimmed');
 archive_check($n['artist_name']==='THE ULTIMATE SIN OF UNBUILT GUNPLA MOUNTAIN'&&$l['artist_name']===$n['artist_name'],'blank/unset artist fallback');
 wp_set_current_user(0);$server=rest_get_server();
 $request=new WP_REST_Request('GET','/tuougm/v1/player');$response=$server->dispatch($request);$data=$response->get_data();
 $ids=array_column($data['albums'],'id');
 archive_check(in_array((string)$archive,$ids,true)&&in_array((string)$normal,$ids,true),'API includes both authorized types');
 archive_check(!in_array((string)$restricted,$ids,true),'anonymous restricted archive denied');
 archive_check(strpos($response->get_headers()['Cache-Control'],'no-store')!==false,'private no-store preserved');
 archive_check(strpos(do_shortcode('[tuougm_track id="'.$track.'"]'),'data-player-track="'.$track.'"')!==false,'existing article shortcode generates archive trigger');
 // Render actual templates. Their header/footer may only load once in this CLI request.
 $GLOBALS['wp_query']=new WP_Query(array('post_type'=>'page','posts_per_page'=>1));
 $GLOBALS['post']=get_post($track);setup_postdata($GLOBALS['post']);
 $locale='ja';$is_english=false;
 ob_start();include get_template_directory().'/page-music.php';$music=ob_get_clean();
 archive_check(strpos($music,'data-detail-album="'.$archive.'"')===false&&strpos($music,'data-detail-album="'.$restricted.'"')===false,'MUSIC omits archives');
 archive_check(strpos($music,'data-detail-album="'.$normal.'"')!==false,'MUSIC retains normal album');
 ob_start();include get_template_directory().'/front-page.php';$home=ob_get_clean();
 archive_check(preg_match('~<section class="sec_info def_sec sec_latest_release">.*?</section>~s',$home,$section)===1,'LATEST RELEASE exists');
 archive_check(strpos($section[0],'ArchiveTest Normal')!==false&&strpos($section[0],'ArchiveTest Hidden')===false,'LATEST RELEASE skips leading archive and finds normal');
 wp_set_current_user(1);
 $filter=static function($value,$user_id,$key){return $key==='membership_level'?array(2):$value;};add_filter('get_user_metadata',$filter,10,3);
 $response=$server->dispatch(new WP_REST_Request('GET','/tuougm/v1/player'));
 archive_check(in_array((string)$restricted,array_column($response->get_data()['albums'],'id'),true),'authorized member receives restricted archive');
 remove_filter('get_user_metadata',$filter,10);
} finally { $wpdb->query('ROLLBACK');wp_cache_flush(); }
