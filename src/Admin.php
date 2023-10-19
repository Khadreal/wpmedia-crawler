<?php

namespace WPCrawler;

/**
 * Admin related function class
 */
class Admin
{
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
	private $hook = 'cron_crawl_pages';

	/**
	 * option key value
	 *
	 * @var
	 */
	private $dataKey = 'wp_media_crawler_pages';

	/**
	 * handles notification text
	 *
	 * @var
	 */
	private $responseText;

	//It would be great if there is section on the admin to specify for the user.
	private $extensions = [
		'.jpg',
		'.png',
		'.jpeg',
		'.css',
		'.js',
		'.svg'
	];

	/**
	 * Initialisation
	 *
	 * @return void
	 */
	public function init() : void
	{
		$this->actionAdminInit();
		$this->registerCallbacks();

	}

	public function registerCallbacks() : void
	{
		add_action( 'cron_crawl_pages', [ $this, 'actionCronCrawlPages' ] );
	}

	/**
	 * Handle manual process
	 *
	 * @return void
	 */
	public function actionAdminInit() : void
	{
		if( ! array_key_exists( 'manual_crawl_pages', $_POST )
			|| ! wp_verify_nonce( $_POST['manual_crawl_pages'], 'manual_crawl_pages' )
		) {
			return;
		}
		$pageId = $_POST['page_id'];

		if( ! isset( $pageId ) ) {
			$this->responseText = __( 'Invalid page id', 'wpmedia-crawl' );
			//Add notification view here
			return;
		}

		$crawlPages = maybe_unserialize( get_option( $this->dataKey ) );
		$crawlPages = ! $crawlPages ? [] : $crawlPages;
		$ids = array_column( $crawlPages, 'id' );
		$key = 'wpmedia_crawl_homepage';
		$pageTitle = 'Homepage';

		if( $pageId !== 'homepage' ) {
			$key = $this->getPageUniqueKey( (int) $pageId );
			$pageTitle = get_the_title( $pageId );
		}

		$pageData = [
			'id' => $pageId === 'homepage' ? 'homepage' : $pageId,
			'title' => $pageTitle,
			'key' => $key
		];

		// We don't need to update the data if the page already existed, we only insert if it's new.
		if( in_array( $pageId, $ids, true ) ) {
			$pageData = [];
		}

		if( ! empty( $pageData ) ) {
			$crawlPages[] = $pageData;
			update_option( $this->dataKey, maybe_serialize( $crawlPages ) );
		}

		// If home page is not dynamic.
		if( $pageId === 'homepage' ) {
			$this->nonDynamicHomePage();

			return;
		}

		// Payload needed for running the cron.
		$cronArgs = [
			'id' => $pageId,
			'key' => $key
		];

		$this->responseText = __( 'Webpage crawl processing', 'wpmedia-crawler' );
		add_action( 'admin_notices', [ $this, 'notification' ] );

		if( ! wp_next_scheduled( $this->hook, 90 ) ) {
			wp_schedule_single_event( time() + 1, $this->hook, [ $cronArgs ] );

			//Scheduled every hour
			wp_schedule_event( time() , 'hourly', $this->hook, [ $cronArgs ] );
		}
	}

	/**
	 * If home page is static and not front page
	 *
	 * @return void
	 */
	private function nonDynamicHomePage()
	{
		$key = 'wpmedia_crawl_homepage';

		$cronArgs = [
			'id' => 0,
			'key' => $key
		];

		$this->responseText = __( 'Webpage crawl processing', 'wpmedia-crawler' );

		if( ! wp_next_scheduled( $this->hook, 90 ) ) {
			//Run event immediately at once
			wp_schedule_single_event( time() + 1, $this->hook, [ $cronArgs ] );

			//Scheduled every hour
			/*
			 * TODO:: Add a section for admin to stop/pause this process
			*/
			wp_schedule_event( time() , 'hourly', $this->hook, [ $cronArgs ] );
		}
	}

	/**
	 * @param int $id
	 *
	 * @return string
	*/
	public function getPageUniqueKey( int $id ): string
	{
		$pageTitle = get_the_title( $id );
		$changeTitleSpaceToUnderscore = strtolower( str_replace( ' ', '_', $pageTitle ) );

		return 'wpmedia_crawler_'. $changeTitleSpaceToUnderscore . '_' . $id;
	}

