<?php
/**
 * Plugin Name: Admin User Control
 * Description: This plugin adds a useful feature to the administration screen that allows administrators to control the users involved in their operations.
 * Version: 2.0.0
 * Author: PRESSMAN
 * Author URI: https://www.pressman.ne.jp
 * Text Domain: admin-user-control
 * Domain Path: /languages
 *
 * @author    PRESSMAN
 * @link      https://www.pressman.ne.jp
 * @copyright Copyright (c) 2018, PRESSMAN
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, v2 or higher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'auc-login-monitor.php';
require_once 'auc-notification.php';
require_once 'auc-maintenance.php';

class Admin_User_Control {
	const LIFETIME = 30; // 30 sec.
	const UM_KEY = 'auc_ip_addr';
	private $am;
	private $an;
	private $lm;
	private $version;

	public function __construct() {
		$this->am      = new AUC_Maintenance();
		$this->an      = new AUC_Notification();
		$this->lm      = new AUC_Login_Monitor();
		$this->version = get_file_data( __FILE__, [ 'v' => 'Version' ] )['v'];
	}

	public function run() {
		register_activation_hook( __FILE__, [ $this, 'activate_plugin' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate_plugin' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'plugins_loaded', [ $this, 'load_acf_lite' ] );
		add_action( 'plugins_loaded', [ $this, 'load_text_domain' ] );
		add_action( 'wp_ajax_admin-user-control', [ $this, 'create_response' ] );

		// add option page
		add_action( 'admin_menu', [ $this, 'add_option_to_menu' ] );

		$this->is_maintenance_enabled  = get_option( 'auc_is_maintenance_enabled', true );
		$this->is_notification_enabled = get_option( 'auc_is_notification_enabled', true );
		$this->is_loginmonitor_enabled = get_option( 'auc_is_loginmonitor_enabled', true );

		if ( $this->is_maintenance_enabled ) {
			$this->am->run();
		}
		if ( $this->is_notification_enabled ) {
			$this->an->run();
		}
		if ( $this->is_loginmonitor_enabled ) {
			$this->lm->run();
		}
	}

	public function activate_plugin() {
		update_option( 'admin-user-control_version', $this->version );
		$this->am->add_maintenance_capabilities();
		$this->an->add_notification_capabilities();
	}

	public function deactivate_plugin() {
		delete_option( 'admin-user-control_version' );
	}

	public function enqueue() {
		$ext = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		if ( is_admin_bar_showing() ) {
			if ( $this->is_loginmonitor_enabled ) {
				wp_enqueue_style( 'login-monitor', plugin_dir_url( __FILE__ ) . "css/login-monitor{$ext}.css", [], $this->version );
			}
		}

		wp_enqueue_style( 'auc-admin', plugin_dir_url( __FILE__ ) . "css/auc-admin{$ext}.css", [], $this->version );
		if ( $this->is_notification_enabled ) {
			wp_enqueue_style( 'admin-notification', plugin_dir_url( __FILE__ ) . "css/admin-notification{$ext}.css", [], $this->version );
		}
		if ( $this->is_maintenance_enabled ) {
			wp_enqueue_style( 'admin-maintenance', plugin_dir_url( __FILE__ ) . "css/admin-maintenance{$ext}.css", [], $this->version );
		}

		if ( is_user_logged_in() ) {
			wp_enqueue_script( 'admin-user-control', plugin_dir_url( __FILE__ ) . "js/admin-user-control{$ext}.js", [], null, true );
			$lifetime = apply_filters( 'change_auc_lifetime', self::LIFETIME );
			$script   = 'var ADMIN_USER_CONTROL_CONST = ' . json_encode( [
					'url'                    => admin_url( 'admin-ajax.php' ),
					'action'                 => 'admin-user-control',
					'readNotificationAction' => 'admin-notification-read',
					'lifetime'               => $lifetime,
				] ) . ';';

			wp_add_inline_script( 'admin-user-control', $script, 'before' );
		}
	}

	public function load_acf_lite() {
		if ( ! class_exists( 'Acf' ) ) {
			if ( ! defined( 'ACF_LITE' ) ) {
				define( 'ACF_LITE', true );
			}
			include_once( plugin_dir_path( __FILE__ ) . 'includes/acf/acf.php' );
		}
	}

	public function load_text_domain() {
		load_plugin_textdomain( 'admin-user-control', false, basename( __DIR__ ) . '/languages' );
	}

	public function add_option_to_menu() {

		//create new top-level menu
		add_menu_page( 'Admin User Control', __( 'Admin User Control', 'admin-user-control' ), 'administrator', __FILE__, [
			$this,
			'require_option_page'
		] );

		//call register settings function
		add_action( 'admin_init', [ $this, 'register_settings_group' ] );
	}

	public function require_option_page() {
		require_once( dirname( __FILE__ ) . '/includes/optionpage.php' );
	}

	public function register_settings_group() {
		register_setting( 'admin-user-control-settings-group', 'auc_lm_display_user' );
		register_setting( 'admin-user-control-settings-group', 'auc_is_maintenance_enabled' );
		register_setting( 'admin-user-control-settings-group', 'auc_is_notification_enabled' );
		register_setting( 'admin-user-control-settings-group', 'auc_is_loginmonitor_enabled' );
	}

	public function create_response() {
		$response = [];
		update_user_meta( get_current_user_id(), self::UM_KEY, $_SERVER['REMOTE_ADDR'] );

		$response = array_merge( $response, $this->lm->create_response( apply_filters( 'change_auc_lifetime', self::LIFETIME ) ) );
		$response = array_merge( $response, $this->an->create_response() );
		$response = array_merge( $response, $this->am->create_response() );

		wp_send_json( $response );
	}
}

if ( ! function_exists( 'auc_is_login_page' ) ) {
	function auc_is_login_page() {
		if ( in_array( $GLOBALS['pagenow'], [ 'wp-login.php', 'wp-register.php' ] ) ) {
			return true;
		} else {
			return false;
		}
	}
}

if ( is_admin() || auc_is_login_page() ) {
	global $auc;
	$auc = new Admin_User_Control();
	$auc->run();
}

