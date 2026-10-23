<?php
/*---------------------------------------------
 * アイキャッチ使用を宣言
 *--------------------------------------------*/
add_theme_support('post-thumbnails');
add_image_size('newthumb', 600, 400, true);

add_filter('pvc_post_views_display', '__return_false');

/*---------------------------------------------
 * 管理画面禁止
 *--------------------------------------------*/
add_action('admin_init', function () {
    if (
        current_user_can('subscriber') &&
        !wp_doing_ajax()
    ) {
        wp_redirect(home_url('/members/'));
        exit;
    }
});

/*---------------------------------------------
 * 固定ページビジュアルエディタ禁止
 *--------------------------------------------*/
function disable_visual_editor() {
	global $typenow;
	if ($typenow == 'page') {
		add_filter('user_can_richedit', 'disable_visual_editor_filter');
	}
}
function disable_visual_editor_filter() {
	return false;
}
add_action('load-post.php', 'disable_visual_editor');
add_action('load-post-new.php', 'disable_visual_editor');

/*---------------------------------------------
 * ウィジェット
 *--------------------------------------------*/
if(function_exists('register_sidebar')){
    register_sidebar();
}
add_filter( 'widget_title', 'remove_widget_title_all' );
function remove_widget_title_all( $widget_title ) {
    return;
}

add_filter( 'bogo_use_flags','bogo_use_flags_false');
function bogo_use_flags_false(){
 return false;
}

add_filter('bogo_language_switcher_links', 'bogo_language_link_text_change');
function bogo_language_link_text_change ($links) {
    foreach ($links as $key => $value) {
        if('ja' === $value['locale']){
            $links[$key]['title'] = 'JP';
            $links[$key]['native_name'] = 'JP';
        }
        if('en_US' === $value['locale']){
            $links[$key]['title'] = 'EN';
            $links[$key]['native_name'] = 'EN';
        }
    }
    return $links;
}

/*---------------------------------------------
 * 余計なcssを削除
 *--------------------------------------------*/
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_mail_emoji' );

add_action('wp_enqueue_scripts', function() {

    // 管理画面は除外
    if (is_admin()) return;

    // Gutenberg関連CSS削除
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');

    // Global Styles削除（念のため）
    wp_dequeue_style('global-styles');

}, 100);


// global-styles-inline-css を完全停止
add_filter('wp_enqueue_global_styles', '__return_false');


// 念のための保険（しつこい環境用）
remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
remove_action('wp_footer', 'wp_enqueue_global_styles', 1);


// embed系も不要なら削除
add_action('wp_enqueue_scripts', function() {

    if (is_admin()) return;

    wp_deregister_script('wp-embed');

}, 100);


/*---------------------------------------------
 * ツールバー非表示
 *--------------------------------------------*/
add_filter('show_admin_bar', '__return_false');

/*---------------------------------------------
 * ショートコード [url] [tmp_dir] [get_tmp] [title]
 *--------------------------------------------*/
add_filter('widget_text', 'do_shortcode' ); 
 
function shortcode_url() {
	return get_bloginfo('url');
}
add_shortcode('url', 'shortcode_url');

function shortcode_title() {
	return get_the_title();
}
add_shortcode('title', 'shortcode_title');

function shortcode_template_directory() {
	return get_template_directory_uri();
}
add_shortcode('tmp_dir', 'shortcode_template_directory');

function shortcode_get_template_part($atts) {
	extract(shortcode_atts(array(
	"slug" => 'templates/part',
	"name" => ''
			), $atts));
			ob_start();
			get_template_part($slug,$name);
			return ob_get_clean();
}
add_shortcode('get_tmp', 'shortcode_get_template_part'); // 例 [get_tmp slug="templates/part" name="breadcrumb"]

function clearboth() {
    return "<br class='clear'>";
}
add_shortcode('clear', 'clearboth');

function subFunc( $atts, $content = null ) {
    return '<div class="detail-sub">' . $content . '</div>';
}
add_shortcode('sub', 'subFunc');

function blockFunc( $atts, $content = null ) {
	extract( shortcode_atts( array(
        'ttl'     => '',
        'url'   => '',
    ), $atts ) );
    return '<blockquote>' . $content . '</blockquote><cite><a href="'.$url.'" target="_blank">'.$ttl.'</a></cite>';
}
add_shortcode('block', 'blockFunc');

