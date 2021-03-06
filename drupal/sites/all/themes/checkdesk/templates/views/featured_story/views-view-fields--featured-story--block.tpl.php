<?php

/**
 * @file
 * Default simple view template to all the fields as a row.
 *
 * - $view: The view in use.
 * - $fields: an array of $field objects. Each one contains:
 *   - $field->content: The output of the field.
 *   - $field->raw: The raw data for the field, if it exists. This is NOT output safe.
 *   - $field->class: The safe class id to use.
 *   - $field->handler: The Views field handler object controlling this field. Do not use
 *     var_export to dump this object, as it can't handle the recursion.
 *   - $field->inline: Whether or not the field should be inline.
 *   - $field->inline_html: either div or span based on the above flag.
 *   - $field->wrapper_prefix: A complete wrapper containing the inline_html to use.
 *   - $field->wrapper_suffix: The closing tag for the wrapper.
 *   - $field->separator: an optional separator that may appear before a field.
 *   - $field->label: The wrap label text to use.
 *   - $field->label_html: The full HTML of the label to use including
 *     configured element type.
 * - $row: The raw result object from the query, with all data it fetched.
 *
 * @ingroup views_templates
 */
  $author = _checkdesk_story_authors($fields['nid']->raw);
  if (isset($fields['uri']->raw)) {
    $has_image_class =  ' cd-item-has-image';  
    // $list_type_class = ' cd-item-list-media-mobile';
  } else {
    $has_image_class = '';
    // $list_type_class = ' cd-item-list-mobile';
  }
  

?>
<div class="cd-item tone-default-item<?php print $has_image_class ?> cd-item-standard-mobile cd-item-full-media-75-tablet">
    <div class="cd-item-container">
        <?php if (isset($fields['uri']->raw)) { ?>
            <div class="cd-item-media-wrapper">
                <div class="cd-item-image-container u-responsive-ratio">
                    <figure class="media-lead">
                        <?php print $fields['uri']->content; ?>
                    </figure>
                </div>
            </div>
        <?php } ?>
        <div class="cd-item-content">
            <div class="cd-item-header">
                <h1 class="cd-item-title">
                    <?php print $fields['title']->raw; ?>
                </h1>
            </div>
            <div class="cd-item-lead">
              <?php print $fields['body']->content; ?>
            </div>
            <aside class="cd-item-meta">
                <div class="byline">
                    <?php print $author; ?>
                </div>
                <?php print $created_at; ?>
                <?php if (isset($story_commentcount)) { ?>
                    <div class="cd-item-count story-commentcount">
                        <a href="<?php print url('node/' . $fields['nid']->raw, array('fragment' => 'story-comments-' . $fields['nid']->raw)); ?>">
                            <span class="icon-comment-o"></span> <?php print render($story_commentcount); ?>
                        </a>
                    </div>
                <?php } ?>
            </aside>
        </div>
        <?php print l($fields['title']->raw, 'node/' . $fields['nid']->raw, array('attributes' => array('class' => array('u-faux-block-link-overlay')))); ?>
    </div>
</div>