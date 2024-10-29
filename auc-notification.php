<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AUC_Notification {

	const POST_TYPE = 'notifications';

	public function run() {
		add_action( 'admin_bar_menu', [ $this, 'add_adminbar_notification' ], 300 );
		add_action( 'admin_footer', [ $this, 'hide_notification_bar' ] );
		add_action( 'delete_post', [ $this, 'delete_read_notification_with_post' ] );
		add_action( 'init', [ $this, 'add_post_type_notifications' ] );
		add_action( 'wp_ajax_admin-notification-read', [ $this, 'admin_ajax_read' ] );
		add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_notifications_widget' ] );
	}

	/**
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function add_adminbar_notification( $wp_admin_bar ) {
		$wp_admin_bar->add_node( [
			'id'    => self::POST_TYPE,
			'title' => '<span>' . __( 'New notification', 'admin-user-control' ) . '</span>',
			'href'  => get_dashboard_url(),
		] );
	}

	public function hide_notification_bar() {
		echo "<script>jQuery('#wp-admin-bar-notifications').hide()</script>";
	}

	public function delete_read_notification_with_post( $post_id ) {
		$deleted_post = get_post( $post_id );
		if ( $deleted_post->post_type === self::POST_TYPE ) {
			global $wpdb;
			$wpdb->delete( 'wp_usermeta', [
				'meta_key'   => 'auc_read_notification',
				'meta_value' => $post_id
			] );
		}
	}

	public function add_post_type_notifications() {
		register_post_type( self::POST_TYPE,
			[
				'labels'          => [
					'name'         => __( 'Notification', 'admin-user-control' ),
					'add_new_item' => __( 'Add New Notification', 'admin-user-control' ),
					'edit_item'    => __( 'Edit Notification', 'admin-user-control' ),
				],
				'capability_type' => self::POST_TYPE,
				'map_meta_cap'    => true,
				'public'          => true,
				'has_archive'     => true,
			]
		);
	}

	public function admin_ajax_read() {
		$result = add_user_meta( get_current_user_id(), 'auc_read_notification', $_POST['notificationId'] );
		wp_send_json( [
			'result' => $result ? true : false
		] );
	}

	public function add_dashboard_notifications_widget() {
		wp_add_dashboard_widget( 'notifications_widget', __( 'Latest notifications', 'admin-user-control' ), [
			$this,
			'add_notifications_to_widget'
		] );
	}

	public function add_notifications_to_widget() {
		$notifications = new WP_Query( [
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'orderby'        => 'post_date',
			'order'          => 'DESC',
			'posts_per_page' => apply_filters( 'auc_notification_per_page', 10 ),
		] );

		if ( $notifications->have_posts() ) {
			$widget                = '<p>' . __( 'Click a title to display a detail. If you read it, mark it as read.', 'admin-user-control' ) . '</p>';
			$read_notification_ids = get_user_meta( get_current_user_id(), 'auc_read_notification' );
			$widget                .= '<ul class="auc-notification-list auc-dashboard-list">';
			foreach ( $notifications->posts as $notification ) {
				$post_date              = date_i18n( get_option( 'date_format' ), strtotime( $notification->post_date ) );
				$read_notification_link = __( 'Mark as read', 'admin-user-control' );
				$is_read                = 'read';

				if ( ! in_array( $notification->ID, $read_notification_ids ) ) {
					$is_read = 'unread';
				}

				$post_title = esc_html( $notification->post_title );
				$post_body  = apply_filters( 'the_content', $notification->post_content );
				$widget     .= <<<HTML
<li class="post {$is_read}" data-postid="{$notification->ID}">
	<div class="date">{$post_date}</div>
	<div class="content">
		<div class="title">{$post_title}</div>
		<div class="body">{$post_body}</div>
		<p class="is_read"><input type="submit" name="read_btn" id="read_btn" class="button read_btn" value="{$read_notification_link}"></p>
	</div>
</li>
HTML;
			}
			$widget .= '</ul>';
		} else {
			$widget = '<p>' . __( 'There is no notifications', 'admin-user-control' ) . '</p>';
		}
		echo $widget;
	}

	public function add_notification_capabilities() {
		$notification_caps = [
			'edit_post'              => 'edit_' . self::POST_TYPE,
			'read_post'              => 'read_' . self::POST_TYPE,
			'delete_post'            => 'delete_' . self::POST_TYPE,
			'edit_posts'             => 'edit_' . self::POST_TYPE . 's',
			'edit_others_posts'      => 'edit_others_' . self::POST_TYPE . 's',
			'delete_posts'           => 'delete_' . self::POST_TYPE . 's',
			'publish_posts'          => 'publish_' . self::POST_TYPE . 's',
			'read_private_posts'     => 'read_private_' . self::POST_TYPE . 's',
			'delete_private_posts'   => 'delete_private_' . self::POST_TYPE . 's',
			'delete_published_posts' => 'delete_published_' . self::POST_TYPE . 's',
			'delete_others_posts'    => 'delete_others_' . self::POST_TYPE . 's',
			'edit_private_posts'     => 'edit_private_' . self::POST_TYPE . 's',
			'edit_published_posts'   => 'edit_published_' . self::POST_TYPE . 's',
			'create_posts'           => 'edit_' . self::POST_TYPE . 's',
		];
		foreach ( $notification_caps as $notification_cap ) {
			$role = get_role( 'administrator' );
			$role->add_cap( $notification_cap );
		}
	}

	public function create_response() {
		$user_id               = get_current_user_id();
		$read_notification_ids = get_user_meta( $user_id, 'auc_read_notification' );

		$get_from_days        = apply_filters( 'auc_notification_get_from_days', 30 );
		$unread_notifications = new WP_Query( [
			'post_type'      => 'notifications',
			'post_status'    => 'publish',
			'post__not_in'   => $read_notification_ids,
			'date_query'     => [
				[
					'after' => date_i18n( 'Ymd', strtotime( "-{$get_from_days} days" ) )
				],
			],
			'orderby'        => 'post_date',
			'order'          => 'DESC',
			'posts_per_page' => 1,
		] );

		$is_exist_notification = false;
		if ( $unread_notifications->found_posts !== 0 ) {
			$is_exist_notification = true;
		}

		return
			[
				'admin-notification' => [
					'is_exist_notification' => $is_exist_notification
				]
			];
	}

}