/*---------------------------------------------
 * ページネーション
 *--------------------------------------------*/

function pagination($pages = '', $range = 4){
     $showitems = ($range * 2)+1;  
     global $paged;
     if(empty($paged)) $paged = 1;
     if($pages == ''){
         global $wp_query;
         $pages = $wp_query->max_num_pages;
         if(!$pages){
             $pages = 1;
         }
     }   

     if(1 != $pages){
         echo "<div class=\"pagination flex_wrap_center\">";
		 if($paged > 1) echo "<a href='".get_pagenum_link($paged - 1)."' class='pg_pre'></a>";
         //if($paged > 2 && $paged > $range+1 && $showitems < $pages) echo "<a href='".get_pagenum_link(1)."'>1</a><span>…</span>";
		 
         for ($i=1; $i <= $pages; $i++){
			 if($paged == $pages){
				 $range = 2;
			 }
             if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems )){
                 echo ($paged == $i)? "<span class=\"current\">".$i."</span>":"<a href='".get_pagenum_link($i)."' class=\"inactive\">".$i."</a>";
             }
         }
		 if ($paged < $pages-1 &&  $paged+$range-1 < $pages && $showitems < $pages) echo "<span>…</span><a href='".get_pagenum_link($pages)."'>".$pages."</a>";
         if ($paged < $pages) echo "<a href=\"".get_pagenum_link($paged + 1)."\" class='pg_next'></a>";
         echo "</div>\n";
     }
}

/*---------------------------------------------
 * ビジュアルエディターcss読み込み
 *--------------------------------------------*/ 
add_editor_style('editor-style.css');
 
function custom_editor_settings( $initArray ) {
    $initArray['body_class'] = 'editor-area';
    return $initArray;
}
 
add_filter( 'tiny_mce_before_init', 'custom_editor_settings' );




//★投稿画面のカテゴリー階層を正常表示
function lig_wp_category_terms_checklist_no_top( $args, $post_id = null ) {
	$args['checked_ontop'] = false;
	return $args;
}
add_action( 'wp_terms_checklist_args', 'lig_wp_category_terms_checklist_no_top' );


/*---------------------------------------------
 * カスタム投稿追加
 *--------------------------------------------*/ 

add_filter('register_post_type_args', function($args, $post_type){

    if($post_type === 'post'){

        $args['labels'] = [
            'name'               => 'ガンプラ×メタルプロジェクト最新情報',
            'singular_name'      => '最新情報',
            'add_new'            => '新規追加',
            'add_new_item'       => '新規投稿を追加',
            'edit_item'          => '投稿を編集',
            'new_item'           => '新規投稿',
            'view_item'          => '投稿を見る',
            'search_items'       => '投稿を検索',
            'not_found'          => '投稿が見つかりません',
            'all_items'          => '投稿一覧',
            'menu_name'          => '最新情報',
        ];
		$args['has_archive'] = 'archives';

        $args['rewrite'] = [
            'slug' => 'archives',
            'with_front' => false
        ];

        $args['show_in_rest'] = true;

    }

    return $args;

}, 10, 2);
add_action( 'init', 'create_post_type' );
function create_post_type() {
  register_post_type( 'gallery', /* post-type */
    array(
      'labels' => array(
        'name' => __( 'gallery' ),
        'singular_name' => __( 'gallery' )
      ),
      'public' => true,
      'supports' => array( 'title', 'thumbnail', 'excerpt'),
      'menu_position' =>5,
      'has_archive' => true,
	  'rewrite' => array('with_front' => false),
    )
  );
  register_taxonomy(
    'gallery-cat', 
    'gallery', 
    array(
      'hierarchical' => true, 
      'update_count_callback' => '_update_post_term_count',
      'label' => 'galleryのカテゴリー',
      'singular_label' => 'galleryのカテゴリー',
      'public' => true,
      'show_ui' => true
    )
  );
}

/* =========================================================
 * Custom Post Type : STORY
 *
 * Single:
 * /story/article-slug/
 *
 * Archive:
 * /story/
 * ========================================================= */

