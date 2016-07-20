<?php
/*
*Template name: Frontend Form
*/
global $give_options;
if (is_user_logged_in()) {
//ob_start();
    if ( isset( $_POST['submitted'] )  )  {

        if(($_POST['posttitle']) == "" ){
            $titleerr = "This Field is required";
            $hasError = true;
        }
        $c_args = array(
            'post_type'     => 'give_forms',
            'tax_query'     => array(
                'relation'  => 'AND',
                array(
                    'taxonomy'  => 'give_forms_category',
                    'field'     => 'slug',
                    'terms'     => 'active'
                ),
                array(
                    'taxonomy'  => 'give_forms_category',
                    'field'     => 'slug',
                    'terms'     => 'cause'
                )
            ),
            'orderby'       => 'post_date',
            'order'         => 'DESC'
        );
        $cause = get_posts( $c_args);
        if (count($cause) > 0) {
            $cause_target_date = get_post_meta($cause[0]->ID, '_give_target_date', true);
        }
        else {
            wp_die("There aren't any active causes right now!");
        }
        $args = array(
            'post_type'		=>	'give_forms',
            'give_forms_category' => 'active',
            'author'        =>  get_current_user_ID(),
            'orderby'       =>  'post_date',
            'order'			=> 'DESC',
            'numberposts'      => 1
        );
        $u_posts = get_posts( $args );

        $post_info = array(
            'post_title' => wp_strip_all_tags( $_POST['posttitle'] ),
            'post_type' => 'give_forms',
            'post_status'=>'publish',
            'post_excerpt' => $_POST['postcontent']
        );
        $new;
        $pid;
        if (count($u_posts) == 0)  {
            $pid = wp_insert_post($post_info);
            $new = true;
        }
        else {
            $pid = $u_posts[0]->ID;
            $post_info['ID'] = $pid;
            wp_update_post($post_info);
            $new = false;
        }

        $year = date('Y');
        $terms = array('active',$year);
        if (has_term('cause','give_forms_category',$pid)) {
            $terms[] = 'cause';
        }

        $prices = array();
        //$30.00 level
        $prices[] = array('_give_id' => array( 'level_id' => 1),
            '_give_amount' => '30.00',
            '_give_text' => '',
            '_give_default' => 'default',);
        //$50.00 level
        $prices[] = array('_give_id' => array( 'level_id' => 2),
            '_give_amount' => '50.00',
            '_give_text' => '',
            '_give_default' => '',);
        //$100.00 level
        $prices[] = array('_give_id' => array( 'level_id' => 3),
            '_give_amount' => '100.00',
            '_give_text' => '',
            '_give_default' => '',);

        wp_set_object_terms($pid,$terms,'give_forms_category');
        update_post_meta($pid,'_give_default_gateway',"global");
        update_post_meta($pid,'_give_price_option',"multi");
        update_post_meta($pid,'_give_donation_levels',$prices);
        update_post_meta($pid,'_give_set_price',"20.00");
        update_post_meta($pid,'_give_custom_amount',"yes");
        update_post_meta($pid,'_give_custom_amount_minimum',"1.00");
        update_post_meta($pid,'_give_custom_amount_text',"Custom Amount | 其他");
        update_post_meta($pid,'_give_content_option','give_pre_form');
        update_post_meta($pid,'_give_form_content',$_POST['postcontent']);
        update_post_meta($pid,'_give_goal_option',"yes");
        update_post_meta($pid,'_give_set_goal',$_POST['goal']);
        update_post_meta($pid,'_give_payment_display',"reveal");
        update_post_meta($pid,'_give_target_date',$cause_target_date);
        update_post_meta($pid,'_give_display_style','radios');
        update_post_meta($pid,'_give_goal_format',"amount");
        update_post_meta($pid,'_give_goal_color','#2bc253');
        update_post_meta($pid,'_give_show_register_form','none');
        update_post_meta($pid,'_give_terms_option','none');
        
        if($_FILES['feat-image']['size'] > 0) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('feat-image', $pid);
            if ($pid && $attachment_id) {
                $post_success = set_post_thumbnail($pid, $attachment_id);
            }
        }
        else if (!get_the_post_thumbnail($pid)){
            $post_success = set_post_thumbnail($pid, 129);
        }
        $redirect = get_home_url();
        if ( !is_wp_error($pid)) {
            $redirect = get_permalink($pid);
            if ($new) {
                $user = wp_get_current_user();
                $message  = "Dear " . $user->user_firstname . ",\n";
                $message .= "Thank you for signing up for " . $cause[0]->post_title . ".";
                $message .= "You can share your new campaign page using the following link: \n";
                $message .= $redirect . "\n\n";
                $message .= "This is your user ID: " . $user->ID . "\n";
                $message .= "Please write it on the envelope you were given when you signed up.\n\n";
                $message .= "Happy fundraising, and see you on " . date('F d',$cause_target_date) . "!\n\n";
                $message .= "Sincerely,\n";
                $message .= "CCM Canada Walk for Love " . $year . "\n";
                $message .= "中信中心愛履同行 " . $year . "\n";
                $from[]   = "From: abelchen@ymail.com";
                //add_filter('wp_mail_from','custom_wp_mail_from');
                //add_filter('wp_mail_from_name','custom_wp_mail_from_name',99);
                do_action('new_campaign_created',$redirect);
                //remove_filter('wp_mail_from','custom_wp_mail_from');
                //remove_filter('wp_mail_from_name','custom_wp_mail_from_name',99 );
            }
        }
        wp_redirect( $redirect );
        exit();
    }
    function custom_wp_mail_from($email) {
        return 'abelchen@ymail.com';
    }
    function custom_wp_mail_from_name($name) {
        return 'CCM Canada';
    }
    function campaign_info($param) {
        $args = array(
            'post_type'		=>	'give_forms',
            'give_forms_category' => 'active',
            'author'        =>  get_current_user_ID(),
            'orderby'       =>  'post_date',
            'order'			=> 'DESC',
            'numberposts'      => 1
        );
        //echo get_current_user_ID();
        $u_posts = get_posts( $args );
        if (count($u_posts) > 0) {
            $pid = $u_posts[0]->ID;
            //echo $pid;
            switch($param) {
                case "title":
                    echo get_the_title($pid);
                    break;
                case "description":
                    $content = get_post_meta($pid,'_give_form_content');
                    foreach ($content as $line) {echo $line;}
                    break;
                case "goal":
                    $goal = get_post_meta($pid,"_give_set_goal");
                    foreach ($goal as $num) {echo $num;}
                    break;
            }
        }
        else {
            // echo $param;
        }
    }
    ?>
    <form action="" method="post" enctype="multipart/form-data">
        <div>*Display name 顯示名稱<br>
            <input name="posttitle" id="posttitle" type="text" placeholder = "Name of your Campaign" value="<?php campaign_info('title'); ?>" required/></div>
        <div>Description 個人簡介<br>
            <textarea name="postcontent" placeholder="A short summary of your campaign" id="postcontent" rows="5" cols="50"><?php campaign_info('description')?></textarea></div>
        <div>Goal 籌款目標<br>
            <input name="goal" id="goal" type="number" value="<?php campaign_info('goal'); ?>"/></div>
        <div> Feature Image 個人圖像<br><input type="file" name="feat-image" id="feat-image"></div>
        <p style="font-size:x-small">* Indicates a required field</p>
        <input type="hidden" name="submitted" id="submitted" value="true" /><br>
        <input type="submit" value="Submit | 傳送" /></form>
<?php } else { ?>
    <div>Please <a href="<?php global $give_options; echo get_permalink($give_options['login_page']);?>">log-in </a>
        or <a href="<?php global $give_options; echo get_permalink($give_options['register_page']);?>">register</a> to create a campaign</div>
    <div>請先<a href="<?php global $give_options; echo get_permalink($give_options['login_page']);?>">登入</a>
        或 <a href="<?php global $give_options; echo get_permalink($give_options['register_page']);?>">註冊</a> 後建立籌款網頁</div>
<?php }
