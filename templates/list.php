<?php
/**
 * The author index — every saved profile as a vertical list.
 *
 * One layout for all ten templates. What varies between them is carried by the
 * tokens on the root element and by a handful of `.abio--tN` modifiers in the
 * stylesheet, so this file does not branch on which template is active.
 *
 * @var array $d authors, heading, counts, site
 */

defined( 'ABSPATH' ) || exit;

$abio_count = count( $d['authors'] );
?>
<div class="abio-list">

	<header class="abio-list__head">
		<span class="abio-kicker">
			<?php
			if ( '' !== $d['heading'] ) {
				echo esc_html( $d['heading'] );
			} else {
				printf(
					/* translators: %d: number of authors listed. */
					esc_html( _n( 'Author · %d', 'Authors · %d', $abio_count, 'author-bio' ) ),
					absint( $abio_count )
				);
			}
			?>
		</span>
	</header>

	<ul class="abio-list__rows">
		<?php foreach ( $d['authors'] as $abio_author ) : ?>
			<li class="abio-list__row">
				<div class="abio-list__portrait">
					<?php
					echo ABIO_View::media( // phpcs:ignore WordPress.Security.EscapeOutput
						$abio_author['portrait'],
						'medium',
						'portrait 1:1',
						'abio-list__portrait-img',
						$abio_author['name']
					);
					?>
				</div>

				<div class="abio-list__body">
					<?php if ( '' !== $abio_author['kicker'] ) : ?>
						<span class="abio-kicker abio-list__kicker"><?php echo esc_html( $abio_author['kicker'] ); ?></span>
					<?php endif; ?>

					<h3 class="abio-list__name">
						<?php echo ABIO_View::optional_link( $abio_author['url'], $abio_author['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</h3>

					<?php if ( '' !== $abio_author['role'] ) : ?>
						<p class="abio-list__role"><?php echo esc_html( $abio_author['role'] ); ?></p>
					<?php endif; ?>

					<?php if ( '' !== $abio_author['short'] ) : ?>
						<p class="abio-list__short"><?php echo esc_html( $abio_author['short'] ); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( $d['counts'] && $abio_author['posts'] ) : ?>
					<div class="abio-list__meta">
						<span class="abio-list__count"><?php echo esc_html( number_format_i18n( $abio_author['posts'] ) ); ?></span>
						<span class="abio-list__count-label">
							<?php
							echo esc_html(
								_n( 'article', 'articles', $abio_author['posts'], 'author-bio' )
							);
							?>
						</span>
					</div>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
