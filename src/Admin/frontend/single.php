<div class="wrap">
	<h2>
		<?php
		echo esc_html(
			sprintf(
				'Internal links available on %s page',
				$title
			)
		);
		?>
	</h2>
	<div id="cavalcade-jobs-list-table">
		<div id="cavalcade-jobs-post-body">

			<?php
			if ( $links && count( $links ) > 0 ) :
				?>
				<table class="wp-list-table widefat striped">
					<tr>
						<th><?php esc_html_e( 'ID', 'wpmedia-crawler' ); ?></th>
						<th><?php esc_html_e( 'Links', 'wpmedia-crawler' ); ?></th>
					</tr>
					<?php foreach ( $links as $wpcrawler_key => $wpcrawler_link ) : ?>
						<tr>
							<td><?php echo esc_html( $wpcrawler_key + 1 ); ?></td>
							<td><?php echo esc_html( $wpcrawler_link ); ?></td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php else : ?>
				<h1><?php esc_html_e( 'No records available', 'wpmedia-crawler' ); ?></h1>
			<?php endif ?>
		</div>
	</div>
</div>
