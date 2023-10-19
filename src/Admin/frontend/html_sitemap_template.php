<!DOCTYPE html>
	<html>
	<head>
		<title>HTML Sitemap</title>
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
		<style>
			body, ol, ul, li{
				margin: 0;
				padding: 0;
			}
			body{
				font-family: 'Lato', sans-serif;
				background: #efefef;
			}
			h5{
				text-align: center; font-size: 18px;
			}
			.container{
				margin: 0 auto;
				width: 650px;
			}
			li{
				margin-bottom: 10px;
			}
		</style>
	</head>
	<body>
		<div class="container">
			<h5>
				<?php echo $title; ?> Internal Links
			</h5>
			<p>
				<?php echo $info; ?>
			</p>
			<ul>
				<?php foreach ( $links as $link ) { ?>
					<li><a target="_blank" href="<?php echo $link ?>"><?php echo $link ?></a></li>
				<?php } ?>
			</ul>
		</div>
	</body>
</html>
