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
class Component
{

	/**
	 * @var string
	*/
	public $dataKey = 'wp_media_crawler_pages';

	public const WP_MEDIA_DIRECTORY = '/wp-media/';

	/**
	 * @var string
	 */
	private $htmlDirectory = Component::WP_MEDIA_DIRECTORY . 'html/';

	/**
	 * @var string
	 */
	private $sitemapDirectory = Component::WP_MEDIA_DIRECTORY . 'sitemap/';

	/**
	 * @var string
	 */
	private $extension = '.html';

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
		add_action( 'admin_init', [ $this, 'actionAdminInit' ] );
	}

	/**
	 * @return void
	*/
	public function actionAdminInit(): void
	{
		( new Admin )->init();
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

	/**
	 * Get results
	 *
	 * @return array
	 */
	public function getResults() : array
	{
		$results = maybe_unserialize( get_option( $this->dataKey ) );

		return ( ! $results ) ? [] : $results;
	}

	/**
	 * @param string $key
	 * @param string $action
	 *
	 * @return string
	 */
	public function singlePageAction( string $key, string $action ) : string
	{
		return add_query_arg( [
			'action' => $action,
			'key' => $key
		] );
	}

	/**
	 * @param string $key
	 * @param string $action
	 *
	 * @return string
	 */
	public function deleteAction( string $key, string $action ) : string
	{
		$nonce = wp_create_nonce( 'delete-' . $key );

		return add_query_arg( [
			'action' => $action,
			'key' => $key,
			'_wpnonce' => $nonce,
		] );
	}


	/**
	 * @param string $key
	 * @param string $type
	 *
	 * @return string
	 */
	public function viewStaticPage( string $key, string $type = 'static' ) : string
	{
		$uploadBaseUrl = wp_upload_dir()['baseurl'];

		$directory = $type === 'static' ? $this->htmlDirectory : $this->sitemapDirectory;

		return $uploadBaseUrl . $directory . $key . $this->extension;
	}

	/**
	 * @param int $id
	 *
	 * @return string
	 */
	public function generatePageKey( int $id ) : string
	{
		$pageTitle = get_the_title( $id );
		$changeTitleSpaceToUnderscore = strtolower( str_replace( ' ', '_', $pageTitle ) );

		return 'wpmedia_crawler_'. $changeTitleSpaceToUnderscore . '_' . $id;
	}
}
