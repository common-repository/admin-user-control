<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AUC_Login_Monitor {
	const UM_KEY = 'lm_session';

	public function run() {
		add_action( 'admin_bar_menu', [ $this, 'add_lm_node' ], 999 );
	}

	/**
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function add_lm_node( $wp_admin_bar ) {
		if ($this->is_target_user()){
			$wp_admin_bar->add_node( [
				'id'     => 'login-monitor',
				'parent' => 'top-secondary',
				'meta'   => [],
				'title'  => '<span class="ab-icon"></span><span class="ab-label"><span id="lm-cnt">--</span> ' . __( 'Logged in', 'admin-user-control' ) . '</span>',
				'href'   => '#',
			] );

			$wp_admin_bar->add_node( [
				'id'     => 'login-monitor-detail',
				'parent' => 'login-monitor',
				'meta'   => [],
				'title'  => '<ul id="lm-list"></ul>',
				'href'   => '#',
			] );
		}
	}

	public function is_target_user(){
		if (current_user_can( 'administrator' ) || get_option('auc_lm_display_user') != '1'){
			return true;
		}
		return false;
	}

	public function create_response( $lifetime ) {
		$ary = [];
		$now = time();

		update_user_meta( get_current_user_id(), self::UM_KEY, $now );

		$expire = $now - $lifetime;

		$users = new WP_User_Query( [
			'meta_key'     => self::UM_KEY,
			'meta_value'   => $expire,
			'meta_compare' => '>',
		] );

		$isAdministrator = current_user_can( 'administrator' );
		foreach ( $users->get_results() as $user ) {
			$avatar_url = esc_url(get_avatar_url( $user->ID, [ 'size' => 28 ] ));
			$ary['login-monitor'][] = [
				'id'           => $user->ID,
				'nice_name'    => $user->user_nicename,
				'display_name' => $user->display_name,
				'color'        => substr( md5( $user->display_name ), 0, 6 ),
				'profile_url' => $isAdministrator ? apply_filters( 'auc_lm_user_link', $this->get_admin_edit_user_link( $user->ID ) ) : null,
				'avatar_url'  => $avatar_url
			];
		}

		return $ary;
	}

	private function get_admin_edit_user_link( $user_id ) {
		if ( get_current_user_id() == $user_id ) {
			$edit_link = null;
		} else {
			$edit_link = add_query_arg( 'user_id', $user_id, self_admin_url( 'user-edit.php' ) );
		}

		return $edit_link;
	}
}
