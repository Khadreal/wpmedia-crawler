<?php

namespace WPCrawler;

/**
 * Admin related function class
 */
class Admin {
	/**
	 * Html directory
	 *
	 * @var string
	 */
	private $html_directory = Component::WP_MEDIA_DIRECTORY . 'html/';

	/**
	 * Site map directory
	 *
	 * @var string
	 */
	private $sitemap_directory = Component::WP_MEDIA_DIRECTORY . 'sitemap/';

	/**
	 * Cron hook
	 *
	 * @var string
	 */
	private $hook = 'cron_crawl_pages';

	/**
	 * Option key value
	 *
	 * @var $data_key
	 */
	private $data_key = 'wp_media_crawler_pages';

	/**
	 * Handles notification text
	 *
	 * @var $response_text
	 */
	private $response_text;

	// TODO:: It would be great if there is section on the admin to specify for the user.

	/**
	 * Array of extensions
	 *
	 * @var $extensions
	 */
	private $extensions = array(
		'.jpg',
		'.png',
		'.jpeg',
		'.css',
		'.js',
		'.svg',
		'.pdf',
	);

	/**
	 * Initialisation
	 *
	 * @return void
	 */
	public function init(): void {
		$this->action_admin_init();
		$this->register_callbacks();
	}

	/**
	 * Register callbacks
	 *
	 * @return void
	 */
	public function register_callbacks(): void {
		add_action( 'cron_crawl_pages', array( $this, 'action_cron_crawl_pages' ) );
	}

