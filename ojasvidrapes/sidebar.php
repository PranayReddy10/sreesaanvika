<?php
/**
 * Blog sidebar.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_active_sidebar( 'sidebar-blog' ) ) {
	return;
}
?>
<aside class="od-sidebar" role="complementary">
	<?php dynamic_sidebar( 'sidebar-blog' ); ?>
</aside>
