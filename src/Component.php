<?php
/**
 * Plugin main class
 *
 * @package     WPCrawler
 * @author      Opeyemi Ibrahim
 * @copyright   opeyemi
 * @license     GPL-2.0-or-later
 */

namespace WPCrawler;

/**
 * Main plugin class. It manages initialization, install, and activations.
 */
class Component {
	/**
	 * Manages plugin initialization
	 *
	 * @return void
	 */
	public function __construct()
	{
		// Register plugin lifecycle hooks.
		register_deactivation_hook( ROCKET_CRWL_PLUGIN_FILENAME, [ $this, 'wpcDeactivate' ] );

		$this->registerCallbacks();
	}

	/**
	 * Register callbacks/action hooks.
	 *
	 * @return void
	 */
	public function registerCallbacks(): void
	{
		add_action( 'admin_menu', [ $this, 'actionAddMenu' ] );
	}

	/**
	 * Add menu to settings
	 */
	public function actionAddMenu()
	{
		add_menu_page(
			__( 'Webpage Crawler Settings', 'wpmedia-crawl' ),
			__( 'Webpage Crawler', 'wpmedia-crawl' ),
			'manage_options',
			'wpmedia-crawler',
			[ $this, 'callbackCrawlerAdminPage' ],
			'dashicons-admin-site-alt2',
			40
		);

		add_submenu_page( 'wpmedia-crawler',
			__( 'View Webpage Links', 'wpmedia-crawl' ),
			__( 'View Page Links', 'wpmedia-crawl' ),
			'manage_options',
			'wpmedia-crawler-view',
			[ $this, 'callbackCrawlerAdminPage' ]
		);
	}

	/**
	 * Handles the views
	 *
	 * @return void
	 */
	public function callbackCrawlerAdminPage() : void
	{
		$action = $_REQUEST['action'] ?? '';
		$key = $_REQUEST['key'] ?? '';
		$id = $_REQUEST['id'] ?? 0;

		if( $action === 'view' ) {
			$links = maybe_unserialize( get_option( $key ) );
			$title = get_the_title( $id );

			include 'Admin/frontend/single.php';

			return;
		}

		include 'Admin/frontend/index.php';
	}



	/**
	 * Handles plugin activation:
	 *
	 * @return void
	 */
	public static function wpcActivate() {
		// Security checks.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';
		check_admin_referer( "activate-plugin_{$plugin}" );
	}

	/**
	 * Handles plugin deactivation
	 *
	 * @return void
	 */
	public function wpcDeactivate()
	{
		// Security checks.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';

		check_admin_referer( "deactivate-plugin_{$plugin}" );
	}

	/**
	 * Handles plugin uninstall
	 *
	 * @return void
	 */
	public static function wpcUninstall()
	{
		// Security checks.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
	}
}
