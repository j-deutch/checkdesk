<?php


include_once(drupal_get_path('theme', 'checkdesk') . '/includes/checkdesk.inc');
include_once(drupal_get_path('theme', 'checkdesk') . '/includes/theme.inc');
include_once(drupal_get_path('theme', 'checkdesk') . '/includes/menu.inc');

/**
 * hook_theme() 
 */
function checkdesk_theme() {
  return array(
    'checkdesk_links' => array(
      'variables' => array('links' => array(), 'attributes' => array(), 'heading' => NULL),
    ),
    'checkdesk_user_menu_item' => array(
      'variables' => array('attributes' => array(), 'type' => NULL),
    ),
    'checkdesk_user_menu_content' => array(
      'variables' => array('items' => array()),
    ),
    'checkdesk_dropdown_menu_item' => array(
      'variables' => array('title' => NULL, 'attributes' => array()),
    ),
    'checkdesk_dropdown_menu_content' => array(
      'variables' => array('id' => NULL, 'content' => array()),
    ),
    'checkdesk_heartbeat_content' => array(
      'variables' => array('message' => array(), 'node' => array()),
    ),
  );
}

/**
 * Preprocess variables for html.tpl.php
 *
 * @see html.tpl.php
 */
function checkdesk_preprocess_html(&$variables) {
  
  if(arg(0) == 'user' && arg(1) == '') {
   $class = 'page-user-login';
   $variables['classes_array'][] = $class;
  }

  // set body class for language
  if ($variables['language']) {
    $class = 'body-' . $variables['language']->language;
    $variables['classes_array'][] = $class;
  }

  // Add classes about widgets sidebar
   if (checkdesk_widgets_visibility()) {
    if (!empty($variables['page']['widgets'])) {
      $variables['classes_array'][] = 'widgets';
      // remove no-sidebars class from drupal
      $variables['classes_array'] = array_diff($variables['classes_array'], array('no-sidebars'));
    }
    else {
      $variables['classes_array'][] = 'no-widgets';
    }
  }

  // Add conditional stylesheets for IE8.
  if ($variables['language'] == 'ar') {
    $filename = 'ie8-rtl.css';
  } else {
    $filename = 'ie8.css';
  }
  drupal_add_css(
    drupal_get_path('theme', 'checkdesk') . '/assets/css/' . $filename,
    array(
      'group' => CSS_THEME,
      'browsers' => array(
        'IE' => 'IE 8',
        '!IE' => FALSE,
      ),
      'weight' => 999,
      'every_page' => TRUE,
    )
  );
  drupal_add_js(
    drupal_get_path('theme', 'checkdesk') . '/assets/js/ie8.js',
    array(
      'group' => JS_THEME,
      // Not supported yet: http://drupal.org/node/865536
      'browsers' => array(
        'IE' => 'IE 8',
        '!IE' => FALSE,
      ),
      'weight' => 999,
      'every_page' => TRUE,
    )
  );
  
  $head_title = array();
  $title = drupal_get_title();
  if (!empty($title)) {
    $head_title[] = $title;
  }
  $head_title[] = variable_get('site_name', 'Drupal');
  $variables['head_title'] = strip_tags(implode(' | ', $head_title));

}

/**
 * Override or insert variables into the region template.
 */
function checkdesk_preprocess_region(&$variables) {
  global $language;
  if ($variables['region'] == 'widgets') {
    // define custom header settings
    $variables['header_image'] = '';
    $image = theme_get_setting('header_image_path');
    
    if (!empty($image) && theme_get_setting('header_image_enabled')) {
      $variables['header_image'] = l(theme('image', array('path' => $image, 'style_name'=> 'partner_logo')), '<front>', array('html' => TRUE,));
    }

    $position = theme_get_setting('header_image_position');
    $variables['header_image_position'] = (empty($position) ? 'left' : $position);

    $bg = theme_get_setting('header_bg_path');
    $variables['header_bg'] = (empty($bg) ? '' : file_create_url($bg));

    $slogan = $variables['header_slogan'] = t('A Checkdesk live blog by <span class="checkdesk-slogan-partner">@partner</span>', array('@partner' => variable_get_value('checkdesk_site_owner', array('language' => $language))));
    $variables['header_slogan'] = (empty($slogan) ? '' : $slogan);
    $variables['header_slogan_position'] = ((!empty($position) && in_array($position, array('center', 'right'))) ? 'left' : 'right'); 
  }
}



/**
 * Preprocess variables for blocks
 */
function checkdesk_preprocess_block(&$variables) {
  // remove subjects for all blocks
  $variables['elements']['#block']->subject = '';
  // Add Compose Update on update form
  if($variables['elements']['#block']->bid == 'checkdesk_core-post') {
    $variables['elements']['#block']->subject = t('Compose Update'); 
  }
}

/**
 * Preprocess variables for page.tpl.php
 *
 * @see page.tpl.php
 */