	/**
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	public function actionCronCrawlPages( array $args )
	{
		// If empty/false bail early.
		if( ! isset( $args['id'] ) ) {
			return;
		}

		$key = $args['key'] ?? '';

		if( ! $key ) {
			$key = $this->getPageUniqueKey( (int) $args['id'] );
		}

		$url = ( $args['id'] === 0 ) ? get_home_url() : get_page_link( $args['id'] ) ;
		$title = ( $args['id'] === 0 ) ? 'Homepage' : get_the_title( $args['id'] );

		$pageData = file_get_contents( $url );
		$doc = new \DOMDocument();
		// Suppress the error if html string is not valid mostly for html 5(weird right).
		libxml_use_internal_errors( true );
		$doc->loadHTML( $pageData );
		libxml_use_internal_errors( false );
		$internalUrls = $this->processPageLinks( $doc->getElementsByTagName('a') );

		$this->generateStaticFile( $pageData, $key );

		$this->generateSitemap( $internalUrls, $key, $title );

		update_option( $key, maybe_serialize( $internalUrls ) );
	}

	/**
	 * Process internal links
	 * @param $links
	 *
	 * @return array
	 */
	private function processPageLinks( $links ) : array
	{
		$siteUrl = parse_url( get_site_url(), PHP_URL_HOST );

		$retval = [];

		foreach( $links as $object ) {
			$link = $object->getAttribute( 'href' );

			$path = parse_url( $link, PHP_URL_PATH );
			$extension = ( $pos = strrpos( $path, '.' ) ) ? substr( $path, $pos ) : '';
			if( in_array( $extension, $this->extensions )
				|| $siteUrl !== parse_url( $link, PHP_URL_HOST )
			) {
				continue;
			}

			$retval[] =  $link;
		}

		return $retval;
	}

	/**
	 * Save page as static .html file
	 *
	 * @param string $data
	 * @param string $filename
	 *
	 * @return void
	 */
	private function generateStaticFile( string $data, string $filename ) : void
	{
		$uploadDir = wp_upload_dir()['basedir'];
		$directory = $uploadDir . $this->htmlDirectory;

		if( ! is_dir( $directory ) ) {
			wp_mkdir_p( $directory );
		}

		$filename = $directory . $filename . '.html';

		// Save to directory, so it can be bundled or retrieved later.
		file_put_contents( $filename, $data );
	}

	/**
	 * Generate sitemap
	 *
	 * @param array $links
	 * @param string $filename
	 * @param string $title
	 * @return void
	 */
	private function generateSitemap( array $links, string $filename, string $title ) : void
	{
		$uploadDir = wp_upload_dir()['basedir'];

		$directory = $uploadDir . $this->sitemapDirectory;

		if ( ! is_dir( $directory ) ) {
			wp_mkdir_p( $directory );
		}
		// Generate sitemap html.
		$this->generateHTMLSitemap( $links, $filename, $directory, $title );

		// Generate sitemp xml.
		$this->generateXMLSitemap( $links, $filename, $directory );
	}

	/**
	 * Generate html sitemap list structure
	 *
	 * @param array $links
	 * @param string $filename
	 * @param string $directory
	 * @param string $title
	 *
	 * @return void
	 */
	private function generateHTMLSitemap( array $links, string $filename, string $directory, string $title ) : void
	{
		$info = sprintf(
			'%d %s %s',
			count($links),
			__( 'internal link(s) found on', 'wpmedia-crawler' ),
			$title,
		);
		ob_start();

		include( 'Admin/frontend/html_sitemap_template.php' );

		$html = ob_get_clean();

		$sitemap = $directory . $filename . '.html';

		file_put_contents( $sitemap, $html );
	}

	/**
	 * Generate xml sitemap based on links generated
	 * @param array $links
	 * @param string $filename
	 * @param string $directory
	 *
	 * @return void
	 */
	private function generateXMLSitemap( array $links, string $filename, string $directory ) : void
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach ($links as $link) {
			$xml .= '<url><loc>'. htmlspecialchars( $link ) .'</loc></url>';
		}

		$xml .= '</urlset>';

		$sitemap = $directory . $filename . '.xml';

		// Save to disc so it can be retrieved later.
		file_put_contents( $sitemap, $xml );
	}
}
