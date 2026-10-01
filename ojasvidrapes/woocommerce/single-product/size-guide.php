<?php
/**
 * Size guide shown in the slide-in panel.
 *
 * Copy a version of this file into a child theme to publish your own
 * measurements.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

$od_rows = array(
	array( 'XS', '32', '26', '34', '38' ),
	array( 'S', '34', '28', '36', '39' ),
	array( 'M', '36', '30', '38', '40' ),
	array( 'L', '38', '32', '40', '41' ),
	array( 'XL', '40', '34', '42', '41' ),
	array( 'XXL', '42', '36', '44', '42' ),
);
?>
<div class="od-sizechart">
	<p style="color:var(--od-muted);font-size:.9rem">
		<?php esc_html_e( 'All measurements are in inches and describe the body, not the garment. Blouses are supplied unstitched with a 6-inch margin unless stated otherwise.', 'ojasvidrapes' ); ?>
	</p>

	<table>
		<thead>
			<tr>
				<th><?php esc_html_e( 'Size', 'ojasvidrapes' ); ?></th>
				<th><?php esc_html_e( 'Bust', 'ojasvidrapes' ); ?></th>
				<th><?php esc_html_e( 'Waist', 'ojasvidrapes' ); ?></th>
				<th><?php esc_html_e( 'Hip', 'ojasvidrapes' ); ?></th>
				<th><?php esc_html_e( 'Length', 'ojasvidrapes' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $od_rows as $od_row ) : ?>
				<tr>
					<?php foreach ( $od_row as $od_i => $od_cell ) : ?>
						<?php if ( 0 === $od_i ) : ?>
							<th scope="row" style="color:var(--od-gold)"><?php echo esc_html( $od_cell ); ?></th>
						<?php else : ?>
							<td><?php echo esc_html( $od_cell ); ?></td>
						<?php endif; ?>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h4 style="margin-top:26px"><?php esc_html_e( 'Saree lengths', 'ojasvidrapes' ); ?></h4>
	<ul style="color:var(--od-text-soft);font-size:.9rem">
		<li><?php esc_html_e( 'Standard saree — 5.5 metres plus a 0.8 metre blouse piece', 'ojasvidrapes' ); ?></li>
		<li><?php esc_html_e( 'Nine-yard (madisar) — 8.2 metres, no separate blouse piece', 'ojasvidrapes' ); ?></li>
		<li><?php esc_html_e( 'Blouse piece — unstitched, cut generously enough for sizes 32 to 42', 'ojasvidrapes' ); ?></li>
	</ul>

	<p style="color:var(--od-muted);font-size:.86rem">
		<?php esc_html_e( 'Between two sizes? Take the larger one — our tailors can take a garment in, but rarely let it out.', 'ojasvidrapes' ); ?>
	</p>
</div>
