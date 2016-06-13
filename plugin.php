<?php
/**
 * Plugin Name: DustPress Commments
 * Plugin URI: https://github.com/devgeniem/dustpress-comments
 * Description: Comments Helper for DustPress - A WordPress plugin that adds a DustPress helper enabling ajaxified commenting.
 * Version: 1.0.1
 * Author: Geniem Oy / Ville Siltala
 * Author URI: http://www.geniem.com
 */

namespace DustPress;

add_action( 'after_setup_theme', __NAMESPACE__ . '\init_comments_helper' );

/**
 * Init the helper.
 */
function init_comments_helper() {
    // Require the class file
    require_once( dirname( __FILE__ ) . '/class.php' );

    // Instantiate the class
    $comments = new Comments();

    // Add into the helpers array
    dustpress()->add_helper( 'comments', $comments );

    // Add templates into DustPress
    add_filter( 'dustpress/partials', __NAMESPACE__ . '\add_comments_templates' );

    /**
     * Hooks for comment posting
     */
    if ( isset( $_POST['dustpress_comments_ajax'] ) ) {

        /**
         * Add a hook for handling comment posting
         */
        add_action( 'comment_post', [ $comments, 'handle_ajax' ], 2 );

        /**
         * Handle WP comment errors
         */
        add_filter( 'wp_die_handler', [ $comments, 'get_error_handler' ] );
        add_filter( 'wp_die_ajax_handler', [ $comments, 'get_error_handler' ] );

    }
}

/**
 * Add plugin template directory
 */
function add_comments_templates( $templatepaths ) {
    array_push( $templatepaths, dirname( __FILE__ ) . '/partials' );
    return $templatepaths;
}
