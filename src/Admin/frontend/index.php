<?php

use WPCrawler\Component;

$pages = [];
if( get_option('page_on_front') ){
	$pages = get_pages();
}

$component = new Component();
$results = $component->getResults();
?>
<div class="wrap">
	<div class="flex d-flex">
		<h2><?php _e( 'Crawl page', 'wpmedia-crawl' ); ?></h2>
		<form method="POST">
			<table>
				<tr>
					<td>
						<label for="page_id">
							<?php _e( 'Select a page to crawl', 'wpmedia-crawler' ); ?>
						</label>
					</td>
					<td>
						<select name="page_id" id="page_id">
							<option value=""><?php echo esc_attr( __( 'Please select page', 'wpmedia-crawler' ) ); ?></option>
							<?php if( ! $pages ) : ?>
								<option value="homepage">
									<?php _e( 'Homepage', 'wpmedia-crawler' ); ?>
								</option>
							<?php else : ?>
								<?php foreach( $pages as $page ) :?>
									<option value="<?php echo esc_attr( $page->ID ) ?>"><?php echo esc_html( $page->post_title ); ?></option>
								<?php endforeach; ?>
							<?php endif;?>
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
			<?php if( count( $results ) > 0 ): ?>
				<table class="wp-list-table widefat striped">
					<tr>
						<th><?php _e( 'ID', 'wpmedia-crawler' ); ?></th>
						<th style="width: 80%"><?php _e( 'Page Title', 'wpmedia-crawler' ); ?></th>
						<th><?php _e( 'Action', 'wpmedia-crawler' ); ?></th>
					</tr>
					<?php foreach( $results as $key => $result ) : ?>
						<tr>
							<td><?php echo $key + 1;?></td>
							<td><?php echo $result['title'] ?></td>
							<td>
								<?php
								echo sprintf(
									'<a href="%s">%s',
									$component->singlePageAction( $result['key'], 'view' ),
									__( 'View links', 'wpmedia-crawler' )
								);
								?>
								<br>
								<?php
								echo sprintf(
									'<a target="%s" href="%s">%s',
									'_blank',
									$component->viewStaticPage( $result['key'] ),
									__( 'View static page', 'wpmedia-crawler' )
								);
								?>
								<br>
								<?php
								echo sprintf(
									'<a target="%s" href="%s">%s',
									'_blank',
									$component->viewStaticPage( $result['key'], 'sitemap' ),
									__( 'View sitemap', 'wpmedia-crawler' )
								);
								?>
								<br>
								<?php
								echo sprintf(
									'<a href="%s"><i class="dashicons dashicons-trash"></i></a>',
									$component->deleteAction( $result['key'], 'delete' )
								);
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php else : ?>
				<h1><?php _e( 'No records available', 'wpmedia-crawler' ); ?></h1>
			<?php endif; ?>
		</div>
	</div>
</div>
