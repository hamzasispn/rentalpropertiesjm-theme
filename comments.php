<?php
/**
 * The template for displaying comments
 */

if ( post_password_required() ) {
	return;
}

/**
 * Custom callback for rendering comments
 */
if ( ! function_exists( 'render_custom_comment' ) ) {
	function render_custom_comment( $comment, $args, $depth ) {
		$GLOBALS['comment'] = $comment;
		?>
		<li id="comment-<?php comment_ID(); ?>" class="<?php echo esc_attr( implode( ' ', get_comment_class( 'bg-white border border-slate-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow duration-200' ) ) ); ?>">
			<div class="flex gap-4">
				<!-- Avatar -->
				<div class="flex-shrink-0">
					<?php echo get_avatar( $comment, 48, '', '', array( 'class' => 'w-12 h-12 rounded-full' ) ); ?>
				</div>

				<!-- Comment Content -->
				<div class="flex-1 min-w-0">
					<!-- Header -->
					<div class="flex items-start justify-between gap-4 mb-2">
						<div>
							<p class="text-sm font-semibold text-slate-900">
								<?php comment_author_link(); ?>
							</p>
							<time datetime="<?php comment_time( 'c' ); ?>" class="text-xs text-slate-500">
								<?php comment_time( 'F j, Y \a\t g:i a' ); ?>
							</time>
						</div>
						<?php if ( current_user_can( 'edit_comment', $comment->comment_ID ) ) : ?>
							<div class="flex gap-2">
								<?php
								edit_comment_link(
									'<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path></svg>',
									'',
									'<span class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 text-slate-700 rounded text-xs font-medium hover:bg-slate-200 transition-colors duration-200">Edit</span>'
								);
								?>
								<?php
								comment_reply_link(
									array(
										'depth'      => $depth,
										'max_depth'  => $args['max_depth'],
										'before'     => '<span class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 text-slate-700 rounded text-xs font-medium hover:bg-slate-200 transition-colors duration-200">',
										'after'      => '</span>',
										'reply_text' => 'Reply',
									)
								);
								?>
							</div>
						<?php endif; ?>
					</div>

					<!-- Comment Text -->
					<div class="prose prose-sm max-w-none text-slate-700 mb-3
						prose-p:text-slate-700 prose-p:mb-2
						prose-a:text-blue-600 prose-a:no-underline hover:prose-a:underline
						prose-strong:text-slate-900
						prose-em:text-slate-700">
						<?php comment_text(); ?>
					</div>

					<!-- Reply Button -->
					<?php if ( current_user_can( 'edit_comment', $comment->comment_ID ) === false ) : ?>
						<?php
						comment_reply_link(
							array(
								'depth'      => $depth,
								'max_depth'  => $args['max_depth'],
								'before'     => '<div class="mt-3"><a href="#" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors duration-200">',
								'after'      => '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path></svg></a></div>',
								'reply_text' => 'Reply to ' . get_comment_author(),
							)
						);
						?>
					<?php endif; ?>
				</div>

				<!-- Pending Badge -->
				<?php if ( ! $comment->comment_approved ) : ?>
					<div class="flex-shrink-0">
						<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
							Pending
						</span>
					</div>
				<?php endif; ?>
			</div>

			<!-- Nested Comments -->
			<?php if ( $args['has_children'] ) : ?>
				<ol class="children mt-6 ml-4 pl-4 border-l-2 border-slate-200 space-y-6 list-none">
					<?php /* Nested comments will be rendered by WordPress */ ?>
				</ol>
			<?php endif; ?>
		</li>
		<?php
	}
}
?>

<div id="comments" class="comments-area">
	<?php if ( have_comments() ) : ?>
		<h3 class="text-2xl font-bold text-slate-900 mb-8">
			<?php
			$comment_count = get_comments_number();
			if ( 1 === $comment_count ) {
				echo esc_html( '1 Comment' );
			} else {
				echo sprintf( esc_html( '%d Comments' ), $comment_count );
			}
			?>
		</h3>

		<ol class="comment-list space-y-8">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
					'callback'   => 'render_custom_comment',
				)
			);
			?>
		</ol>

		<?php
		if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) :
			?>
			<nav class="comment-navigation my-8">
				<div class="flex gap-4">
					<?php if ( get_previous_comments_link() ) : ?>
						<div class="flex-1">
							<?php previous_comments_link( '<span class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-900 rounded-lg hover:bg-slate-300 transition-colors duration-200 text-sm font-medium">← Older Comments</span>' ); ?>
						</div>
					<?php endif; ?>
					<?php if ( get_next_comments_link() ) : ?>
						<div class="flex-1 text-right">
							<?php next_comments_link( '<span class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-900 rounded-lg hover:bg-slate-300 transition-colors duration-200 text-sm font-medium">Newer Comments →</span>' ); ?>
						</div>
					<?php endif; ?>
				</div>
			</nav>
			<?php
		endif;
	endif;

	if ( comments_open() ) :
		?>
		<div class="comment-form-wrapper mt-12 pt-12 border-t border-slate-200">
			<?php
			comment_form(
				array(
					'class_submit'    => 'px-6 py-3 bg-[var(--primary-color)] text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-semibold text-base',
					'label_submit'    => 'Post Comment',
					'comment_field'   => '<div class="mb-6">
						<label for="comment" class="block text-sm font-semibold text-slate-900 mb-2">Comment</label>
						<textarea id="comment" name="comment" class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical" rows="5" required></textarea>
					</div>',
					'fields'          => array(
						'author' => '<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
							<div>
								<label for="author" class="block text-sm font-semibold text-slate-900 mb-2">Name <span class="text-red-500">*</span></label>
								<input id="author" name="author" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
							</div>',
						'email'  => '<div>
								<label for="email" class="block text-sm font-semibold text-slate-900 mb-2">Email <span class="text-red-500">*</span></label>
								<input id="email" name="email" type="email" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
							</div>',
						'url'    => '<div>
								<label for="url" class="block text-sm font-semibold text-slate-900 mb-2">Website</label>
								<input id="url" name="url" type="url" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
							</div>
						</div>',
					),
				)
			);
			?>
		</div>
		<?php
	elseif ( is_single() ) :
		?>
		<p class="text-slate-600 text-center py-8">Comments are closed.</p>
		<?php
	endif;
	?>
</div>
