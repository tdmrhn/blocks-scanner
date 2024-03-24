<?php
/*
Plugin Name: Blocks Scanner
Plugin URI: https://github.com/tdmrhn/blocks-scanner
Description: Easily scan and list the Gutenberg blocks used on your site. Quickly edit or view the posts that use the blocks.
Author: dmrhn
Author URI: https://dmrhn.com
Version: 0.5
*/

add_action('admin_enqueue_scripts', function () {
    $plugin_version = get_plugin_data( __FILE__ )['Version'];
    wp_enqueue_script('blocks-scanner-script', plugin_dir_url(__FILE__) . 'script.min.js', array(), $plugin_version, true);
    wp_enqueue_style('blocks-scanner-style', plugin_dir_url(__FILE__) . 'styles.min.css', array(), $plugin_version);
});


add_action('admin_menu', function () {
    $hook = add_management_page(
        'Blocks Scanner',
        'Blocks Scanner',
        'manage_options',
        'blocks_scanner',
        'blocks_scanner_contents',
        5
    );
});

function blocks_scanner_contents() {
    $blocks = get_blocks_in_use();
    
    echo '<div class="wrap blocks_scanner">';
    echo '<h1>' . esc_html__('Blocks Scanner', 'blocks-scanner') . '</h1>';
    echo '<nav class="nav-tab-wrapper">';
    echo '<a href="#other-blocks" class="nav-tab">' . esc_html__('Block Plugins', 'blocks-scanner') . '</a>';
    echo '<a href="#core-blocks" class="nav-tab">' . esc_html__('Core Blocks', 'blocks-scanner') . '</a>';
    echo '</nav>';
    echo '<div id="other-blocks" class="tab-content">';
    generate_blocks_table($blocks, false);
    echo '</div>';
    echo '<div id="core-blocks" class="tab-content">';
    generate_blocks_table($blocks, true);
    echo '</div>';
}

function generate_blocks_table($blocks, $is_core) {
    $table_id = $is_core ? 1 : 2;
    
    echo '<div class="tablenav">';
    echo '<select id="block-dropdown">';
    echo '<option value="all">' . esc_html__('All Blocks', 'blocks-scanner') . '</option>';
    
    $total_block_count = 0;
    
    foreach ($blocks as $block => $count) {
        if (!empty($block) && (($is_core && strpos($block, 'core/') === 0) || (!$is_core && strpos($block, 'core/') !== 0))) {
            echo '<option value="' . esc_attr($block) . '">' . esc_html($block) . ' ('. esc_html($count) . ' ' . esc_html(_n('post', 'posts', $count, 'blocks-scanner')) . ')</option>';
        }
    }
    
    echo '</select>';
    echo '<div>';
    echo '<span class="displaying-num"><span class="row-count"></span> ' . esc_html__('rows', 'blocks-scanner') . '</span>';
    echo '<input type="text" id="dhn-filter' . '-' . esc_html($table_id) . '" class="dhn-filter" placeholder="' . esc_html__('Search Blocks', 'blocks-scanner') . '">';
    echo '</div>';
    echo '</div>';

    echo '<table id="dhn-list' . '-' . esc_html($table_id) . '" class="wp-list-table widefat striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th id="post_page_title" data-type="string">' . esc_html__('Post/Page Title', 'blocks-scanner') . '</th>';
    echo '<th id="block_name" data-type="string">' . esc_html__('Block Name', 'blocks-scanner') . '</th>';
    echo '<th id="block_usage" data-type="string">' . esc_html__('Block Usage', 'blocks-scanner') . '</th>';
    echo '<th id="post_type" data-type="string">' . esc_html__('Post Type', 'blocks-scanner') . '</th>';
    echo '<th id="publish_date" data-type="date">' . esc_html__('Updated Date', 'blocks-scanner') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($blocks as $block => $count) {
        if (!empty($block) && (($is_core && strpos($block, 'core/') === 0) || (!$is_core && strpos($block, 'core/') !== 0))) {
            $posts = get_posts_related_to_block($block);
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $post_type = get_post_type($post);
                    $block_count = count_block_occurrences(parse_blocks($post->post_content), $post->post_content, $block);
                    echo '<tr>';
                    echo '<td class="has-row-actions">';
                    echo '<strong><a href="' . esc_url(get_edit_post_link($post->ID)) . '">' . esc_html($post->post_title) . '</a></strong>';
                    echo '<div class="row-actions">';
                    echo '<span class="edit"><a href="' . esc_url(get_edit_post_link($post->ID)) . '">' . esc_html__('Edit', 'blocks-scanner') . '</a> | </span>';
                    echo '<span class="view"><a href="' . esc_url(get_permalink($post->ID)) . '">' . esc_html__('View', 'blocks-scanner') . '</a></span>';
                    echo '</div>';
                    echo '</td>';
                    echo '<td>' . esc_html($block) . '</td>';
                    echo '<td>' . esc_html($block_count) . ' ' . esc_html(_n('block', 'blocks', $block_count, 'blocks-scanner')) . '</td>';
                    
                    echo '<td>' . esc_html($post_type) . '</td>';
                    echo '<td>' . esc_html(get_the_modified_date('', $post->ID)) . '</td>';
                    echo '</tr>';
                }
            }
        }
    }
    
    echo '</tbody>';
    echo '</table>';
}

function count_block_occurrences($blocks, $content, $block_name) {
    $block_count = 0;
    foreach ($blocks as $content_block) {
        if ($content_block['blockName'] === $block_name) {
            $block_count++;
        }
        if (!empty($content_block['innerBlocks'])) {
            $block_count += count_block_occurrences($content_block['innerBlocks'], $content, $block_name);
        }
    }
    return $block_count;
}

function get_blocks_in_use() {
    $blocks = [];
    $processed_posts = [];
    $post_types = get_post_types(array('public' => true), 'objects');
    $post_type_slugs = array_keys($post_types);
    $args = array(
        'post_type'      => $post_type_slugs,
        'posts_per_page' => -1,
    );
    $posts = get_posts($args);
    foreach ($posts as $post) {
        if (!in_array($post->ID, $processed_posts)) {
            $content_blocks = parse_blocks($post->post_content);
            $unique_blocks = [];
            count_block_usage($content_blocks, $blocks, $unique_blocks);
            $processed_posts[] = $post->ID;
        }
    }
    return $blocks;
}

function count_block_usage($blocks, &$block_counts, &$unique_blocks) {
    foreach ($blocks as $block) {
        $block_name = $block['blockName'];
        if (!empty($block_name) && !in_array($block_name, $unique_blocks)) {
            if (!isset($block_counts[$block_name])) {
                $block_counts[$block_name] = 1;
            } else {
                $block_counts[$block_name]++;
            }
            $unique_blocks[] = $block_name; 
        }
        if (!empty($block['innerBlocks'])) {
            count_block_usage($block['innerBlocks'], $block_counts, $unique_blocks);
        }
    }
}


function get_posts_related_to_block($block) {
    $post_types = get_post_types(array('public' => true), 'objects');
    $post_type_slugs = array_keys($post_types);
    $args = array(
        'post_type'      => $post_type_slugs,
        'posts_per_page' => -1,
    );
    $query = new WP_Query($args);
    $related_posts = array();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $content = get_the_content();
            if (has_block($block, $content)) {
                $related_posts[] = get_post();
            }
        }
        wp_reset_postdata();
    }
    return $related_posts;
}
?>
