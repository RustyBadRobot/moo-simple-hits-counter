<?php
/**
Plugin Name: Moo Simple hits Counter
Plugin URI: https://deckslog.com/
Description: Simple plugin to count and show a total number of hits (Unique visitors or page-views) to the site without using any third party code.
Author: WP Skate
Version: 1.0.0
Author URI: https://deckslog.com/
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

//create tables
register_activation_hook(__FILE__, 'moo_hits_counter_installNewTables');
register_activation_hook(__FILE__, 'moo_hits_counter_installHourlyTables');
register_activation_hook(__FILE__, 'moo_schedule_hourly_purge');

// Purge scheduling
add_action('moo_purge_hourly_data', 'moo_purge_hourly_data_function');
register_deactivation_hook(__FILE__, 'moo_deactivate');

// Schedule the event on activation
register_activation_hook(__FILE__, 'moo_schedule_hourly_purge');
function moo_schedule_hourly_purge() {
    if (!wp_next_scheduled('moo_purge_hourly_data')) {
        wp_schedule_event(time(), 'daily', 'moo_purge_hourly_data');
    }
}

// Define the purge function
add_action('moo_purge_hourly_data', 'moo_purge_hourly_data_function');
function moo_purge_hourly_data_function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'moo_simple_hits_counter_hourly';
    $wpdb->query("DELETE FROM $table_name WHERE moo_datetime < DATE_SUB(NOW(), INTERVAL 3 DAY)");
}

// Clear the schedule on deactivation
register_deactivation_hook(__FILE__, 'moo_deactivate');
function moo_deactivate() {
    wp_clear_scheduled_hook('moo_purge_hourly_data');
}

function moo_hits_counter_installNewTables() {
    global $wpdb;
    $tableName = $wpdb->prefix . "moo_simple_hits_counter";

    $sql = "CREATE TABLE IF NOT EXISTS " . $tableName . "(
	moo_id mediumint(9) UNSIGNED AUTO_INCREMENT NOT NULL,
	moo_date date,
	moo_time time,
	moo_post_id mediumint(9),
	moo_post_type varchar(200),
	moo_visitors_count mediumint(9),
	moo_views_count mediumint(9),
	PRIMARY KEY (moo_id)
	)DEFAULT CHARSET=utf8;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function moo_hits_counter_installHourlyTables() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'moo_simple_hits_counter_hourly';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        moo_id mediumint(9) UNSIGNED AUTO_INCREMENT NOT NULL,
        moo_datetime datetime,
        moo_post_id mediumint(9),
        moo_post_type varchar(200),
        moo_visitors_count mediumint(9),
        moo_views_count mediumint(9),
        PRIMARY KEY (moo_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'moo_get_previous_visitors_views');
function moo_get_previous_visitors_views(){
    if(get_option('migrated_to_version') != 1){
        $moo_pageViews_count = get_option('moo_pageViews_count');
        $moo_visitors_count = get_option('moo_visitors_count');
        moo_reset_views_visitors(0, $moo_visitors_count, $moo_pageViews_count);
        update_option('migrated_to_version', '1' );
        update_option('moo_update_ran' ,1);
    }
}

// MIGRATION
add_action('wp_head', 'moo_plugin_data_migration');
add_action('admin_head', 'moo_plugin_data_migration');
function moo_plugin_data_migration(){
    if(get_option('moo_simple_hits_counter_version')!='1.0.2'){
        moo_hits_counter_installNewTables();
		moo_hits_counter_installHourlyTables();
        moo_get_previous_visitors_views();
        update_option('moo_simple_hits_counter_version', '1.0.2');
        update_option('moo_update_ran', intval(get_option('moo_update_ran'))+1 );
    }
}


// ENQUEUE SCRIPTS
add_action('wp_footer','moo_simple_hits_counter_js');
function moo_simple_hits_counter_js() { ?>
    <script type="text/javascript">
        var templateUrl = '<?php echo get_site_url(); ?>';
        var post_id = '<?php echo get_the_ID(); ?>';
    </script>
    <?php  wp_enqueue_script( 'moo_simple_hits_counter_js', plugins_url( '/js/moo_simple_hits_counter_js.js', __FILE__ ), array('jquery'), '', true);
}

// UPDATE COUNTER
add_action('wp_ajax_moo_update_counter','moo_simple_hits_counter');
add_action('wp_ajax_nopriv_moo_update_counter','moo_simple_hits_counter');
function moo_simple_hits_counter(){
    $post_id = sanitize_text_field($_GET['post_id']);
    $visitors = $views = 0;

    if(!isset($_COOKIE['moo_unique_visitor'])){
        setcookie("moo_unique_visitor", "1", 0 ,'/', parse_url(site_url(), PHP_URL_HOST));
        $visitors = 1;
    }

    $views = 1;
    moo_update_views_visitors($post_id, $visitors, $views);
}
function moo_update_views_visitors($post_id, $visitors, $views){
    global $wpdb;
    $daily_table = $wpdb->prefix.'moo_simple_hits_counter';
    $date = Date("Y-m-d");
    $time = Date("h:i:s");
    $post_type = get_post_type($post_id);
	$daily_data = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $daily_table WHERE moo_post_id = %d AND moo_date = %s",
        $post_id,
        $date
    ));
	if ($daily_data) {
        $new_visitors = $daily_data[0]->moo_visitors_count + $visitors;
        $new_views = $daily_data[0]->moo_views_count + $views;
        $wpdb->update(
            $daily_table,
            array('moo_visitors_count' => $new_visitors, 'moo_views_count' => $new_views),
            array('moo_post_id' => $post_id, 'moo_date' => $date)
        );
    } else {
        $wpdb->insert(
            $daily_table,
            array(
                'moo_date' => $date,
                'moo_time' => $time,
                'moo_post_id' => $post_id,
                'moo_post_type' => $post_type,
                'moo_visitors_count' => $visitors,
                'moo_views_count' => $views
            )
        );
    }
	
	// Hourly table update
    $hourly_table = $wpdb->prefix . 'moo_simple_hits_counter_hourly';
    $current_hour = date('Y-m-d H:00:00'); // e.g., "2023-10-01 10:00:00"
    $hourly_data = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $hourly_table WHERE moo_post_id = %d AND moo_datetime = %s",
        $post_id,
        $current_hour
    ));

    if ($hourly_data) {
        $new_visitors = $hourly_data[0]->moo_visitors_count + $visitors;
        $new_views = $hourly_data[0]->moo_views_count + $views;
        $wpdb->update(
            $hourly_table,
            array('moo_visitors_count' => $new_visitors, 'moo_views_count' => $new_views),
            array('moo_post_id' => $post_id, 'moo_datetime' => $current_hour)
        );
    } else {
        $wpdb->insert(
            $hourly_table,
            array(
                'moo_datetime' => $current_hour,
                'moo_post_id' => $post_id,
                'moo_post_type' => $post_type,
                'moo_visitors_count' => $visitors,
                'moo_views_count' => $views
            )
        );
    }
}

function moo_reset_views_visitors($post_id, $visitors, $views){
    global $wpdb;
    $table_name = $wpdb->prefix.'moo_simple_hits_counter';
    $date = Date("Y-m-d");
    $time = Date("h:i:s");
    $post_type = get_post_type($post_id);
    $post_data = $wpdb->get_results("SELECT * FROM $table_name WHERE moo_post_id = $post_id");
    if($post_data){
        if($visitors == 'null'){
            $visitors = $post_data[0]->moo_visitors_count;
        }
        if($views == 'null'){
            $views = $post_data[0]->moo_views_count;
        }
        $wpdb->update($table_name, array('moo_visitors_count' => $visitors, 'moo_views_count' => $views), array('moo_post_id' => $post_id));
    }else{
        if($visitors == 'null'){
            $visitors = 0;
        }
        if($views == 'null'){
            $views == 0;
        }
        $wpdb->insert(
            $table_name,
            array(
                'moo_id' => NULL,
                'moo_date' => $date,
                'moo_time' => $time,
                'moo_post_id' => $post_id,
                'moo_post_type' => $post_type,
                'moo_visitors_count' =>$visitors,
                'moo_views_count' => $views
            )
        );
    }
}

function moo_count_total_visitors_views($return){
    global $wpdb;
    $table_name = $wpdb->prefix . 'moo_simple_hits_counter';
    if ($return == 'views') {
        return $wpdb->get_results("SELECT SUM(moo_views_count) as total FROM $table_name ")[0];
    } else {
        return $wpdb->get_results("SELECT SUM(moo_visitors_count) as total FROM $table_name ")[0];
    }
}

// SHORTCODE
function moo_getTotal_pageViews(){
    $data_return_views = moo_count_total_visitors_views('views');
    if(get_option('moo_pageViews_number_format_count') == 'yes'){
        return "<span class='page-views'>".number_format(intval($data_return_views->total))."</span>";
    }else{
        return "<span class='page-views'>".intval($data_return_views->total)."</span>";
    }
}
add_shortcode('moo_total_pageViews', 'moo_getTotal_pageViews');

function moo_get_most_popular_post_24h() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'moo_simple_hits_counter_hourly';

    $result = $wpdb->get_row("
        SELECT moo_post_id, SUM(moo_views_count) as total_views
        FROM $table_name
        WHERE moo_datetime >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY moo_post_id
        ORDER BY total_views DESC
        LIMIT 1
    ");

    if ($result && $post = get_post($result->moo_post_id)) {
        return '<a href="' . get_permalink($post->ID) . '">' . esc_html($post->post_title) . '</a>';
    }
    return 'No popular post found.';
}
add_shortcode('moo_most_popular_post_24h', 'moo_get_most_popular_post_24h');

function moo_getTotal_visitors(){
    $data_return_visitors = moo_count_total_visitors_views('visitors');
    if(get_option('moo_pageViews_number_format_count') == 'yes'){
        return "<span class='visitors'>".number_format(intval($data_return_visitors->total))."</span>";
    }else{
        return "<span class='visitors'>".intval($data_return_visitors->total)."</span>";
    }
}
add_shortcode('moo_total_visitors', 'moo_getTotal_visitors');

// WIDGET
add_action( 'widgets_init', 'moo_shc_register_widget' );
function moo_shc_register_widget() {
    register_widget( 'Moo_SHC_Widget' );
}
class Moo_SHC_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            'moo_shc_widget', // Base ID
            __( 'Moo Simple Hits Counter', 'text_domain' ), // Name
            array( 'description' => __( 'Add this widget to the sidebar or any other widget area available on your theme where you would like to display the Total Hits Count for your whole site.', 'text_domain' ), ) // Args
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
        }
        if( $instance['type']=='visitors' ){
            $data_return_visitors = moo_count_total_visitors_views('visitors');
            if(get_option('moo_pageViews_number_format_count') == 'yes'){
                $moo_total_visitors =  number_format(intval( $data_return_visitors->total ));
            }else{
                $moo_total_visitors =  intval( $data_return_visitors->total );
            }
            echo "<span class='visitors'>" . __( $moo_total_visitors, 'text_domain' ) . "</span>";
        }elseif( $instance['type']=='pageviews' ){
            $data_return_views = moo_count_total_visitors_views('views');
            if(get_option('moo_pageViews_number_format_count') == 'yes'){
                $moo_total_pageViews =  number_format(intval( $data_return_views->total ));
            }else{
                $moo_total_pageViews =  intval( $data_return_views->total );
            }
            echo "<span class='page-views'>" . __( $moo_total_pageViews, 'text_domain' ) . "</span>";
        }

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'text_domain' );
        $visitors_count = ! empty( $instance['visitors_count'] ) ? $instance['visitors_count'] : __( '00000', 'text_domain' );
        $pageViews_count = ! empty( $instance['pageViews_count'] ) ? $instance['pageViews_count'] : __( '00000', 'text_domain' );
        $type = ! empty( $instance['type'] ) ? $instance['type'] : __( 'visitors', 'text_domain' );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Counter Type:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" type="radio" value="visitors" <?php echo esc_attr( $type )=='visitors'?'checked':'' ; ?>>Visitors
            <input class="widefat" id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" type="radio" value="pageviews" <?php echo esc_attr( $type )=='pageviews'?'checked':'' ; ?>>Page Views
        </p>

        <p>
            Reset options have been moved to the options page under the settings menu.
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();

        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['type'] = ( ! empty( $new_instance['type'] ) ) ? sanitize_text_field( $new_instance['type'] ) : '';
        $instance['visitors_count_reset_check'] = ( ! empty( $new_instance['visitors_count_reset_check'] ) ) ? sanitize_text_field( $new_instance['visitors_count_reset_check'] ) : '';
        $instance['pageViews_count_reset_check'] = ( ! empty( $new_instance['pageViews_count_reset_check'] ) ) ? sanitize_text_field( $new_instance['pageViews_count_reset_check'] ) : '';

        return $instance;
    }
}

// Register and load Admin menus
add_action('admin_menu', 'moo_add_menu_function_call' );
function moo_add_menu_function_call(){
    $settings_page  = add_menu_page( 'Moo Simple Hits Counter Dashboard', 'Simple Counter', 'administrator', 'moo-simple-hits-counter', 'moo_hits_counter_graphs' );
    $page           = add_submenu_page( 'moo-simple-hits-counter', 'Dashboard', 'Dashboard', 'administrator', 'moo-simple-hits-counter', 'moo_hits_counter_graphs' );
    $page           = add_submenu_page( 'moo-simple-hits-counter', 'Moo Hits Counter Settings', 'Settings', 'administrator',  'moo-simple-hits-counter-settings','moo_admin_settings_page');
}

//generate graph and load js libraries  for graph
function moo_hits_counter_graphs(){
    global $wpdb;
    //load libraries for graph
    wp_enqueue_script( 'moo_hits_counter_Chart_bundle_js', plugins_url( '/js/Chart.bundle.min.js', __FILE__ ), array('jquery'), '', true);
    wp_enqueue_script( 'moo_hits_counter_Chart_js', plugins_url( '/js/Chart.min.js', __FILE__ ), array('jquery'), '', true);
    //check if user select last month or last week option   .. default to last week
    $dates_range = array();
    $begin = new DateTime();
    if(isset($_GET['range_filter']) && sanitize_text_field($_GET['range_filter']) == 'month'){

        $begin->sub(new DateInterval('P29D'));
        $end = new DateTime();
        $end->add(new DateInterval('P1D'));
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);
        foreach ( $period as $dt ){
            $dates_range[] = $dt->format( "Y-m-d" );
        }
    }else{
        $begin->sub(new DateInterval('P6D'));
        $end = new DateTime();
        $end->add(new DateInterval('P1D'));
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);
        foreach ( $period as $dt ){
            $dates_range[] = $dt->format( "Y-m-d" );
        }
    }
    $table_name = $wpdb->prefix . 'moo_simple_hits_counter';

    //retrieve data from database
    $post_data = $wpdb->get_results("SELECT moo_id, moo_date, moo_time, moo_post_id, moo_post_type, sum(moo_visitors_count) moo_visitors_count, SUM(moo_views_count) moo_views_count FROM $table_name WHERE moo_date >= '".$begin->format( "Y-m-d" )."' GROUP BY moo_date ORDER BY moo_date DESC ");
    ?>
    <!--- generating html for filters -->
    <div class="filter" style="margin-top: 30px; width: 50%;">
        <form action="" method="get" class="">
            <select name="range_filter" style="width: 20%">
                <option value="week"> Last Week</option>
                <option value="month" <?php if(isset($_GET['range_filter']) && sanitize_text_field($_GET['range_filter']) == 'month'){ echo "selected"; } ?>>Last Month</option>
            </select>
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']) ?>">
            <input type="submit" value="Apply" style="width: 10%" class="button button-primary">
        </form>

    </div>
    <!-- Add canvas in which we will show graph   -->
    <canvas id="moo_visitors_views_charts" style="width: 75% !important;"></canvas>
    <script>
        //javascript code for graph
        var visitors = [];
        var views = [];
        var date_labels = [];
        var counter = 0;
        <?php
        $moo_visitors_count = 0;
        $moo_views_count = 0;
        $moo_date = 0;
        //$dates_range = array_reverse($dates_range);
        foreach ($dates_range as $date){
        $moo_date = date("F j, Y", strtotime($date));
        foreach($post_data as $post){
            if(date("Y-m-d", strtotime($post->moo_date)) == $date){
                $moo_visitors_count = $post->moo_visitors_count;
                $moo_views_count = $post->moo_views_count;
            }
        }
        ?>
        visitors[counter] = '<?php echo $moo_visitors_count; ?>';
        views[counter]  = '<?php echo $moo_views_count; ?>';
        date_labels[counter] = '<?php echo $moo_date; ?>';
        counter ++;
        <?php
        $moo_visitors_count = 0;
        $moo_views_count = 0;
        }
        ?>
        window.chartColors = {
            red: 'rgb(255, 99, 132)',
            orange: 'rgb(255, 159, 64)',
            yellow: 'rgb(255, 205, 86)',
            green: 'rgb(75, 192, 192)',
            blue: 'rgb(54, 162, 235)',
            purple: 'rgb(153, 102, 255)',
            grey: 'rgb(201, 203, 207)'
        };
        var config = {
            type: 'line',
            data: {
                labels: date_labels,
                datasets: [{
                    label: "Visitors",
                    backgroundColor: window.chartColors.red,
                    borderColor: window.chartColors.red,
                    data: visitors,
                    fill: false,
                }, {
                    label: "Views",
                    fill: false,
                    backgroundColor: window.chartColors.blue,
                    borderColor: window.chartColors.blue,
                    data: views,
                }]
            },
            options: {
                responsive: true,
                title:{
                    display:true,
                    text:'Graph'
                },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    xAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Month'
                        }
                    }],
                    yAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Value'
                        }
                    }]
                }
            }
        };
        window.onload = function() {
            var ctx = document.getElementById("moo_visitors_views_charts").getContext("2d");
            window.myLine = new Chart(ctx, config);
        };
    </script>
    <?php
}

function moo_admin_settings_page(){
    global $wpdb;
    $table_name = $wpdb->prefix.'moo_simple_hits_counter';
    echo '<h1>Moo Simple Hits Counter</h1>';

    if( isset($_POST['moo_shc_options_save']) ){
        // Reset Unique Visitor Counter
        if( $_POST['unique_visitor_reset_val']!='' && $_POST['unique_visitor_checkbox'] == "yes" ){
            $sql = "UPDATE $table_name SET `moo_visitors_count` = '0'";
            $wpdb->query($sql);
            $views = 'null';
            if( $_POST['page_views_reset_val']!='' &&$_POST['page_views_checkbox'] == "yes" ){
                $views = sanitize_text_field($_POST['page_views_reset_val']);
            }
            moo_reset_views_visitors(0, sanitize_text_field($_POST['unique_visitor_reset_val']), $views);
        }

        // Reset Page Views Counter
        if( $_POST['page_views_reset_val']!='' &&$_POST['page_views_checkbox'] == "yes" ){
            $visitors = 'null';
            if( $_POST['unique_visitor_reset_val']!='' && $_POST['unique_visitor_checkbox'] == "yes" ){
                $visitors = sanitize_text_field($_POST['unique_visitor_reset_val']);
            }
            $sql = "UPDATE $table_name SET `moo_views_count` = '0' ";
            $wpdb->query($sql);
            moo_reset_views_visitors(0, $visitors, sanitize_text_field($_POST['page_views_reset_val']));
        }

        // Change number format
        if($_POST['page_views_number_format_checkbox']!='' && $_POST['page_views_number_format_checkbox'] == 'yes'){
            update_option('moo_pageViews_number_format_count', 'yes' );
        }else{
            update_option('moo_pageViews_number_format_count', 'no' );
        }

        // Reset plugin data
        if($_POST['reset_data']!='' && $_POST['reset_data'] == 'yes'){
            $wpdb->query("TRUNCATE $table_name");
        }
    }
    $data_return_visitors = moo_count_total_visitors_views('visitors');
    $data_return_views = moo_count_total_visitors_views('views');
    $moo_shc_unique_visitors_count = $data_return_visitors->total;
    $moo_shc_page_views_count = $data_return_views->total;
    $page_views_number_format_checkbox = get_option('moo_pageViews_number_format_count');
    ?>
    <div class="metabox-holder">
        <div class="postbox">
            <h3 class="hndle">
                <span>Short Codes</span>
            </h3><?php //print_r($_POST) ?>
            <div class="inside">
                <div class="main">
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th>Unique Visitors </th>
                            <td>
                                [moo_total_visitors]
                            </td>
                        </tr>
                        <tr>
                            <th>Page Views</th>
                            <td>
                                [moo_total_pageViews]
                            </td>
                        </tr>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
    <form method="post" action="">

        <div class="metabox-holder">
            <div class="postbox">
                <h3 class="hndle">
                    <span>Reset Counters</span>
                </h3><?php //print_r($_POST) ?>
                <div class="inside">
                    <div class="main">

                        <p>Textfields below show the current counter values. To reset the counter, change the value and tick the checkbox below the textfield to verify that you really want to reset that counter. </p>
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th>Unique Visitors:</th>
                                <td>
                                    <input type="text" name="unique_visitor_reset_val" placeholder="00000" value="<?php echo $moo_shc_unique_visitors_count ?>">
                                    <br><span class="description">Are you sure you want to reset 'Unique Visitors Counter'? <input type="checkbox" name="unique_visitor_checkbox" value="yes"></span>
                                </td>
                            </tr>
                            <tr>
                                <th>Page Views:</th>
                                <td>
                                    <input type="text" name="page_views_reset_val" placeholder="00000" value="<?php echo $moo_shc_page_views_count ?>">
                                    <br><span class="description">Are you sure you want to reset 'Page Views Counter'? <input type="checkbox" name="page_views_checkbox" value="yes"></span>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>

        <div class="metabox-holder">
            <div class="postbox">
                <h3 class="hndle">
                    <span>Formatting</span>
                </h3><?php //print_r($_POST) ?>
                <div class="inside">
                    <div class="main">
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th>Add Commas:</th>
                                <td>
                                    <span class="description">Yes? <input type="checkbox" name="page_views_number_format_checkbox" value="yes" <?php if(isset($page_views_number_format_checkbox) && $page_views_number_format_checkbox == 'yes' ){ echo "checked"; } ?> > Example:(10,000,000) </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>

        <div class="metabox-holder">
            <div class="postbox">
                <h3 class="hndle">
                    <span>Reset Plugin</span>
                </h3><?php //print_r($_POST) ?>
                <div class="inside">
                    <div class="main">
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th>Reset:</th>
                                <td>
                                    <span class="description">Yes? <input type="checkbox" name="reset_data" value="yes" > Deletes all the existing plugin data and starts fresh </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>


        <p class="submit">
            <input type="submit" name="moo_shc_options_save" class="button-primary" value="Save settings">
        </p>
    </form>
    <?php
}
