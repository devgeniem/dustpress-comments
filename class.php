<?php
/**
 * Comments
 */

namespace DustPress;

/**
 * Comments Helper class
 */
class Comments extends \DustPress\Helper {

    private $uniqid;
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
    private $form_id;
    private $threaded;
    private $comments;
    private $form;
    private $output;

    /**
     * Fired by DustPress core before the helper is rendered.
     */
    public function prerun() {
        $js_args = [
            'comments_per_page' => get_option('comments_per_page'),
            'post_id'           => $post_id     ? $post_id      : $post->ID,
            'form_id'           => $form_id     ? $form_id      : 'commentform',
            'status_id'         => $status_id   ? $status_id    : 'comments__status',
            'reply_label'       => $reply_label ? $reply_label  : __( 'Reply to comment', 'DustPress-Comments')
        ];

        // styles
        wp_enqueue_style( 'dustpress-comments-styles', plugin_dir_url( __FILE__ ) . '/dist/dustpress-comments.min.css', false, 1, all );

        // js
        wp_register_script( 'dustpress-comments', plugin_dir_url( __FILE__ ) . '/dist/dustpress-comments.min.js', array('jquery'), null, true);
        wp_localize_script( 'dustpress-comments', 'comments', $js_args );
        wp_enqueue_script( 'dustpress-comments' );
    }

