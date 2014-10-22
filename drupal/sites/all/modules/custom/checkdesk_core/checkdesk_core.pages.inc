<?php


/**
 * Render collaborate page.
 *
 * @parm object $node
 *    story node object
 *
 * @return
 *   Return a story collaboration page.
 */
function _checkdesk_story_collaboration($node) {
  global $user;
  $output = '';
  if ($user->uid == 0) {
    // Include the CTools tools that we need.
    ctools_include('ajax');
    ctools_include('modal');
    ctools_modal_add_js();
    // Custom modal settings arrays
    $modal_style_login = array(
      'modal-popup-login' => array(
        'modalSize' => array(
          'type' => 'fixed',
          'width' => 700,
          'height' => 450,
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
    drupal_add_js($modal_style_login, 'setting');
    $output .= '<div id="cd-collaboration-semi-frosted" style="display:none">' . ctools_modal_text_button(t('Modal Login (custom style)'),
      'story-collaboration-anonymous/nojs/login/'. arg(1), t('Login via modal'),
      'ctools-modal-modal-popup-login') .'</div>';
    drupal_add_js('
      jQuery(window).bind("load", function() {
        jQuery(".ctools-modal-modal-popup-login").click();
      });
      jQuery(function() {
        Drupal.behaviors.collanon = {
          attach: function (context, settings) {
            jQuery(".ctools-modal-content .modal-header").hide();
            jQuery(document).unbind("keydown", modalEventEscapeCloseHandler);
            jQuery("#modalBackdrop").unbind("click");
          }
        };
      });
      ', 'inline');
  }
  $node_view = node_view($node, 'checkdesk_collaborate');
  $output .= render($node_view);
  return $output;
}

/**
 *
 *
 */
function _checkdesk_story_links($nid) {
  global $user;
  $output = '';
  if ($user->uid) {
    $items = array();

    $items[] = array(
      'data' => l(t('Add a report'), 'node/add/media', array('query' => array('ref_nid' => $nid, drupal_get_destination()))),
      'class' => array('submit-report'),
    );

    if (user_access('create post content')) {
      $items[] = array(
        'data' => l(t('Compose update'), 'node/add/post', array('query' => array('story' => $nid))),
        'class' => array('compose-update'),
      );
    }
    $output .= theme('item_list', array('items' => $items, 'title' => '', 'attributes' => array('class' => 'story-collaborate-links')));
  }

  // Add tooltip for add a report.
  if (isset($_SESSION['report_tooltip'])) {
    $tour = demo_tour_preset_load('journalist');
    $tooltip = '<span class="popover tour-tour fade right in collaboration-report-tooltip">';
    $tooltip .= '  <div class="popover-arrow"></div>';
    $tooltip .= '  <div class="popover-close" data-role="end" onclick="jQuery(this).parent().hide();"><span>[X]</span></div>';
    $tooltip .= '  <h3><span class="popover-title">' . t($tour->steps[1]['title']) . '</span></h3>';
    $tooltip .= '  <div class="popover-content">' . t($tour->steps[1]['content']) . '</div>';
    $tooltip .= '</span>';
    $output .= $tooltip;
    unset($_SESSION['report_tooltip']);
  }

  return $output;
}

/**
 * List story collaborators
 *  @param story_nid
 *
 */
function _checkdesk_story_get_collaborators($nid) {
  $output = '';
  //ToDO check node type
  $users = db_query('
    SELECT ha.uid, u.name, f.fid, f.uri
    FROM {heartbeat_activity} ha
    INNER JOIN {users} u ON ha.uid = u.uid
    LEFT JOIN {file_managed} f ON u.picture = f.fid
    WHERE nid_target = :nid AND message_id != :message_id
    GROUP BY ha.uid
    ORDER BY COUNT(ha.uid) DESC
    ', array(':nid' => arg(1), ':message_id' => 'checkdesk_follow_story'))->fetchAll();
  $total_users = count($users);
  if ($total_users) {
    $num_to_show = variable_get('collaborators_display_count', 3);
    $title = format_plural($total_users, '1 collaborator', '@count collaborators');
    $extra_users = $total_users > $num_to_show ? $total_users - $num_to_show : 0;
    $uitems = $pitems = array();
    if ($extra_users) {
      $users = array_slice($users, 0, $num_to_show);
    }
    foreach ($users as $user) {
      // split user name into first name and last
      $fullname = explode(' ', $user->name);
      $fname = array_shift($fullname);
      $uitems[] = l($fname, 'user/'. $user->uid);
      $avatar = _set_user_avatar_bg(user_load($user->uid));
      if ($avatar) {
        $pitems[] = $avatar;
      }
    }
    $output .= '<div class="story-collaborators-wrapper"><div class="story-collaborators"><span class="sc-users"><h3>' . $title . '</h3>' . implode(', ', $uitems) . '</span>';
    if ($extra_users) {
      $output .= '<span class="sc-extra-users">+ '. $extra_users .'</span>';
    }
    $output .= '<span class="sc-avatars">';
    foreach ($pitems as $pitem) {
      $output .= $pitem;
    }
    $output .= '</span></div></div>';
  }
  return $output;
}

/**
 * A modal login callback.
 */
function story_collaboration_anonymous_login($js = NULL) {
  $story_nid = arg(3);
  // Fall back if $js is not set.
  if (!$js) {
    return drupal_get_form('user_login');
  }

  ctools_include('modal');
  ctools_include('ajax');
  $form_state = array(
    'title' => t('Login'),
    'ajax' => TRUE,
    'story' => $story_nid,
  );

  $output = ctools_modal_form_wrapper('user_login', $form_state);
  if (!empty($form_state['executed'])) {
    ctools_add_js('ajax-responder');
    //$commands[] = ctools_modal_command_dismiss();
    $commands[] = ctools_ajax_command_redirect('story-collaboration/' . $form_state['story']);
    print ajax_render($commands);
    exit();
  }
  print ajax_render($output);
  exit;
}