<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AUC_Maintenance {

	const POST_TYPE = 'maintenance';
	const START_DATE_KEY = 'auc_maintenance_start_date';
	const END_DATE_KEY = 'auc_maintenance_end_date';
	private $under_maintenance_post;
	private $is_maintenance = null;

	public function run() {
		add_action( 'admin_init', [ $this, 'restrict_login_maintenance' ] );
		add_action( 'init', [ $this, 'acf_add_local_field_maintenance' ], 12 );
		add_action( 'init', [ $this, 'add_maintenance_action' ], 11 );
		add_action( 'init', [ $this, 'add_post_type_maintenance' ] );
		add_action( 'init', [ $this, 'is_maintenance' ] );
		add_filter( 'manage_maintenance_posts_columns', [ $this, 'add_maintenance_columns' ] );
		add_action( 'manage_maintenance_posts_custom_column', [ $this, 'set_maintenance_columns_value' ], 10, 2 );
		add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_maintenance_widget' ] );
	}

	public function restrict_login_maintenance() {
		if ( $this->is_maintenance() && ! current_user_can( 'administrator' ) && ! wp_doing_ajax() ) {
			wp_logout();
			wp_redirect( wp_login_url() );
		}
	}

	public function acf_add_local_field_maintenance() {
		if ( function_exists( 'acf_add_local_field_group' ) ):

			acf_add_local_field_group( [
				'key'                   => 'group_5ea298215838d',
				'title'                 => __( 'Maintenance', 'admin-user-control' ),
				'fields'                => [
					[
						'key'               => 'field_5ea29835684ce',
						'label'             => __( 'maintenance_start_date', 'admin-user-control' ),
						'name'              => self::START_DATE_KEY,
						'type'              => 'date_time_picker',
						'instructions'      => '',
						'required'          => 1,
						'conditional_logic' => 0,
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'display_format'    => 'Y-m-d H:i',
						'return_format'     => 'Y-m-d H:i',
						'first_day'         => 0,
					],
					[
						'key'               => 'field_5ea29870684cf',
						'label'             => __( 'maintenance_end_date', 'admin-user-control' ),
						'name'              => self::END_DATE_KEY,
						'type'              => 'date_time_picker',
						'instructions'      => '',
						'required'          => 1,
						'conditional_logic' => 0,
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'display_format'    => 'Y-m-d H:i',
						'return_format'     => 'Y-m-d H:i',
						'first_day'         => 0,
					],
				],
				'location'              => [
					[
						[
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => self::POST_TYPE,
						],
					],
				],
				'menu_order'            => 0,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => true,
				'description'           => '',
			] );

		endif;
	}

	public function add_maintenance_action() {
		if ( ! current_user_can( 'administrator' ) ) {
			add_action( 'admin_footer', [ $this, 'add_maintenance_modal' ] );
			add_action( 'login_header', [ $this, 'add_maintenance_page' ] );
			add_filter( 'login_title', [ $this, 'modify_title_for_maintenance' ] );
		}
	}

	public function add_maintenance_modal() {
		$modal_html = '
		<div id="auc-modal" class="auc-modal">
			<div class="modal-content">
				<div class="modal-body">
						<div class="icon">
						<span class="dashicons dashicons-warning"></span>
					</div>
					<div class="text">
						<p>' . __( 'Forced logout after 10 seconds because the maintenance start time', 'admin-user-control' ) . '</p>
						<p>' . __( 'Scheduled maintenance end time', 'admin-user-control' ) . '<span id="auc-maintenanceEndTime"></span></p>
					</div>
				</div>
			</div>
		</div>
		';
		echo $modal_html;
	}

	public function add_maintenance_page() {
		if ( $this->is_maintenance() ) {
			$title   = esc_html( $this->under_maintenance_post->post_title );
			$content = apply_filters( 'the_content', $this->under_maintenance_post->post_content );
			echo <<<HTML
<div class="auc-maintenance">
<h1>{$title}</h1>
{$content}
</div>
<style>
.auc-maintenance {
	background: #ffffff;
	margin: 50px 50px 0 50px;
	padding: 30px;
	border-radius: 5px;
	border: 2px solid #ca4a1f;
	box-shadow: 0 3px 3px rgba(0, 0, 0, 0.2);
}
.auc-maintenance h1 {
	font-size: 24px;
	font-weight: bold;
	line-height: 1.4;
	margin: 0 0 1em 0;
}
.auc-maintenance p {
	font-size: 14px;
	font-weight: normal;
	line-height: 1.5;
	margin: 1em 0;
}
.auc-maintenance p:last-child {
	margin-bottom: 0;
}
</style>
HTML;
		}
	}

	public function modify_title_for_maintenance( $title ) {
		if ( $this->is_maintenance() === true ) {
			$title = apply_filters( 'auc_title_for_maintenance', __( 'Maintenance' ) );
		}

		return $title;
	}

	public function add_post_type_maintenance() {
		register_post_type( self::POST_TYPE,
			[
				'labels'          => [
					'name'         => __( 'Maintenance', 'admin-user-control' ),
					'add_new_item' => __( 'Add New Maintenance', 'admin-user-control' ),
					'edit_item'    => __( 'Edit Maintenance', 'admin-user-control' ),
				],
				'capability_type' => self::POST_TYPE,
				'map_meta_cap'    => true,
				'public'          => true,
				'has_archive'     => true,
			]
		);
	}

	public function is_maintenance() {
		if ( ! is_null( $this->is_maintenance ) ) {
			return $this->is_maintenance;
		}
		$maintenances = new WP_Query( [
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_query'     => [
				[
					'key'     => self::START_DATE_KEY,
					'value'   => date_i18n( 'Y-m-d H:i:s' ),
					'compare' => '<=',
					'type'    => 'CHAR'
				],
				[
					'key'     => self::END_DATE_KEY,
					'value'   => date_i18n( 'Y-m-d H:i:s' ),
					'compare' => '>=',
					'type'    => 'CHAR'
				]
			],
		] );

		$this->is_maintenance = false;
		if ( $maintenances->found_posts !== 0 && ! current_user_can( 'administrator' ) ) {
			$this->is_maintenance         = true;
			$this->under_maintenance_post = $maintenances->posts[0];
		}

		return $this->is_maintenance;
	}

	function add_maintenance_columns( $columns ) {
		$add_columns = array(
			'maintenance_start_date' => __( 'maintenance_start_date', 'admin-user-control' ),
			'maintenance_end_date'   => __( 'maintenance_end_date', 'admin-user-control' ),
		);
		$columns     = array_merge( $columns, $add_columns );

		return $columns;
	}

	function set_maintenance_columns_value( $column, $post_id ) {
		switch ( $column ) {
			case 'maintenance_start_date' :
				$maintenance_start_date = get_field( self::START_DATE_KEY, $post_id );
				if ( ! empty( $maintenance_start_date ) ) {
					echo date( 'Y-m-d H:i', strtotime( $maintenance_start_date ) );
				}
				break;
			case 'maintenance_end_date' :
				$maintenance_end_date = get_field( self::END_DATE_KEY, $post_id );
				if ( ! empty( $maintenance_end_date ) ) {
					echo date( 'Y-m-d H:i', strtotime( $maintenance_end_date ) );
				}
				break;
		}
	}

	public function add_dashboard_maintenance_widget() {
		wp_add_dashboard_widget( 'maintenance_widget', __( 'Latest maintenance information', 'admin-user-control' ), [
			$this,
			'add_maintenance_to_widget'
		] );
	}

	public function add_maintenance_to_widget() {
		$maintenances = new WP_Query( [
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'     => self::START_DATE_KEY,
					'value'   => date_i18n( 'Y-m-d H:i:s' ),
					'compare' => '<=',
					'type'    => 'CHAR'
				]
			],
			'orderby'        => 'post_date',
			'order'          => 'DESC',
			'posts_per_page' => apply_filters( 'auc_maintenance_per_page', 10 ),
		] );

		$widget = '<p>' . __( 'Click a title to display a detail.', 'admin-user-control' ) . '</p>';
		$widget .= '<ul class="auc-maintenance-list auc-dashboard-list">';
		if ( $maintenances->have_posts() ) {
			foreach ( $maintenances->posts as $maintenance ) {
				$post_date  = date_i18n( get_option( 'date_format' ), strtotime( $maintenance->post_date ) );
				$post_title = esc_html( $maintenance->post_title );
				$post_body  = apply_filters( 'the_content', $maintenance->post_content );
				$widget     .= <<<HTML
<li class="post">
	<div class="date">{$post_date}</div>
	<div class="content">
		<div class="title">{$post_title}</div>
		<div class="body">{$post_body}</div>
	</div>
</li>
HTML;
			}
		} else {
			$widget .= __( 'No maintenance information', 'admin-user-control' );
		}
		$widget .= '</ul>';
		echo $widget;
	}

	public function add_maintenance_capabilities() {
		$maintenance_caps = [
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
		foreach ( $maintenance_caps as $maintenance_cap ) {
			$role = get_role( 'administrator' );
			$role->add_cap( $maintenance_cap );
		}
	}

	public function create_response() {
		return
			[
				'admin-maintenance' => [
					'is_maintenance'       => $this->is_maintenance(),
					'logout_url'           => htmlspecialchars_decode( wp_logout_url() ),
					'maintenance_end_time' => ! empty( $this->under_maintenance_post ) ? get_post_meta( $this->under_maintenance_post->ID, self::END_DATE_KEY, true ) : null
				]
			];
	}
}