function tuougm_register_story_post_type() {

    $labels = array(
        'name'               => 'Story Archives',
        'singular_name'      => 'Story Archive',
        'menu_name'          => 'Story Archives',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Story Archive',
        'edit_item'          => 'Edit Story Archive',
        'new_item'           => 'New Story Archive',
        'view_item'          => 'View Story Archive',
        'search_items'       => 'Search Story Archives',
        'not_found'          => 'No story archives found',
        'all_items'          => 'All Story Archives',
    );

    $args = array(
        'labels'             => $labels,

        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'publicly_queryable' => true,

        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-book-alt',

        'supports'           => array(
            'title',
            'editor',
            'thumbnail',
            'excerpt',
            'author',
            'revisions',
        ),

        'has_archive'        => true,

        /*
         * Individual Story URL
         *
         * /story/article-slug/
         */
        'rewrite' => array(
            'slug'       => 'story',
            'with_front' => false,
        ),
    );

    register_post_type(
        'story',
        $args
    );
}

add_action(
    'init',
    'tuougm_register_story_post_type'
);


/* =========================================================
 * Taxonomy : STORY CATEGORY
 *
 * Hierarchical
 *
 * HISTORY
 * CHARACTERS
 * LOCATION
 *
 * ARCHIVES
 * ├─ MUSIC
 * ├─ INCIDENT
 * ├─ DISCOVERY
 * └─ RECORD
 *
 * URLs:
 *
 * /story/history/
 * /story/characters/
 * /story/location/
 * /story/archives/
 * /story/archives/music/
 * ========================================================= */

function tuougm_register_story_category() {

    $labels = array(
        'name'              => 'Story Categories',
        'singular_name'     => 'Story Category',
        'menu_name'         => 'Story Categories',

        'all_items'         => 'All Categories',
        'parent_item'       => 'Parent Category',
        'parent_item_colon' => 'Parent Category:',
        'edit_item'         => 'Edit Category',
        'update_item'       => 'Update Category',
        'add_new_item'      => 'Add New Category',
        'new_item_name'     => 'New Category Name',
    );

    register_taxonomy(
        'story_category',
        array('story'),
        array(
            'labels'            => $labels,

            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,

            /*
             * Parent / Child
             */
            'hierarchical'      => true,

            /*
             * Category URLs
             *
             * /story/history/
             * /story/archives/music/
             */
            'rewrite' => array(
                'slug'         => 'story',
                'with_front'   => false,
                'hierarchical' => true,
            ),
        )
    );
}

add_action(
    'init',
    'tuougm_register_story_category'
);

function tuougm_story_request_filter($query_vars) {
    if (!isset($query_vars['story_category']) && !isset($query_vars['name'])) {
        return $query_vars;
    }

    // /story/xxx/yyy/ の形式
    if (isset($_SERVER['REQUEST_URI'])) {
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $parts = explode('/', $uri);

        if (count($parts) >= 2 && $parts[0] === 'story') {
            $slug = end($parts);

            // 投稿が存在するか確認
            $post = get_page_by_path($slug, OBJECT, 'story');
            if ($post) {
                // 投稿として処理
                return array('post_type' => 'story', 'name' => $slug);
            }

            // タームとして処理
            $term_slug = implode('/', array_slice($parts, 1));
            $term = get_term_by('slug', $slug, 'story_category');
            if ($term) {
                return array('story_category' => $term_slug);
            }
        }
    }
    return $query_vars;
}
add_filter('request', 'tuougm_story_request_filter');

/* =========================================================
 * Taxonomy : TIMELINE
 *
 * Example:
 *
 * A.D.
 * S.E.
 *
 * Actual chronological order is controlled separately
 * by timeline_order.
 * ========================================================= */

function tuougm_register_story_timeline() {

    $labels = array(
        'name'          => 'Timeline',
        'singular_name' => 'Timeline',
        'menu_name'     => 'Timeline',
    );

    register_taxonomy(
        'story_timeline',
        array('story'),
        array(
            'labels'            => $labels,

            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,

            // カテゴリー型
            'hierarchical'      => true,

            'rewrite' => array(
                'slug'       => 'timeline',
                'with_front' => false,
            ),
        )
    );
}

add_action(
    'init',
    'tuougm_register_story_timeline'
);

function tuougm_register_story_type() {

    $labels = array(
        'name'          => 'type',
        'singular_name' => 'type',
        'menu_name'     => 'type',
    );

    register_taxonomy(
        'story_type',
        array('story'),
        array(
            'labels'            => $labels,

            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,

            // カテゴリー型
            'hierarchical'      => true,

            'rewrite' => array(
                'slug'       => 'type',
                'with_front' => false,
            ),
        )
    );
}

