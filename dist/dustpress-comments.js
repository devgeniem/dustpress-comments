/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};

/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {

/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId])
/******/ 			return installedModules[moduleId].exports;

/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			exports: {},
/******/ 			id: moduleId,
/******/ 			loaded: false
/******/ 		};

/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);

/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;

/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}


/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;

/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;

/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";

/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ function(module, exports, __webpack_require__) {

	__webpack_require__( 1 );

	window.DustPressComments = ( function( window, document, $ ){

	    var app = {
	        replyLabel: comments.reply_label,
	        containers: {},
	        listeners: []
	    };

	    app.cache = function() {
	        // Get all comment sections
	        app.$sections = $('.dustpress-comments');

	        // Cache all elements
	        app.$sections.each(function(index, elem) {
	            app.initContainer(elem);
	        });
	    };

	    app.init = function(){
	        // Cache all comment sections from DOM
	        app.cache();
	        // Init listeners
	        $.each(app.containers, app.listen);
	        // Display visible messages
	        $.each(app.containers, app.displayMessages);
	    };

	    app.initContainer = function(container) {
	        // Wrap the element into a jQuery object and generate an unique id
	        var jObj  = $(container),
	            uid  = app.uniqid();

	        // Get the form element
	        jObj.$commentForm       = jObj.find('form');
	        jObj.$formContainer     = jObj.find('.dustpress-comments__form_container');

	        // Identify the container and the form just because WordPress is lazy.
	        app.containers[uid] = jObj;
	        jObj.attr( 'id', uid );
	        jObj.$formContainer.attr( 'data-container', uid );
	        jObj.$commentForm.attr( 'data-container', uid );

	        // Init message boxes
	        jObj.$successBox        = jObj.$formContainer.find('.dustpress-comments__success');
	        jObj.$errorBox          = jObj.$formContainer.find('.dustpress-comments__error');
	        jObj.$warningBox        = jObj.$formContainer.find('.dustpress-comments__warning');

	        // Init reply links
	        jObj.$replyLinks = jObj.find('.comment-reply-link');
	        $.each( jObj.$replyLinks, app.removeWPReplyLink );
	    };

	    app.addListener = function(fn) {
	        if ( typeof f === 'function' ) {
	            app.listeners.push(fn);
	        } else {
	            console.log('DustPress Comments: This listener is not a function.', fn);
	        }
	    };

	    app.fireListeners = function(modified) {
	        $.each(app.listeners, function(i, fn) {
	            fn.call(modified);
	        });
	    };

	    // Event listeners
	    app.listen = function(containerID, container) {
	        // Replying
	        container.on('click', '.comment-reply-link', app.addReplyForm);
	        // Form submission
	        container.$commentForm.submit(app.submit);
	        // Enable message hiding
	        container.on('click', '.close', function(e) {
	            container.stop(e);
	            app.hideMessages(container);
	        });
	    };

	    app.addReplyForm = function(e) {

	        // Get the stored object based by id
	        var $container = app.containers[e.delegateTarget.id];

	        app.stop(e);
	        app.hideMessages($container);

	        var $closest  = $( e.target ).closest('.comment');
	        var commentId = $closest.data('id');

	        // Initialize reply form
	        app.clearReplyForm($container);
	        $container.$replyForm = $container.$formContainer.clone(true);

	        // Hide main form
	        $container.$formContainer.hide();

	        // Change form heading
	        var small = $container.$replyForm.find('#reply-title small');
	        $container.$replyForm.find('#reply-title').html(app.replyLabel).append(small);

	        // Set parent id
	        $container.$replyForm.find('#comment_parent').val(commentId);

	        // Init message fields
	        $container.$replyForm.find('.comments__success').attr('id', 'comments__success_' + commentId);
	        $container.$replyForm.find('.comments__error').attr('id', 'comments__error_' + commentId);
	        $container.$replyForm.find('.comments__warning').attr('id', 'comments__warning_' + commentId);

	        // Append to DOM
	        $closest.append( $container.$replyForm ); // With data and events
	        $container.$replyForm.show();

	        // Cancel link
	        $container.$cancelReplyLink = $container.$replyForm.find('#cancel-comment-reply-link');
	        $container.$replyForm.on('click', '#cancel-comment-reply-link', app.cancelReply);
	        $container.$cancelReplyLink.show();
	    };

	    app.cancelReply = function(e) {
	        app.stop(e);

	        // Get the container
	        var containerID = e.delegateTarget.dataset.container,
	            container   = app.containers[containerID];

	        app.clearReplyForm(container);
	        container.$formContainer.show();
	    };

	    app.removeWPReplyLink = function(idx, link) {
	        link.removeAttribute('onclick');
	    };

	    app.submit = function(e) {
	        app.stop(e);

	        var formData;

	        // Store the container for listener reloading after DOM modifications
	        app.$modified = app.containers[e.target.dataset.container];
	console.log(app.$modified);
	        if ( FormData ) {
	            formData = new FormData(this);

	            $.ajax({
	                url: e.target.action,
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

	    app.hideMessages = function(container) {
	        container.$successBox.removeClass('display');
	        container.$successBox.hide();
	        container.$errorBox.removeClass('display');
	        container.$errorBox.hide();
	        container.$warningBox.removeClass('display');
	        container.$warningBox.hide();
	    };

	    app.handleSuccess = function(data) {
	        // Remove reply form
	        if (app.$modified.$replyForm) {
	            app.clearReplyForm(app.$modified);
	        }
	        // Replace comments section with rendered html
	        app.$modified.replaceWith(data.html);
	        // Reload listeners
	        app.init(app.$modified.attr('id'), app.$modified);
	        // Fire external lister functions
	        app.fireListeners(app.$modified);

	        delete app.$modified;
	    };

	    app.handleError = function(data) {
	        if ( data.error ) {
	            console.log(app.$modified.$errorBox);
	            app.$modified.$errorBox.html(data.message);
	            app.$modified.$errorBox.show();
	        }
	        else {
	            app.$modified.$errorBox.html('Error');
	            app.$modified.$errorBox.show();
	        }

	        delete app.$modified;
	    };

	    app.clearReplyForm = function(container) {
	        if ( container.$replyForm ) {
	            container.$replyForm.remove();
	            delete container.$replyForm;
	        }
	    };

	    app.getParent = function(parentId) {

	    };

	    app.stop = function(event) {
	        event.preventDefault ? event.preventDefault() : ( event.returnValue = false );
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

/***/ },
/* 1 */
/***/ function(module, exports) {

	// removed by extract-text-webpack-plugin

/***/ }
/******/ ]);