function checkdesk_preprocess_page(&$variables) {
  global $user, $language;

  // Unescape HTML in title
  $variables['title'] = htmlspecialchars_decode(drupal_get_title());

  // Add a path to the theme so checkdesk_inject_bootstrap.js can load libraries
  $variables['basePathCheckdeskTheme'] = url(drupal_get_path('theme', 'checkdesk'), array('language' => (object) array('language' => FALSE)));
  drupal_add_js(array('basePathCheckdeskTheme' => $variables['basePathCheckdeskTheme']), 'setting');

  // Primary nav
  $variables['primary_nav'] = FALSE;
  if ($variables['main_menu']) {
    // Build links
    $tree = menu_tree_page_data(variable_get('menu_main_links_source', 'main-menu'));


    // Remove empty expanded menus
    foreach ($tree as $id => $item) {
      if (preg_match('/^<[^>]*>$/', $item['link']['link_path']) && $item['link']['expanded'] && count($item['below']) == 0) {
        unset($tree[$id]);
      }

      if (isset($item['below']) && $item['link']['title'] == t('...')) {
        $tree[$id]['link']['title'] = '&nbsp;';
        $tree[$id]['link']['link_title'] = '&nbsp;';
        $tree[$id]['link']['html'] = TRUE;
      }
    }

    $variables['main_menu'] = checkdesk_menu_navigation_links($tree);

    foreach ($variables['main_menu'] as $id => $item) {
      // Change "Submit Report" link
      if ($item['link_path'] == 'node/add/media') {
        $src = url('node/add/media', array('query' => array('meedan_bookmarklet' => '1'), 'absolute' => TRUE));
        $content = array(
          '#type' => 'markup',
          '#markup' => theme('meedan_iframe', array('src' => $src)),
        );

        $variables['main_menu'][$id]['html'] = TRUE;
        $variables['main_menu'][$id]['title'] = theme('checkdesk_dropdown_menu_item', array('title' => t('Submit report')));
        $variables['main_menu'][$id]['attributes']['data-toggle'] = 'dropdown';
        $variables['main_menu'][$id]['attributes']['class'] = array('dropdown-toggle');
        $variables['main_menu'][$id]['attributes']['id'] = 'menu-submit-report';
        $variables['main_menu'][$id]['suffix'] = theme('checkdesk_dropdown_menu_content', array('id' => 'nav-media-form', 'content' => $content));
      }
      else if ($item['link_path'] == 'node/add/discussion') {
        module_load_include('inc', 'node', 'node.pages');
        $node = (object) array('uid' => $user->uid, 'name' => (isset($user->name) ? $user->name : ''), 'type' => 'discussion', 'language' => LANGUAGE_NONE);
        // The third 'ajax' parameter is a flag for checkdesk_core
        $content = drupal_get_form('discussion_node_form', $node, 'ajax');

        $variables['main_menu'][$id]['html'] = TRUE;
        $variables['main_menu'][$id]['title'] = theme('checkdesk_dropdown_menu_item', array('title' => t('Create story')));
        $variables['main_menu'][$id]['attributes']['data-toggle'] = 'dropdown';
        $variables['main_menu'][$id]['attributes']['class'] = array('dropdown-toggle');
        $variables['main_menu'][$id]['attributes']['id'] = 'discussion-form-menu-link';
        $variables['main_menu'][$id]['suffix'] = theme('checkdesk_dropdown_menu_content', array('id' => 'nav-discussion-form', 'content' => $content));
      }
    }

    // Build list
    $variables['primary_nav'] = theme('checkdesk_links', array(
      'links' => $variables['main_menu'],
      'attributes' => array(
        'id' => 'main-menu',
        'class' => array('nav'),
      ),
      'heading' => NULL,
    ));
  }

  // Secondary nav
  $variables['secondary_nav'] = FALSE;
  $menu = menu_load('menu-common');
  $tree = menu_tree_page_data($menu['menu_name']);

  // Remove items that are not from this language or that does not have children, or are not enabled
  foreach ($tree as $id => $item) {
    if ((preg_match('/^<[^>]*>$/', $item['link']['link_path']) && $item['link']['expanded'] && count($item['below']) == 0) || $item['link']['hidden']) {
      unset($tree[$id]);
    }

    if ($item['link']['language'] != LANGUAGE_NONE && $item['link']['language'] != $language->language) unset($tree[$id]);
    foreach ($item['below'] as $subid => $subitem) {
      if ($subitem['link']['language'] != LANGUAGE_NONE && $subitem['link']['language'] != $language->language) unset($tree[$id]['below'][$subid]);
    }
  }

  // Add classes for modal
  foreach ($tree as $id => &$item) {
    if (strpos($item['link']['link_path'], '/ajax/') !== FALSE) {
      $item['link']['class'] = array('use-ajax', 'ctools-modal-modal-popup-bookmarklet');
    }
  }

  $variables['secondary_menu'] = checkdesk_menu_navigation_links($tree);

  // Change links
  foreach ($variables['secondary_menu'] as $id => $item) {

    if ($item['title'] === '<user>') {
      foreach ($item['below'] as $subid => $subitem) {
        if ($subitem['link_path'] == 'user/login') {
          if (user_is_logged_in()) unset($variables['secondary_menu'][$id]['below'][$subid]);
          else $variables['secondary_menu'][$id] = $subitem;
        }
      }
      if (user_is_logged_in()) {
        $variables['secondary_menu'][$id]['html'] = TRUE;
        $variables['secondary_menu'][$id]['title'] = theme('checkdesk_user_menu_item');
        $variables['secondary_menu'][$id]['attributes']['data-toggle'] = 'dropdown';
        $variables['secondary_menu'][$id]['attributes']['class'] = 'dropdown-toggle';
        $variables['secondary_menu'][$id]['suffix'] = theme('checkdesk_user_menu_content', array('items' => $variables['secondary_menu'][$id]['below']));

        unset($variables['secondary_menu'][$id]['below']);
      }
    }

    else if ($item['link_path'] == 'my-notifications') {
      if (user_is_logged_in()) {
        $count = checkdesk_notifications_number_of_new_items($user);
        $counter = '';
        if ($count > 0) $counter = '<span>' . $count . '</span>';
        $variables['secondary_menu'][$id]['attributes']['id'] = 'my-notifications-menu-link';
        $variables['secondary_menu'][$id]['html'] = TRUE;
        $variables['secondary_menu'][$id]['title'] = '<span class="notifications-count">' . $counter . '</span> <span class="notifications-label">' . $item['title'] . '</span>';
      }
      else {
        unset($variables['secondary_menu'][$id]);
      }
    }

  }

  // Build list
  $variables['secondary_nav'] = theme('checkdesk_links', array(
    'links' => $variables['secondary_menu'],
    'attributes' => array(
      'id' => 'user-menu',
      'class' => array('nav'),
    ),
    'heading' => NULL,
  ));


  // information nav
  $variables['information_nav'] = FALSE;
  $menu = menu_load('menu-information');
  $tree = menu_tree_page_data($menu['menu_name']);

  // Remove items that are not from this language or that does not have children
  foreach ($tree as $id => $item) {
    if (preg_match('/^<[^>]*>$/', $item['link']['link_path']) && $item['link']['expanded'] && count($item['below']) == 0) {
      unset($tree[$id]);
    }
    if ($item['link']['language'] != LANGUAGE_NONE && $item['link']['language'] != $language->language) unset($tree[$id]);
    foreach ($item['below'] as $subid => $subitem) {
      if ($subitem['link']['language'] != LANGUAGE_NONE && $subitem['link']['language'] != $language->language) unset($tree[$id]['below'][$subid]);
    }
  }

  // Add classes for modal
  foreach ($tree as $id => $item) {
    $tree[$id]['link']['class'] = array('use-ajax', 'ctools-modal-modal-popup-bookmarklet');
  }

  $variables['information_menu'] = checkdesk_menu_navigation_links($tree);

  // Build list
  $variables['information_nav'] = theme('checkdesk_links', array(
    'links' => $variables['information_menu'],
    'attributes' => array(
      'id' => 'information-menu',
      'class' => array('nav'),
    ),
    'heading' => NULL,
  ));

  // footer nav
  $variables['footer_nav'] = FALSE;
  $menu = menu_load('menu-footer');
  $tree = menu_tree_page_data($menu['menu_name']);

  // Remove items that are not from this language or that does not have children
  foreach ($tree as $id => $item) {
    if (preg_match('/^<[^>]*>$/', $item['link']['link_path']) && $item['link']['expanded'] && count($item['below']) == 0) {
      unset($tree[$id]);
    }
    if ($item['link']['language'] != LANGUAGE_NONE && $item['link']['language'] != $language->language) unset($tree[$id]);
    foreach ($item['below'] as $subid => $subitem) {
      if ($subitem['link']['language'] != LANGUAGE_NONE && $subitem['link']['language'] != $language->language) unset($tree[$id]['below'][$subid]);
    }
  }

  // Add checkdesk logo class
  foreach ($tree as $id => $item) {
    if($tree[$id]['link']['link_path'] == 'http://checkdesk.org') {
      $tree[$id]['link']['class'] = array('checkdesk');
    }
  }

  $variables['footer_menu'] = checkdesk_menu_navigation_links($tree);

  // Build list
  $variables['footer_nav'] = theme('checkdesk_links', array(
    'links' => $variables['footer_menu'],
    'attributes' => array(
      'id' => 'footer-menu',
      'class' => array('nav'),
    ),
    'heading' => NULL,
  ));

  // ctools modal

  ctools_include('modal');
  ctools_modal_add_js();

  // Custom modal settings arrays
  $modal_style = array(
    'modal-popup-small' => array(
      'modalSize' => array(
        'type' => 'fixed',
        'width' => 420,
        'height' => 300,
        'addWidth' => 0,
        'addHeight' => 0
      ),
      'modalOptions' => array(
        'opacity' => .5,
        'background-color' => '#000',
      ),
      'animation' => 'show',
      'animationSpeed' => 40,
      'modalTheme' => 'CheckDeskModal',
      'throbber' => theme('image', array('path' => ctools_image_path('ajax-loader.gif', 'checkdesk_core'), 'alt' => t('Loading'), 'title' => t('Loading'))),
    ),
    'modal-popup-medium' => array(
      'modalSize' => array(
        'type' => 'fixed',
        'width' => 520,
        'height' => 350,
        'addWidth' => 0,
        'addHeight' => 0
      ),
      'modalOptions' => array(
        'opacity' => .5,
        'background-color' => '#000',
      ),
      'animation' => 'show',
      'animationSpeed' => 40,
      'modalTheme' => 'CheckDeskModal',
      'throbber' => theme('image', array('path' => ctools_image_path('ajax-loader.gif', 'checkdesk_core'), 'alt' => t('Loading'), 'title' => t('Loading'))),
    ),
    'modal-popup-large' => array(
      'modalSize' => array(
        'type' => 'fixed',
        'width' => 420,
        'height' => 380,
        'addWidth' => 0,
        'addHeight' => 0
      ),
      'modalOptions' => array(
        'opacity' => .5,
        'background-color' => '#000',
      ),
      'animation' => 'show',
      'animationSpeed' => 40,
      'modalTheme' => 'CheckDeskModal',
      'throbber' => theme('image', array('path' => ctools_image_path('ajax-loader.gif', 'checkdesk_core'), 'alt' => t('Loading'), 'title' => t('Loading'))),
    ),
  );
  drupal_add_js($modal_style, 'setting');

  // define custom header settings
  $variables['header_image'] = '';
  $image = theme_get_setting('header_image_path');
  
  if (!empty($image) && theme_get_setting('header_image_enabled')) {
    $variables['header_image'] = l(theme('image', array('path' => file_create_url($image))), '<front>', array('html' => TRUE));
  }

  $position = theme_get_setting('header_image_position');
  $variables['header_image_position'] = (empty($position) ? 'left' : $position);

  $bg = theme_get_setting('header_bg_path');
  $variables['header_bg'] = (empty($bg) ? '' : file_create_url($bg));

  $variables['header_slogan'] = t('A <span class="checkdesk-slogan-logo">Checkdesk</span> Liveblog by <span class="checkdesk-slogan-partner">@partner</span>', array('@partner' => variable_get_value('checkdesk_site_owner', array('language' => $language))));
  $variables['header_slogan_position'] = ((!empty($position) && in_array($position, array('center', 'right'))) ? 'left' : 'right');

  // set page variable if widgets should be visible
  $variables['show_widgets'] = checkdesk_widgets_visibility();

  // set page variable if widgets should be visible
  $variables['show_footer'] = checkdesk_footer_visibility();

}


