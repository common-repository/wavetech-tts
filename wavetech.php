<?php
/*
   Plugin Name: wavetech. - TTS
   Plugin URI: http://wavetech.ai
   description: Text-to-speech plugin
   a plugin to create awesomeness and spread joy
   Version: 1.0
   Author: wavetech.
   Author URI: https://wavetech.ai
   License: GPL2
*/


// Adding wavetech link to menu
add_action('admin_menu', 'wavetech_plugin_setup_menu');

function wavetech_plugin_setup_menu()
{
  add_menu_page('wavetech Plugin Page', 'wavetech.', 'manage_options', 'wavetech-plugin', 'wavetech_init', plugins_url('/assets/images/Icon-Plugin.svg', __FILE__));
}


function wavetech_init()
{
  // check if plugin is enabled
  global $wpdb;

  include(plugin_dir_path(__FILE__) . 'templates/body.php');
}


// Add styles
function wavetech_add_plugin_stylesheet()
{
  wp_enqueue_style('plugin-styles', plugins_url('/assets/css/style.css', __FILE__));
}

add_action('admin_print_styles', 'wavetech_add_plugin_stylesheet');
add_action('wp_print_styles', 'wavetech_add_plugin_stylesheet');


// Add scripts
function wavetech_add_admin_scripts()
{
  wp_enqueue_script('plugin-js', plugins_url('/assets/js/admin-scripts.js', __FILE__), array(), '1.0', true);
  wp_enqueue_script('aws-sdk', plugins_url('/assets/js/aws-sdk.min.js', __FILE__), array(), '1.0', false);
  wp_enqueue_script('axios', plugins_url('/assets/js/axios.min.js', __FILE__), false);
}

add_action('admin_enqueue_scripts', 'wavetech_add_admin_scripts');


function wavetech_add_front_scripts()
{
  wp_enqueue_script('plugin-js', plugins_url('/assets/js/audio-front.js', __FILE__), array(), '1.0', true);
  wp_enqueue_script('minio-js', plugins_url('/assets/js/aws-sdk.min.js', __FILE__), array(), '1.0', false);
  wp_enqueue_script('axios', plugins_url('/assets/js/axios.min.js', __FILE__), false);
}

add_action('wp_enqueue_scripts', 'wavetech_add_front_scripts');



// Create Posts DB upon activation
function wavetech_create_posts_db()
{
  global $wpdb;
  $table_name = $wpdb->prefix . "wt_posts";
  $my_products_db_version = '1.0.0';
  $charset_collate = $wpdb->get_charset_collate();

  if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {

    $sql = "CREATE TABLE $table_name (
              id mediumint(9) NOT NULL AUTO_INCREMENT,
              `post_id` mediumint(9) NOT NULL,
              `audio_id` text NOT NULL,
              `enabled` text NOT NULL,
              PRIMARY KEY  (ID)
      ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    add_option('my_db_version', $my_products_db_version);
  }
}
register_activation_hook(__file__, 'wavetech_create_posts_db');



function wavetech_delete_wt_posts_table(){
  global $wpdb;
  $table_name = $wpdb->prefix . 'wt_posts';
  $sql = "DROP TABLE IF EXISTS $table_name";
  $wpdb->query($sql);
  delete_option('wavetech_enabled');
  delete_option('wavetech_key');
  delete_option('wavetech_project_id');
}

register_uninstall_hook(__FILE__, 'wavetech_delete_wt_posts_table');


// audio after the post
function wavetech_after_title($title)
{
  global $wpdb;

  $result_enabled = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name = 'wavetech_enabled'");

  $enabled = json_decode(json_encode($result_enabled[0]->{'option_value'}));

  if (in_the_loop() && (is_single() || is_page())) {
    $write = include(plugin_dir_path(__FILE__) . 'templates/front-audio.php');
    // $title = $title . $write;
  }

  return $title;
}

global $wpdb;

$result_enabled = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name = 'wavetech_enabled'");
$key_enabled = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name = 'wavetech_key'");

$enabled = json_decode(json_encode($result_enabled[0]->{'option_value'}));

if ($result_enabled[0]->{'option_value'} == 'true' && $key_enabled[0]->{'option_value'}) {
  add_filter('the_content', 'wavetech_after_title');

  // meta box in post
  add_action('add_meta_boxes', 'wavetech_meta_box', 10, 2);
}


function wavetech_meta_box($post_type, $post)
{
  add_meta_box('wavetech. - Text To Speech', __('wavetech. - Text To Speech', 'wk-custom-meta-box'), 'wk_custom_meta_box_content', 'post', 'side', 'high');
}

function wk_custom_meta_box_content()
{
  include(plugin_dir_path(__FILE__) . 'templates/meta_box.php');
}







