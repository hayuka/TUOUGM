<?php
$_SERVER['HTTP_HOST']='localhost:8088'; $_SERVER['SERVER_NAME']='localhost'; $_SERVER['REQUEST_METHOD']='GET';
require getenv('TUOUGM_WP_LOAD') ?: '/var/www/html/wp-load.php';
// Disposable fixtures are rolled back; run only against a development database.
global $wpdb;
$engines=$wpdb->get_col($wpdb->prepare(
 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE %s',
 $wpdb->esc_like($wpdb->prefix).'%' ));
if (!$engines || array_filter($engines,static function($engine){return strtoupper($engine)!=='INNODB';})) {
 throw new Exception('Run fixtures only on a transactional InnoDB development database.');
}
if ($wpdb->query('START TRANSACTION')===false) throw new Exception('Cannot start fixture transaction.');
function check_detail($ok,$label) { if(!$ok) throw new Exception($label); echo "PASS $label\n"; }
function fixture($type,$title,$status='publish',$password='') { return wp_insert_post(['post_type'=>$type,'post_title'=>$title,'post_status'=>$status,'post_password'=>$password]); }
try {
 $fixture_track=fixture('music_track','Phase2 fixture track');
 $draft=fixture('music_track','Phase2 draft','draft');
 $locked=fixture('music_track','Phase2 password','publish','secret-fixture');
 $a=fixture('music_album','Phase2 album A'); $b=fixture('music_album','Phase2 album B'); $private=fixture('music_album','Phase2 restricted');
 foreach([$a,$b,$private] as $id){ update_post_meta($id,'album_tracks',[$fixture_track,$draft,$locked]); update_post_meta($id,'required_level',$id===$private?2:0); }
 update_post_meta($fixture_track,'track_title_en','English fixture');
 update_post_meta($fixture_track,'track_description_ja','日本語説明'); update_post_meta($fixture_track,'track_description_en','English description');
 wp_set_current_user(0);
 check_detail(!is_wp_error(tuougm_music_track_detail($fixture_track,$a,'ja')),'public detail');
 check_detail(tuougm_music_track_detail($fixture_track,$b,'ja')['album']['id']==(string)$b,'clicked album wins');
 check_detail(tuougm_music_track_detail($fixture_track,$a,'en_US')['title']==='English fixture','English fields');
 check_detail(is_wp_error(tuougm_music_track_detail($fixture_track,$private,'ja')),'anonymous restricted denied');
 check_detail(is_wp_error(tuougm_music_track_detail($draft,$a,'ja')),'draft denied');
 check_detail(is_wp_error(tuougm_music_track_detail($locked,$a,'ja')),'password track denied');
 check_detail(is_wp_error(tuougm_music_track_detail($fixture_track,$fixture_track,'ja')),'wrong album type denied');
 $draft_album=fixture('music_album','Phase2 draft album','draft');
 $password_album=fixture('music_album','Phase2 password album','publish','fixture-only');
 foreach([$draft_album,$password_album] as $id) update_post_meta($id,'album_tracks',[$fixture_track]);
 check_detail(is_wp_error(tuougm_music_track_detail($fixture_track,$draft_album,'ja')),'draft album denied');
 check_detail(is_wp_error(tuougm_music_track_detail($fixture_track,$password_album,'ja')),'password album denied');

 $other=fixture('music_track','Phase2 unrelated');
 check_detail(is_wp_error(tuougm_music_track_detail($other,$a,'ja')),'nonmember track denied');
 foreach(['ja','en_US'] as $locale){
  $_GET=$locale==='en_US'?['loc'=>'en_US']:[];
  $GLOBALS['wp_query']=new WP_Query(['post_type'=>'music_track','p'=>$fixture_track]);
  $GLOBALS['post']=get_post($fixture_track);
  setup_postdata($GLOBALS['post']);
  $baseline=getenv('TUOUGM_BASELINE_SINGLE') ?: '/tmp/tuougm-original-single.php';
  if (!is_readable($baseline)) throw new Exception('Export main single-music_track.php to TUOUGM_BASELINE_SINGLE before testing.');
  ob_start();load_template(get_template_directory().'/header.php',false);include $baseline;$old=ob_get_clean();
  ob_start();load_template(get_template_directory().'/header.php',false);include get_template_directory().'/single-music_track.php';$new=ob_get_clean();
  preg_match('~<main\b.*?</main>~s',$old,$om);preg_match('~<main\b.*?</main>~s',$new,$nm);
  $normalize=static function($s){return preg_replace('/>\s+</','><',trim($s));};
  check_detail(isset($om[0],$nm[0])&&$normalize($om[0])===$normalize($nm[0]),'standalone main HTML unchanged '.$locale);
  preg_match_all('~<title>.*?</title>|<link rel="canonical"[^>]*>|<meta property="og:[^>]*>~s',$old,$old_head);
  preg_match_all('~<title>.*?</title>|<link rel="canonical"[^>]*>|<meta property="og:[^>]*>~s',$new,$new_head);
  check_detail($old_head[0]===$new_head[0],'standalone title canonical OGP unchanged '.$locale);
 }
 $server=rest_get_server();
 foreach([[$fixture_track,$a,'ja',200],[$fixture_track,$private,'ja',404],[$other,$a,'ja',404],[$draft,$a,'ja',404],[$fixture_track,$a,'bad',400]] as [$t,$al,$loc,$status]){
  $req=new WP_REST_Request('GET','/tuougm/v1/track-detail');$req->set_query_params(['track_id'=>$t,'album_id'=>$al,'locale'=>$loc]);
  $r=$server->dispatch($req);check_detail($r->get_status()===$status,'REST status '.$status.' '.$loc);
  if($status===200){$d=$r->get_data();check_detail(strpos($d['html'],'music-modal-track-title')!==false,'modal IDs');check_detail(strpos($d['html'],'<script')===false,'no scripts in fragment');check_detail(!isset($d['src'])&&!isset($d['track']),'no audio data in detail response');check_detail(strpos($r->get_headers()['Cache-Control'],'no-store')!==false,'private response');}
 }
 wp_set_current_user(1);
 $level_filter=static function($v,$id,$key){return $key==='membership_level'?[2]:$v;};
 add_filter('get_user_metadata',$level_filter,10,3);
 check_detail(!is_wp_error(tuougm_music_track_detail($fixture_track,$private,'ja')),'member access uses existing level');
 $detail=tuougm_music_track_detail($fixture_track,$private,'ja');
 $catalog=array_filter(tuougm_player_catalog(),static function($album){return (int)$album['required_level']<=tuougm_player_access_level();});
 check_detail(count(array_filter($catalog,static function($album)use($private){return $album['id']==(string)$private;}))===1,'detail and player catalog share album access');
 // Export real server-rendered fragments for browser transport fixtures, not live DB rows.
 wp_set_current_user(0);
 $fixtures=[];
 foreach ([$a,$b] as $al) {
  $ids=[$fixture_track];
  if ($al===$a) {
   $ids=[];
   foreach (['Third','First','Second'] as $name) {
    $id=fixture('music_track','Phase2 '.$name);
    update_post_meta($id,'track_description_ja',str_repeat('日本語説明。',100));
    update_post_meta($id,'track_description_en',str_repeat('English description. ',100));
    update_post_meta($id,'track_title_en',$name);
    $ids[]=$id;
   }
   update_post_meta($al,'album_tracks',$ids);
   acf_flush_value_cache($al,'album_tracks');
  }
  foreach ($ids as $id) {
   $request=new WP_REST_Request('GET','/tuougm/v1/track-detail');
   $request->set_query_params(['track_id'=>$id,'album_id'=>$al,'locale'=>'en_US']);
   $response=$server->dispatch($request);
   check_detail($response->get_status()===200,'export valid UI fragment');
   $fixtures[]=$response->get_data();
  }
 }
 if (getenv('TUOUGM_UI_FIXTURES')) file_put_contents(getenv('TUOUGM_UI_FIXTURES'),wp_json_encode($fixtures));
} finally { $wpdb->query('ROLLBACK'); wp_cache_flush(); }
