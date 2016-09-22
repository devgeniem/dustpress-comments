<?php
/**
 * Comments
 */

namespace DustPress;

/**
 * Comments Helper class
 */
class Comments extends \DustPress\Helper {

    private $ajaxify;
    private $comment_post_id;
    private $data;
    private $input_class;
    private $input_attrs;
    private $replacements;
    private $remove;
    private $status_div;
    private $echo_form;
    private $load_comments;
    private $pagination;
    private $paginate;
    private $items;
    private $per_page;
    private $page_label;
    private $page;
    private $reply;
    private $comment_class;
    private $form_args;
    private $comments_args;
    private $reply_args;
    private $avatar_args;
    private $section_title;
    private $section_id;
    private $threaded;
    private $comments;
    private $form;
    private $output;
    private $version;

    /**
     * The constructor.
     *
     * @param string $version The current plugin version.
     */
    public function __construct( $version ) {
        $this->version = $version;
    }

    /**
     * Fired by DustPress core before the helper is rendered.
     */
    public function prerun() {
        $js_args = [
            'paginationUrl' => apply_filters( 'dustpress/comments/paginationUrl', home_url() ),
            'replyLabel'    => apply_filters( 'dustpress/comments/reply_label', __( 'Reply to comment', 'dustpress-comments' ) ),
            'formDataError' => apply_filters( 'dustpress/comments/formDataError', __( 'Your browser does not support this type of form handling.', 'dustpress-comments' ) ),
        ];

        // Styles
        wp_enqueue_style( 'dustpress-comments-styles', plugins_url( 'dist/dustpress-comments.css', dirname( __FILE__ ) ), false, $this->version, all );

        // JS
        wp_register_script( 'dustpress-comments', plugins_url( 'dist/dustpress-comments.js', dirname( __FILE__ ) ), [ 'jquery' ], $this->version, true );
        wp_localize_script( 'dustpress-comments', 'CommentsData', $js_args );
        wp_enqueue_script( 'dustpress-comments' );
    }

    /**
     * Outputs the contents of the comments.
     *
     * @return string
     */
    public function output() {
        // If not ajaxing, get params
        if ( ! defined( 'DUSTPRESS_AJAX' ) && true !== DUSTPRESS_AJAX ) {
            $this->handle_params();
        }

        // Trying to paginate the wp way
        if ( $this->wp_pagination() ) {
            return;
        }

        // Closing args
        $this->close_old = $this->comments_args['close_comments_for_old_posts'] ? $this->comments_args['close_comments_for_old_posts'] : get_option( 'close_comments_for_old_posts', false );

        // Maybe close comments
        if ( $this->close_old ) {
            $this->closed_after = $this->comments_args['close_comments_days_old'] ? $this->comments_args['close_comments_days_old'] : get_option( 'close_comments_days_old', 14 );

            $closing_time = strtotime( get_the_date( 'c', $this->comment_post_id ) ) + $this->closed_after * 86400;

            if ( $closing_time < time() ) {
                $this->echo_form = false;
            }
        }

        // Form loading and modification arguments
        if ( $this->echo_form ) {
            $this->replacements         = $this->form_args['replace_input'];
            $this->remove               = $this->form_args['remove_input'];
            $this->status_div           = $this->form_args['status_div'];
            $this->status_id            = $this->form_args['status_id'];
            $this->input_class          = $this->form_args['input_class'];
            $this->input_attrs          = $this->form_args['input_attrs'];
        }

        // Default args
        $reply_defaults = [
            'depth'     => 1,
            'max_depth' => get_option( 'thread_comments_depth' ),
        ];
        $this->reply_args = array_merge( $reply_defaults, (array) $this->reply_args );
        $comments_defaults = [
            'status' => current_user_can( 'moderate_comments' ) ? 'all' : 'approve',
        ];
        $this->comments_args = array_merge( $comments_defaults, (array) $this->comments_args );

        if ( $this->echo_form ) {
            $this->get_form();
        }

        // Get comments
        if ( $this->load_comments ) {
            $this->get_comments();
        }

        // Add additional data for comments
        if ( is_array( $this->comments ) ) {
            $this->extend_comments();
        }

        // Map data
        $rendering_data                 = (object) [];
        $rendering_data->ID             = apply_filters( 'dustpress/comments/comment_post_id', $this->comment_post_id );
        $rendering_data->title          = apply_filters( 'dustpress/comments/section_title', $this->section_title );
        $rendering_data->message        = apply_filters( 'dustpress/comments/message', $this->params->message );
        $rendering_data->form           = apply_filters( 'dustpress/comments/form', $this->form );
        $rendering_data->comments       = apply_filters( 'dustpress/comments/comments', $this->comments );
        $rendering_data->after_comments = apply_filters( 'dustpress/comments/after_comments', $this->after_comments );
        $rendering_data->model          = $this->params->model;
        $rendering_data->filter_slug    = $this->params->filter_slug;

        // Set the partial name and possibly override it with a filter
        $partial    = apply_filters( 'dustpress/comments/partial', 'comments' );
        $c_data     = apply_filters( 'dustpress/comments/data', $c_data );

        // Render output
        return dustpress()->render( [
            'partial'   => $partial,
            'data'      => $rendering_data,
            'type'      => 'html',
            'echo'      => false,
        ]);
    }

