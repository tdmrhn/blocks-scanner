<?php
/*
Plugin Name: Blocks Scanner
Plugin URI: https://github.com/tdmrhn/blocks-scanner
Description: Easily scan and list the Gutenberg blocks used on your site. Quickly edit or view the posts that use the blocks.
Author: dmrhn
Author URI: https://dmrhn.com
Version: 0.1
*/

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('blocks-scanner-script', plugin_dir_url(__FILE__) . 'script.min.js', array(), null, true);
    wp_enqueue_style('blocks-scanner-style', plugin_dir_url(__FILE__) . 'styles.min.css');
});

add_action('admin_menu', function () {
    add_management_page(
        'Blocks Scanner',
        'Blocks Scanner',
        'manage_options',
        'blocks_scanner',
        'blocks_scanner_contents',
        'dashicons-schedule',
        1
    );
});

function blocks_scanner_contents() {
    $blocks = get_blocks_in_use();
    
    echo '<div class="wrap">';
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
            echo '<option value="' . esc_attr($block) . '">' . esc_html($block) . ' ('. $count .')</option>';
        }
    }
    
    echo '</select>';
    echo '<div>';
    echo '<span class="displaying-num"><span class="row-count"></span> ' . esc_html__('rows', 'blocks-scanner') . '</span>';
    echo '<input type="text" id="dhn-filter' . '-' . $table_id . '" class="dhn-filter" placeholder="' . esc_html__('Search Blocks', 'blocks-scanner') . '">';
    echo '</div>';
    echo '</div>';

    echo '<table id="dhn-list' . '-' . $table_id . '" class="wp-list-table widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>' . esc_html__('Post/Page Title', 'blocks-scanner') . '</th>';
    echo '<th>' . esc_html__('Block Name', 'blocks-scanner') . '</th>';
    echo '<th>' . esc_html__('Block Usage', 'blocks-scanner') . '</th>';
    echo '<th>' . esc_html__('Post Type', 'blocks-scanner') . '</th>';
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
                    echo '<strong><a href="' . get_edit_post_link($post->ID) . '">' . $post->post_title . '</a></strong>';
                    echo '<div class="row-actions">';
                    echo '<span class="edit"><a href="' . get_edit_post_link($post->ID) . '">' . esc_html__('Edit', 'blocks-scanner') . '</a> | </span>';
                    echo '<span class="view"><a href="' . get_permalink($post->ID) . '">' . esc_html__('View', 'blocks-scanner') . '</a></span>';
                    echo '</div>';
                    echo '</td>';
                    echo '<td>' . $block . '</td>';
                    echo '<td>' . $block_count . ' ' . ($block_count > 1 ? 'blocks' : 'block') . '</td>';
                    
                    echo '<td>' . $post_type . '</td>';
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
    $post_types = get_post_types(array('public' => true), 'objects');
    $post_type_slugs = array_keys($post_types);
    $args = array(
        'post_type'      => $post_type_slugs,
        'posts_per_page' => -1,
    );
    $posts = get_posts($args);
    foreach ($posts as $post) {
        $content_blocks = parse_blocks($post->post_content);
        foreach ($content_blocks as $content_block) {
            count_block_usage($content_block, $blocks);
        }
    }
    return $blocks;
}

function count_block_usage($block, &$blocks) {
    $block_name = $block['blockName'];
    if (!empty($block_name)) {
        if (!isset($blocks[$block_name])) {
            $blocks[$block_name] = 1;
        } else {
            $blocks[$block_name]++;
        }
    }
    if (!empty($block['innerBlocks'])) {
        foreach ($block['innerBlocks'] as $inner_block) {
            count_block_usage($inner_block, $blocks);
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