<?php

require get_theme_file_path('/inc/like-route.php');
require get_theme_file_path('/inc/search-route.php');


function university_custom_rest()
{
    register_rest_field('post', 'authorName', array(
        'get_callback' => function () {
            return get_the_author();
        }
    ));

    register_rest_field('note', 'userNoteCount', array(
        'get_callback' => function () {
            return count_user_posts(get_current_user_id(), 'note'); //adds note count to the info returned from the rest api
        }
    ));
}

add_action('rest_api_init', 'university_custom_rest');

//function that makes page banner dynamic and easily adjustable from different pages
function pageBanner($args = NULL)   //NULL makes the argument optional, not required
{
    if (!isset($args['title'])) {
        $args['title'] = get_the_title(); //sets the title for that post's title
    }

    if (!isset($args['subtitle'])) {
        $args['subtitle'] = get_field('page_banner_subtitle');
    }

    if (!isset($args['photo'])) {
        if (get_field('page_banner_background_image') and !is_archive() and !is_home()) {
            $args['photo'] = get_field('page_banner_background_image')['sizes']['pageBanner'];
        } else {
            $args['photo'] = get_theme_file_uri('/images/ocean.jpg');
        }
    }

?>
    <div class="page-banner">
        <div class="page-banner__bg-image" style="background-image: url(<?php echo $args['photo'] ?>);"></div>
        <div class="page-banner__content container container--narrow">
            <h1 class="page-banner__title"><?php echo $args['title'] ?></h1>
            <div class="page-banner__intro">
                <p><?php echo $args['subtitle']; ?></p>
            </div>
        </div>
    </div>
<?php }

function university_files()
{
    wp_enqueue_script('main-university-js', get_theme_file_uri('/build/index.js'), array('jquery'), '1.0', true);
    wp_enqueue_style('custom-google-fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
    wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
    wp_enqueue_style('university_main_styles', get_theme_file_uri('/build/style-index.css'));
    wp_enqueue_style('university_extra_styles', get_theme_file_uri('/build/index.css'));

    wp_localize_script('main-university-js', 'universityData', array(
        'root_url' => get_site_url(),  //outputs website root url
        'nonce' => wp_create_nonce('wp_rest') //whenever user successfully logs in, a random nonce number will be generated
    ));
}

add_action('wp_enqueue_scripts', 'university_files');

//function to add some features to the theme
function university_features()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');       //enables featured images for posts
    add_image_size('professorLandscape', 400, 260, true);   //name, width, height, yes or no to cropping
    add_image_size('professorPortrait', 480, 650, true);
    add_image_size('pageBanner', 1500, 350, true);
    add_theme_support('editor-styles');
    add_editor_style(array('https://fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i', 'build/style-index.css', 'build/index.css'));
}

add_action('after_setup_theme', 'university_features');

//function to customize wordpress queries
function university_adjust_queries($query)
{
    if (!is_admin() and is_post_type_archive('campus') and is_main_query()) {
        $query->set('posts_per_page', -1);
    }

    if (!is_admin() and is_post_type_archive('program') and is_main_query()) {
        $query->set('orderby', 'title');
        $query->set('order', 'ASC');
        $query->set('posts_per_page', -1);
    }

    if (!is_admin() and is_post_type_archive('event') and $query->is_main_query()) {
        $today = date('Ymd');
        $query->set('meta_key', 'event_date');
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'ASC');
        $query->set('meta_query', array(
            array(
                'key' => 'event_date',
                'compare' => '>=',
                'value' => $today,
                'type' => 'numeric'
            )
        ));
    }
}

add_action('pre_get_posts', 'university_adjust_queries');


//Redirect subscriber accounts out of admin and onto homepage
add_action('admin_init', 'redirectSubsToFrontend');

function redirectSubsToFrontend() {
    $ourCurrentUser = wp_get_current_user();
    
    if(count($ourCurrentUser->roles) == 1 AND $ourCurrentUser->roles[0] == 'subscriber') {
        wp_redirect(site_url('/'));
        exit;            
    }
}

//Function prevents admin bar from showing to subscribers 
add_action('wp_loaded', 'noSubsAdminBar');

function noSubsAdminBar()
{
    $ourCurrentUser = wp_get_current_user();

    if (count($ourCurrentUser->roles) == 1 and $ourCurrentUser->roles[0] == 'subscriber') {
        show_admin_bar(false);
    }
}

// Customize Login Screen
add_filter('login_headerurl', 'ourHeaderUrl');

function ourHeaderUrl() {
    return esc_url(site_url('/'));
}

// Function to add CSS to WordPress login page
add_action('login_enqueue_scripts', 'ourLoginCSS');

function ourLoginCSS() {
    wp_enqueue_style('custom-google-fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
    wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
    wp_enqueue_style('university_main_styles', get_theme_file_uri('/build/style-index.css'));
    wp_enqueue_style('university_extra_styles', get_theme_file_uri('/build/index.css'));
}

// Replaces "Powered by Wordpress" on the login page with the website actual name
add_filter('login_headertitle', 'ourLoginTitle');

function ourLoginTitle() {
    return get_bloginfo('name');
}

// Force note posts to be private
add_filter('wp_insert_post_data', 'makeNotePrivate', 10, 2); //10 priority number, 2 allows function to work with two parameters 

function makeNotePrivate($data, $postarr) {
    if ($data['post_type'] == 'note') {
        if(count_user_posts(get_current_user_id(), 'note') > 99 AND !$postarr['ID']) {
            die("You have reached your note limit"); 
        }

        $data['post_content'] = sanitize_textarea_field($data['post_content']); //strips all html tags before saving the note to the database
        $data['post_title'] = sanitize_text_field($data['post_title']); 
    }

    if ($data['post_type'] == 'note' AND $data['post_status'] != 'trash') {
        $data['post_status'] = "private";
    }

    return $data;
}

//code below excludes node modules folder from export
// add_filter('ai1wm_exclude_themes_from_export', 'ignoreCertainFiles');

// function ignoreCertainFiles($exclude_filters) {
//     $exclude_filters[] = 'themes/fictional-university-theme/node_modules';
//     return $exclude_filters;
// }

add_filter( 'ai1wm_exclude_themes_from_export', function ( $exclude_filters ) {
  $exclude_filters[] = 'fictional-university-theme/node_modules';
  return $exclude_filters;
} );

add_filter( 'ai1wm_exclude_themes_from_export', function ( $exclude_filters ) {
    $exclude_filters[] = 'wp-content/plugins/are-you-paying-attention/node_modules';
    return $exclude_filters;
  } );

//function for google maps API, can't use because I don't want to enter my billing info
/* function universityMapKey($api) {
    $api['key'] = 'AIzaSyCNUCBEax5YvcwiHY32QUSXBO8tUSKcl6w';
    return $api;
}

add_filter('acf/fields/google_map/api', 'universityMapKey'); */

//registers Banner Block
function bannerBlock() {
    wp_register_script('bannerBlockScript', get_stylesheet_directory_uri() . '/build/banner.js', array('wp-blocks', 'wp-editor'));
    register_block_type("ourblocktheme/banner", array(
        'editor_script' => 'bannerBlockScript'
    ));
}

add_action('init', 'bannerBlock');

?>