add_action(
    'init',
    'tuougm_register_story_type'
);


/* =========================================================
 * Taxonomy : RELATED CHARACTER
 *
 * RE:NA
 * NOVA
 * SPRU
 * etc.
 * ========================================================= */

function tuougm_register_related_character() {

    $labels = array(
        'name'          => 'Related Characters',
        'singular_name' => 'Related Character',
        'menu_name'     => 'Related Characters',
    );

    register_taxonomy(
        'related_character',
        array('story'),
        array(
            'labels'            => $labels,

            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,

            'hierarchical'      => false,

            'rewrite' => array(
                'slug'       => 'character',
                'with_front' => false,
            ),
        )
    );
}

add_action(
    'init',
    'tuougm_register_related_character'
);


/* =========================================================
 * Taxonomy : RELATED LOCATION
 *
 * GUNPLA CITY
 * CENTRAL PLAZA
 * etc.
 * ========================================================= */

function tuougm_register_related_location() {

    $labels = array(
        'name'          => 'Related Locations',
        'singular_name' => 'Related Location',
        'menu_name'     => 'Related Locations',
    );

    register_taxonomy(
        'related_location',
        array('story'),
        array(
            'labels'            => $labels,

            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,

            'hierarchical'      => false,

            'rewrite' => array(
                'slug'       => 'location',
                'with_front' => false,
            ),
        )
    );
}

add_action(
    'init',
    'tuougm_register_related_location'
);
/*---------------------------------------------
 * 管理画面カスタマイズ
 *--------------------------------------------*/ 
add_action(
    'show_user_profile',
    'tuougm_user_level'
);

add_action(
    'edit_user_profile',
    'tuougm_user_level'
);

function tuougm_user_level($user){
?>
    <h2>TUOUGM</h2>

    <table class="form-table">
        <tr>
            <th>SIN LEVEL</th>
            <td>
                <?php echo esc_html(
                    get_user_meta(
                        $user->ID,
                        'sin_level',
                        true
                    )
                ); ?>
            </td>
        </tr>
		<tr>
            <th>MENBERSHIP LEVEL</th>
            <td>
                <?php echo esc_html(
                    get_user_meta(
                        $user->ID,
                        'membership_level',
                        true
                    )
                ); ?>
            </td>
        </tr>
    </table>
<?php
}

add_filter('wp_mail_from', function() {
    return 'info@zakubaka.com';
});

add_filter('wp_mail_from_name', function() {
    return 'TUOUGM';
});

/**
 * SIN LEVEL カラム追加
 */
add_filter('manage_users_columns', function($columns){

    $columns['sin_level'] = 'SIN LEVEL';
    $columns['membership_level'] = 'CLEARANCE';

    return $columns;

});

/**
 * SIN LEVEL / CLEARANCE 表示
 */
add_filter(
    'manage_users_custom_column',
    function($output, $column_name, $user_id){

        if($column_name === 'sin_level'){

            $level = get_user_meta(
                $user_id,
                'sin_level',
                true
            );

            if($level === ''){
                $level = 1;
            }

            return sprintf(
                'LEVEL %02d',
                intval($level)
            );

        }

        if($column_name === 'membership_level'){

            $level = intval(
                get_user_meta(
                    $user_id,
                    'membership_level',
                    true
                )
            );

            $names = [
                1 => 'MEMBER',
                2 => 'SUPPORTER',
				3 => 'PREMIUM',
            ];

            return $names[$level] ?? 'MEMBER';

        }

        return $output;

    },
    10,
    3
);

add_action(
    'show_user_profile',
    'tuougm_user_levels_field'
);

add_action(
    'edit_user_profile',
    'tuougm_user_levels_field'
);

function tuougm_user_levels_field($user){

    if(!current_user_can('manage_options')){
        return;
    }

    $sinLevel = get_user_meta(
        $user->ID,
        'sin_level',
        true
    );

    $membershipLevel = get_user_meta(
        $user->ID,
        'membership_level',
        true
    );
?>
<h2>TUOUGM STATUS</h2>

<table class="form-table">

<tr>
<th>SIN LEVEL</th>
<td>
<input
    type="number"
    name="sin_level"
    value="<?php echo esc_attr($sinLevel); ?>"
    min="1"
>
</td>
</tr>

<tr>
<th>CLEARANCE</th>
<td>
<select name="membership_level">
    <option value="1" <?php selected($membershipLevel,1); ?>>
        MEMBER
    </option>

    <option value="2" <?php selected($membershipLevel,2); ?>>
        ARCHIVIST
    </option>

    <option value="3" <?php selected($membershipLevel,3); ?>>
        KEEPER
    </option>
</select>
</td>
</tr>

</table>
<?php
}
add_action(
    'personal_options_update',
    'tuougm_save_user_levels'
);

