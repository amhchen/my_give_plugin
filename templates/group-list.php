<?php
/**
 * Created by PhpStorm.
 * User: Abel
 * Date: 7/19/2016
 * Time: 10:38 AM
 */
?><div><?php
    $team_args = array(
        'taxonomy' => 'give_forms_tag'
    );
    $tags = get_terms($team_args);
    if ($tags) {
        foreach( $tags as $group) { 
            ?>
            <div style='padding-bottom: 20px'><h3 style='margin-bottom: 0'><?php echo $group->name; ?></h3><?php
            $args = array(
                'post_type' => 'give_forms',
                'tax_query' => array(
                    'relation'  => 'AND',
                    array(
                        'taxonomy' => 'give_forms_tag',
                        'field' => 'slug',
                        'terms' => $group->slug
                    ),
                    array(
                        'taxonomy'  => 'give_forms_category',
                        'field'     => 'slug',
                        'terms'     => 'active'
                    )
                )
            );
            $members = get_posts($args);
            $goal = 0;
            $income = 0;
            $output= '';
            foreach($members as $member) {
                $goal   += give_get_form_goal($member->ID);
                $income += give_get_form_earnings_stats($member->ID) + get_post_meta($member->ID, '_give_offline_money',true);
                $output .= '<a href="' . get_permalink($member->ID) . '">' . get_the_title($member->ID) . '</a><br>';
            }
            echo '<p>' . give_currency_filter(give_format_amount($income)) . ' of ' . give_currency_filter(give_format_amount($goal)) . ' raised</p>';
            echo $output;
            ?></div><hr><?php
        }
    }
    else {
        echo 'No Teams Yet!';
    }?>
</div> 