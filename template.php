<?php

/**
 * Override or insert variables into the node templates.
 *
 * @param $variables
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered ("node" in this case.)
 */
function zen_preprocess_node(&$variables, $hook) {
  // Add $unpublished variable.
  $variables['unpublished'] = (!$variables['status']) ? TRUE : FALSE;

  // If the node is unpublished, add the "unpublished" watermark class.
  if ($variables['unpublished'] || isset($variables['preview']) && $variables['preview']) {
    $variables['classes_array'][] = 'watermark__wrapper';
  }
}

/**
 * Override or insert variables into the comment templates.
 *
 * @param $variables
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered ("comment" in this case.)
 */
function zen_preprocess_comment(&$variables, $hook) {
  // Add $unpublished variable.
  $variables['unpublished'] = ($variables['status'] == 'comment-unpublished') ? TRUE : FALSE;

  // Add $preview variable.
  $variables['preview'] = ($variables['status'] == 'comment-preview') ? TRUE : FALSE;

  // If comment subjects are disabled, don't display them.
  if (variable_get('comment_subject_field_' . $variables['node']->type, 1) == 0) {
    $variables['title'] = '';
  }

  // If the comment is unpublished/preview, add a "unpublished" watermark class.
  if ($variables['unpublished'] || $variables['preview']) {
    $variables['classes_array'][] = 'watermark__wrapper';
  }

  // Add the comment__permalink class.
  $uri = entity_uri('comment', $variables['comment']);
  $uri_options = $uri['options'] + array('attributes' => array('class' => array('comment__permalink'), 'rel' => 'bookmark'));
  $variables['permalink'] = l(t('Permalink'), $uri['path'], $uri_options);

  // Remove core's permalink class and add the comment__title class.
  $variables['title_attributes_array']['class'][] = 'comment__title';
  $uri_options = $uri['options'] + array('attributes' => array('rel' => 'bookmark'));
  $variables['title'] = l($variables['comment']->subject, $uri['path'], $uri_options);
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 *
 * Prevent user-facing field styling from screwing up node edit forms by
 * renaming the classes on the node edit form's field wrappers.
 */
function zen_form_node_form_alter(&$form, &$form_state, $form_id) {
  // Remove if #1245218 is backported to D7 core.
  foreach (array_keys($form) as $item) {
    if (strpos($item, 'field_') === 0) {
      if (!empty($form[$item]['#attributes']['class'])) {
        foreach ($form[$item]['#attributes']['class'] as &$class) {
          // Core bug: the field-type-text-with-summary class is used as a JS hook.
          if ($class != 'field-type-text-with-summary' && strpos($class, 'field-type-') === 0 || strpos($class, 'field-name-') === 0) {
            // Make the class different from that used in theme_field().
            $class = 'form-' . $class;
          }
        }
      }
    }
  }
}

/**
 * Implements hook_preprocess_menu_link().
 */
function zen_preprocess_menu_link(&$variables, $hook) {
  // Normalize menu item classes to be an array.
  if (empty($variables['element']['#attributes']['class'])) {
    $variables['element']['#attributes']['class'] = array();
  }
  $menu_item_classes =& $variables['element']['#attributes']['class'];
  if (!is_array($menu_item_classes)) {
    $menu_item_classes = array($menu_item_classes);
  }

  // Normalize menu link classes to be an array.
  if (empty($variables['element']['#localized_options']['attributes']['class'])) {
    $variables['element']['#localized_options']['attributes']['class'] = array();
  }
  $menu_link_classes =& $variables['element']['#localized_options']['attributes']['class'];
  if (!is_array($menu_link_classes)) {
    $menu_link_classes = array($menu_link_classes);
  }

  // Add BEM-style classes to the menu item classes.
  $extra_classes = array('menu__item');
  foreach ($menu_item_classes as $key => $class) {
    switch ($class) {
      // Menu module classes.
      case 'expanded':
      case 'collapsed':
      case 'leaf':
      case 'active':
      // Menu block module classes.
      case 'active-trail':
        $extra_classes[] = 'is-' . $class;
        break;
      case 'has-children':
        $extra_classes[] = 'is-parent';
        break;
    }
  }
  $menu_item_classes = array_merge($extra_classes, $menu_item_classes);

  // Add BEM-style classes to the menu link classes.
  $extra_classes = array('menu__link');
  if (empty($menu_link_classes)) {
    $menu_link_classes = array();
  }
  else {
    foreach ($menu_link_classes as $key => $class) {
      switch ($class) {
        case 'active':
        case 'active-trail':
          $extra_classes[] = 'is-' . $class;
          break;
      }
    }
  }
  $menu_link_classes = array_merge($extra_classes, $menu_link_classes);
}

/**
 * Returns HTML for status and/or error messages, grouped by type.
 */
function zen_status_messages($variables) {
  $display = $variables['display'];
  $output = '';

  // Allow a preprocess function to override the default SVG icons.
  if (!isset($variables['icon'])) {
    $variables['icon'] = array();
    foreach (array('status', 'warning', 'error') as $type) {
      // Add a GPL-licensed icon from IcoMoon. https://icomoon.io/#preview-free
      $icon_size = 'width="24" height="24"';
      // All of the IcoMoon SVGs have the same header.
      $variables['icon'][$type] = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" ' . $icon_size . ' viewBox="0 0 64 64">';
      switch ($type) {
        case 'error':
          $variables['icon'][$type] .= '<path d="M63.416 51.416c-0-0-0.001-0.001-0.001-0.001l-19.415-19.416 19.415-19.416c0-0 0.001-0 0.001-0.001 0.209-0.209 0.36-0.453 0.457-0.713 0.265-0.711 0.114-1.543-0.458-2.114l-9.172-9.172c-0.572-0.572-1.403-0.723-2.114-0.458-0.26 0.097-0.504 0.248-0.714 0.457 0 0-0 0-0.001 0.001l-19.416 19.416-19.416-19.416c-0-0-0-0-0.001-0.001-0.209-0.209-0.453-0.36-0.713-0.457-0.711-0.266-1.543-0.114-2.114 0.457l-9.172 9.172c-0.572 0.572-0.723 1.403-0.458 2.114 0.097 0.26 0.248 0.505 0.457 0.713 0 0 0 0 0.001 0.001l19.416 19.416-19.416 19.416c-0 0-0 0-0 0.001-0.209 0.209-0.36 0.453-0.457 0.713-0.266 0.711-0.114 1.543 0.458 2.114l9.172 9.172c0.572 0.572 1.403 0.723 2.114 0.458 0.26-0.097 0.504-0.248 0.713-0.457 0-0 0-0 0.001-0.001l19.416-19.416 19.416 19.416c0 0 0.001 0 0.001 0.001 0.209 0.209 0.453 0.36 0.713 0.457 0.711 0.265 1.543 0.114 2.114-0.458l9.172-9.172c0.572-0.572 0.723-1.403 0.458-2.114-0.097-0.26-0.248-0.504-0.457-0.713z" fill="#000000"></path>';
          break;
        case 'warning':
          $variables['icon'][$type] .= '<path d="M26,64l12,0c1.105,0 2,-0.895 2,-2l0,-9c0,-1.105 -0.895,-2 -2,-2l-12,0c-1.105,0 -2,0.895 -2,2l0,9c0,1.105 0.895,2 2,2Z" fill="#000000"></path><path d="M26,46l12,0c1.105,0 2,-0.895 2,-2l0,-42c0,-1.105 -0.895,-2 -2,-2l-12,0c-1.105,0 -2,0.895 -2,2l0,42c0,1.105 0.895,2 2,2Z" fill="#000000"></path>';
          break;
        default:
          $variables['icon'][$type] .= '<path d="M54 8l-30 30-14-14-10 10 24 24 40-40z" fill="#000000"></path>';
      }
      $variables['icon'][$type] .= '</svg>';
    }
  }

  $status_heading = array(
    'status' => t('Status message'),
    'error' => t('Error message'),
    'warning' => t('Warning message'),
  );
  foreach (drupal_get_messages($display) as $type => $messages) {
    $output .= "<div class=\"messages messages--$type\">\n";
    if (!empty($status_heading[$type])) {
      $output .= '<h2 class="visually-hidden">' . $status_heading[$type] . "</h2>\n";
    }

    if (!empty($variables['icon'])) {
      $output .= '<div class="messages__icon">';
      switch ($type) {
        case 'error':
        case 'warning':
          $output .= $variables['icon'][$type];
          break;
        default:
          $output .= $variables['icon']['status'];
      }
      $output .= "</div>";
    }

    if (count($messages) > 1) {
      $output .= " <ul class=\"messages__list\">\n";
      foreach ($messages as $message) {
        $output .= '  <li class="messages__item">' . $message . "</li>\n";
      }
      $output .= " </ul>\n";
    }
    else {
      $output .= reset($messages);
    }
    $output .= "</div>\n";
  }
  return $output;
}

/**
 * Returns HTML for a marker for new or updated content.
 */
function zen_mark($variables) {
  $type = $variables['type'];

  if ($type == MARK_NEW) {
    return ' <mark class="highlight-mark">' . t('new') . '</mark>';
  }
  elseif ($type == MARK_UPDATED) {
    return ' <mark class="highlight-mark">' . t('updated') . '</mark>';
  }
}
