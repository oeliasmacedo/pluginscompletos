<?php
/**
 * Course Bundle Builder Root Component
 *
 * This file works as a placeholder for the course bundle builder react app
 * for the both admin & front side.
 *
 * @package TutorLMS/CourseBundle
 * @since 3.2.0
 */

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<?php wp_head(); ?>

	<style>
		#wpadminbar {
			z-index: 9999;
			position: fixed;
		}
		#adminmenu, 
		#adminmenuback, 
		#adminmenuwrap, 
		#wpfooter {
			display: none !important;
		}
		#wpcontent {
			margin: 0 !important;
		}
		#wpbody-content {
			padding-bottom: 0px !important;
			float: none;
		}
		.notice {
			display: none;
		}
	</style>
</head>
<body <?php body_class(); ?>>
	<div id="tutor-course-bundle-builder-root"></div>
	<?php wp_footer(); ?>
</body>
</html>