    private function handle_params() {
        global $post;

        // Set comment post id
        $this->comment_post_id = isset( $this->params->comment_post_id ) ? $this->params->comment_post_id : $post->ID;

        // Load arguments.
        $this->get_helper_params();

        $this->section_title    = $this->comments_args['section_title'];
        $this->comment_class    = $this->comments_args['comment_class'];
        $this->avatar_args      = $this->comments_args['avatar_args'];
        $this->section_id       = $this->comments_args['section_id']        ? $this->comments_args['section_id']    : 'comments__section';
        $this->echo_form        = $this->form_args['echo_form']             ? $this->form_args['echo_form']         : true;

        // Get_comment_reply_link functions arguments
        $this->reply_args       = $this->comments_args['reply_args'];

        $this->load_comments    = isset( $this->comments_args['load_comments'] )     ? $this->comments_args['load_comments']     : true;
        $this->after_comments   = isset( $this->comments_args['after_comments'] )    ? $this->comments_args['after_comments']    : null;
        $this->threaded         = isset( $this->comments_args['threaded'] )          ? $this->comments_args['threaded']          : get_option('thread_comments');
        $this->paginate         = isset( $this->comments_args['page_comments'] )     ? $this->comments_args['page_comments']     : get_option( 'page_comments', true );
        $this->per_page         = isset( $this->comments_args['comments_per_page'] ) ? $this->comments_args['comments_per_page'] : get_option( 'comments_per_page', 20 );
        $this->page_label       = isset( $this->comments_args['page_label'] )        ? $this->comments_args['page_label']        : __( 'comment-page', 'dusptress-comments' );
        $this->reply            = null !== $this->comments_args['reply']             ? $this->comments_args['reply']             : true;
    }

    /**
     * Filter input values and get helper parameters based on the data.
     */
    private function handle_ajax_params() {
        // Handle input values
        $model                  = filter_input( INPUT_POST, 'dustpress_comments_model', FILTER_SANITIZE_STRING );
        $filter_slug            = filter_input( INPUT_POST, 'dustpress_comments_filter_slug', FILTER_SANITIZE_STRING );

        // A model is defined
        if ( $model ) {
            $this->params->model = $model;
        }

        // A filter slug is defined
        if ( $filter_slug ) {
            $this->params->filter_slug = $filter_slug;
        }

        // Get params
        $this->handle_params();
    }

