![geniem-github-banner](https://cloud.githubusercontent.com/assets/5691777/14319886/9ae46166-fc1b-11e5-9630-d60aa3dc4f9e.png)

# Comments Helper

DustPress helpers are a handy tool for creating templates with a bit more logic. Comments Helper is a DustPress plugin for enabling commenting in DustPress based themes.

Use Comments Helper with your desired parameters and DustPress will provide an AJAX powered commenting for your posts and pages. This plugin provides base templates for a quick start. Just install and enable the plugin. You can override the templates with your own templates by adding them with the corresponding names in `/partials` directory inside your theme, for example starting with the container in: `wp-content/themes/best-theme-ever/partials/commentscontainer.dust`.

To enable *full page caching* on pages using DustPress Comments the helper parameters must be passed via filter or model functions. You have to provide two functions: one for fetching the comments parameters and another for comment form parameters. The filter slug or the model name will be written into the comments section dataset and into a hidden input field in the comment form. Each AJAX request will include the slug or the model name for the DustPress Comments to be able to retrieve the correct helper parameters. There are examples of both cases at the end of this document.

## Functionalities

- threaded comment loading _(threaded commenting is enabled via the WordPress discussion settings)_
- comment list rendering
- form rendering
- ajaxified pagination _(pagination is enabled via the WordPress discussion settings and uses the DustPress Pagination helper)_
- ajaxified comment submission
- ajaxified and JS powered replying
- status message displaying and hiding with JS and CSS
- external JS invocation
- support for multiple commenting sections on one page!
- support for full page caching!


## Included base templates

- _**comments-container.dust** (the comments section)_
- _**comments.dust** (comment list, pagination, form)_
- _**comment.dust** (single comment)_

## Accepted parameters
We will document a full list of parameters in the near future! If you have questions of the helper usage, please contact us at info@geniem.com or create a new issue.

## Example usage

We provide two options for using the helper and passing parameters for its use.

### Model functions for helper parameters

Both functions will get the current post id as a parameter.

```
class CommentsModel extends \DustPress\Model {
    
    public function get_comments_args( $post_id ) {
        // This disables replying to comments.
        $args['reply'] = false;
        return $args;
    }

    public function get_form_args( $post_id ) {

        $args['title_reply']  = __('Comment', 'POF');
        $args['label_submit'] = __('Submit', 'POF');
        $args['class_submit'] = 'button radius';
        $args['remove_input'] = array( 'url' );
        $args['input_class']  = 'radius';

        return $args;
    }
}
```
Then in your dust partial pass this model for your helper as a string.

```
{@comments model="CommentsModel" /}
```

### Filter functions for helper parameters

The other way to pass parameters for the helper is to create two filter functions. The filter slug to be passed for the helper is the third part of the filter string devided with `/`. Each function must extend the passed `$args` array and then return it.

```
add_filter( 'dustpress/comments/my_comments/get_comments_args', 'my_comments_get_comments_args', 1, 2 );

function my_comments_get_comments_args( $args, $post_id ) {
    $args['reply'] = false;
    return $args;
}

add_filter( 'dustpress/comments/my_comments/get_form_args', 'my_comments_get_form_args', 1, 2 );

function my_comments_get_form_args( $args, $post_id ) {
    $args['remove_input'] = array( 'url' );
    return $args;
}
```

Then in your dust partial pass the filter slug for your helper as a string.

```
{@comments filter_slug="my_comments" /}
```
## Extended functionality

DustPress Comments extends [WordPress comment form](https://codex.wordpress.org/Function_Reference/comment_form) functionalities with some additional features. Use the following associative keys in your comment form params with the corresponding value types:

- _**replace\_input**_: array( '{field_name}' => {some_html} )_
    - replace a comment form input field by assigning an array key to match the name field of the input
- _**remove\_input**_: array( '{field_name}' )_
    - remove comment form input by assigning array key to match the input name field

    
## External JavaScript function invocation

If you need to hook your own scripts into DustPress Comments, we provide a handy way of adding external listeners for JavaScript actions. This is done via accessing the global Comments object under the `window` object which holds our global `DustPress` object.

```
var myFunction = function(state, container) {
   if ('success' === state) {
      alert('Your comment was posted!');
  	}
};
window.DustPress.Comments.addListener(myFunction);
```
Each time DustPress Comments is performing an action it will fire your listener function with two parameters. `state` refers to the state commenting is in and `container` is the current commenting section as a jQuery object. The section also works a wrapper object for you to fetch all the data that is linked into the commenting at the current state.

Go and build a loader or some other neat functionality!

### States

- submit
- reply
- cancelReply
- paginate
- success
- error