	/**
	 * Handle manual process
	 *
	 * @return void
	 */
	public function action_admin_init(): void {
		if ( ! array_key_exists( 'manual_crawl_pages', $_POST )
			|| ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['manual_crawl_pages'] ) ),
				'manual_crawl_pages'
			)
		) {
			return;
		}

		if ( ! isset( $_POST['page_id'] ) ) {
			$this->response_text = __( 'Invalid page id', 'wpmedia-crawler' );
			// Add notification view here.
			return;
		}

		$page_id = sanitize_text_field( wp_unslash( $_POST['page_id'] ) );

		$crawl_pages = maybe_unserialize( get_option( $this->data_key ) );
		$crawl_pages = ! $crawl_pages ? array() : $crawl_pages;
		$ids         = array_column( $crawl_pages, 'id' );
		$key         = 'wpmedia_crawl_homepage';
		$page_title  = 'Homepage';

		if ( 'homepage' !== $page_id ) {
			$key        = $this->get_page_unique_key( (int) $page_id );
			$page_title = get_the_title( $page_id );
		}

		$page_data = array(
			'id'    => 'homepage' === $page_id ? 'homepage' : $page_id,
			'title' => $page_title,
			'key'   => $key,
		);

		// We don't need to update the data if the page already existed, we only insert if it's new.
		if ( in_array( $page_id, $ids, true ) ) {
			$page_data = array();
		}

		if ( ! empty( $page_data ) ) {
			$crawl_pages[] = $page_data;
			update_option( $this->data_key, maybe_serialize( $crawl_pages ) );
		}

		// If home page is not dynamic.
		if ( 'homepage' === $page_id ) {
			$this->non_dynamic_home_page();

			return;
		}

		// Payload needed for running the cron.
		$cron_args = array(
			'id'  => $page_id,
			'key' => $key,
		);

		$this->response_text = __( 'Webpage crawl processing', 'wpmedia-crawler' );
		add_action( 'admin_notices', array( $this, 'notification' ) );

		if ( ! wp_next_scheduled( $this->hook, 90 ) ) {
			wp_schedule_single_event( time() + 1, $this->hook, array( $cron_args ) );

			// Scheduled every hour.
			wp_schedule_event( time(), 'hourly', $this->hook, array( $cron_args ) );
		}
	}

	/**
	 * If home page is static and not front page
	 *
	 * @return void
	 */
	private function non_dynamic_home_page() {
		$key = 'wpmedia_crawl_homepage';

		$cron_args = array(
			'id'  => 0,
			'key' => $key,
		);

		$this->response_text = __( 'Webpage crawl processing', 'wpmedia-crawler' );

		if ( ! wp_next_scheduled( $this->hook, 90 ) ) {
			// Run event immediately at once.
			wp_schedule_single_event( time() + 1, $this->hook, array( $cron_args ) );

			// Scheduled every hour.

			/*
			 * TODO:: Add a section for admin to stop/pause this process
			*/
			wp_schedule_event( time(), 'hourly', $this->hook, array( $cron_args ) );
		}
	}

	/**
	 * Get page unique key
	 *
	 * @param int $id Page id.
	 *
	 * @return string
	 */
	public function get_page_unique_key( int $id ): string {
		$page_title                       = get_the_title( $id );
		$change_title_space_to_underscore = strtolower( str_replace( ' ', '_', $page_title ) );

		return 'wpmedia_crawler_' . $change_title_space_to_underscore . '_' . $id;
	}

	/**
	 * Action to crawl pages
	 *
	 * @param array $args Argument to crawl pages.
	 *
	 * @return void
	 */
	public function action_cron_crawl_pages( array $args ): void {
		// If empty/false bail early.
		if ( ! isset( $args['id'] ) ) {
			return;
		}

		$key = $args['key'] ?? '';

		if ( ! $key ) {
			$key = $this->get_page_unique_key( (int) $args['id'] );
		}

		$url   = ( 0 === $args['id'] ) ? get_home_url() : get_page_link( $args['id'] );
		$title = ( 0 === $args['id'] ) ? 'Homepage' : get_the_title( $args['id'] );

		$page_data = file_get_contents( $url );
		$doc       = new \DOMDocument();
		// Suppress the error if html string is not valid mostly for html 5(weird right).
		libxml_use_internal_errors( true );
		$doc->loadHTML( $page_data );
		libxml_use_internal_errors( false );
		$internal_urls = $this->process_page_links( $doc->getElementsByTagName( 'a' ) );

		$this->generate_static_file( $page_data, $key );

		$this->generate_sitemap( $internal_urls, $key, $title );

		update_option( $key, maybe_serialize( $internal_urls ) );
	}

	/**
	 * Process internal links
	 *
	 * @param mixed $links Links generated from page.
	 *
	 * @return array
	 */
	private function process_page_links( $links ): array {
		$site_url = parse_url( get_site_url(), PHP_URL_HOST );

		$retval = array();

		foreach ( $links as $object ) {
			$link = $object->getAttribute( 'href' );

			$path      = parse_url( $link, PHP_URL_PATH );
			$pos       = strrpos( $path, '.' );
			$extension = $pos ? substr( $path, $pos ) : '';
			if ( in_array( $extension, $this->extensions, true )
				|| parse_url( $link, PHP_URL_HOST ) !== $site_url
			) {
				continue;
			}

			$retval[] = $link;
		}

		return $retval;
	}

	/**
	 * Save page as static .html file
	 *
	 * @param string $data Page data.
	 * @param string $filename Filename of the page.
	 *
	 * @return void
	 */
	private function generate_static_file( string $data, string $filename ): void {
		$upload_dir = wp_upload_dir()['basedir'];
		$directory  = $upload_dir . $this->html_directory;

		if ( ! is_dir( $directory ) ) {
			wp_mkdir_p( $directory );
		}

		$filename = $directory . $filename . '.html';

		// Save to directory, so it can be bundled or retrieved later.
		file_put_contents( $filename, $data );
	}

	/**
	 * Generate sitemap
	 *
	 * @param array  $links internal links.
	 * @param string $filename filename to store.
	 * @param string $title Page title.
	 * @return void
	 */
	private function generate_sitemap( array $links, string $filename, string $title ): void {
		$upload_dir = wp_upload_dir()['basedir'];

		$directory = $upload_dir . $this->sitemap_directory;

		if ( ! is_dir( $directory ) ) {
			wp_mkdir_p( $directory );
		}
		// Generate sitemap html.
		$this->generate_html_sitemap( $links, $filename, $directory, $title );

		// Generate sitemap xml.
		$this->generate_xml_sitemap( $links, $filename, $directory );
	}

	/**
	 * Generate html sitemap list structure
	 *
	 * @param array  $links Internal links generated.
	 * @param string $filename filename of the sitemap.
	 * @param string $directory the directory to store the sitemap.
	 * @param string $title Page title.
	 *
	 * @return void
	 */
	private function generate_html_sitemap( array $links, string $filename, string $directory, string $title ): void {
		$info = sprintf(
			'%d %s %s',
			count( $links ),
			__( 'internal link(s) found on', 'wpmedia-crawler' ),
			$title,
		);
		ob_start();

		include 'Admin/frontend/html_sitemap_template.php';

		$html = ob_get_clean();

		$sitemap = $directory . $filename . '.html';

		file_put_contents( $sitemap, $html );
	}

	/**
	 * Generate xml sitemap based on links generated
	 *
	 * @param array  $links Internal links generated.
	 * @param string $filename filename of the sitemap.
	 * @param string $directory the directory to store the sitemap.
	 *
	 * @return void
	 */
	private function generate_xml_sitemap( array $links, string $filename, string $directory ): void {
		$xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach ( $links as $link ) {
			$xml .= '<url><loc>' . htmlspecialchars( $link ) . '</loc></url>';
		}

		$xml .= '</urlset>';

		$sitemap = $directory . $filename . '.xml';

		// Save to disc so it can be retrieved later.
		file_put_contents( $sitemap, $xml );
	}
}