/**
 * Override or insert variables into the node template.
 */
function checkdesk_preprocess_node(&$variables) {

  // set $alpha and $omega for language directions
  global $language;
  if ($language->direction == LANGUAGE_RTL) {
    $variables['alpha'] = 'right';
    $variables['omega'] = 'left';
  } else {
    $variables['alpha'] = 'left';
    $variables['omega'] = 'right';
  }

  if ($variables['type'] == 'post' || $variables['type'] == 'discussion') {
    //Add author info to variables
    $user = user_load($variables['elements']['#node']->uid);
    $user_picture = $user->picture;
    if (!empty($user_picture)) {
      $options = array(
        'html' => TRUE,
        'attributes' => array(
          'class' => 'gravatar'
        )    
      );
      $variables['user_avatar'] = l(theme('image_style', array('path' => $user_picture->uri, 'alt' => t(check_plain($variables['elements']['#node']->name)), 'style_name' => 'navigation_avatar')), 'user/'. $variables['uid'], $options);
    }

    // Add creation info
    $variables['creation_info'] = t('<a href="@user">!user</a> <span class="separator">&#9679;</span> <time datetime="!date">!datetime</time>', array(
      '@user' => url('user/'. $variables['uid']),
      '!user' => $variables['elements']['#node']->name,
      '!date' => format_date($variables['created'], 'custom', 'Y-m-d'),
      '!datetime' => format_date($variables['created'], 'custom', t('l M d, Y \a\t g:ia')),
    ));
    $variables['created_by'] = t('<a href="@user">!user</a>', array(
      '@user' => url('user/'. $variables['uid']),
      '!user' => $variables['elements']['#node']->name,
    ));
    $variables['created_at'] = t('<time datetime="!date">!interval ago</time>', array(
      '!date' => format_date($variables['created'], 'custom', 'Y-m-d'),
      '!datetime' => format_date($variables['created'], 'custom', t('M d, Y \a\t g:ia')),
      '!interval' => format_interval((time() - $variables['created']), 1),
    ));
    $variables['updated_at'] = t('<time datetime="!date">!datetime</time>', array(
      '!date' => format_date($variables['changed'], 'custom', 'Y-m-d'),
      '!datetime' => format_date($variables['changed'], 'custom', t('M d, Y \a\t g:ia')),
      '!interval' => format_interval((time() - $variables['changed']), 1),
    ));
  }

  if ($variables['type'] == 'discussion') {
    // get updates for a particular story
    $view = views_get_view('updates_for_stories');
    $view->set_arguments(array($variables['nid']));
    $view_output = $view->preview('block');
    $total_rows = $view->total_rows;
    $view->destroy();
    if ($total_rows) {
      $variables['updates'] = $view_output;
    }
  }

  $variables['icon'] = '';
  
  if ($variables['type'] == 'media') {
    //Add author info to variables
    $user = user_load($variables['elements']['#node']->uid);
    $user_picture = $user->picture;
    if (!empty($user_picture)) {
      $options = array(
        'html' => TRUE,
        'attributes' => array(
          'class' => 'gravatar'
        )    
      );
      $variables['user_avatar'] = l(theme('image_style', array('path' => $user_picture->uri, 'alt' => t(check_plain($variables['elements']['#node']->name)), 'style_name' => 'navigation_avatar')), 'user/'. $variables['uid'], $options);
    }
    //Add node creation info(author name plus creation time
    $variables['media_creation_info'] = t('Added by <a href="@user">!user</a> <span class="separator">&#9679;</span> <time class="date-time" datetime="!timestamp">!interval ago</time>', array(
      '@user' => url('user/'. $variables['uid']),
      '!user' => $variables['elements']['#node']->name,
      '!timestamp' => format_date($variables['created'], 'custom', 'Y-m-d\TH:i:sP'),
      '!datetime' => format_date($variables['created'], 'custom', t('M d, Y \a\t g:ia')),
      '!interval' => format_interval(time() - $variables['created'], 1),
    ));
    //Add activity report with status
    $term = isset($variables['elements']['#node']->field_rating[LANGUAGE_NONE][0]['taxonomy_term']) ? 
      $variables['elements']['#node']->field_rating[LANGUAGE_NONE][0]['taxonomy_term'] : 
      taxonomy_term_load($variables['elements']['#node']->field_rating[LANGUAGE_NONE][0]['tid']);
    $status_name = $term->name;
    if ($status_name !== 'Not Applicable') {
      $view = views_get_view('activity_report');
      $view->set_arguments(array($variables['nid']));
      $view_output = $view->preview('block');
      $total_rows = $view->total_rows;
      $view->destroy();
      if ($total_rows) {
        $variables['media_activity_report_count'] = $total_rows;
        $variables['media_activity_report'] = $view_output;
        $status_class = '';
        $icon = '';
        if ($status_name == 'Verified') {
          $status_class = 'verified';
          $icon = '<span class="icon-ok-sign"></span> ';
        }
        elseif ($status_name == 'In Progress') {
          $status_class = 'in-progress';
          $icon = '<span class="icon-random"></span> ';
        }
        elseif ($status_name == 'Undetermined') {
          $status_class = 'undetermined';
          $icon = '<span class="icon-question-sign"></span> ';
        }
        elseif ($status_name == 'False') {
          $status_class = 'false';
          $icon = '<span class="icon-remove-sign"></span> ';
        }
        $variables['status_class'] = $status_class;
        $variables['status'] = $icon . '<span class="status-name">' . t($status_name) . '</span>';
      }
      if (user_is_logged_in()) {
        $variables['media_activity_footer'] = '';
      }
      else {
        $variables['media_activity_footer'] = t('Please <a href="@login_url">login</a> to be able to add footnotes and contribute to the fact-checking of this report.', array('@login_url' => url('user/login')));
      }
    }

    // HACK: Refs #1338, add a unique class to the ctools modal for a report
    if (arg(0) == 'report-view-modal') {
      $variables['modal_class_hack'] = '<script>jQuery("#modalContent, #modalBackdrop").addClass("modal-report");</script>';
    }

    if (isset($variables['content']['field_link'])) {
      $field_link_rendered = render($variables['content']['field_link']);

      // Never lazy-load inside the modal
      if (arg(0) != 'report-view-modal') {
        // Quick and easy, replace all src attributes with data-somethingelse
        // Drupal.behavior.lazyLoadSrc handles re-applying the src attribute when
        // the iframe tag enters the viewport.
        // See: http://stackoverflow.com/a/7154968/806988
        $placeholder = base_path() . path_to_theme() . '/assets/imgs/icons/loader_white.gif';
        $field_link_rendered = preg_replace('/<(iframe|img)([^>]*)(src)=/i', '<\1\2src="' . $placeholder . '" data-lazy-load-src=', $field_link_rendered);

        // Lazy load classes as well (for dynamic-loaded content, like tweets, for example)
        $field_link_rendered = preg_replace('/<(blockquote)([^>]*)class=/i', '<\1\2data-lazy-load-class=', $field_link_rendered);
      }

      $variables['field_link_lazy_load'] = $field_link_rendered;
    }
  }
}