    private function get_helper_params() {

        // The helper was defined with arguments.
        if ( isset( $this->params->comments_args ) && isset( $this->params->form_args ) ) {
            $this->comments_args    = $this->params->comments_args;
            $this->form_args        = $this->params->form_args;
            // Arguments passed via helper params -> do not ajaxify
            $this->ajaxify = 0;
            return;
        }

        // If the arguments are loaded from a model or filter hooks, the comments are ajaxified by default.
        $this->ajaxify = apply_filters( 'dustpress/comments/ajaxify', 1 );

        // Fetch arguments from a model
        if ( isset( $this->params->model ) ) {

            if ( class_exists( $this->params->model ) ) {
                $comments_model = $this->params->model;
                $comments_model = new $comments_model();

                if ( method_exists( $comments_model, 'get_comments_args' ) ) {
                    $this->comments_args = $comments_model->get_comments_args();
                } else {
                    die( __( 'DustPress-Comments: The \'get_comments_args\' function is not defined in the model.', 'dustpress-comments' ) );
                }

                if ( method_exists( $comments_model, 'get_form_args' ) ) {
                    $this->form_args = $comments_model->get_form_args();
                } else {
                    die( __( 'DustPress-Comments: The \'get_form_args\' function is not defined in the model.', 'dustpress-comments' ) );
                }

            } else {
                die( __( 'DustPress-Comments: The model class {' . $this->params->model . '} does not exist.', 'dustpress-comments' ) );
            }
        }

        // Fetch arguments from filter functions
        if ( isset( $this->params->filter_slug ) ) {
            $this->comments_args    = [];
            $this->form_args        = [];

            // First filter the global args and then filter with the given slug
            $this->comments_args    = apply_filters( 'dustpress/comments/get_comments_args', $this->comments_args, $this->comment_post_id );
            $this->comments_args    = apply_filters( 'dustpress/comments/' . $this->params->filter_slug . '/get_comments_args', $this->comments_args, $this->comment_post_id );
            $this->form_args        = apply_filters( 'dustpress/comments/get_form_args', $this->form_args, $this->comment_post_id );
            $this->form_args        = apply_filters( 'dustpress/comments/' . $this->params->filter_slug . '/get_form_args', $this->form_args, $this->comment_post_id  );

            if ( ! is_array( $this->comments_args ) ) {
                die( __( 'DustPress-Comments: The \'get_comments_args\' filter return value is not an array.', 'dustpress-comments' ) );
            }

            if ( ! is_array( $this->comments_args ) ) {
                die( __( 'DustPress-Comments: The \'get_form_args\' filter return value is not an array.', 'dustpress-comments' ) );
            }
        }
    }

    /**
     * Fired after a comment is succesfully saved in wp-comments-post.php
     *
     * @param  integer $comment_id Id of the new comment.
     * @return json
     */
    public function comment_posted( $comment_id ) {

        if ( empty( $this->params ) ) {
            $this->params = (object) [];
        }

        $comment = get_comment( $comment_id );

        // The post we are commenting
        $this->params->comment_post_id = filter_input( INPUT_POST, 'comment_post_ID', FILTER_SANITIZE_NUMBER_INT );

        // On ajax calls get params from a model or a filter function.
        $this->handle_ajax_params();

        if ( $comment->comment_approved ) {
            $this->params->message = [ 'success' => __( 'Comment sent.', 'dusptress-comments' ) ];
        } else {
            $this->params->message = [ 'warning' => __( 'Comment is waiting for approval.', 'dusptress-comments' ) ];
        }

        $output = $this->output();

        $return = [
            'success'   => true,
            'html'      => $output,
        ];

        wp_send_json( $return );
    }

    /**
     * Get the form html.
     */
    private function get_form() {
        // Add input classes
        if ( $this->input_class
            || isset( $this->form_args['replace_input'] )
            || isset( $this->form_args['remove_input'] ) ) {
            add_filter( 'comment_form_default_fields', array( $this, 'modify_fields' ) );
            add_filter( 'comment_form_field_comment', array( $this, 'modify_comment_field' ) );
        }

        // Insert status div
        add_filter( 'comment_form_top', array( $this, 'form_status_div' ) );

        // Insert hidden field to identify dustpress helper
        add_filter( 'comment_id_fields', array( $this, 'insert_identifier' ), 1 );

        // Compile form and store it
        ob_start();
        comment_form( $this->form_args, $this->comment_post_id );
        $this->form = ob_get_clean();
    }

