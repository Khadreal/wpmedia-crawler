<?php

use WPCrawler\Component;

$wpcrawler_pages = array();

if ( get_option( 'page_on_front' ) ) {
	$wpcrawler_pages = get_pages();
}

$wpcrawler_component = new Component();
$wpcrawler_results   = $wpcrawler_component->get_results();
?>
<div class="wrap">
	<div class="flex d-flex">
		<h2><?php esc_html_e( 'Crawl page', 'wpmedia-crawler' ); ?></h2>
		<form method="POST">
			<table>
				<tr>
					<td>
						<label for="page_id">
							<?php esc_html_e( 'Select a page to crawl', 'wpmedia-crawler' ); ?>
						</label>
					</td>
					<td>
						<select name="page_id" id="page_id">
							<option value=""><?php echo esc_attr( __( 'Please select page', 'wpmedia-crawler' ) ); ?></option>
							<?php if ( ! $wpcrawler_pages ) : ?>
								<option value="homepage">
									<?php esc_html_e( 'Homepage', 'wpmedia-crawler' ); ?>
								</option>
							<?php else : ?>
								<?php foreach ( $wpcrawler_pages as $wpcrawler_page ) : ?>
									<option value="<?php echo esc_attr( $wpcrawler_page->ID ); ?>"><?php echo esc_html( $wpcrawler_page->post_title ); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</td>
					<td>
						<?php
						wp_nonce_field( 'manual_crawl_pages', 'manual_crawl_pages' );
						submit_button( __( 'Crawl page', 'wpmedia-crawler' ), 'primary', 'add_crawl_process' );
						?>
					</td>
				</tr>
			</table>
		</form>
	</div>

	<div id="crawl-jobs-list-table">
		<div id="crawl-jobs-post-body">
			<?php if ( count( $wpcrawler_results ) > 0 ) : ?>
				<table class="wp-list-table widefat striped">
					<tr>
						<th><?php esc_html_e( 'ID', 'wpmedia-crawler' ); ?></th>
						<th style="width: 80%"><?php esc_html_e( 'Page Title', 'wpmedia-crawler' ); ?></th>
						<th><?php esc_html_e( 'Action', 'wpmedia-crawler' ); ?></th>
					</tr>
					<?php foreach ( $wpcrawler_results as $wpcrawler_key => $wpcrawler_result ) : ?>
						<tr>
							<td><?php esc_html( $wpcrawler_key + 1 ); ?></td>
							<td><?php esc_html( $wpcrawler_result['title'] ); ?></td>
							<td>
								<?php
								printf(
									'<a href="%s">%s',
									esc_url( $wpcrawler_component->single_page_action( $wpcrawler_result['key'], 'view' ) ),
									esc_html__( 'View links', 'wpmedia-crawler' )
								);
								?>
								<br>
								<?php
								printf(
									'<a target="%s" href="%s">%s',
									'_blank',
									esc_url( $wpcrawler_component->view_static_page( $result['key'] ) ),
									esc_html__( 'View static page', 'wpmedia-crawler' )
								);
								?>
								<br>
								<?php
								printf(
									'<a target="%s" href="%s">%s',
									'_blank',
									esc_url( $wpcrawler_component->view_static_page( $result['key'], 'sitemap' ) ),
									esc_html__( 'View sitemap', 'wpmedia-crawler' )
								);
								?>
								<br>
								<?php
								printf(
									'<a href="%s"><i class="dashicons dashicons-trash"></i></a>',
									esc_url( $wpcrawler_component->delete_action( $result['key'], 'delete' ) )
								);
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php else : ?>
				<h1><?php esc_html_e( 'No records available', 'wpmedia-crawler' ); ?></h1>
			<?php endif; ?>
		</div>
	</div>
</div>
