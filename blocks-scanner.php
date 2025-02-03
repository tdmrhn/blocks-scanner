<?php
/* 
* Plugin Name:       Blocks Scanner – Find and Manage Blocks Across Your Site
* Plugin URI:        https://github.com/tdmrhn/blocks-scanner
* Description:       Easily find and scan blocks used in posts and pages. Quickly access and edit these posts and pages to efficiently manage block usage.
* Version:           1.0
* Requires at least: 5.2 
* Requires PHP:      7.2 
* Author:            dmrhn
* Author URI:        https://dmrhn.com
* License:           GPL v2 or later 
* License URI:       https://www.gnu.org/licenses/gpl-2.0.html 
*/

if (!defined('ABSPATH')) exit;

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

add_action('admin_enqueue_scripts', function () {
    global $pagenow;    
    if ($pagenow === 'tools.php' && isset($_GET['page']) && $_GET['page'] === 'blocks_scanner') {
        $nonce = wp_create_nonce('blocks_scanner_nonce');
        $url = admin_url('tools.php?page=blocks_scanner&blocks_scanner_wpnonce=' . $nonce);
        if (!isset($_POST['blocks_scanner_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blocks_scanner_nonce'])), 'blocks_scanner_nonce')) {
            $plugin_data = get_plugin_data(__FILE__);
            $plugin_version = $plugin_data['Version'];
            wp_enqueue_script('blocks-scanner-script', plugin_dir_url(__FILE__) . 'build/script.min.js', array(), $plugin_version, true);
            wp_enqueue_style('blocks-scanner-style', plugin_dir_url(__FILE__) . 'build/styles.min.css', array(), $plugin_version);
        } else {
            wp_redirect($url);
            exit;
        }
    }
});

