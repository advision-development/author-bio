<?php
/**
 * Template 6 — Full-bleed dossier.
 *
 * Ported from docs/design/author-page-templates.dc.html:603-713
 *
 * @var array $d    Profile data.
 * @var array $hide Suppressed section slugs.
 */

defined( 'ABSPATH' ) || exit;

$a = $d['author'];

// A profile with no gallery items must not leave the fixed 2x2 photo grid
// half-empty, and a profile with neither gallery nor portrait must not
// render the grid frame at all (porting convention rule 5).
$abio_t6_has_gallery = ! empty( $d['gallery']['items'] );
$abio_t6_show_photos = $abio_t6_has_gallery || $a['portrait'];
?>
<div class="abio-t6">

	<header class="abio-t6__header abio-panel--dark">
		<div class="<?php echo esc_attr( 'abio-t6__header-inner' . ( $abio_t6_show_photos ? '' : ' abio-t6__header-inner--single' ) ); ?>">
			<div class="abio-t6__intro">
				<?php if ( $a['kicker'] || $a['location'] || $a['since'] ) : ?>
					<span class="abio-kicker abio-t6__kicker">
						<?php echo esc_html( $a['kicker'] ); ?>
						<?php if ( $a['kicker'] && ( $a['location'] || $a['since'] ) ) : ?> · <?php endif; ?>
						<?php echo esc_html( $a['location'] ); ?>
						<?php if ( $a['location'] && $a['since'] ) : ?> · <?php endif; ?>
						<?php if ( $a['since'] ) : ?><?php esc_html_e( 'Since', 'author-bio' ); ?> <?php echo esc_html( $a['since'] ); ?><?php endif; ?>
					</span>
				<?php endif; ?>

				<h1 class="abio-t6__name"><?php echo esc_html( $a['name'] ); ?></h1>

				<?php if ( $a['role'] ) : ?>
					<p class="abio-t6__role"><?php echo esc_html( $a['role'] ); ?></p>
				<?php endif; ?>

				<?php if ( $a['bio'] ) : ?>
					<div class="abio-t6__bio"><?php echo wp_kses_post( wpautop( $a['bio'] ) ); ?></div>
				<?php endif; ?>

				<?php if ( ! empty( $a['badges'] ) ) : ?>
					<ul class="abio-chips abio-t6__badges">
						<?php foreach ( $a['badges'] as $badge ) : ?>
							<li><span><?php echo esc_html( $badge ); ?></span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<?php if ( $abio_t6_show_photos ) : ?>
				<ul class="<?php echo esc_attr( 'abio-t6__photos' . ( $abio_t6_has_gallery ? '' : ' abio-t6__photos--single' ) ); ?>">
					<?php foreach ( $d['gallery']['items'] as $g ) : ?>
						<li class="abio-t6__photo">
							<?php echo ABIO_View::media( $g['image'], 'medium', '', 'abio-t6__photo-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<div class="abio-t6__photo-overlay">
								<span class="abio-t6__photo-label"><?php echo esc_html( $g['label'] ); ?></span>
								<span class="abio-t6__photo-caption"><?php echo esc_html( $g['caption'] ); ?></span>
							</div>
						</li>
					<?php endforeach; ?>
					<li class="abio-t6__photo abio-t6__photo--portrait">
						<?php echo ABIO_View::media( $a['portrait'], 'medium', 'portrait 1:1', 'abio-t6__photo-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</li>
				</ul>
			<?php endif; ?>
		</div>
	</header>

	<?php if ( ! empty( $d['stats'] ) ) : ?>
		<div class="abio-t6__stats-wrap">
			<ul class="abio-t6__stats">
				<?php foreach ( $d['stats'] as $s ) : ?>
					<li>
						<span class="abio-t6__stat-value"><?php echo esc_html( $s['value'] ); ?></span>
						<span class="abio-t6__stat-label"><?php echo esc_html( $s['label'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $d['focus'] ) ) : ?>
		<section id="abio-focus" class="abio-t6__section">
			<h2 class="abio-t6__heading"><?php esc_html_e( 'Areas of focus', 'author-bio' ); ?></h2>
			<ul class="abio-t6__focus">
				<?php foreach ( $d['focus'] as $f ) : ?>
					<li>
						<span class="abio-t6__rule"></span>
						<h3><?php echo esc_html( $f['title'] ); ?></h3>
						<p><?php echo esc_html( $f['body'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $d['edits'] ) ) : ?>
		<section id="abio-edits" class="abio-t6__section abio-t6__section--edits">
			<h2 class="abio-t6__heading"><?php esc_html_e( 'Latest edits', 'author-bio' ); ?></h2>
			<ul class="abio-t6__edits">
				<?php foreach ( $d['edits'] as $e ) : ?>
					<li>
						<span class="abio-t6__edit-date"><?php echo esc_html( $e['date'] ); ?></span>
						<span class="abio-t6__edit-type"><?php echo esc_html( $e['type'] ); ?> · <?php echo esc_html( $e['status'] ); ?></span>
						<div class="abio-t6__edit-body">
							<h3><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a></h3>
							<p><?php echo esc_html( $e['summary'] ); ?></p>
						</div>
						<span class="abio-t6__edit-time"><?php echo esc_html( $e['readTime'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $d['experience'] ) ) : ?>
		<section id="abio-experience" class="abio-t6__section">
			<h2 class="abio-t6__heading"><?php esc_html_e( 'Experience', 'author-bio' ); ?></h2>
			<ul class="abio-t6__exp">
				<?php foreach ( $d['experience'] as $x ) : ?>
					<li>
						<span class="abio-t6__exp-years"><?php echo esc_html( $x['years'] ); ?></span>
						<h3><?php echo esc_html( $x['title'] ); ?></h3>
						<span class="abio-t6__exp-org"><?php echo esc_html( $x['org'] ); ?></span>
						<p><?php echo esc_html( $x['body'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $d['follows'] ) || ! empty( $d['others'] ) || $d['pitch']['title'] ) : ?>
		<footer class="abio-t6__footer abio-panel--dark">
			<div class="abio-t6__footer-grid">

				<?php if ( ! empty( $d['follows'] ) ) : ?>
					<div class="abio-t6__footer-col">
						<h3 class="abio-eyebrow"><?php esc_html_e( 'Follows', 'author-bio' ); ?></h3>
						<ul class="abio-chips">
							<?php foreach ( $d['follows'] as $h ) : ?>
								<li><a href="<?php echo esc_url( $h['url'] ); ?>" rel="nofollow ugc noopener" target="_blank"><?php echo esc_html( $h['handle'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $d['others'] ) ) : ?>
					<div class="abio-t6__footer-col">
						<h3 class="abio-eyebrow"><?php esc_html_e( 'Other authors', 'author-bio' ); ?></h3>
						<ul class="abio-t6__others">
							<?php foreach ( $d['others'] as $o ) : ?>
								<li>
									<?php echo ABIO_View::optional_link( $o['url'], $o['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<span><?php echo esc_html( $o['role'] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( $d['pitch']['title'] ) : ?>
					<div class="abio-t6__footer-col">
						<h3 class="abio-eyebrow"><?php echo esc_html( $d['pitch']['title'] ); ?></h3>
						<div class="abio-t6__pitch-body"><?php echo wp_kses_post( $d['pitch']['body'] ); ?></div>
						<?php if ( $d['site']['contactUrl'] && $d['pitch']['cta'] ) : ?>
							<a class="abio-cta" href="<?php echo esc_url( $d['site']['contactUrl'] ); ?>"><?php echo esc_html( $d['pitch']['cta'] ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>

			</div>
		</footer>
	<?php endif; ?>

</div>
