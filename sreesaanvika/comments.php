<?php
/**
 * Comments.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section class="ss-comments" id="comments">

	<?php if ( have_comments() ) : ?>
		<h2 class="ss-comments__title" style="font-size:1.5rem;margin-bottom:22px">
			<?php
			$ss_count = get_comments_number();

			if ( '1' === $ss_count ) {
				esc_html_e( 'One response', 'sreesaanvika' );
			} else {
				printf(
					/* translators: %s: comment count */
					esc_html( _n( '%s response', '%s responses', $ss_count, 'sreesaanvika' ) ),
					esc_html( number_format_i18n( $ss_count ) )
				);
			}
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
					'avatar_size' => 48,
				)
			);
			?>
		</ol>

		<?php
		the_comments_navigation(
			array(
				'prev_text' => esc_html__( 'Older comments', 'sreesaanvika' ),
				'next_text' => esc_html__( 'Newer comments', 'sreesaanvika' ),
			)
		);
		?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'sreesaanvika' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_submit'       => 'ss-btn',
			'title_reply'        => esc_html__( 'Leave a comment', 'sreesaanvika' ),
			'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
			'title_reply_after'  => '</h3>',
			'comment_field'      => '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Your comment', 'sreesaanvika' ) . '</label><textarea id="comment" name="comment" cols="45" rows="6" required></textarea></p>',
		)
	);
	?>
</section>
