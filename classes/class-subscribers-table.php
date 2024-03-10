<?php

// No direct access!
defined( 'ABSPATH' ) OR exit;

// Make sure base class exists
if ( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class FCNCN_Subscribers_Table
 *
 * @since 0.1.0
 */

class FCNCN_Subscribers_Table extends WP_List_Table {
  private $table_data;
  private $view = '';
  private $uri = '';

  public $total_items = 0;
  public $all_count = 0;
  public $confirmed_count = 0;
  public $trashed_count = 0;
  public $pending_count = 0;

  /**
   * Constructor for the WP_List_Table subclass.
   *
   * @since 0.1.0
   */

  function __construct() {
    global $wpdb;

    parent::__construct([
      'singular' => 'subscriber',
      'plural' => 'subscribers',
      'ajax' => false
    ]);

    // Validate GET actions
    if ( isset( $_GET['action'] ) ) {
      if ( ! isset( $_GET['fcncn-nonce'] ) || ! check_admin_referer( 'fcncn-table-action', 'fcncn-nonce' ) ) {
        wp_die( __( 'Nonce verification failed. Please try again.', 'fcncn' ) );
      }
    }

    // Initialize
    $table_name = $wpdb->prefix . 'fcncn_subscribers';
    $this->confirmed_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE confirmed = 1 AND trashed = 0" );
    $this->pending_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE confirmed = 0 AND trashed = 0" );
    $this->trashed_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE trashed = 1" );
    $this->all_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ) - $this->trashed_count;
    $this->view = $_GET['view'] ?? '';
    $this->uri = remove_query_arg( ['action', 'id', 'subscribers', 'fcncn-nonce'], $_SERVER['REQUEST_URI'] );

    // Redirect from empty views
    switch ( $this->view ) {
      case 'confirmed':
        if ( $this->confirmed_count < 1 ) {
          wp_safe_redirect( remove_query_arg( 'view', $this->uri  ) );
          exit();
        }
        break;
      case 'pending':
        if ( $this->pending_count < 1 ) {
          wp_safe_redirect( remove_query_arg( 'view', $this->uri  ) );
          exit();
        }
        break;
      case 'trash':
        if ( $this->trashed_count < 1 ) {
          wp_safe_redirect( remove_query_arg( 'view', $this->uri  ) );
          exit();
        }
        break;
    }

    // Finishing cleaning up URI
    $this->uri = remove_query_arg( ['fcncn-notice', 'fcncn-message'], $this->uri );
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
      'id' => __( 'ID', 'fcnes' ),
      'email' => __( 'Email', 'fcnes' ),
      'status' => __( 'Status', 'fcnes' ),
      'date' => __( 'Date', 'fcnes' )
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
    $primary = 'email';

    // Data
    $this->table_data = $this->get_table_data();
    $this->_column_headers = [ $columns, $hidden, $sortable, $primary ];

    // Reorder (must be done after the SQL query due to appended post data)
    usort( $this->table_data, array( &$this, 'usort_reorder' ) );

    // Paginate (must be done after the SQL query due to appended post data)
    $per_page = $this->get_items_per_page( 'fcnes_subscribers_per_page', 25 );
    $current_page = $this->get_pagenum();

    $this->total_items = count( $this->table_data );
    $this->table_data = array_slice( $this->table_data, ( ($current_page - 1) * $per_page ), $per_page );

    $this->set_pagination_args(
      array(
        'total_items' => $this->total_items,
        'per_page' => $per_page,
        'total_pages' => ceil( $this->total_items / $per_page )
      )
    );

    // Prepare rows
    $this->items = $this->table_data;
  }

  /**
   * Retrieve the data for the table
   *
   * @since 0.1.0
   * @global wpdb $wpdb  The WordPress database object.
   *
   * @return array The table data.
   */

  function get_table_data() {
    global $wpdb;

    // Guard
    if ( ! current_user_can( 'manage_options' ) ) {
      return [];
    }

    // Setup
    $table_name = $wpdb->prefix . 'fcncn_subscribers';

    // Search?
    if ( ! empty( $_POST['s'] ?? '' ) ) {
      $search = sanitize_text_field( $_POST['s'] );
      $query = "SELECT * FROM $table_name WHERE email LIKE '%$search%'";
    } else {
      $query = "SELECT * FROM $table_name";
    }

    // Prepare for extension
    if ( ! strpos( $query, 'WHERE' ) ) {
      $query .= ' WHERE ';
    } else {
      $query .= ' AND ';
    }

    // View
    switch ( $this->view ) {
      case 'confirmed':
        $query .= "confirmed = 1 AND trashed = 0";
        break;
      case 'pending':
        $query .= "confirmed = 0 AND trashed = 0";
        break;
      case 'trash':
        $query .= "trashed = 1";
        break;
      default:
        $query .= "trashed = 0";
    }

    // Query
    $subscribers = $wpdb->get_results( $query, ARRAY_A );

    // Return results
    return $subscribers;
  }

  /**
   * Render the default column value
   *
   * @since 0.1.0
   *
   * @param array  $item         The current row's data.
   * @param string $column_name  The name of the column being rendered.
   *
   * @return string The rendered column value.
   */

  function column_default( $item, $column_name ) {
    switch ( $column_name ) {
      case 'status':
        return $item['confirmed'] ? __( 'Confirmed', 'fcncn' ) : __( 'Pending', 'fcncn' );
      case 'date':
        $created_date = date_i18n( 'Y-m-d H:i:s', strtotime( $item['created_at'] ) );
        return sprintf( __( 'Submitted<br>%s', 'fcncn' ), $created_date );
      default:
        return $item[ $column_name ];
    }
  }

  /**
   * Render the content of the "cb" column
   *
   * @since 0.1.0
   *
   * @param array $item  The data for the current row item.
   *
   * @return string The "cb" column content.
   */

  function column_cb( $item ) {
    return sprintf( '<input type="checkbox" name="subscribers[]" value="%s" />', $item['id'] );
  }

  /**
   * Render the content of the "email" column
   *
   * @since 0.1.0
   *
   * @param array $item  The data for the current row item.
   *
   * @return string The "email" column content.
   */

  function column_email( $item ) {
    // Setup
    $actions = [];

    // Confirm action
    if ( empty( $item['confirmed'] ) && empty( $item['trashed'] ) ) {
      $actions['confirm'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg( array( 'action' => 'confirm_subscriber', 'id' => $item['id'] ), $this->uri ),
          'fcncn-table-action',
          'fcncn-nonce'
        ),
        __( 'Confirm', 'fcncn' )
      );
    }

    // Unconfirm action
    if ( ! empty( $item['confirmed'] ) && empty( $item['trashed'] ) ) {
      $actions['unconfirm'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg( array( 'action' => 'unconfirm_subscriber', 'id' => $item['id'] ), $this->uri ),
          'fcncn-table-action',
          'fcncn-nonce'
        ),
        __( 'Unconfirm', 'fcncn' )
      );
    }

    // Resend confirmation email action
    if ( empty( $item['confirmed'] ) && empty( $item['trashed'] ) ) {
      $actions['resend'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg( array( 'action' => 'resend_confirmation', 'id' => $item['id'] ), $this->uri ),
          'fcncn-table-action',
          'fcncn-nonce'
        ),
        __( 'Resend Confirmation Email', 'fcncn' )
      );
    }

    // Trash action
    if ( empty( $item['trashed'] ) ) {
      $actions['trash'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg( array( 'action' => 'trash_subscriber', 'id' => $item['id'] ), $this->uri ),
          'fcncn-table-action',
          'fcncn-nonce'
        ),
        __( 'Trash', 'fcncn' )
      );
    }

    // Restore action
    if ( ! empty( $item['trashed'] ) ) {
      $actions['restore'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg( array( 'action' => 'restore_subscriber', 'id' => $item['id'] ), $this->uri ),
          'fcncn-table-action',
          'fcncn-nonce'
        ),
        __( 'Restore', 'fcncn' )
      );
    }

    // Delete action
    if ( ! empty( $item['trashed'] ) ) {
      $actions['delete'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg( array( 'action' => 'delete_subscriber', 'id' => $item['id'] ), $this->uri ),
          'fcncn-table-action',
          'fcncn-nonce'
        ),
        __( 'Delete permanently', 'fcncn' )
      );
    }

    // Return the final output
    return sprintf(
      '<span>%s</span> %s',
      $item['email'],
      $this->row_actions( $actions )
    );
  }

  /**
   * Render extra content in the table navigation section
   *
   * @since 0.1.0
   *
   * @param string $which  The position of the navigation, either 'top' or 'bottom'.
   */

  function extra_tablenav( $which ) {
    // Setup
    $actions = [];

    // Empty trash
    if ( $this->view === 'trash' ) {
      $actions[] = sprintf(
        '<a href="%s" class="button action">%s</a>',
        wp_nonce_url(
          admin_url( 'admin-post.php?action=fcncn_empty_trashed_subscribers' ),
          'fcncn-empty-trash',
          'fcncn-nonce'
        ),
        __( 'Empty Trash', 'fcncn' )
      );
    }

    // Output
    if ( ! empty( $actions ) ) {
      // Start HTML ---> ?>
      <div class="alignleft actions"><?php echo implode( ' ', $actions ); ?></div>
      <?php // <--- End HTML
    }
  }

  /**
   * Display the views for filtering the table
   *
   * @since Fictioneer Email Subscriptions 1.0.0
   */

  function display_views() {
    // Guard
    if ( ! current_user_can( 'manage_options' ) ) {
      echo '';
      return;
    }

    // Setup
    $views = [];
    $current = 'all';

    // Current
    if ( ! empty( $this->view ) ) {
      switch ( $this->view ) {
        case 'confirmed':
          $current = 'confirmed';
          break;
        case 'pending':
          $current = 'pending';
          break;
        case 'trash':
          $current = 'trash';
          break;
        default:
          $current = 'all';
      }
    }

    // Build views HTML
    $views['all'] = sprintf(
      '<li class="all"><a href="%s" class="%s">%s</a></li>',
      add_query_arg( ['view' => 'all'], $this->uri ),
      $current === 'all' ? 'current' : '',
      sprintf( __( 'All <span class="count">(%s)</span>', 'fcncn' ), $this->all_count )
    );

    if ( $this->confirmed_count > 0 ) {
      $views['confirmed'] = sprintf(
        '<li class="confirmed"><a href="%s" class="%s">%s</a></li>',
        add_query_arg( ['view' => 'confirmed'], $this->uri ),
        $current === 'confirmed' ? 'current' : '',
        sprintf( __( 'Confirmed <span class="count">(%s)</span>', 'fcncn' ), $this->confirmed_count )
      );
    }

    if ( $this->pending_count > 0 ) {
      $views['pending'] = sprintf(
        '<li class="pending"><a href="%s" class="%s">%s</a></li>',
        add_query_arg( ['view' => 'pending'], $this->uri ),
        $current === 'pending' ? 'current' : '',
        sprintf( __( 'Pending <span class="count">(%s)</span>', 'fcncn' ), $this->pending_count )
      );
    }

    if ( $this->trashed_count > 0 ) {
      $views['trash'] = sprintf(
        '<li class="trash"><a href="%s" class="%s">%s</a></li>',
        add_query_arg( ['view' => 'trash'], $this->uri ),
        $current === 'trash' ? 'current' : '',
        sprintf( __( 'Trash <span class="count">(%s)</span>', 'fcncn' ), $this->trashed_count )
      );
    }

    // Output final HTML
    echo '<ul class="subsubsub">' . implode( ' | ', $views ) . '</ul>';
  }

  /**
   * Reorder the table rows based on the specified column and order
   *
   * @since 0.1.0
   *
   * @param array $a  The first row to compare.
   * @param array $b  The second row to compare.
   *
   * @return int Returns a negative, zero, or positive number indicating the order of $a
   *             relative to $b. If $a should come before $b, a negative number is returned.
   *             If $a and $b are equal, zero is returned. If $a should come after $b, a
   *             positive number is returned.
   */

  function usort_reorder( $a, $b ) {
    // Setup
    $orderby = $_GET['orderby'] ?? 'created_at';
    $order = $_GET['order'] ?? 'dsc';

    // Compare
    $result = strcmp( $a[ $orderby ], $b[ $orderby ] );

    // Return to usort
    return $order === 'asc' ? $result : -$result;
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
      'id' => ['id', false],
      'email' => ['email', false],
      'status' => ['confirmed', false],
      'date' => ['created_at', false]
    );
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
    $table_name = $wpdb->prefix . 'fcncn_subscribers';
    $query_args = [];

    // GET actions
    if ( isset( $_GET['action'] ) ) {
      $id = absint( $_GET['id'] ?? 0 );

      // Confirm subscriber
      if ( ! empty( $id ) && $_GET['action'] === 'confirm_subscriber' ) {
        if ( $wpdb->update( $table_name, array( 'confirmed' => 1 ), array( 'id' => $id ), ['%d'], ['%d'] ) ) {
          $query_args['fcncn-notice'] = 'confirm-subscriber-success';
        } else {
          $query_args['fcncn-notice'] = 'confirm-subscriber-failure';
        }
      }

      // Unconfirm subscriber
      if ( ! empty( $id ) && $_GET['action'] === 'unconfirm_subscriber' ) {
        if ( $wpdb->update( $table_name, array( 'confirmed' => 0 ), array( 'id' => $id ), ['%d'], ['%d'] ) ) {
          $query_args['fcncn-notice'] = 'unconfirm-subscriber-success';
        } else {
          $query_args['fcncn-notice'] = 'unconfirm-subscriber-failure';
        }
      }

      // Resend confirmation email
      if ( ! empty( $id ) && $_GET['action'] === 'resend_confirmation' ) {
        fcncn_send_confirmation_email( array( 'id' => $id ) );
        $query_args['fcncn-notice'] = 'confirmation-email-sent';
      }

      // Redirect with notice (prevents multi-submit)
      wp_safe_redirect( add_query_arg( $query_args, $this->uri ) );
      exit();
    }
  }
}