/***********************************************************************/
/********************** POST PUBLISHING  ************************/
/***********************************************************************/
remove_action('publish_post', 'wavetech_publish_post_func', 10, 0);
function wavetech_publish_post_func($ID, $post)
{

  global $wpdb;

  $audio_id = sanitize_text_field($_POST['wt_audio_id']);
  // $version = sanitize_text_field($_POST['voice_actor']);
  $is_wt_enabled = $_POST['wt_is_enabled'];
  

  if (!$audio_id) {
    $audio_id = 'null';
  }

  $values = [
    "post_id" => $ID,
    "audio_id" => $audio_id,
    "enabled" => $is_wt_enabled
  ];

  // update_option('wavetech_actor', $version);

  if ($post->post_date != $post->post_modified) {
    //THIS IS AN UPDATE

    $where = ['post_id' => $ID]; // NULL value in WHERE clause.
    $result = $wpdb->get_results(" SELECT * FROM {$wpdb->prefix}wt_posts WHERE post_id = '{$ID}'");

    //  $where_format = [NULL];  // Ignored when corresponding WHERE data is NULL, set to NULL for readability.
    if (count($result)) {
      $wpdb->update($wpdb->prefix . 'wt_posts', $values, $where);
    } else {
      $wpdb->insert($wpdb->prefix . "wt_posts", $values);
    }
  } else {
    //POST JUST GOT PUBLISHED
    $wpdb->insert($wpdb->prefix . "wt_posts", $values);
  }
}

add_action('publish_post', 'wavetech_publish_post_func', 10, 2);




/***********************************************************************/
/********************** PLUGIN ACTIVATION FORM  ************************/
/***********************************************************************/

add_action('wp_ajax_nopriv_plugin_activation', 'wavetech_plugin_activation_action');
add_action('wp_ajax_plugin_activation', 'wavetech_plugin_activation_action');


function wavetech_plugin_activation_action()
{

  global $wpdb;

  $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name = 'wavetech_key'")[0]->option_value;
  $projectId = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name = 'wavetech_project_id'")[0]->option_value;
  $isWavetechEnabled = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name = 'wavetech_enabled'")[0]->option_value;

  $wavetech_enabled = sanitize_text_field($_POST['wavetech_enabled']);
  $wavetech_key = sanitize_text_field($_POST['wavetech_key']);
  $wavetech_project_id = sanitize_text_field($_POST['wavetech_project_id']);

  if (isset($isWavetechEnabled)) {
    update_option('wavetech_enabled', $wavetech_enabled);
  } else {
    add_option('wavetech_enabled', $wavetech_enabled);
  }

  if (isset($result) && isset($_POST['wavetech_key'])) {
    update_option('wavetech_key', $wavetech_key);
  } else {
    add_option('wavetech_key', $wavetech_key);
  }

  if (isset($projectId) && isset($_POST['wavetech_project_id'])) {

    update_option('wavetech_project_id', $wavetech_project_id);
  } else {
    add_option('wavetech_project_id', $wavetech_project_id);
  }

  die(); // this is required to terminate immediately and return a proper response
}





/***********************************************************************/
/********************** Add script in admin footer  ************************/
/***********************************************************************/

add_action('in_admin_footer', 'activation_form');

function activation_form()
{
  $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  if (!strpos($actual_link, 'page=wavetech-plugin'))
    return;
?>
  <script type="text/javascript">
    //alert('Working only in the desired screen'); // Debug
    jQuery(document).ready(function($) {


      var keyInput = document.getElementById('wavetech-key-input'),
        keyInputVal = document.getElementById('wavetech-key-input').value,
        isPluginEnabled = document.getElementById('enable-wavetech').getAttribute('checked') ? true : false;

      // change key value
      var onKeyInputChange = function(evt) {
        keyInputVal = this.value;
      };
      var input = document.getElementById('some-id');
      keyInput.addEventListener('input', onKeyInputChange, false);

      // enable disable
      document.getElementById('enable-wavetech').addEventListener('click', function() {
        isPluginEnabled = !isPluginEnabled;
      });


      $('#wt-activation-save-button').click(function() {

        const activationURL = `https://api.wavetech.ai/v1/project?platform=WP&title=<?php echo get_bloginfo('name') ?>&apiKey=${keyInputVal}`;

        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        // if (keyInputVal == "<?php echo get_option('wavetech_key'); ?>") {

        //   var data = {
        //     'action': 'plugin_activation',
        //     'wavetech_key': keyInputVal,
        //     'wavetech_enabled': isPluginEnabled,
        //   };
        //   // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        //   jQuery.post(ajaxurl, data, function(response) {
        //     //alert('Got this from the server: ' + response);
        //     window.location.reload();
        //   });
        // } else {
        jQuery.get(activationURL).done(function(response) {

          var data = {
            'action': 'plugin_activation',
            'wavetech_key': keyInputVal,
            'wavetech_enabled': isPluginEnabled,
            'wavetech_project_id': response.id
          };
          // We can also pass the url value separately from ajaxurl for front end AJAX implementations
          jQuery.post(ajaxurl, data, function(response) {
            // alert('Got this from the server: ' + response);
            $('#wavetech-key-input').css('border-color', '#a0a0a0');
            $('.activation-status-text').text('Successfuly activated').addClass('color-green');
            $('.activation-status-text').removeClass('color-red');
            $('.wt-failed').css('display', 'none');
            $('.wt-successful').css('display', 'inline');
            // window.location.reload();
          });
        }).fail(
          function() {
            // alert('Wrong key');
            $('#wavetech-key-input').css('border-color', 'red');
            $('.activation-status-text').html('Wrong API Key <a href="https://wavetech.ai/auth" target="_blank">check your Kernel</a>').addClass('color-red');
            $('.activation-status-text').removeClass('color-green');
            $('.wt-failed').css('display', 'inline');
            $('.wt-successful').css('display', 'none');
          });
        // }

      })
    });
  </script>
<?php
}