    /**
     * Loads the comments.
     *
     * @return array
     */
    private function get_comments() {
        $this->comments_args['comment_post_id'] = $this->comment_post_id;

        $get_all = $this->get_int( __( 'all-comments', 'dusptress-comments' ) );

        // Maybe paginate
        if ( 1 !== $get_all && $this->paginate ) {
            $args       = array_merge( $this->comments_args, [ 'count' => true ] );
            $this->page = $this->page ? $this->page : 1;

            $this->comments_args['parent'] = 0;
            $this->comments_args['offset'] = $this->page == 1 ? 0 : ( $this->page - 1 ) * $this->per_page;
            $this->comments_args['number'] = $this->per_page;

            // This is a nice undocumented feature of WP
            $this->comments_args['hierarchical'] = true;

            // Get the comments
            $this->comments         = get_comments( $this->comments_args );
            // Count top level comments for pagination
            $this->items            = $this->count_top_level_comments( $this->comments_args['comment_post_id'] );
            // Add the pagination HTML after comments
            $this->after_comments   = $this->pagination();

            // No need to proceed
            return;
        }
        // Load all comments
        $this->comments = get_comments( $this->comments_args );
    }

    /**
     * Outputs a pagination for comments using the DustPress Pagination helper
     *
     * @return string
     */
    private function pagination() {
        $params = (object)[];

        $params->page_label = $this->page_label;
        $params->page       = $this->page;
        $params->per_page   = $this->per_page;
        $params->items      = $this->items;
        $params->hash       = $this->section_id;

        $this->pagination = new \DustPress\Pagination();
        $this->pagination->set_params( $params );

        return $this->pagination->output();
    }

    public function paginate() {

        // Init params.
        if ( empty( $this->params ) ) {
            $this->params = (object) [];
        }

        // The requested page.
        $this->page = filter_input( INPUT_POST, 'dustpress_comments_page', FILTER_SANITIZE_NUMBER_INT );

        // The post to load comments from
        $this->params->comment_post_id = filter_input( INPUT_POST, 'comment_post_ID', FILTER_SANITIZE_NUMBER_INT );

        // Get params.
        $this->handle_ajax_params();

        $output = $this->output();

        $return = [
            'success'   => true,
            'html'      => $output,
        ];

        wp_send_json( $return );
    }

    private function extend_comments() {
        foreach ( $this->comments as &$comment ) {
            $cid = $comment->comment_ID;

            // Get author link
            $comment->author_link = get_comment_author_link( $cid );

            // Set comment classes
            $classes = $this->comment_has_parent( $comment ) ? 'reply ' . $this->comment_class : $this->comment_class;

            switch ( $comment->comment_approved ) {
            case 1:
                $classes .= ' comment-approved';
                break;
            case 0:
                $classes .= ' comment-hold';
                break;
            case 'spam':
                $classes .= ' comment-spam';
                break;
            }

            $comment->comment_class = comment_class( $classes, $cid, $post_id, false );

            // Load a reply link
            if ( $this->reply ) {
                $comment->reply_link    = \get_comment_reply_link( $this->reply_args, $cid );
                $comment->reply         = true;
            }

            // Set an avatar
            if ( is_array( $this->avatar_args ) ) {
                extract( $this->avatar_args );
                $comment->avatar = get_avatar( $id_or_email, $size, $default, $alt );
            }

            // Load a custom profile picture
            $pic = apply_filters( 'dustpress/comments/profile_picture', $comment );
            if ( is_string( $pic ) ) {
                $comment->profile_pic = $pic;
            }

            // Filter comment
            $comment = apply_filters( 'dustpress/comments/comment', $comment );
        }
        // Sort replies
        if ( $this->threaded ) {
            $this->comments = $this->threadify_comments( $this->comments );
        }
    }

    public function modify_fields( $fields ) {
        $input_class    = $this->input_class;
        $input_attrs    = $this->input_attrs;
        $replacements   = $this->replacements;
        $remove         = $this->remove;

        foreach ( $fields as $key => &$field ) {

            if ( isset( $replacements[$key] ) ) {
                $field = $replacements[$key];
            }
            elseif ( array_search( $key, $remove ) !== false ) {
                unset( $fields[$key] );
            }
            elseif ( $input_class ) {
                $field = preg_replace( '/<input/', '<input class="' . $input_class . '"' . $input_attrs, $field );
            }
            elseif ( $input_attrs ) {
                $field = preg_replace( '/<input/', '<input ' . $input_attrs, $field );
            }

        }

        return $fields;
    }