function checkdesk_links__node($variables) {
  $links = $variables['links'];
  $attributes = $variables['attributes'];
  $heading = $variables['heading'];

  $class[] = 'content-actions';
  // set $alpha and $omega for language directions
  global $language;
  if ($language->direction == LANGUAGE_RTL) {
    $alpha = 'right';
    $omega = 'left';
  } else {
    $alpha = 'left';
    $omega = 'right';
  }

  $output = '';

  // Prepare for modal dialogs.
  ctools_include('modal');
  ctools_include('ajax');
  ctools_modal_add_js();
  ctools_add_js('checkdesk_core', 'checkdesk_core');

  if (count($links) > 0) {
    $output = '<ul' . drupal_attributes(array('class' => $class)) . '>';

    // if (isset($links['checkdesk-view-original'])) {
    //   $output .= '<li>' . l('<span class="icon-link"></span>', $links['checkdesk-view-original']['href'], array_merge($links['checkdesk-view-original'], array('html' => TRUE))) . '</li>';
    // }

    if (isset($links['checkdesk-share-facebook']) || 
        isset($links['checkdesk-share-twitter']) || 
        isset($links['checkdesk-share-google'])
    ) {
      // Share on
      $output .= '<li class="share-on">';
      $output .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="icon-share"></span></a>';
      $output .= '<ul class="dropdown-menu pull-'. $omega .'">';
      if (isset($links['checkdesk-share-facebook'])) {
        $output .= '<li>' . l($links['checkdesk-share-facebook']['title'], $links['checkdesk-share-facebook']['href'], $links['checkdesk-share-facebook']) . '</li>';
      }
      if (isset($links['checkdesk-share-twitter'])) {
        $output .= '<li>' . l($links['checkdesk-share-twitter']['title'], $links['checkdesk-share-twitter']['href'], $links['checkdesk-share-twitter']) . '</li>';
      }
      if (isset($links['checkdesk-share-google'])) {
        $output .= '<li>' . l($links['checkdesk-share-google']['title'], $links['checkdesk-share-google']['href'], $links['checkdesk-share-google']) . '</li>';
      }
      $output .= '</ul></li>'; 
    }

    if (isset($links['flag-spam']) || 
        isset($links['flag-graphic']) || 
        isset($links['flag-factcheck']) || 
        isset($links['flag-delete'])
    ) {
      // Flag as
      $output .= '<li class="flag-as">';
      $output .= l('<span class="icon-flag"></span>', $links['checkdesk-flag']['href'], $links['checkdesk-flag']);
      $output .= '<ul class="dropdown-menu pull-'. $omega .'">';

      if (isset($links['flag-spam'])) {
        $output .= '<li>' . $links['flag-spam']['title'] . '</li>';
      }
      if (isset($links['flag-graphic'])) {
        // $output .= '<li>' . ctools_modal_text_button('Custom title', 'node/nojs/flag/confirm/flag/graphic/74', 'Another title',  'ctools-modal-checkdesk-style') .'</li>';
        $output .= '<li>' . $links['flag-graphic']['title'] . '</li>';
      }
      if (isset($links['flag-factcheck'])) {
        $output .= '<li>' . $links['flag-factcheck']['title'] . '</li>';
      }

      if (isset($links['flag-delete'])) {
        $output .= '<li class="divider"></li>';
        $output .= '<li>' . $links['flag-delete']['title'] . '</li>';
      }
      $output .= '</ul></li>'; 
    }
     
    if (isset($links['checkdesk-suggest']) || 
        isset($links['checkdesk-edit']) || 
        isset($links['checkdesk-delete']) ||
        isset($links['flag-factcheck_journalist']) ||
        isset($links['flag-graphic_journalist'])
    ) {
      // Add to
      $output .= '<li class="add-to">';
      $output .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="icon-ellipsis-horizontal">&nbsp;</span></a>';
      $output .= '<ul class="dropdown-menu pull-'. $omega .'">';
      if (isset($links['checkdesk-suggest'])) {
        $output .= '<li>' . ctools_modal_text_button($links['checkdesk-suggest']['title'], $links['checkdesk-suggest']['href'], $links['checkdesk-suggest']['title'],  'ctools-modal-modal-popup-medium') .'</li>';
      }
      if (isset($links['checkdesk-edit'])) {
        $output .= '<li>' . l($links['checkdesk-edit']['title'], $links['checkdesk-edit']['href'], $links['checkdesk-edit']) .'</li>';
      }
      if (isset($links['checkdesk-delete'])) {
        $output .= '<li>' . l($links['checkdesk-delete']['title'], $links['checkdesk-delete']['href'], $links['checkdesk-delete']) .'</li>';
      }
      if (isset($links['flag-factcheck_journalist'])) {
        $output .= '<li class="divider"></li>';
        $output .= '<li>' . $links['flag-factcheck_journalist']['title'] .'</li>';
      }
      if (isset($links['flag-graphic_journalist'])) {
        $output .= '<li>' . $links['flag-graphic_journalist']['title'] .'</li>';
      }
      $output .= '</ul></li>';
    }

    if (isset($links['checkdesk-edit-flat']) || 
        isset($links['checkdesk-delete-flat'])
    ) {
      // Edit or delete, but not as a dropdown menu
      if (isset($links['checkdesk-edit-flat'])) {
        $output .= '<li>' . l($links['checkdesk-edit-flat']['title'], $links['checkdesk-edit-flat']['href'], $links['checkdesk-edit-flat']) .'</li>';
      }
      if (isset($links['checkdesk-delete-flat'])) {
        $output .= '<li>' . l($links['checkdesk-delete-flat']['title'], $links['checkdesk-delete-flat']['href'], $links['checkdesk-delete-flat']) .'</li>';
      }
    }

    $output .= '</ul>';
  }

  return $output;
}