add_action(
    'edit_user_profile_update',
    'tuougm_save_user_levels'
);

function tuougm_save_user_levels($user_id){

    if(!current_user_can('manage_options')){
        return;
    }

    update_user_meta(
        $user_id,
        'sin_level',
        intval($_POST['sin_level'] ?? 1)
    );

    update_user_meta(
        $user_id,
        'membership_level',
        intval($_POST['membership_level'] ?? 1)
    );
}



/*---------------------------------------------
 * TUOUGM PLAYER DATA API
 * 会員レベルに応じてプレイヤーへ返すアルバムを絞り込む
 * access: 0=PUBLIC / 1=MEMBER / 2=SUPPORTER / 3=PREMIUM
 *---------------------------------------------*/
function tuougm_player_catalog() {
    $base = trailingslashit(get_template_directory_uri()) . 'audio/';

    return [
        [
            'id' => 'Stackism',
            'title' => 'STACKISM',
            'artwork' => get_template_directory_uri() . '/img/album4.png',
            'access' => 0,
            'tracks' => [
                ['id'=>'st-01','title'=>'Stackism','src'=>$base.'track04_01.mp3'],
                ['id'=>'st-02','title'=>'家族の恨み買っても買う','src'=>$base.'track04_02.mp3'],
                ['id'=>'st-03','title'=>'RX-78','src'=>$base.'track04_03.mp3'],
                ['id'=>'st-04','title'=>'ガンプラ作りは来世で本気だす','src'=>$base.'track04_04.mp3'],
                ['id'=>'st-05','title'=>'Something Else','src'=>$base.'track04_05.mp3'],
            ],
        ],
		[
            'id' => 'world-after-static',
            'title' => 'THE WORLD AFTER STATIC',
            'artwork' => get_template_directory_uri() . '/img/album3.png',
            'access' => 3,
            'tracks' => [
                ['id'=>'was-01','title'=>'SYNC ENGINE','src'=>$base.'track03_11.mp3'],
                ['id'=>'was-02','title'=>'Brand New World','src'=>$base.'track03_01.mp3'],
                ['id'=>'was-03','title'=>'Naked Gate','src'=>$base.'track03_02.mp3'],
                ['id'=>'was-04','title'=>'BANDED','src'=>$base.'track02_13.mp3'],
                ['id'=>'was-05','title'=>'Where the Runners End','src'=>$base.'track03_03.mp3'],
                ['id'=>'was-06','title'=>'The World After Static','src'=>$base.'track03_04.mp3'],
                ['id'=>'was-07','title'=>'Empire of Tumi（ガンプラ保管計画？）','src'=>$base.'track03_05.mp3'],
                ['id'=>'was-08','title'=>'SPRUE GRAVEYARD','src'=>$base.'track03_06.mp3'],
                ['id'=>'was-09','title'=>'One','src'=>$base.'track03_07.mp3'],
                ['id'=>'was-10','title'=>'BROKEN FRAME','src'=>$base.'track03_08.mp3'],
                ['id'=>'was-11','title'=>'Awakening Protocol','src'=>$base.'track03_09.mp3'],
                ['id'=>'was-12','title'=>'RE:BIRTH SIGNAL','src'=>$base.'track03_10.mp3'],
            ],
        ],
        [
            'id' => 'screaming',
            'title' => 'Screaming into the universe',
            'artwork' => get_template_directory_uri() . '/img/album2.png',
            'access' => 3,
            'tracks' => [
                ['id'=>'screaming-01','title'=>'Build it or die','src'=>$base.'track02_01.mp3'],
                ['id'=>'screaming-02','title'=>'Screaming into the universe','src'=>$base.'track02_02.mp3'],
                ['id'=>'screaming-03','title'=>'G•U•N•P•L•A','src'=>$base.'track02_03.mp3'],
                ['id'=>'screaming-04','title'=>'Runner’s Rebellion','src'=>$base.'track02_04.mp3'],
                ['id'=>'screaming-05','title'=>'Ghosts of the Factory','src'=>$base.'track02_05.mp3'],
                ['id'=>'screaming-06','title'=>'Noise in the vacuum','src'=>$base.'track02_06.mp3'],
                ['id'=>'screaming-07','title'=>'Beyond the Gate','src'=>$base.'track02_07.mp3'],
                ['id'=>'screaming-08','title'=>'Plastic Prayers','src'=>$base.'track02_08.mp3'],
                ['id'=>'screaming-09','title'=>'Sprue of destiny','src'=>$base.'track02_09.mp3'],
                ['id'=>'screaming-10','title'=>'Space Hanger Noise','src'=>$base.'track02_10.mp3'],
                ['id'=>'screaming-11','title'=>'Until official Bandai notices us','src'=>$base.'track02_11.mp3'],
                ['id'=>'screaming-12','title'=>'Sleep Mode','src'=>$base.'track02_12.mp3'],
            ],
        ],
		[
            'id' => 'origin',
            'title' => 'THE ULTIMATE SIN OF UNBUILT GUNPLA MOUNTAIN',
            'artwork' => get_template_directory_uri() . '/img/album1.webp',
            'access' => 0,
            'tracks' => [
                ['id'=>'origin-01','title'=>'IS THIS EVOLVED OR WRECKED？','src'=>$base.'track01_01.mp3'],
                ['id'=>'origin-02','title'=>'いまどきのガンプラ情報量エグい','src'=>$base.'track01_02.mp3'],
                ['id'=>'origin-03','title'=>'仕事する暇あっらガンプラ作れ','src'=>$base.'track01_03.mp3'],
                ['id'=>'origin-04','title'=>'NO EXCUSE','src'=>$base.'track01_04.mp3'],
                ['id'=>'origin-05','title'=>'8UN674','src'=>$base.'track01_05.mp3'],
                ['id'=>'origin-06','title'=>'Ever since 1981','src'=>$base.'track01_06.mp3'],
                ['id'=>'origin-07','title'=>'ニッパー片手に','src'=>$base.'track01_07.mp3'],
                ['id'=>'origin-08','title'=>'色もついてるし、スナップフィットだから接着剤もバテも塗料もガンプラには必要ないってよ','src'=>$base.'track01_08.mp3'],
                ['id'=>'origin-09','title'=>'ガンプラ系YouTuberの憂鬱','src'=>$base.'track01_09.mp3'],
                ['id'=>'origin-10','title'=>'THE ULTIMATE SIN OF UNBUILT GUNPLA MOUNTAIN','src'=>$base.'track01_10.mp3'],
                ['id'=>'origin-11','title'=>'PAYBACK','src'=>$base.'track01_11.mp3'],
                ['id'=>'origin-12','title'=>'積みプラは罪？','src'=>$base.'track01_12.mp3'],
                ['id'=>'origin-13','title'=>'魔改造中毒','src'=>$base.'track01_13.mp3'],
                ['id'=>'origin-14','title'=>'Just picture a world ruled by Gunpla','src'=>$base.'track01_14.mp3'],
                ['id'=>'origin-15','title'=>'Chase the Ideal Mobile Suit','src'=>$base.'track01_15.mp3'],
            ],
        ],
        [
            'id' => 'banned',
            'title' => 'BANNED',
            'artwork' => get_template_directory_uri() . '/img/single3.png',
            'access' => 0,
            'tracks' => [
                ['id'=>'banned-01','title'=>'BANNED','src'=>$base.'track02_13.mp3'],
            ],
        ],
    ];
}

function tuougm_player_access_level() {
    if (!is_user_logged_in()) {
        return 0;
    }

    return max(0, intval(get_user_meta(get_current_user_id(), 'membership_level', true)));
}

add_action('rest_api_init', function() {
    register_rest_route('tuougm/v1', '/player', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function() {
            $level = tuougm_player_access_level();
            $albums = array_values(array_filter(
                tuougm_player_catalog(),
                function($album) use ($level) {
                    return intval($album['access']) <= $level;
                }
            ));

            $response = new WP_REST_Response([
                'version' => 1,
                'access_level' => $level,
                'albums' => $albums,
            ]);
            $response->header('Cache-Control', 'private, no-store, max-age=0');
            return $response;
        },
    ]);
});
