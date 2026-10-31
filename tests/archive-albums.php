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
 archive_check(get_post_meta($archive,'archive_only',true)==='1','ACF true stored as 1');
 archive_check(in_array(get_post_meta($normal,'archive_only',true),array('0',''),true),'ACF false stored as 0 or empty string');
 archive_check(!metadata_exists('post',$legacy,'archive_only'),'legacy album has no archive meta row');
 echo 'INFO update_field(false) stored as '.wp_json_encode(get_post_meta($normal,'archive_only',true))."\n";
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
 // Exercise the real template query, not a copied approximation of its meta_query.
 $release_result=static function(){
  $locale='ja';ob_start();include get_template_directory().'/front-page.php';$html=ob_get_clean();
  preg_match('~<section class="sec_info def_sec sec_latest_release">.*?</section>~s',$html,$match);
  return array($match[0]??'',array_map('intval',wp_list_pluck($release_query->posts,'ID')),$release_query->request,$release_query->get('posts_per_page'));
 };
 $limited=$release_result();
 archive_check($limited[1]===array($normal)&&$limited[3]===1&&strpos($limited[2],'LIMIT 0, 1')!==false,'query limits eligible normal candidates to one');
 foreach(array('0','') as $false_value){
  update_field('field_archive_test_flag',$false_value,$normal);
  acf_flush_value_cache($normal,'archive_only');
  archive_check(get_post_meta($normal,'archive_only',true)===$false_value&&!tuougm_get_album_data($normal)['archive_only'],'false scalar save matches formatted ACF value');
  archive_check($release_result()[1]===array($normal),'query includes false storage '.wp_json_encode($false_value));
 }
 wp_update_post(array('ID'=>$legacy,'menu_order'=>-100001));
 archive_check($release_result()[1]===array($legacy),'query includes missing archive flag and honors menu_order');
 wp_update_post(array('ID'=>$legacy,'menu_order'=>0));
 update_field('field_archive_test_flag',false,$restricted);
 acf_flush_value_cache($restricted,'archive_only');
 wp_update_post(array('ID'=>$restricted,'menu_order'=>-100002));
 archive_check($release_result()[1]===array($normal),'required_level still excludes earlier restricted normal');
 $tied=archive_fixture('music_album','ArchiveTest Tied',-99998);
 update_post_meta($tied,'album_tracks',array($track));
 archive_check($release_result()[1]===array($tied),'ID DESC breaks equal menu_order ties');
 update_post_meta($tied,'required_level',1);
 archive_check($release_result()[1]===array($normal),'required_level positive excludes latest tied normal');
 update_post_meta($normal,'required_level',0);
 archive_check($release_result()[1]===array($normal),'required_level explicit zero remains eligible');
 foreach(array($normal,$legacy) as $id)update_post_meta($id,'required_level',1);
 $none=$release_result();
 archive_check($none[1]===array()&&$none[0]==='','no release when only archive or restricted candidates remain');
 update_post_meta($normal,'required_level',0);
 // Restore the restricted archive for the existing member/API test below.
 update_field('field_archive_test_flag',true,$restricted);
 acf_flush_value_cache($restricted,'archive_only');
 wp_set_current_user(1);
 $filter=static function($value,$user_id,$key){return $key==='membership_level'?array(2):$value;};add_filter('get_user_metadata',$filter,10,3);
 $response=$server->dispatch(new WP_REST_Request('GET','/tuougm/v1/player'));
 archive_check(in_array((string)$restricted,array_column($response->get_data()['albums'],'id'),true),'authorized member receives restricted archive');
 remove_filter('get_user_metadata',$filter,10);
} finally { $wpdb->query('ROLLBACK');wp_cache_flush(); }
