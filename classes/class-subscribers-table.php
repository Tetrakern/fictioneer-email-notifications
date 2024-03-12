<?php

// No direct access!
defined( 'ABSPATH' ) OR exit;

// Make sure base class exists
if ( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class FCNEN_Subscribers_Table
 *
 * @since 0.1.0
 */

class FCNEN_Subscribers_Table extends WP_List_Table {
  private $table_data;
  private $view = '';
  private $uri = '';
  private $term_names = [];

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
      if ( ! isset( $_GET['fcnen-nonce'] ) || ! check_admin_referer( 'fcnen-table-action', 'fcnen-nonce' ) ) {
        wp_die( __( 'Nonce verification failed. Please try again.', 'fcnen' ) );
      }
    }

    // Initialize
    $table_name = $wpdb->prefix . 'fcnen_subscribers';
    $this->confirmed_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE confirmed = 1 AND trashed = 0" );
    $this->pending_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE confirmed = 0 AND trashed = 0" );
    $this->trashed_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE trashed = 1" );
    $this->all_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ) - $this->trashed_count;
    $this->view = $_GET['view'] ?? '';
    $this->uri = remove_query_arg( ['action', 'id', 'subscribers', 'fcnen-nonce'], $_SERVER['REQUEST_URI'] );

    // Initialize terms
    $categories = get_categories( array( 'hide_empty' => 0 ) );
    $tags = get_tags( array( 'hide_empty' => 0 ) ) ?: [];
    $genres = get_terms( array( 'taxonomy' => 'fcn_genre', 'hide_empty' => 0 ) ) ?: [];
    $fandoms = get_terms( array( 'taxonomy' => 'fcn_fandom', 'hide_empty' => 0 ) ) ?: [];
    $characters = get_terms( array( 'taxonomy' => 'fcn_character', 'hide_empty' => 0 ) ) ?: [];
    $warnings = get_terms( array( 'taxonomy' => 'fcn_content_warning', 'hide_empty' => 0 ) ) ?: [];
    $merged_terms = array_merge( $categories, $tags, $genres, $fandoms, $characters, $warnings );

    foreach ( $merged_terms as $term ) {
      $this->term_names[ $term->term_id ] = $term->name;
    }

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
    $this->uri = remove_query_arg( ['fcnen-notice', 'fcnen-message'], $this->uri );
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
      'id' => __( 'ID', 'fcnen' ),
      'email' => __( 'Email', 'fcnen' ),
      'scopes' => __( 'Scopes', 'fcnen' ),
      'status' => __( 'Status', 'fcnen' ),
      'date' => __( 'Date', 'fcnen' )
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
    $per_page = $this->get_items_per_page( 'fcnen_subscribers_per_page', 25 );
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
    $table_name = $wpdb->prefix . 'fcnen_subscribers';

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
        return $item['confirmed'] ? __( 'Confirmed', 'fcnen' ) : __( 'Pending', 'fcnen' );
      case 'date':
        $created_date = date_i18n( 'Y-m-d H:i:s', strtotime( $item['created_at'] ) );
        return sprintf( __( 'Submitted<br>%s', 'fcnen' ), $created_date );
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
          'fcnen-table-action',
          'fcnen-nonce'
        ),
        __( 'Confirm', 'fcnen' )
      );
    }

    // Unconfirm action
    if ( ! empty( $item['confirmed'] ) && empty( $item['trashed'] ) ) {
      $actions['unconfirm'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg( array( 'action' => 'unconfirm_subscriber', 'id' => $item['id'] ), $this->uri ),
          'fcnen-table-action',
          'fcnen-nonce'
        ),
        __( 'Unconfirm', 'fcnen' )
      );
    }

    // Resend confirmation email action
    if ( empty( $item['confirmed'] ) && empty( $item['trashed'] ) ) {
      $actions['resend'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg( array( 'action' => 'resend_confirmation', 'id' => $item['id'] ), $this->uri ),
          'fcnen-table-action',
          'fcnen-nonce'
        ),
        __( 'Resend Confirmation Email', 'fcnen' )
      );
    }

    // Send code action
    if ( ! empty( $item['confirmed'] ) && empty( $item['trashed'] ) ) {
      $actions['send_code'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg( array( 'action' => 'send_code', 'id' => $item['id'] ), $this->uri ),
          'fcnen-table-action',
          'fcnen-nonce'
        ),
        __( 'Send Code', 'fcnen' )
      );
    }

    // Trash action
    if ( empty( $item['trashed'] ) ) {
      $actions['trash'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg( array( 'action' => 'trash_subscriber', 'id' => $item['id'] ), $this->uri ),
          'fcnen-table-action',
          'fcnen-nonce'
        ),
        __( 'Trash', 'fcnen' )
      );
    }

    // Restore action
    if ( ! empty( $item['trashed'] ) ) {
      $actions['restore'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg( array( 'action' => 'restore_subscriber', 'id' => $item['id'] ), $this->uri ),
          'fcnen-table-action',
          'fcnen-nonce'
        ),
        __( 'Restore', 'fcnen' )
      );
    }

    // Delete action
    if ( ! empty( $item['trashed'] ) ) {
      $actions['delete'] = sprintf(
        '<a href="%s">%s</a>',
        wp_nonce_url(
          add_query_arg( array( 'action' => 'delete_subscriber', 'id' => $item['id'] ), $this->uri ),
          'fcnen-table-action',
          'fcnen-nonce'
        ),
        __( 'Delete permanently', 'fcnen' )
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
   * Render the content of the "scopes" column
   *
   * @since 0.1.0
   *
   * @param array $item  The data for the current row item.
   *
   * @return string The "scopes" column content.
   */

  function column_scopes( $item ) {
    $scopes = array(
      'post_ids' => unserialize( $item['post_ids'] ),
      'post_types' => unserialize( $item['post_types'] ),
      'categories' => unserialize( $item['categories'] ),
      'tags' => unserialize( $item['tags'] ),
      'taxonomies' => unserialize( $item['taxonomies'] ),
    );

    $translations = array(
      'post_ids' => __( 'Posts', 'fcnen' ),
      'post_types' => __( 'Types', 'fcnen' ),
      'categories' => __( 'Categories', 'fcnen' ),
      'tags' => __( 'Tags', 'fcnen' ),
      'taxonomies' => __( 'Taxonomies', 'fcnen' )
    );

    if ( ! empty( $item['everything'] ) ) {
      return __( 'Everything', 'fcnen' );
    }

    foreach ( $scopes as $key => $values ) {
      if ( empty( $values ) ) {
        unset( $scopes[ $key ] );
      } else {
        switch ( $key ) {
          case 'post_types':
            $values = array_map(
              function( $val ) {
                return get_post_type_object( $val )->labels->singular_name;
              },
              $values
            );
            break;
          case 'post_ids':
            $values = array_map(
              function ( $val ) {
                $link = get_permalink( $val );
                if ( empty( $link ) ) {
                  return $val;
                } else {
                  return '<a href="' . $link . '">' . mb_strimwidth( get_the_title( $val ), 0, 20, '…' ) . '</a>';
                }
              },
              $values
            );
            break;
          default:
            $values = array_map(
              function ( $val ) {
                return isset( $this->term_names[ $val ] ) ? mb_strimwidth( $this->term_names[ $val ], 0, 20, '…' ) : $val;
              },
              $values
            );
        }
        $scopes[ $key ] = '<strong>' . $translations[ $key ] . ':</strong> ' . implode( ', ', $values );
      }
    }

    return implode( ' &bull; ', $scopes );
  }

  /**
   * Retrieve the bulk actions available for the table
   *
   * @since 0.1.0
   *
   * @return array An associative array of bulk actions. The keys represent the actions,
   *               and the values represent the labels.
   */

  function get_bulk_actions() {
    // Add actions depending on view
    switch ( $this->view ) {
      case 'confirmed':
        return array(
          'unconfirm_all_subscribers' => __( 'Unconfirm', 'fcnen' ),
          'trash_all_subscribers' => __( 'Move to Trash', 'fcnen' )
        );
      case 'pending':
        return array(
          'confirm_all_subscribers' => __( 'Confirm', 'fcnen' ),
          'trash_all_subscribers' => __( 'Move to Trash', 'fcnen' )
        );
      case 'trash':
        return array(
          'restore_all_subscribers' => __( 'Restore', 'fcnen' ),
          'delete_all_subscribers' => __( 'Delete Permanently', 'fcnen' )
        );
      default:
        return array(
          'confirm_all_subscribers' => __( 'Confirm', 'fcnen' ),
          'unconfirm_all_subscribers' => __( 'Unconfirm', 'fcnen' ),
          'trash_all_subscribers' => __( 'Move to Trash', 'fcnen' )
        );
    }
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
          admin_url( 'admin-post.php?action=fcnen_empty_trashed_subscribers' ),
          'fcnen-empty-trash',
          'fcnen-nonce'
        ),
        __( 'Empty Trash', 'fcnen' )
      );
    }

    // CSV export
    if ( $this->view !== 'trash' && $this->all_count > 0 ) {
      $actions[] = sprintf(
        '<a href="%s" class="button action">%s</a>',
        wp_nonce_url(
          admin_url( 'admin-post.php?action=fcnen_export_subscribers_csv' ),
          'fcnen-export-csv',
          'fcnen-nonce'
        ),
        __( 'Export CSV', 'fcnen' )
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
      sprintf( __( 'All <span class="count">(%s)</span>', 'fcnen' ), $this->all_count )
    );

    if ( $this->confirmed_count > 0 ) {
      $views['confirmed'] = sprintf(
        '<li class="confirmed"><a href="%s" class="%s">%s</a></li>',
        add_query_arg( ['view' => 'confirmed'], $this->uri ),
        $current === 'confirmed' ? 'current' : '',
        sprintf( __( 'Confirmed <span class="count">(%s)</span>', 'fcnen' ), $this->confirmed_count )
      );
    }

    if ( $this->pending_count > 0 ) {
      $views['pending'] = sprintf(
        '<li class="pending"><a href="%s" class="%s">%s</a></li>',
        add_query_arg( ['view' => 'pending'], $this->uri ),
        $current === 'pending' ? 'current' : '',
        sprintf( __( 'Pending <span class="count">(%s)</span>', 'fcnen' ), $this->pending_count )
      );
    }

    if ( $this->trashed_count > 0 ) {
      $views['trash'] = sprintf(
        '<li class="trash"><a href="%s" class="%s">%s</a></li>',
        add_query_arg( ['view' => 'trash'], $this->uri ),
        $current === 'trash' ? 'current' : '',
        sprintf( __( 'Trash <span class="count">(%s)</span>', 'fcnen' ), $this->trashed_count )
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
    $table_name = $wpdb->prefix . 'fcnen_subscribers';
    $query_args = [];

    // GET actions
    if ( isset( $_GET['action'] ) ) {
      $id = absint( $_GET['id'] ?? 0 );

      // Confirm subscriber
      if ( ! empty( $id ) && $_GET['action'] === 'confirm_subscriber' ) {
        if ( $wpdb->update( $table_name, array( 'confirmed' => 1 ), array( 'id' => $id ), ['%d'], ['%d'] ) ) {
          $query_args['fcnen-notice'] = 'confirm-subscriber-success';
        } else {
          $query_args['fcnen-notice'] = 'confirm-subscriber-failure';
        }

        $query_args['fcnen-message'] = $id;
      }

      // Unconfirm subscriber
      if ( ! empty( $id ) && $_GET['action'] === 'unconfirm_subscriber' ) {
        if ( $wpdb->update( $table_name, array( 'confirmed' => 0 ), array( 'id' => $id ), ['%d'], ['%d'] ) ) {
          $query_args['fcnen-notice'] = 'unconfirm-subscriber-success';
        } else {
          $query_args['fcnen-notice'] = 'unconfirm-subscriber-failure';
        }

        $query_args['fcnen-message'] = $id;
      }

      // Resend confirmation email
      if ( ! empty( $id ) && $_GET['action'] === 'resend_confirmation' ) {
        fcnen_send_confirmation_email( array( 'id' => $id ) );
        $query_args['fcnen-notice'] = 'confirmation-email-resent';
        $query_args['fcnen-message'] = $id;
      }

      // Send code
      if ( ! empty( $id ) && $_GET['action'] === 'send_code' ) {
        fcnen_send_code_email( array( 'id' => $id ) );
        $query_args['fcnen-notice'] = 'code-email-sent';
        $query_args['fcnen-message'] = $id;
      }

      // Trash subscriber
      if ( ! empty( $id ) && $_GET['action'] === 'trash_subscriber' ) {
        if ( $wpdb->update( $table_name, array( 'trashed' => 1 ), array( 'id' => $id ), ['%d'], ['%d'] ) ) {
          $query_args['fcnen-notice'] = 'trash-subscriber-success';
        } else {
          $query_args['fcnen-notice'] = 'trash-subscriber-failure';
        }

        $query_args['fcnen-message'] = $id;
      }

      // Restore subscriber
      if ( ! empty( $id ) && $_GET['action'] === 'restore_subscriber' ) {
        if ( $wpdb->update( $table_name, array( 'trashed' => 0 ), array( 'id' => $id ), ['%d'], ['%d'] ) ) {
          $query_args['fcnen-notice'] = 'restore-subscriber-success';
        } else {
          $query_args['fcnen-notice'] = 'restore-subscriber-failure';
        }

        $query_args['fcnen-message'] = $id;
      }

      // Delete subscriber
      if ( ! empty( $id ) && $_GET['action'] === 'delete_subscriber' ) {
        if ( $wpdb->delete( $table_name, array( 'id' => $id ), ['%d'] ) ) {
          $query_args['fcnen-notice'] = 'delete-subscriber-success';
        } else {
          $query_args['fcnen-notice'] = 'delete-subscriber-failure';
        }

        $query_args['fcnen-message'] = $id;
      }

      // Redirect with notice (prevents multi-submit)
      wp_safe_redirect( add_query_arg( $query_args, $this->uri ) );
      exit();
    }

    // POST actions
    if ( isset( $_POST['action'] ) && empty( $_POST['s'] ?? 0 ) ) {
      $ids = array_map( 'absint', $_POST['subscribers'] ?? [] );
      $collection = implode( ',', $ids );

      // Confirm all subscribers
      if ( ! empty( $collection ) && $_POST['action'] === 'confirm_all_subscribers' ) {
        $query = "UPDATE $table_name SET confirmed = 1 WHERE id IN ($collection) AND trashed = 0";
        $result = $wpdb->query( $query );

        if ( $result !== false ) {
          $query_args['fcnen-notice'] = 'bulk-confirm-subscribers-success';
          $query_args['fcnen-message'] = $result;
        } else {
          $query_args['fcnen-notice'] = 'bulk-confirm-subscribers-failure';
        }
      }

      // Unconfirm all subscribers
      if ( ! empty( $collection ) && $_POST['action'] === 'unconfirm_all_subscribers' ) {
        $query = "UPDATE $table_name SET confirmed = 0 WHERE id IN ($collection) AND trashed = 0";
        $result = $wpdb->query( $query );

        if ( $result !== false ) {
          $query_args['fcnen-notice'] = 'bulk-unconfirm-subscribers-success';
          $query_args['fcnen-message'] = $result;
        } else {
          $query_args['fcnen-notice'] = 'bulk-unconfirm-subscribers-failure';
        }
      }

      // Trash all subscribers
      if ( ! empty( $collection ) && $_POST['action'] === 'trash_all_subscribers' ) {
        $query = "UPDATE $table_name SET trashed = 1 WHERE id IN ($collection)";
        $result = $wpdb->query( $query );

        if ( $result !== false ) {
          $query_args['fcnen-notice'] = 'bulk-trash-subscribers-success';
          $query_args['fcnen-message'] = $result;
        } else {
          $query_args['fcnen-notice'] = 'bulk-trash-subscribers-failure';
        }
      }

      // Restore all subscribers
      if ( ! empty( $collection ) && $_POST['action'] === 'restore_all_subscribers' ) {
        $query = "UPDATE $table_name SET trashed = 0 WHERE id IN ($collection)";
        $result = $wpdb->query( $query );

        if ( $result !== false ) {
          $query_args['fcnen-notice'] = 'bulk-restore-subscribers-success';
          $query_args['fcnen-message'] = $result;
        } else {
          $query_args['fcnen-notice'] = 'bulk-restore-subscribers-failure';
        }
      }

      // Delete all subscribers
      if ( ! empty( $collection ) && $_POST['action'] === 'delete_all_subscribers' ) {
        $query = "DELETE FROM $table_name WHERE id IN ($collection) AND trashed = 1";
        $result = $wpdb->query( $query );

        if ( $result !== false ) {
          $query_args['fcnen-notice'] = 'bulk-delete-subscribers-success';
          $query_args['fcnen-message'] = $result;
        } else {
          $query_args['fcnen-notice'] = 'bulk-delete-subscribers-failure';
        }
      }

      // Redirect with notice (prevents multi-submit)
      wp_safe_redirect( add_query_arg( $query_args, $this->uri ) );
      exit();
    }
  }
}