    public function modify_comment_field( $textarea ) {
        $input_class    = $this->input_class;
        $input_attrs    = $this->input_attrs;
        $replacements   = $this->replacements;
        $remove         = $this->remove;

        if ( isset( $replacements['comment'] ) ) {
            return $replacements['comment'];
        }
        elseif ( array_search( 'comment', $remove ) !== false ) {
            return '';
        }
        elseif ( $input_class ) {
            return preg_replace( '/<textarea/', '<textarea class="' . $input_class . '"', $textarea );
        }
        elseif ( $input_attrs ) {
            return preg_replace( '/<textarea/', '<textarea ' . $input_attrs, $textarea );
        }

        return $textarea;
    }

    /**
     * Echoes the container displaying loading status
     */
    public function form_status_div() {
        if ( $this->status_div ) {
            echo $this->status_div;
        }
        else {
            if ( $this->loader ) {
                echo $this->loader;
            }
            else {
                echo '<div class="dustpres-comments__loader"><span>' . __( 'Processing comments...', 'dusptress-comments' ) . '<span></div>';
            }
        }
    }

    public function insert_identifier( $id_elements ) {

        $id_elements .= "<input type=\"hidden\" name=\"dustpress_comments_ajax\" id=\"dustpress_comments_ajax\" value=\"1\" />\n";

        // A model is set
        if ( isset( $this->params->model ) ) {
            $model = $this->params->model;
            $id_elements .= "<input type=\"hidden\" name=\"dustpress_comments_model\" id=\"dustpress_comments_model\" value=\"$model\" />\n";
        }

        // A filter slug is set
        if ( isset( $this->params->filter_slug ) ) {
            $slug = $this->params->filter_slug;
            $id_elements .= "<input type=\"hidden\" name=\"dustpress_comments_filter_slug\" id=\"dustpress_comments_filter_slug\" value=\"$slug\" />\n";
        }

        return $id_elements;
    }

    public function threadify_comments( $comments, $parent = 0 ) {
        $threaded = array();

        foreach ( $comments as $key => $c ) {
            if ( $c->comment_parent == $parent ) {
                $c->replies = $this->threadify_comments( $comments, $c->comment_ID );
                $threaded[] = $c;
                unset( $comments[$key] );
            }
        }

        return $threaded;
    }

    public function comment_has_parent( $c ) {
        $parent = (int) $c->comment_parent;
        if ( $parent > 0 ) {
            return true;
        }
        else {
            return false;
        }
    }

    public function get_error_handler() {
        return [ $this, 'handle_error' ];
    }

    public function handle_error( $message, $title, $args ) {
        $return = [
            'error'     => true,
            'title'     => $title,
            'message'   => $message
        ];

        die( json_encode( $return ) );
    }

    private function get_int( $param ) {
        return (int) $_GET[$param];
    }

    private function wp_pagination() {
        $pattern = '/\/comment-page-(\d+)\//';
        $uri = $_SERVER['REQUEST_URI'];
        if ( preg_match( $pattern, $uri, $matches ) ) {
            $uri        = preg_replace( $pattern, '', $uri );
            wp_redirect( $uri . '?' . __( 'all-comments', 'dusptress-comments' ) . '=1' );
            exit();
        }

    }

    /**
     * This functions counts the top level comments for a given post.
     *
     * @param  integer $post_id The post id.
     * @return integer
     */
    private function count_top_level_comments( $post_id ) {
        global $wpdb;

        return $wpdb->get_var( $wpdb->prepare( "
            SELECT  COUNT(*)
            FROM    $wpdb->comments
            WHERE   comment_parent = 0
            AND     comment_post_ID = %d
            AND     comment_approved = 1
        ", $post_id ) );
    }

}