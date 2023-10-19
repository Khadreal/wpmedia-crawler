<div class="wrap">
	<h2>
		<?php echo sprintf( __( 'Internal links available on %s page', 'wpmedia-crawler' ), $title ); ?>
	</h2>
	<div id="cavalcade-jobs-list-table">
		<div id="cavalcade-jobs-post-body">

			<?php
			if( $links && count( $links ) > 0 ): ?>
				<table class="wp-list-table widefat striped">
					<tr>
						<th><?php _e( 'ID', 'wpmedia-crawl' ); ?></th>
						<th><?php _e( 'Links', 'wpmedia-crawl' ); ?></th>
					</tr>
					<?php foreach( $links as $key => $url ): ?>
						<tr>
							<td><?php echo $key + 1 ?></td>
							<td><?php echo $url ?></td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php else: ?>
				<h1><?php _e( 'No records available', 'wpmedia-crawl' ); ?></h1>
			<?php endif ?>
		</div>
	</div>
</div>