function blocks_scanner_contents() {
    $data = blocks_scanner_posts_blocks();
    $blocks = $data['blocks'];
    $related_posts = $data['related_posts'];
    
    // Get unique post types and titles
    $post_types = array();
    $post_titles = array();
    foreach ($related_posts as $block_posts) {
        foreach ($block_posts as $post) {
            $post_type = get_post_type($post);
            $post_types[$post_type] = isset($post_types[$post_type]) ? $post_types[$post_type] + 1 : 1;
            $post_titles[$post->ID] = array(
                'title' => $post->post_title,
                'count' => isset($post_titles[$post->ID]) ? $post_titles[$post->ID]['count'] + 1 : 1
            );
        }
    }
    
    echo '<div class="wrap blocks_scanner">';
    echo '<div class="content-wrap">';
    echo '<div>';
    echo '<h1>' . esc_html__('Blocks Scanner', 'blocks-scanner') . '</h1>';
    // Filters section
    echo '<div class="content-nav">';
    
    // Block Categories filter
    echo '<div class="dhn-group">';
        echo '<div class="head">';
	echo '<span>' . esc_html__('Block Plugin', 'blocks-scanner') . '</span>';
	echo '<span>' . esc_html__('Blocks', 'blocks-scanner') . '</span>';
        echo '</div>';
        echo '<ul>';
    $processed_categories = array();
    foreach ($blocks as $block => $count) {
        $block_category = substr($block, 0, strpos($block, '/'));
        $block_parts = explode('-', str_replace('_', '-', $block_category));
        $block_name = reset($block_parts);
        if ($block_name === "wp") {
            $block_name = $block_name . "-" . $block_parts[1];
        }
        if (!in_array($block_name, $processed_categories)) {
            $block_name_show = str_replace('-', ' ', $block_name);
            $block_name_show = ucwords(strtolower($block_name_show));
            echo '<li class="filter-item">';			
            echo '<input type="checkbox" id="category-' . esc_attr($block_name) . '" class="category-filter" value="' . esc_attr($block_name) . '">';
            echo '<label for="category-' . esc_attr($block_name) . '"><span>' . esc_html($block_name_show) . '</span> <span class="count">0</span></label>';
            echo '</li>';
            $processed_categories[] = $block_name;
        }
    }
    echo '</ul>';
    echo '</div>';
	
	
    // Block Names filter
    echo '<div class="dhn-group">';
	
        echo '<div class="head">';
	echo '<span>' . esc_html__('Block Name', 'blocks-scanner') . '</span>';
	echo '<span>' . esc_html__('Posts', 'blocks-scanner') . '</span>';
        echo '</div>';
    echo '<input type="text" class="block-name-search" placeholder="' . esc_html__('Search blocks...', 'blocks-scanner') . '">';
        echo '<ul class="block-names-list">';
    foreach ($blocks as $block => $count) {
        echo '<li class="filter-item">';
        echo '<input type="checkbox" id="block-' . esc_attr($block) . '" class="block-filter" value="' . esc_attr($block) . '">';
        echo '<label for="block-' . esc_attr($block) . '"><span>' . esc_html($block) . '</span> <span class="count">0</span></label>';
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
    
    // Post Types filter
    echo '<div class="dhn-group">';
        echo '<div class="head">';
	echo '<span>' . esc_html__('Post Type', 'blocks-scanner') . '</span>';
	echo '<span>' . esc_html__('Blocks', 'blocks-scanner') . '</span>';
        echo '</div>';
        echo '<ul>';
    foreach ($post_types as $type => $count) {
        echo '<li class="filter-item">';
        echo '<input type="checkbox" id="post-type-' . esc_attr($type) . '" class="postType-filter" value="' . esc_attr($type) . '">';
        echo '<label for="post-type-' . esc_attr($type) . '"><span>' . esc_html($type) . '</span> <span class="count">0</span></label>';
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
    
    // Post/Page Titles filter
    echo '<div class="dhn-group">';
	
        echo '<div class="head">';
	echo '<span>' . esc_html__('Post/Page Title', 'blocks-scanner') . '</span>';
	echo '<span>' . esc_html__('Blocks', 'blocks-scanner') . '</span>';
        echo '</div>';
    echo '<input type="text" class="title-search" placeholder="' . esc_html__('Search titles...', 'blocks-scanner') . '">';
        echo '<ul class="titles-list">';
    foreach ($post_titles as $post_id => $data) {
        echo '<li class="filter-item">';
        echo '<input type="checkbox" id="title-' . esc_attr($post_id) . '" class="title-filter" value="' . esc_attr($post_id) . '">';
        echo '<label for="title-' . esc_attr($post_id) . '"><span>' . esc_html($data['title']) . '</span> <span class="count">0</span></label>';
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
	
    
    echo '</div>';
    echo '</div>'; // End filters-section
    
    // Table section
    echo '<div class="content-table">';
    echo '<div class="content-table_top">';
    echo '<div><span class="row-count">0</span> ' . esc_html__('rows', 'blocks-scanner') . '</div>';
    echo '<input type="text" class="table-search" placeholder="' . esc_html__('Search Table', 'blocks-scanner') . '">';
    echo '</div>';
    
    echo '<div class="content-table_wrap">';
    echo '<table class="wp-list-table widefat striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th id="post_id" data-type="number">' . esc_html__('ID', 'blocks-scanner') . '</th>';
    echo '<th id="post_page_title" data-type="string">' . esc_html__('Post/Page Title', 'blocks-scanner') . '</th>';
    echo '<th id="block_name" data-type="string">' . esc_html__('Block Name', 'blocks-scanner') . '</th>';
    echo '<th id="block_usage" data-type="string">' . esc_html__('Block Usage', 'blocks-scanner') . '</th>';
    echo '<th id="post_type" data-type="string">' . esc_html__('Post Type', 'blocks-scanner') . '</th>';
    echo '<th id="publish_date" data-type="date">' . esc_html__('Updated Date', 'blocks-scanner') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    // Output all rows initially (they will be filtered by JavaScript)
    foreach ($blocks as $block => $count) {
        $posts = isset($related_posts[$block]) ? $related_posts[$block] : array();
        if (!empty($posts)) {
            foreach ($posts as $post) {
                $post_type = get_post_type($post);
                $block_count = blocks_scanner_count_blocks(parse_blocks($post->post_content), $block);
                $block_category = substr($block, 0, strpos($block, '/'));
                $block_parts = explode('-', str_replace('_', '-', $block_category));
                $category = reset($block_parts);
                if ($category === "wp") {
                    $category = $category . "-" . $block_parts[1];
                }
                
                echo '<tr data-block="' . esc_attr($block) . '" data-category="' . esc_attr($category) . '" data-post-type="' . esc_attr($post_type) . '">';
                echo '<td>' . esc_html($post->ID) . '</td>';
                echo '<td class="has-row-actions">';
                echo '<strong><a href="' . esc_url(get_edit_post_link($post->ID)) . '">' . esc_html($post->post_title) . '</a></strong>';
                echo '<div class="row-actions">';
                echo '<span class="edit"><a href="' . esc_url(get_edit_post_link($post->ID)) . '">' . esc_html__('Edit', 'blocks-scanner') . '</a> | </span>';
                echo '<span class="view"><a href="' . esc_url(get_permalink($post->ID)) . '">' . esc_html__('View', 'blocks-scanner') . '</a></span>';
                echo '</div>';
                echo '</td>';
                echo '<td>' . esc_html($block) . '</td>';
                echo '<td>' . esc_html($block_count) . ' ' . esc_html(_n('block', 'blocks', $block_count, 'blocks-scanner')) . '</td>';
                echo '<td>' . esc_html($post_type === 'wp_block' ? 'pattern' : $post_type) . '</td>';
                echo '<td>' . esc_html(get_the_modified_date('', $post->ID)) . '</td>';
                echo '</tr>';
            }
        }
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // End content-wrap
    echo '</div>'; // End wrap
}

// Keep existing helper functions
function blocks_scanner_count_blocks($blocks, $block_name) {
    $block_count = 0;
    foreach ($blocks as $content_block) {
        if ($content_block['blockName'] === $block_name) {
            $block_count++;
        }
        if (!empty($content_block['innerBlocks'])) {
            $block_count += blocks_scanner_count_blocks($content_block['innerBlocks'], $block_name);
        }
    }
    return $block_count;
}

function blocks_scanner_posts_blocks() {
    $blocks = [];
    $related_posts = [];
    $processed_posts = [];

    $post_types = get_post_types([], 'objects');
    $post_type_slugs = array_keys($post_types);
	$excluded_post_types = ['wp_navigation', 'attachment', 'nav_menu_item', 'revision', 'custom_css', 'customize_changeset', 'user_request', 'oembed_cache'];
	$post_type_slugs = array_diff($post_type_slugs, $excluded_post_types);


    $args = array(
        'post_type'      => $post_type_slugs,
        'posts_per_page' => -1,
    );
    $posts = get_posts($args);

    $count_blocks = function ($blocks, &$block_counts, &$unique_blocks) use (&$count_blocks) {
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
                $count_blocks($block['innerBlocks'], $block_counts, $unique_blocks);
            }
        }
    };

    foreach ($posts as $post) {
        $content_blocks = parse_blocks($post->post_content);
        $unique_blocks = [];
        $count_blocks($content_blocks, $blocks, $unique_blocks);
        
        foreach ($unique_blocks as $block_name) {
            if (!isset($related_posts[$block_name])) {
                $related_posts[$block_name] = [];
            }
            $related_posts[$block_name][] = $post;
        }
        
        $processed_posts[] = $post->ID;
    }

    return array('blocks' => $blocks, 'related_posts' => $related_posts);
}
?>