    /**
     * Outputs the contents of the comments.
     *
     * @return string
     */
    public function output() {
        global $post;

        $c_data                 = new \stdClass();
        $params                 = $this->params;
        $this->section_title    = $params->section_title;
        $this->section_id       = $params->section_id ? $params->section_id : 'comments__section';
        $this->comment_class    = $params->comment_class;
        $this->form_args        = $params->form_args;
        $this->comments_args    = $params->comments_args;
        $this->avatar_args      = $params->avatar_args;
        $this->post_id          = $params->post_id              ? $params->post_id              : $post->ID;
        $this->echo_form        = $this->form_args['echo_form'] ? $this->form_args['echo_form'] : true;

        // Store params
        $this->uniqid                   = uniqid();
        $session_data                   = (object)[];
        $session_data->section_title    = $this->section_title;
        $session_data->comment_class    = $this->comment_class;
        $session_data->form_args        = $this->form_args;
        $session_data->comments_args    = $this->comments_args;
        $session_data->avatar_args      = $this->avatar_args;
        $session_data->post_id          = $this->post_id;
        $_SESSION[ $this->uniqid ]      = $session_data;

        // Get_comment_reply_link functions arguments
        $this->reply_args       = $params->reply_args;

        // Comments' arguments
        $this->load_comments    = $this->comments_args['load_comments']     ? $this->comments_args['load_comments']     : true;
        $this->after_comments   = $this->comments_args['after_comments']    ? $this->comments_args['after_comments']    : null;
        $this->reply            = $this->comments_args['reply'] !== null    ? $this->comments_args['reply']             : true;
        $this->threaded         = $this->comments_args['threaded']          ? $this->comments_args['threaded']          : get_option('thread_comments');
        $this->paginate         = $this->comments_args['page_comments']     ? $this->comments_args['page_comments']     : get_option( 'page_comments', true );
        $this->per_page         = $this->comments_args['comments_per_page'] ? $this->comments_args['comments_per_page'] : get_option( 'comments_per_page', 20 );
        $this->page_label       = $this->comments_args['page_label']        ? $this->comments_args['page_label']        : __( 'comment-page', 'dusptress-comments' );

        // Trying to paginate the wp way
        if ( $this->wp_pagination() ) {
            return;
        }

        // Closing args
        $this->close_old = $this->comments_args['close_comments_for_old_posts'] ? $this->comments_args['close_comments_for_old_posts'] : get_option( 'close_comments_for_old_posts', false );

        // Maybe close comments
        if ( $this->close_old ) {
            $this->closed_after = $this->comments_args['close_comments_days_old'] ? $this->comments_args['close_comments_days_old'] : get_option( 'close_comments_days_old', 14 );

            $closing_time = strtotime( get_the_date( 'c', $this->post_id ) ) + $this->closed_after * 86400;

            if ( $closing_time < time() ) {
                $this->echo_form = false;
            }
        }

        // Form loading and modification arguments
        if ( $this->echo_form ) {
            $this->replacements     = $this->form_args['replace_input'];
            $this->remove           = $this->form_args['remove_input'];
            $this->status_div       = $this->form_args['status_div'];
            $this->status_id        = $this->form_args['status_id'];
            $this->input_class      = $this->form_args['input_class'];
            $this->input_attrs      = $this->form_args['input_attrs'];
            $this->form_id          = $this->form_args['form_id'] ? $form_args['form_id'] : 'commentform';
        }

        // Default args
        $reply_defaults = [
            'depth'     => 1,
            'max_depth' => get_option('thread_comments_depth')
        ];
        $this->reply_args = array_merge( $reply_defaults, (array) $this->reply_args );
        $comments_defaults = [
            'status' => current_user_can('moderate_comments') ? 'all' : 'approve'
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
        $c_data->title          = apply_filters( 'dustpress/comments/section_title', $this->section_title );
        $c_data->form_id        = apply_filters( 'dustpress/comments/form_id', $this->form_id );
        $c_data->message        = apply_filters( 'dustpress/comments/message', $params->message );
        $c_data->form           = apply_filters( 'dustpress/comments/form', $this->form );
        $c_data->comments       = apply_filters( 'dustpress/comments/comments', $this->comments );
        $c_data->after_comments = apply_filters( 'dustpress/comments/after_comments', $this->after_comments );

        // Set the partial name and possibly override it with a filter
        $partial                = apply_filters( 'dustpress/comments/partial', 'comments' );

        // Add data into debugger
        dustpress()->set_debugger_data( 'Comments', $c_data );

        // Render output
        return dustpress()->render( [
            "partial"   => $partial,
            "data"      => $c_data,
            "type"      => "html",
            "echo"      => false
        ]);
    }

    /**
     * Fired after comment is succesfully saved in wp-comments-post.php
     * @param  integer $comment_id Id of the new comment.
     * @return json
     */
    public function handle_ajax( $comment_id ) {

        if ( ! defined('DUSTPRESS_AJAX') ) {
            define("DUSTPRESS_AJAX", true);
        }

        $comment        = get_comment( $comment_id );
        $comment_data   = wp_unslash( $_POST );

        // Get params from session
        $uniqid         = $comment_data['dustpress_comments'];
        $this->params   = $_SESSION[ $uniqid ];

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

        die( json_encode( $return ) );
    }

    /**
     * Get the form html.
     *
     * @return string
     */
    private function get_form() {
        // add input classes
        if ( $this->input_class || isset( $this->form_args['replace_input'] ) || isset( $this->form_args['remove_input'] ) ) {
            add_filter('comment_form_default_fields', array( $this, 'modify_fields' ) );
            add_filter('comment_form_field_comment', array( $this, 'modify_comment_field' ) );
        }

        // insert status div
        add_filter( 'comment_form_top', array( $this, 'form_status_div' ) );

        // insert hidden field to identify dustpress helper
        add_filter( 'comment_id_fields', array( $this, 'insert_identifier' ), 1 );

        // compile form and store it
        ob_start();
        comment_form( $this->form_args, $this->post_id );
        $this->form = ob_get_clean();
    }

    /**
     * Loads the comments.
     *
     * @return array
     */
    private function get_comments() {
        if ( ! isset( $this->comments_args['post_id'] ) ) {
            $this->comments_args['post_id'] = $this->post_id;
        }

        $get_all = $this->get_int( __( 'all-comments', 'dusptress-comments' ) );

        // Maybe paginate
        if ( 1 !== $get_all && $this->paginate ) {
            $args       = array_merge( $this->comments_args, [ 'count' => true ] );
            $page       = $this->get_int( $this->page_label );
            $this->page = $page ? $page : 1;

            $this->comments_args['parent'] = 0;
            $this->comments_args['offset'] = $this->page == 1 ? 0 : ( $this->page - 1 ) * $this->per_page;
            $this->comments_args['number'] = $this->per_page;

            // This is a nice undocumented feature of WP
            $this->comments_args['hierarchical'] = true;

            $this->comments = get_comments( $this->comments_args );

            $this->items = $this->count_top_level_comments( $this->comments_args['post_id'] );

            $this->after_comments = $this->pagination();

            // No need to proceed
            return;
        }

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
        $params->items       = $this->items;
        $params->hash       = $this->section_id;

        $this->pagination = new \DustPress\Pagination();
        $this->pagination->set_params( $params );

        return $this->pagination->output();
    }

    private function extend_comments() {
        foreach ( $this->comments as &$comment ) {
            $cid = $comment->comment_ID;

            // get author link
            $comment->author_link = get_comment_author_link( $cid );

            // set comment classes
            $classes = $this->has_parent( $comment ) ? 'reply ' . $this->comment_class : $this->comment_class;
            switch ( $comment->comment_approved ) {
                case 1:
                    $classes .= 'comment-approved';
                    break;
                case 0:
                    $classes .= 'comment-hold';
                    break;
                case 'spam':
                    $classes .= 'comment-spam';
                    break;
            }
            $comment->comment_class = comment_class( $classes, $cid, $post_id, false );

            // load reply link
            if ( $this->reply ) {
                $comment->reply_link    = get_comment_reply_link( $this->reply_args, $cid );
                $comment->reply         = true;
            }

            // set avatar
            if ( is_array( $this->avatar_args ) ) {
                extract( $this->avatar_args );
                $comment->avatar = get_avatar( $id_or_email, $size, $default, $alt );
            }

            // load a custom profile picture
            $pic = apply_filters( 'dustpress/comments/profile_picture', $comment );
            if ( is_string( $pic ) ) {
                $comment->profile_pic = $pic;
            }

            // filter comment
            $comment = apply_filters( 'dustpress/comments/comment', $comment );
        }
        // sort replies
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

    public function form_status_div() {
        if ( $this->status_div ) {
            echo $this->status_div;
        }
        else {
            if ( $this->loader ) {
                echo $this->loader;
            }
            else {
                echo '<div class="comments_loader"><span>' . __('Processing comments...', 'dusptress-comments') . '<span></div>';
            }
            echo '<div id="comment-status"></div>';
        }
    }

    public function insert_identifier( $id_elements ) {
        return $id_elements . "<input type='hidden' name='dustpress_comments' id='dustpress_comments_identifier' value='" . $this->uniqid . "' />\n";
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

    public function has_parent( $c ) {
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

    public function handle_error(  $message, $title, $args ) {
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