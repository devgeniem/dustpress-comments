![geniem-github-banner](https://cloud.githubusercontent.com/assets/5691777/14319886/9ae46166-fc1b-11e5-9630-d60aa3dc4f9e.png)

# Comments Helper

DustPress helpers are a handy tool for creating templates with a bit more logic. Comments Helper is a DustPress plugin for enabling commenting in DustPress based themes.

Use Comments Helper with your desired parameters and DustPress will provide an AJAX powered commenting for your posts and pages. This plugin provides base templates for a quick start. Just install and enable the plugin. You can override the templates with your own templates by adding them with the corresponding names in `/partials` directory inside your theme, for example `wp-content/themes/best-theme-ever/partials/comments.dust`.

## Included base templates

- _**comments.dust** (comment list, pagination, form)_
- _**comment.dust** (single comment)_

## Extended functionality

DustPress Comments extends [WordPress comment form](https://codex.wordpress.org/Function_Reference/comment_form) functionalities with some additional features. Use the following associative keys in your comment form params with the corresponding value types:
- _**replace_input**: array( '{field_name}' => {some_html} )_
    - replace a comment form input field by assigning an array key to match the name field of the input
- _**remove_input**: array( '{field_name}' )_
    - remove comment form input by assigning array key to match the input name field
- _**status_div**: html_
    -   container div to hold status messages
- _**status_id**: string_
    - status divs id to locate with js
- _**loader**: html_
    - html to display while processing comments ajax

## Example usage

_Bind the data in your model._

```
// Some DustPress model
class PageExample extends \DustPress\Model {
...
public function some_comments() {

  // Args for loading the form without the 'url' input field.
  $data->form_args  = [
    'title_reply'           => __( 'Add a comment', 'text-domain' ),
    'label_submit'          => __( 'Send', 'text-domain' ),
    'class_submit'          => 'button',
    'remove_input'          => array( 'url' ),
    'comment_notes_before'  => false,
    'comment_notes_after'   => false
  ];

    $after_comments = '<div class="comments__pagination-container"><ul class="pagination comments__pagination"></ul></div>';

  // Args for loading all comments at once and adding
  // a custom pagination container after the comments.
  $data->comments_args  = [
    'reply'             => false,
    'after_comments'    => $after_comments,
    'get_all            = > 1
  ];

  $data->section_title = __('Comments', 'text-domain');

  return $data;
}
...
```

_Then use the helper in your template._
```
{#some_comments}
    {@comments form_args=form_args comments_args=comments_args section_title=section_title /}
{/some_comments}
```