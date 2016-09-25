require( __dirname + '/dustpress-comments.css' );

if (typeof(window.DustPress) === 'undefined') {
    window.DustPress = {};
}

window.DustPress.Comments = ( function( window, document, $ ) {

    var app = {};

    /**
     * This string will be set as the heading of a reply form.
     * @type string
     */
    app.replyLabel = CommentsData.replyLabel;

    /**
     * Defaults to home url. Used by the pagination ajaxing.
     *
     * @type string
     */
    app.paginationUrl = CommentsData.paginationUrl;

    /**
     * A map containing all comments sections mapped by DOM ids.
     * @type object
     */
    app.containers = {};

    /**
     * An array containing all external listeners.
     * @type array
     */
    app.listeners = [];

    /**
     * This object contains always the last modified comments section as a jQuery object.
     *
     * @type object
     */
    app.$modified = {};

    /**
     * This function can be used to get the last modified comments section.
     *
     * @return object The jQuery object of the comments section container.
     */
    app.getLastModified = function() {
        return app.$modified;
    };

    /**
     * A string indicating the last changed state of the DustPressComments application.
     * @type string
     */
    app.state = 'initializing';

    /**
     * A getter for the state property.
     * @return string
     */
    app.getState = function() {
        return app.state;
    };

    /**
     * Add all comments sections into the map for later access.
     * This type of cacheing reduces the need to traverse the DOM.
     */
    app.cache = function() {
        // Get all comment sections
        app.$sections = $('.dustpress-comments');
        // Cache all elements
        app.$sections.each(app.initContainer);
    };

    /**
     * This function initializes our application functionality
     * by cacheing the sections and adding the needed listeners.
     */
    app.init = function(){
        // Cache all comment sections from DOM
        app.cache();
        // Init listeners
        $.each(app.containers, app.listen);
        // Display visible messages
        $.each(app.containers, app.displayMessages);
    };

    /**
     * This function identifies a comments section and maps all its needed DOM elements.
     *
     * @param  int      idx         Index of the current container.
     * @param  object   container   The DOM (!) object of the comments section.
     */
    app.initContainer = function(idx, container) {
        // Wrap the element into a jQuery object.
        var jObj  = $(container);

        // Init messages.
        app.initMessageFields(jObj);

        // Identify the container and the form just because WordPress is lazy.
        app.identify(jObj);

        // Init DustPress Comments reply links.
        jObj.$replyLinks = jObj.find('.comment-reply-link');
        // Remove WP reply links.
        $.each( jObj.$replyLinks, app.removeWPReplyLink );

        // Init pagination
        jObj.$pagination = jObj.find('.pagination');
    };

    /**
     * This function maps the message fields of a form into the container object.
     *
     * @param  object $container    The jQuery object of a comments section.
     */
    app.initMessageFields = function($container) {
        $container.$successBox      = $container.find('.dustpress-comments__success');
        $container.$successBoxText  = $container.$successBox.find('.dustpress-comments__status_message');
        $container.$errorBox        = $container.find('.dustpress-comments__error');
        $container.$errorBoxText    = $container.$errorBox.find('.dustpress-comments__status_message');
        $container.$warningBox      = $container.find('.dustpress-comments__warning');
        $container.$warningBoxText  = $container.$warningBox.find('.dustpress-comments__status_message');
    };

    /**
     * Add event listeners for DOM elements.
     *
     * @param string containerID   The DOM id of the comments section.
     * @param object $container    The jQuery object of the comments section.
     */
    app.listen = function(containerID, $container) {
        // Replying
        $container.on('click', '.comment-reply-link', app.addReplyForm);
        // Form submission
        $container.on('click', 'input[type="submit"]', app.submit);
        // Enable message hiding
        $container.on('click', '.close', function(e) {
            app.stop(e);
            app.hideMessages($container);
        });
        // Enable pagination ajaxing.
        if ( $container.$pagination.length ) {
            $container.on('click', '.paginate', app.paginate);
        }
    };

    /**
     * This function enables third party scripts to listen for DustPress Comments events.
     * The called function will get the application context as 'this' and the state and the modified section as parameters.
     *
     * @param function fn Your function.
     */
    app.addListener = function(fn) {
        if ( typeof fn === 'function' ) {
            app.listeners.push(fn);
        } else {
            console.log('DustPress Comments: This listener is not a function.', fn);
        }
    };

    /**
     * This function fires all added listeners on all state changing events of a comments section.
     * A string defining the changed state and the changed comments section object will be passed on to the fired function.
     *
     * @param string state The changed state: 'success', 'error', 'reply' or 'cancelReply'.
     */
    app.fireListeners = function(state) {
        app.state = state;
        for (var i = 0; i < app.listeners.length; i++) {
            // The first parameter is the context (this) passed to the called function.
            app.listeners[i].call(app, app.state, app.$modified);
        }
    };

    /**
     * Performs a AJAX request to change the comments state to display the selected page.
     *
     * @param object e The deletaged reply link click event object fired by the container listener.
     */
    app.paginate = function(e) {
        app.stop(e);

        // Get the stored comments section based by delegated target id and store the modified
        var $container = app.$modified = app.containers[e.delegateTarget.id];

        // Get the comments model name or the filter slug
        var model   = $container.data('model');
            slug    = $container.data('filterslug'),
            id      = $container.data('objectid'),
            page    = $(this).data('page');

        // Fire external listener functions
        app.fireListeners('paginate');

        $.ajax({
            dataType: 'json',
            url: app.paginationUrl,
            type: 'POST',
            data: {
                'comment_post_ID': id,
                'dustpress_comments_paginate': 1,
                'dustpress_comments_ajax': 1,
                'dustpress_comments_model': model,
                'dustpress_comments_filter_slug': slug,
                'dustpress_comments_page': page,
            },
            success: function (data) {
                if ( 'object' !== typeof data ) {
                    data = JSON.parse(data);
                }
                if ( data.success ) {
                    app.handleSuccess(data);
                } else {
                    app.handleError(data);
                }
            },
            cache: false,
        });

    };

    /**
     * Clone the comment form of the current comments section underneath the comment and do some DOM manipulation.
     *
     * @param object e The deletaged reply link click event object fired by the container listener.
     */
    app.addReplyForm = function(e) {

        // Get the stored comments section based by delegated target id and store the modified
        var $container = app.$modified = app.containers[e.delegateTarget.id];

        app.stop(e);
        app.hideMessages($container);

        // Get the comment container
        var $closest  = $( e.target ).closest('.comment'),
            commentId = $closest.data('id');

        // Initialize reply form
        app.clearReplyForm($container);
        if ( 'undefined' === typeof $container.$formContainer ) {
            $container.$formContainer = $container.find('.dustpress-comments__form_container');
        }
        $container.$replyForm = $container.$formContainer.clone(true);

        // Hide the main form
        $container.$formContainer.hide();

        // Change the form heading and copy the cancellation link.
        var small = $container.$replyForm.find('#reply-title small');
        // First add the
        $container.$replyForm.find('#reply-title').html(app.replyLabel).append(small);
        // Set an input value indicating the parent comment
        $container.$replyForm.find('#comment_parent').val(commentId);

        // Replace the message fields of the container
        app.initMessageFields($container, $container.$replyForm);

        // Append the reply form into DOM
        $closest.append( $container.$replyForm ); // With data and events
        $container.$replyForm.show();

        // Add listener for the cancel link and display it.
        $container.$cancelReplyLink = $container.$replyForm.find('#cancel-comment-reply-link');
        $container.on('click', '#cancel-comment-reply-link', app.cancelReply);
        $container.$cancelReplyLink.show();

        // Fire external listener functions
        app.fireListeners('reply');
    };

    /**
     * This functions removes the reply form and displays the default form of a comments section.
     *
     * @param object e The deletaged cancel reply link click event object fired by the container listener.
     */
    app.cancelReply = function(e) {
        app.stop(e);

        // Get the container
        var containerID = e.delegateTarget.id,
            $container = app.$modifie = app.containers[containerID];

        // Replace the message fields of the container with the defaults.
        app.initMessageFields($container);

        // Clear the reply form and display the default form.
        app.clearReplyForm($container);
        $container.$formContainer.show();

        // Fire external listener functions
        app.fireListeners('cancelReply');
    };

    /**
     * WordPress forces an attribute for the reply link. This function removes it from all reply links.
     *
     * @param  int      idx     The index of the current link.
     * @param  object   link    The jQuery object of the link.
     */
    app.removeWPReplyLink = function(idx, link) {
        link.removeAttribute('onclick');
    };

    app.submit = function(e) {
        app.stop(e);

        var formData;

        // Store the container
        app.$modified = app.containers[e.delegateTarget.id];

        // Fire external listener functions
        app.fireListeners('submit');

        if ( FormData ) {
            // Get the form node and pass it to FormData.
            var $form = $(this).closest('form');
            formData = new FormData($form.get(0));

            $.ajax({
                dataType: 'json',
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                success: function (data) {
                    if ( 'object' !== typeof data ) {
                        data = JSON.parse(data);
                    }
                    if ( data.success ) {
                        app.handleSuccess(data);
                    } else {
                        app.handleError(data);
                    }
                },
                cache: false,
                contentType: false,
                processData: false
            });
        } else {
            console.log(comments.form_data_e);
        }

        return false;
    };

    /**
     * This function displays all the message elements of a comments section.
     *
     * @param  object container The jQuery object of the comments section.
     */
    app.displayMessages = function(containerID, container) {
        if ( container.$successBox.hasClass('display') ) {
            container.$successBox.show();
        }
        if ( container.$errorBox.hasClass('display') ) {
            container.$errorBox.show();
        }
        if ( container.$warningBox.hasClass('display') ) {
            container.$warningBox.show();
        }
    };

    /**
     * This function hides all the message elements of a comments section.
     *
     * @param  object container The jQuery object of the comments section.
     */
    app.hideMessages = function(container) {
        container.$successBox.removeClass('display');
        container.$successBox.hide();
        container.$errorBox.removeClass('display');
        container.$errorBox.hide();
        container.$warningBox.removeClass('display');
        container.$warningBox.hide();
    };

    /**
     * This function updates the comments section state on an AJAX success response.
     *
     * @param  object data The AJAX response data.
     */
    app.handleSuccess = function(data) {
        // Remove the reply form
        app.clearReplyForm(app.$modified);
        if (app.$modified.$replyForm) {
        }
        // Change the state
        app.$modified.html(data.html);
        // Cache message fields
        app.initMessageFields(app.$modified);
        // Fire external listener functions
        app.fireListeners('success');
    };

    /**
     * This function displays an error message on AJAX response.
     *
     * @param  object data The AJAX response data.
     */
    app.handleError = function(data) {

        if ( data.error ) {
            app.$modified.$errorBoxText.html(data.message);
            app.$modified.$errorBox.show();
        }
        else {
            app.$modified.$errorBoxText.html('Error');
            app.$modified.$errorBox.show();
        }

        // Fire external listener functions
        app.fireListeners('error');
    };

    /**
     * This function removes a reply form from the DOM and deletes the instance.
     *
     * @param  object container The comments section.
     */
     app.clearReplyForm = function(container) {
        if ( container.$replyForm ) {
            container.$replyForm.remove();
            delete container.$replyForm;
        }
    };

    /**
     * Event stopping wrapper.
     *
     * @param  object event The event.
     */
    app.stop = function(event) {
        event.preventDefault ? event.preventDefault() : ( event.returnValue = false );
    };

    /**
     * Add an unique id to identify each comments section and add it into the containers map.
     *
     * @param  object container The jQuery object of the comments section.
     */
    app.identify = function(container) {
        var uid  = app.uniqid();
        app.containers[uid] = container;
        container.attr( 'id', uid );
    };

    app.uniqid = function (pr, en) {
        pr = pr || '';
        en = en || false;
        var result;

        this.seed = function (s, w) {
            s = parseInt(s, 10).toString(16);
            return w < s.length ? s.slice(s.length - w) : (w > s.length) ? new Array(1 + (w - s.length)).join('0') + s : s;
        };

        result = pr + this.seed(parseInt(new Date().getTime() / 1000, 10), 8) + this.seed(Math.floor(Math.random() * 0x75bcd15) + 1, 5);

        if (en) result += (Math.random() * 10).toFixed(8).toString();

        return result;
    };

    $(document).ready( app.init );

    return app;

})( window, document, jQuery );