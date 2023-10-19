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
	 * Data Key
	 *
	 * @var string
	 */
	public $data_key = 'wp_media_crawler_pages';

	public const WP_MEDIA_DIRECTORY = '/wp-media-crawler/';

	/**
	 * Html directory
	 *
	 * @var string
	 */
	private $html_directory = self::WP_MEDIA_DIRECTORY . 'html/';

	/**
	 * Sitemap directory
	 *
	 * @var string
	 */
	private $sitemap_directory = self::WP_MEDIA_DIRECTORY . 'sitemap/';

	/**
	 * Html Extension
	 *
	 * @var string
	 */
	private $extension = '.html';

	/**
	 * Manages plugin initialization
	 *
	 * @return void
	 */
	public function __construct() {
		// Register plugin lifecycle hooks.
		register_deactivation_hook( ROCKET_CRWL_PLUGIN_FILENAME, array( $this, 'wpc_deactivate' ) );

		$this->register_callbacks();
	}

	/**
	 * Register callbacks/action hooks.
	 *
	 * @return void
	 */
	public function register_callbacks(): void {
		add_action( 'admin_menu', array( $this, 'action_add_menu' ) );
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
	}

	/**
	 * Admin action initialisation
	 *
	 * @return void
	 */
	public function action_admin_init(): void {
		( new Admin() )->init();
	}

	/**
	 * Add menu to settings
	 *
	 * @return void
	 */
	public function action_add_menu(): void {
		add_menu_page(
			__( 'Webpage Crawler Settings', 'wpmedia-crawler' ),
			__( 'Webpage Crawler', 'wpmedia-crawler' ),
			'manage_options',
			'wpmedia-crawler',
			array( $this, 'callback_crawler_admin_page' ),
			'dashicons-admin-site-alt2',
			40
		);
	}

	/**
	 * Handles the views
	 *
	 * @return void
	 */
	public function callback_crawler_admin_page(): void {
		if ( ! isset( $_GET['key'] ) ) {
			include 'Admin/frontend/index.php';

			return;
		}
		$info = '';
		if ( isset( $_GET['info'] ) ) {
			$info = sanitize_text_field( wp_unslash( $_GET['info'] ) );
		}

		$id = 0;
		if ( isset( $_GET['id'] ) ) {
			$id = sanitize_text_field( wp_unslash( $_GET['id'] ) );
		}

		$key = sanitize_text_field( wp_unslash( $_GET['key'] ) );
		if ( 'view' === $info ) {
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
	public static function wpc_activate() {
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
	public function wpc_deactivate() {
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
	public static function wpc_uninstall() {
		// Security checks.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
	}

	/**
	 * Get results
	 *
	 * @return array
	 */
	public function get_results(): array {
		$results = maybe_unserialize( get_option( $this->data_key ) );

		return ( ! $results ) ? array() : $results;
	}

	/**
	 * Single page action
	 *
	 * @param string $key The unique key of the page.
	 * @param string $info The page you want to view.
	 *
	 * @return string
	 */
	public function single_page_action( string $key, string $info ): string {
		return add_query_arg(
			array(
				'info' => $info,
				'key'  => $key,
			)
		);
	}

	/**
	 * Delete action
	 *
	 * @param string $key The unique key of the page.
	 * @param string $action The action to be carried out.
	 *
	 * @return string
	 */
	public function delete_action( string $key, string $action ): string {
		$nonce = wp_create_nonce( 'delete-' . $key );

		return add_query_arg(
			array(
				'action'   => $action,
				'key'      => $key,
				'_wpnonce' => $nonce,
			)
		);
	}


	/**
	 * View static page for admin
	 *
	 * @param string $key The page key you're trying to view.
	 * @param string $type This is default to static.
	 *
	 * @return string
	 */
	public function view_static_page( string $key, string $type = 'static' ): string {
		$upload_base_url = wp_upload_dir()['baseurl'];

		$directory = 'static' === $type ? $this->html_directory : $this->sitemap_directory;

		return $upload_base_url . $directory . $key . $this->extension;
	}
}