/**
 * Utitity function to determine whether to show widgets or not
 */
function checkdesk_widgets_visibility() {
  global $user;
  $current_node = menu_get_object();
  // what to check for
  $roles = array('administrator', 'journalist');
  $check_role = array_intersect($roles, array_values($user->roles));
  $check_role = empty($check_role) ? FALSE : TRUE;

  $pages = array('edit', 'delete');
  $check_page = array_intersect($pages, array_values(arg()));
  $check_page = empty($check_page) ? FALSE : TRUE;

  $user_pages = array('login', 'password', 'register');
  $check_user_page = array_intersect($user_pages, array_values(arg()));
  $check_user_page = empty($check_user_page) ? FALSE : TRUE;

  // node types to check for anonymous user
  $anon_node_types = array('media', 'discussion', 'post');
  // node types to check for logged in user
  $user_node_types = array('media', 'post');

  // for anonymous user
  if (isset($current_node->type) && !$check_role) {
    foreach ($anon_node_types as $node_type) {
      // matches node types
      if ($node_type == $current_node->type) return TRUE;
    }
  // for logged in users with specific role
  } elseif (isset($current_node->type) && $check_role) {
    foreach ($user_node_types as $node_type) {
      // matches node types and does not include any pages
      if ($node_type == $current_node->type && arg(0) == 'node' && !$check_page) {
        return TRUE; 
      }
      
    }
  // for user login, register and forgot pass page
  } elseif (arg(0) == 'user' && $check_user_page) {
      return TRUE;
  }

  // Always display on front page
  if (drupal_is_front_page()) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Utitity function to determine whether to show footer or not
 */
function checkdesk_footer_visibility() {
  global $user;
  $current_node = menu_get_object();
  // what to check for
  $pages = array('edit', 'delete');
  $check_page = array_intersect($pages, array_values(arg()));
  $check_page = empty($check_page) ? FALSE : TRUE;

  // node types to check
  $node_types = array('media', 'discussion', 'post');

  // for anonymous user
  if (isset($current_node->type)) {
    foreach ($node_types as $node_type) {
      // matches node types and does not include any pages
      if ($node_type == $current_node->type && arg(0) == 'node' && !$check_page) {
        return TRUE; 
      } 
    }
  } 

  // Always display on front page
  if (drupal_is_front_page()) {
    return TRUE;
  }

  return FALSE;
}

/**
 * Adjust story blogger markup
 */
function checkdesk_checkdesk_core_story_blogger(&$variables) {
  $output = '';

  $output .= '<div class="story-blogger '. $variables['classes'] .'">';
  $output .= '<div class="avatar">' . $variables['blogger_picture'] . $variables['blogger_name'] . '</div>';
  $output .= '<div class="blogger-status '. $variables['blogger_status_class'] .'"><span class="blogger-status-indicator"></span><span class="blogger-status-text">' . $variables['blogger_status_text'] . '</span></div>';
  $output .= '</div>';

  return $output;
}

/**
 * Adjust story status (blog by and it is currently)
 */
function checkdesk_checkdesk_core_story_status(&$variables) {
  $output = '';

  $output .= '<div class="story-by">' . $variables['story_status'] . '</div>';
  $output .= '<div class="story-context">' . $variables['story_context'] . '</div>';

  return $output;
}

/**
 * Inform if story has drafts
 */
function checkdesk_checkdesk_core_story_drafts(&$variables) {
  $output = '';

  if (isset($variables['story_drafts']) && !empty($variables['story_drafts'])) $output .= '<div class="story-drafts">' . $variables['story_drafts'] . '</div>';

  return $output;
}

/**
 * Adjust report source markup
 */
function checkdesk_checkdesk_core_report_source(&$variables) {
  $output = '';

  $output .= '<span class="source-url">' . $variables['source_url_short'] . '</span> ';
  $output .= $variables['source_author'];

  return $output;
}


/**
 * Adjust node comments form
 */
function checkdesk_form_comment_form_alter(&$form, &$form_state) {
  $nid = $form['#node']->nid;
  $form['author']['homepage'] = NULL;
  $form['author']['mail'] = NULL;
  $form['actions']['submit']['#attributes']['class'] = array('btn');
  $form['actions']['submit']['#value'] = t('Add footnote');
  $form['actions']['submit']['#ajax'] = array(
    'callback' => '_checkdesk_comment_form_submit',
    'wrapper' => 'node-' . $nid,
    'method' => 'replace',
    'effect' => 'fade',
  );
  $form_state['ctools comment alter'] = FALSE;
}

function _checkdesk_comment_form_submit($form, $form_state) {
  drupal_get_messages();

  $nid = $form['#node']->nid;
  $view = views_get_view('activity_report');
  $view->set_arguments(array($nid));
  $output = $view->preview('block');

  $commands = array();
  // Update footnotes
  $commands[] = ajax_command_replace('#node-' . $nid . ' .view-activity-report', $output);
  // Update footnotes count
  $commands[] = ajax_command_replace('#node-' . $nid . ' .report-footnotes-count span', '<span>' . $view->total_rows . '</span>');
  // Clear textarea
  $commands[] = ajax_command_invoke('#node-' . $nid . ' .comment-form textarea', 'val', array(''));
  // Scroll to new footnote
  $commands[] = ajax_command_invoke('#report-activity-node-' . $nid, 'scrollToHere');

  return array('#type' => 'ajax', '#commands' => $commands);
}

function checkdesk_field__field_rating(&$variables) {
  $output = '';
  foreach($variables['items'] as $key => $tag) {
      $output = $tag['#title']; 
  }
  return $output;
}


/**
 * Adjust user login form
 */
function checkdesk_form_alter(&$form, &$form_state) {
  // user login form
  if($form['form_id']['#id'] == 'edit-user-login') {
    unset($form['social_media_signin']['#title']);
    // $form['social_media_signin']['#prefix'] = '<div class="social-media-signin-label"><span>' . t('Sign in with:') . '</span></div>';
    $form['social_media_signin']['#suffix'] = '<div class="or"><span>' . t('or') . '</span></div>';
    unset($form['name']['#description']);
    // unset($form['name']['#title']);
    unset($form['pass']['#description']);
    $form['pass']['#title'] = t('Password');
    // unset($form['pass']['#title']);
    $form['name']['#attributes']['placeholder'] = t('Username or e-mail address');
    $form['pass']['#attributes']['placeholder'] = t('Password');
    // Add forgot link and a wrapper around forgot pass and remember me
    $forgot_pass_link = l(t('Forgot your password?'), 'user/password');
    $form['pass']['#suffix'] = '<div class="user-links"><div class="user-forgot-pass-link">' . $forgot_pass_link . '</div>';
    $form['remember_me']['#suffix'] = '</div>';
    // add a wrapper around new account link
    unset($form['links']);
    $link['attributes']['class'][] = 'register';
    $link['attributes']['title'] = t('Sign Up');
    $new_account_link = l(t('Sign Up'), 'user/register', $link);
    $form['actions']['submit']['#suffix'] = '<div class="user-new-account-link"><span>' . t('Don\'t have an account? ') . '</span>
    ' . $new_account_link . '</div>';    
  }
  // create new account form
  if($form['form_id']['#id'] == 'edit-user-register-form') {  
    $form['account']['name']['#attributes']['placeholder'] = t('Username');
    unset($form['account']['name']['#description']);
    $form['account']['mail']['#attributes']['placeholder'] = t('E-mail address');
    unset($form['account']['mail']['#description']);
    unset($form['account']['pass']['#description']);
  }
  // forgot password form
  if($form['form_id']['#id'] == 'edit-user-pass') {
    $form['name']['#attributes']['placeholder'] = t('Username or e-mail address');
  }
}

function checkdesk_fboauth_action__connect(&$variables) {
  $action = $variables['action'];
  $link = $variables['properties'];
  $url = url($link['href'], array('query' => $link['query']));
  $link['attributes']['class'] = isset($link['attributes']['class']) ? $link['attributes']['class'] : 'facebook-action-connect';
  // Button i18n.
  $language = $GLOBALS['language']->language;
  $link['attributes']['class'] .= " fb-button-$language";
  $attributes = isset($link['attributes']) ? drupal_attributes($link['attributes']) : '';
  $title = isset($link['title']) ? check_plain($link['title']) : '';
  $text = t('Facebook');
  return "<a $attributes href='$url' alt='$title'>$text</a>";
}

/**
 * Change Twitter login button from image to simple
 */
function checkdesk_twitter_signin_button() {
  $link['attributes']['class'][] = 'twitter-action-signin';
  $link['attributes']['title'] = t('Sign In with Twitter');
  return l(t('Twitter'), 'twitter/redirect', $link);
}



/**
 * Adjust compose update form
 */
function checkdesk_form_post_node_form_alter(&$form, &$form_state) {
  $form['title']['#attributes']['placeholder'] = t('Update headline');

  // $form['body'][LANGUAGE_NONE][0]['#title'] = NULL;
  $form['body'][LANGUAGE_NONE][0]['#attributes']['placeholder'] = t('Write text and drag reports here to compose the update');
}

/**
 * Adjust create story form
 */
function checkdesk_form_discussion_node_form_alter(&$form, &$form_state) {
  $form['title']['#title'] = t('Story title');
  $form['title']['#attributes']['placeholder'] = t('Story title');

  $form['body'][LANGUAGE_NONE][0]['#attributes']['placeholder'] = t('Add a brief description of the story (optional)');
  $form['body'][LANGUAGE_NONE][0]['#description'] = t('A story contains one or more liveblog updates. The story will remain unpublished until the first update is created.');

  $form['field_lead_image']['#prefix'] = '<div class="custom_file_upload">';
  $form['field_lead_image']['#suffix'] = '</div">';
  $form['field_lead_image'][LANGUAGE_NONE][0]['#title'] = t('Add feature image');
}



/**
 * Theme views
 */
function checkdesk_preprocess_views_view(&$vars) {
  global $base_path;
  $vars['base_path'] = $base_path;
  // set template functions for individual views
  if (isset($vars['view']->name)) {
    $function = 'checkdesk_preprocess_views_view__'.$vars['view']->name; 
    if (function_exists($function)) {
      $function($vars);
    }
  }
}

/* Desk Reports */
function checkdesk_preprocess_views_view__desk_reports(&$vars) {
  if ($vars['display_id'] == 'block') {
    _checkdesk_ensure_reports_modal_js();
  }
}

/* Desk Updates */
function checkdesk_preprocess_views_view__desk_updates(&$vars) {
  if ($vars['display_id'] == 'block') {
    
  }
}

/* Reports page */
function checkdesk_preprocess_views_view__reports(&$vars) {
  // add masonry library
  drupal_add_js(drupal_get_path('theme', 'checkdesk') .'/assets/js/libs/jquery.masonry.min.js', 'file', array('group' => JS_THEME, 'every_page' => FALSE));
  _checkdesk_ensure_reports_modal_js();
}

function _checkdesk_ensure_reports_modal_js() {
  ctools_include('modal');
  ctools_modal_add_js();
  $modal_style = array(
    'modal-popup-report' => array(
      'modalSize' => array(
        'type' => 'fixed',
        'width' => 700,
        'height' => 540,
        'addWidth' => 0,
        'addHeight' => 0
      ),
      'modalOptions' => array(
        'opacity' => .5,
        'background-color' => '#000',
      ),
      'animation' => 'show',
      'animationSpeed' => 40,
      'modalTheme' => 'CToolsModalDialog',
      'throbber' => theme('image', array('path' => ctools_image_path('ajax-loader.gif', 'checkdesk_core'), 'alt' => t('Loading...'), 'title' => t('Loading'))),
    ),
  );
  drupal_add_js($modal_style, 'setting');
}

/**
 * Adjust edit node form
 */
function checkdesk_form_media_node_form_alter(&$form, &$form_state) {
  $form['field_link'][LANGUAGE_NONE][0]['#title'] = t('URL');
  if (isset($form['nid']['#value'])) {
    $node = $form['#node'];
    unset($form['field_stories']);
    drupal_set_title(t('Edit @type <em>@title</em>', array('@type' => t('Report'), '@title' => $node->title)), PASS_THROUGH);
  }
}

/**
 * Implements template_preprocess_views_view_fields().
 */
function checkdesk_preprocess_views_view_fields(&$vars) {
  if (in_array($vars['view']->name, array('reports', 'desk_reports'))) {
    $vars['name_i18n'] = t($vars['fields']['field_rating']->content);
  }

  if ($vars['view']->name === 'liveblog') {
    $vars['updates'] = $vars['view']->result[$vars['view']->row_index]->updates;
  }

  if ($vars['view']->name === 'updates_for_stories') {
    $vars['counter'] = intval($vars['view']->total_rows) - intval(strip_tags($vars['fields']['counter']->content)) + 1;
    if ($vars['counter'] === $vars['view']->total_rows) {
      $vars['update'] = $vars['fields']['rendered_entity']->content;
    }
    else {
      $vars['update'] = $vars['fields']['rendered_entity_1']->content;
    }
  }
}
