<?php

// No direct access!
defined( 'ABSPATH' ) OR exit;

// Make sure base class exists
if ( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class FCNEN_Notifications_Table
 *
 * @since 0.1.0
 */

class FCNEN_Notifications_Table extends WP_List_Table {
  private $table_data;
  private $uri = '';

  public $total_items;

  /**
   * Constructor for the WP_List_Table subclass.
   *
   * @since Fictioneer Email Subscriptions 1.0.0
   */

  function __construct() {
    parent::__construct([
      'singular' => 'notification',
      'plural' => 'notifications',
      'ajax' => false
    ]);

    // Validate GET actions
    if ( isset( $_GET['action'] ) ) {
      if ( ! isset( $_GET['fcnen-nonce'] ) || ! check_admin_referer( 'fcnen-table-action', 'fcnen-nonce' ) ) {
        wp_die( __( 'Nonce verification failed. Please try again.', 'fcnen' ) );
      }
    }

    // Validate POST actions
    if ( isset( $_POST['action'] ) ) {
      if ( ! isset( $_POST['_wpnonce'] ) || ! check_admin_referer( 'bulk-' . $this->_args['plural'] ) ) {
        wp_die( __( 'Nonce verification failed. Please try again.', 'fcnen' ) );
      }
    }

    // Initialize
    $this->uri = remove_query_arg(
      ['action', 'id', 'notifications', 'fcnen-nonce', 'fcnen-notice', 'fcnen-message'],
      $_SERVER['REQUEST_URI']
    );
  }

  /**
   * Retrieve the column headers for the table
   *
   * @since 0.1.0
   *
   * @return array Associative array of column names with their corresponding labels.
   */

  function get_columns() {
    return array(
      'cb' => '<input type="checkbox" />',
      'post_title' => __( 'Title', 'fcnen' ),
      'status' => __( 'Status', 'fcnen' ),
      'post_author' => __( 'Author', 'fcnen' ),
      'post_id' => __( 'Post ID', 'fcnen' ),
      'post_type' => __( 'Type', 'fcnen' ),
      'added_at' => __( 'Date', 'fcnen' ),
      'last_sent' => __( 'Last Sent', 'fcnen' )
    );
  }

  /**
   * Prepare the items for display in the table
   *
   * @since 0.1.0
   */

  function prepare_items() {
    // Setup
    $columns = $this->get_columns();
    $hidden = [];
    $sortable = $this->get_sortable_columns();
    $primary = 'post_title';

    // Hidden columns?
    // if ( is_array( get_user_meta( get_current_user_id(), 'managenotifications_page_fcnen-subscriberscolumnshidden', true ) ) ) {
    //   $hidden = get_user_meta( get_current_user_id(), 'managenotifications_page_fcnen-subscriberscolumnshidden', true );
    // }

    // Data
    $this->table_data = $this->get_table_data();
    $this->_column_headers = [ $columns, $hidden, $sortable, $primary ];

    // Post data
    $post_data = [];
    $post_ids = array_column( $this->table_data, 'post_id' );

    $posts = get_posts(
      array(
        'post_type' => ['post', 'fcn_story', 'fcn_chapter'],
        'post__in' => $post_ids ?: [0],
        'numberposts' => -1,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'no_found_rows' => true
      )
    );

    foreach ( $posts as $post ) {
      $post_data[ $post->ID ] = array(
        'post_link' => get_permalink( $post ),
        'post_title' => $post->post_title,
        'post_type' => $post->post_type,
        'post_status' => $post->post_status,
        'post_password' => $post->post_password,
        'post_author' => $post->post_author
      );
    }

    // Prime author cache
    if ( function_exists( 'update_post_author_caches' ) ) {
      update_post_author_caches( $posts );
    }

    // Merge datasets
    foreach ( $this->table_data as $key => $notification ) {
      $post_id = $notification['post_id'];

      if ( isset( $post_data[ $post_id ] ) ) {
        $notification = array_merge( $notification, $post_data[ $post_id ] );
      }

      $notification['status'] = fcnen_post_sendable( $post_id, true );

      $notification['last_sent'] = ! empty( $notification['last_sent'] ) ?
        __( 'Mailed', 'fcnen' ) . '<br>' . $notification['last_sent'] : '';

      $this->table_data[ $key ] = $notification;
    }

    // Prepare rows
    $this->items = $this->table_data;
  }

  /**
   * Retrieve the data for the table
   *
   * @since 0.1.0
   * @global wpdb $wpdb  The WordPress database object.
   *
   * @return array The table data with appended post data.
   */

  function get_table_data() {
    global $wpdb;

    // Guard
    if ( ! current_user_can( 'manage_options' ) ) {
      return [];
    }

    // Setup
    $table_name = $wpdb->prefix . 'fcnen_notifications';
    $per_page = $this->get_items_per_page( 'fcnen_notifications_per_page', 25 );
    $current_page = $this->get_pagenum();
    $offset = ( $per_page * max( 0, absint( $current_page ) - 1 ) );
    $orderby = sanitize_text_field( $_GET['orderby'] ?? 'id' );
    $order = strtolower( $_GET['order'] ?? 'desc' ) === 'desc' ? 'DESC' : 'ASC';

    // Sanitize orderby
    $orderby = in_array( $orderby, ['post_id', 'post_title', 'post_type', 'post_author', 'added_at', 'last_sent'] ) ?
      $orderby : 'added_at';

    // Total items
    $this->total_items = $wpdb->get_var( "SELECT COUNT(post_id) FROM {$table_name}" );

    // Search?
    if ( ! empty( $_POST['s'] ?? '' ) ) {
      $search = sanitize_text_field( $_POST['s'] );
      $query = "SELECT * FROM {$table_name} WHERE post_title LIKE '%" . $wpdb->esc_like( $search ) . "%'";
    } else {
      $query = "SELECT * FROM {$table_name}";
    }

    // Prepare for extension
    // if ( ! strpos( $query, 'WHERE' ) ) {
    //   $query .= ' WHERE ';
    // } else {
    //   $query .= ' AND ';
    // }

    // Order
    $query .= " ORDER BY {$orderby} {$order}";

    // Query
    $notifications = $wpdb->get_results( $wpdb->prepare( "{$query} LIMIT %d OFFSET %d", $per_page, $offset ), ARRAY_A );

    // Pagination
    $this->set_pagination_args(
      array(
        'total_items' => $this->total_items,
        'per_page' => $per_page,
        'total_pages' => ceil( $this->total_items / $per_page )
      )
    );

    // Return results
    return $notifications;
  }

  /**
   * Renders the content of the "cb" column
   *
   * @since 0.1.0
   *
   * @param array $item  The data for the current row item.
   *
   * @return string The "cb" column content.
   */

  function column_cb( $item ) {
    return sprintf( '<input type="checkbox" name="notifications[]" value="%s" />', $item['post_id'] );
  }

  /**
   * Render the content of the "post_title" column
   *
   * @since 0.1.0
   *
   * @param array $item  The data for the current row item.
   *
   * @return string The "post_title" column content.
   */

  function column_post_title( $item ) {
    // Setup
    $actions = [];
    $notes = [];
    $title = '';
    $suffix = '';

    // Chapter?
    if ( $item['post_type'] === 'fcn_chapter' ) {
      $story_id = get_post_meta( $item['post_id'], 'fictioneer_chapter_story', true );
      $story_title = get_the_title( $story_id ) ?: '';

      // Story title as suffix
      if ( ! empty( $story_title ) ) {
        $story_title = mb_strimwidth( $story_title, 0, 25, '…' ); // Truncate to max 24 characters
        $suffix = " &mdash; {$story_title}";
      }

      // Hidden?
      if ( ! empty( get_post_meta( $item['post_id'], 'fictioneer_chapter_hidden', true ) ) ) {
        $notes[] = __( 'Hidden', 'fcnen' );
      }
    }

    // Story?
    if ( $item['post_type'] === 'fcn_story' ) {
      // Hidden?
      if ( ! empty( get_post_meta( $item['post_id'], 'fictioneer_story_hidden', true ) ) ) {
        $notes[] = __( 'Hidden', 'fcnen' );
      }
    }

    // Build title
    $title = sprintf(
      _x( '<a href="%1$s">%2$s</a> %3$s %4$s', 'Notification list table title column.', 'fcnen' ),
      $item['post_link'],
      mb_strimwidth( trim( $item['post_title'] ), 0, 41, '…' ), // Truncate to max 40 characters
      $suffix,
      empty( $notes ) ? '' : '(' . implode( ', ', $notes ) . ')'
    );

    // Unsent action
    if ( ! empty( $item['last_sent'] ) ) {
      $actions['unsent'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg(
            array( 'action' => 'unsent_notification', 'id' => $item['post_id'] ),
            $this->uri
          ),
          'fcnen-table-action',
          'fcnen-nonce'
        ),
        __( 'Unsent', 'fcnen' )
      );
    }

    // Pause action
    if ( empty( $item['last_sent'] ) ) {
      $actions['pause'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg(
            array( 'action' => 'pause_notification', 'id' => $item['post_id'] ),
            $this->uri
          ),
          'fcnen-table-action',
          'fcnen-nonce'
        ),
        __( 'Pause', 'fcnen' )
      );
    }

    // Unpause action
    if ( $item['paused'] ) {
      $actions['unpause'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg(
            array( 'action' => 'unpause_notification', 'id' => $item['post_id'] ),
            $this->uri
          ),
          'fcnen-table-action',
          'fcnen-nonce'
        ),
        __( 'Unpause', 'fcnen' )
      );
    }

    // Delete action
    $actions['delete'] = sprintf(
      '<a href="%s">%s</a>',
      wp_nonce_url(
        add_query_arg(
          array( 'action' => 'delete_notification', 'id' => $item['post_id'] ),
          $this->uri
        ),
        'fcnen-table-action',
        'fcnen-nonce'
      ),
      __( 'Remove', 'fcnen' )
    );

    // Return the final output
    return sprintf(
      '<span>%s</span> %s',
      trim( $title ),
      $this->row_actions( $actions )
    );
  }

  /**
   * Render the content of the "post_author" column
   *
   * @since 0.1.0
   *
   * @param array $item  The data for the current row item.
   *
   * @return string The "post_author" column content.
   */

  function column_post_author( $item ) {
    return empty( $item['post_author'] ) ? '&mdash;' : get_the_author_meta( 'display_name', $item['post_author'] ) ;
  }

  /**
   * Render the content of the "status" column
   *
   * @since 0.1.0
   *
   * @param array $item  The data for the current row item.
   *
   * @return string The "status" column content.
   */

  function column_status( $item ) {
    if ( $item['status']['sendable'] ?? 0 ) {
      return _x( 'Ready', 'Notification list table status column.', 'fcnen' );
    }

    $status = '';

    switch ( $item['status']['message'] ) {
      case 'post-unpublished':
        $status = _x( 'Blocked:<br>Unpublished', 'Notification list table status column.', 'fcnen' );
        break;
      case 'post-protected':
        $status = _x( 'Blocked:<br>Protected', 'Notification list table status column.', 'fcnen' );
        break;
      case 'post-invalid-type':
        $status = _x( 'Blocked:<br>Invalid', 'Notification list table status column.', 'fcnen' );
        break;
      case 'post-excluded':
        $status = _x( 'Blocked:<br>Excluded', 'Notification list table status column.', 'fcnen' );
        break;
      case 'post-hidden':
        $status = _x( 'Blocked:<br>Hidden', 'Notification list table status column.', 'fcnen' );
        break;
      default:
        $status = _x( 'Blocked', 'Notification list table status column.', 'fcnen' );
    }

    return $status;
  }

  /**
   * Render the content of the "post_id" column
   *
   * @since 0.1.0
   *
   * @param array $item  The data for the current row item.
   *
   * @return string The "post_id" column content.
   */

  function column_post_id( $item ) {
    return $item['post_id'];
  }

  /**
   * Render the content of the "post_type" column
   *
   * @since 0.1.0
   *
   * @param array $item  The data for the current row item.
   *
   * @return string The "post_type" column content.
   */

  function column_post_type( $item ) {
    $type_object = get_post_type_object( $item['post_type'] );

    return $type_object->labels->singular_name;
  }

  /**
   * Render the content of the "added_at" column
   *
   * @since 0.1.0
   *
   * @param array $item  The data for the current row item.
   *
   * @return string The "added_at" column content.
   */

  function column_added_at( $item ) {
    return __( 'Enqueued', 'fcnen' ) . '<br>' . $item['added_at'];
  }

  /**
   * Render the content of the "last_sent" column
   *
   * @since 0.1.0
   *
   * @param array $item  The data for the current row item.
   *
   * @return string The "last_sent" column content.
   */

  function column_last_sent( $item ) {
    return empty( $item['last_sent'] ) ?
      '&mdash;' : __( 'Mailed', 'fcnen' ) . '<br>' . $item['last_sent'];
  }

  /**
   * Retrieve the bulk actions available for the table
   *
   * @since 0.1.0
   *
   * @return array An associative array of bulk actions. The keys represent the action identifiers,
   *               and the values represent the action labels.
   */

  function get_bulk_actions() {
    return array(
      'remove_all' => __( 'Remove', 'fcnen' ),
      'unsent_all' => __( 'Unsent', 'fcnen' )
    );
  }

  /**
   * Render extra content in the table navigation section
   *
   * @since 0.1.0
   *
   * @param string $which  The position of the navigation, either "top" or "bottom".
   */

  function extra_tablenav( $which ) {
  }

  /**
   * Perform actions based on the GET and POST requests
   *
   * @since 0.1.0
   * @global wpdb $wpdb  The WordPress database object.
   */

  function perform_actions() {
    global $wpdb;

    // Guard
    if ( ! current_user_can( 'manage_options' ) ) {
      return;
    }

    // Setup
    $table_name = $wpdb->prefix . 'fcnen_notifications';
    $query_args = [];

    // GET actions
    if ( isset( $_GET['action'] ) ) {
      $id = absint( $_GET['id'] ?? 0 );

      // Delete notifications
      if ( ! empty( $id ) && $_GET['action'] === 'delete_notification' ) {

      }

      // Pause notifications
      if ( ! empty( $id ) && $_GET['action'] === 'pause_notification' ) {

      }

      // Unpause notifications
      if ( ! empty( $id ) && $_GET['action'] === 'unpause_notification' ) {

      }

      // Unsent notifications
      if ( ! empty( $id ) && $_GET['action'] === 'unsent_notification' ) {

      }

      // Redirect with notice (prevents multi-submit)
      wp_safe_redirect( add_query_arg( $query_args, $this->uri ) );
      exit();
    }

    // POST actions
    if ( isset( $_POST['action'] ) && empty( $_POST['s'] ?? 0 ) ) {

      // Redirect with notice (prevents multi-submit)
      wp_safe_redirect( add_query_arg( $query_args, $this->uri ) );
      exit();
    }
  }

  /**
   * Retrieve the sortable columns for the table
   *
   * @since 0.1.0
   *
   * @return array An associative array of sortable columns and their sort parameters.
   *               The keys represent the column names, and the values are arrays
   *               with the column key and sort order (true for ascending, false for descending).
   */

  protected function get_sortable_columns() {
    return array(
      'post_title' => ['post_title', false],
      'post_id' => ['post_id', false],
      'post_type' => ['post_type', false],
      'added_at' => ['added_at', false],
      'last_sent' => ['last_sent', false]
    );
  }
}
