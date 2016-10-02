<?php
/**
 * Plugin Name: DustPress Commments
 * Plugin URI: https://github.com/devgeniem/dustpress-comments
 * Description: Comments Helper for DustPress - A WordPress plugin that adds a DustPress helper enabling ajaxified commenting.
 * Version: 1.1.2
 * Author: Geniem Oy / Ville Siltala
 * Author URI: http://www.geniem.com
 * Text Domain: dustpress-comments
 */

namespace DustPress;

use get_file_data;

add_action( 'after_setup_theme', __NAMESPACE__ . '\init_comments_helper' );

/**
 * Init the helper.
 */
function init_comments_helper() {
    // Require the class file
    require_once( dirname( __FILE__ ) . '/classes/comments.php' );

    // Get the current plugin version
    $plugin_version = get_file_data( __FILE__, [ 'Version' ], 'plugin' );

    // Instantiate the class
    $comments = new Comments( $plugin_version );

    // Add into the helpers array
    dustpress()->add_helper( 'comments', $comments );

    // Add templates into DustPress
    add_filter( 'dustpress/partials', __NAMESPACE__ . '\add_comments_templates' );

    /**
     * Hooks for comment posting and paginating
     */
    $ajaxing = filter_input( INPUT_POST, 'dustpress_comments_ajax', FILTER_SANITIZE_NUMBER_INT );
    if ( $ajaxing ) {

        /**
         * Notify others
         */
        if ( ! defined( 'DUSTPRESS_AJAX' ) ) {
            define( 'DUSTPRESS_AJAX', true );
        }

        /**
         * Add a hook for handling comment posting
         */
        add_action( 'comment_post', [ $comments, 'comment_posted' ], 2 );

        /**
         * Handle WP comment errors
         */
        add_filter( 'wp_die_handler', [ $comments, 'get_error_handler' ] );
        add_filter( 'wp_die_ajax_handler', [ $comments, 'get_error_handler' ] );

        /**
         * Run pagination
         */
        $paginate = filter_input( INPUT_POST, 'dustpress_comments_paginate', FILTER_SANITIZE_NUMBER_INT );
        if ( $paginate ) {
            add_action( 'wp_ajax_dustpress_comments_paginate', [ $comments, 'paginate' ] );
            add_action( 'wp_ajax_nopriv_dustpress_comments_paginate', [ $comments, 'paginate' ] );
        }
    }

    /**
     * Handle pagination
     */
    $paginating = filter_input( INPUT_POST, 'dustpress_comments_paginate', FILTER_SANITIZE_NUMBER_INT );
    if ( $paginating ) {
        $page   = filter_input( INPUT_POST, 'page', FILTER_SANITIZE_NUMBER_INT );
        $offset = filter_input( INPUT_POST, 'offset', FILTER_SANITIZE_NUMBER_INT );
        $comments->paginate();
    }
}

/**
 * Add plugin template directory
 */
function add_comments_templates( $templatepaths ) {
    array_push( $templatepaths, dirname( __FILE__ ) . '/partials' );
    return $templatepaths;
}

/**
 * Plugin text-domain
 * @return no return
 */
add_action( 'init', __NAMESPACE__ . '\comments_textdomain' );

function comments_textdomain() {
    load_plugin_textdomain( 'dustpress-comments', false, 'dustpress-comments/languages' );
}