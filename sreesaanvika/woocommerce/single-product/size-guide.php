<?php
/**
 * Size guide shown in the slide-in panel.
 *
 * Copy a version of this file into a child theme to publish your own
 * measurements.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

$ss_rows = array(
	array( 'XS', '32', '26', '34', '38' ),
	array( 'S', '34', '28', '36', '39' ),
	array( 'M', '36', '30', '38', '40' ),
	array( 'L', '38', '32', '40', '41' ),
	array( 'XL', '40', '34', '42', '41' ),
	array( 'XXL', '42', '36', '44', '42' ),
);
?>
<div class="ss-sizechart">
	<p style="color:var(--ss-muted);font-size:.9rem">
		<?php esc_html_e( 'All measurements are in inches and describe the body, not the garment. Blouses are supplied unstitched with a 6-inch margin unless stated otherwise.', 'sreesaanvika' ); ?>
	</p>

	<table>
		<thead>
			<tr>
				<th><?php esc_html_e( 'Size', 'sreesaanvika' ); ?></th>
				<th><?php esc_html_e( 'Bust', 'sreesaanvika' ); ?></th>
				<th><?php esc_html_e( 'Waist', 'sreesaanvika' ); ?></th>
				<th><?php esc_html_e( 'Hip', 'sreesaanvika' ); ?></th>
				<th><?php esc_html_e( 'Length', 'sreesaanvika' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $ss_rows as $ss_row ) : ?>
				<tr>
					<?php foreach ( $ss_row as $ss_i => $ss_cell ) : ?>
						<?php if ( 0 === $ss_i ) : ?>
							<th scope="row" style="color:var(--ss-gold)"><?php echo esc_html( $ss_cell ); ?></th>
						<?php else : ?>
							<td><?php echo esc_html( $ss_cell ); ?></td>
						<?php endif; ?>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h4 style="margin-top:26px"><?php esc_html_e( 'Saree lengths', 'sreesaanvika' ); ?></h4>
	<ul style="color:var(--ss-text-soft);font-size:.9rem">
		<li><?php esc_html_e( 'Standard saree — 5.5 metres plus a 0.8 metre blouse piece', 'sreesaanvika' ); ?></li>
		<li><?php esc_html_e( 'Nine-yard (madisar) — 8.2 metres, no separate blouse piece', 'sreesaanvika' ); ?></li>
		<li><?php esc_html_e( 'Lehenga — free size waist with a 3-inch adjustable drawstring', 'sreesaanvika' ); ?></li>
	</ul>

	<p style="color:var(--ss-muted);font-size:.86rem">
		<?php esc_html_e( 'Between two sizes? Take the larger one — our tailors can take a garment in, but rarely let it out.', 'sreesaanvika' ); ?>
	</p>
</div>
