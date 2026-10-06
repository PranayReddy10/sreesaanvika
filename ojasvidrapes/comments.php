<?php
/**
 * Comments.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section class="od-comments" id="comments">

	<?php if ( have_comments() ) : ?>
		<h2 class="od-comments__title" style="font-size:1.5rem;margin-bottom:22px">
			<?php
			$od_count = get_comments_number();

			if ( '1' === $od_count ) {
				esc_html_e( 'One response', 'ojasvidrapes' );
			} else {
				printf(
					/* translators: %s: comment count */
					esc_html( _n( '%s response', '%s responses', $od_count, 'ojasvidrapes' ) ),
					esc_html( number_format_i18n( $od_count ) )
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
				'prev_text' => esc_html__( 'Older comments', 'ojasvidrapes' ),
				'next_text' => esc_html__( 'Newer comments', 'ojasvidrapes' ),
			)
		);
		?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'ojasvidrapes' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_submit'       => 'od-btn',
			'title_reply'        => esc_html__( 'Leave a comment', 'ojasvidrapes' ),
			'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
			'title_reply_after'  => '</h3>',
			'comment_field'      => '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Your comment', 'ojasvidrapes' ) . '</label><textarea id="comment" name="comment" cols="45" rows="6" required></textarea></p>',
		)
	);
	?>
</section>
