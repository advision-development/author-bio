<?php
/**
 * Template 8 — Fintech product.
 *
 * Ported from docs/design/author-page-templates.dc.html:840-964
 *
 * @var array $d    Profile data.
 * @var array $hide Suppressed section slugs.
 */

defined( 'ABSPATH' ) || exit;

$a = $d['author'];

// The identity card's meta list only has rows worth showing when at least
// one of location / since / badges has content — otherwise the bordered
// list frame would render empty (porting convention rule 5).
$abio_t8_show_meta = $a['location'] || $a['since'] || ! empty( $a['badges'] );
?>
<div class="abio-t8">

	<div class="abio-t8__topbar">
		<div class="abio-t8__topbar-inner">
			<span class="abio-t8__topbar-label">
				<?php echo esc_html( $d['site']['name'] ); ?>
				<?php if ( $d['site']['name'] ) : ?> · <?php endif; ?>
				<?php esc_html_e( 'Author profile', 'author-bio' ); ?>
			</span>

			<?php if ( ! empty( $d['stats'] ) ) : ?>
				<ul class="abio-t8__topbar-stats">
					<?php foreach ( $d['stats'] as $s ) : ?>
						<li>
							<span class="abio-t8__topbar-stat-value"><?php echo esc_html( $s['value'] ); ?></span>
							<span class="abio-t8__topbar-stat-label"><?php echo esc_html( $s['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="abio-t8__body">

		<aside class="abio-t8__aside">

			<div class="abio-t8__card abio-t8__identity">
				<div class="abio-t8__identity-head">
					<?php echo ABIO_View::media( $a['portrait'], 'thumbnail', 'portrait 1:1', 'abio-t8__identity-portrait' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div class="abio-t8__identity-name">
						<h1 class="abio-t8__name"><?php echo esc_html( $a['name'] ); ?></h1>
						<?php if ( $a['role'] ) : ?>
							<p class="abio-t8__role"><?php echo esc_html( $a['role'] ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( $a['short'] ) : ?>
					<p class="abio-t8__short"><?php echo esc_html( $a['short'] ); ?></p>
				<?php endif; ?>

				<?php if ( $abio_t8_show_meta ) : ?>
					<ul class="abio-t8__meta">
						<?php if ( $a['location'] ) : ?>
							<li class="abio-t8__meta-row">
								<span class="abio-t8__meta-label"><?php esc_html_e( 'Location', 'author-bio' ); ?></span>
								<span><?php echo esc_html( $a['location'] ); ?></span>
							</li>
						<?php endif; ?>
						<?php if ( $a['since'] ) : ?>
							<li class="abio-t8__meta-row">
								<span class="abio-t8__meta-label"><?php esc_html_e( 'Contributing since', 'author-bio' ); ?></span>
								<span><?php echo esc_html( $a['since'] ); ?></span>
							</li>
						<?php endif; ?>
						<?php foreach ( $a['badges'] as $badge ) : ?>
							<li class="abio-t8__meta-badge">
								<span class="abio-t8__meta-check" aria-hidden="true">✓</span>
								<?php echo esc_html( $badge ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $d['site']['contactUrl'] && $d['pitch']['cta'] ) : ?>
					<a class="abio-t8__cta" href="<?php echo esc_url( $d['site']['contactUrl'] ); ?>"><?php echo esc_html( $d['pitch']['cta'] ); ?></a>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $d['credentials'] ) ) : ?>
				<div class="abio-t8__card">
					<h3 class="abio-t8__card-heading"><?php esc_html_e( 'Credentials', 'author-bio' ); ?></h3>
					<ul class="abio-t8__list">
						<?php foreach ( $d['credentials'] as $c ) : ?>
							<li><?php echo esc_html( $c ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $d['follows'] ) ) : ?>
				<div class="abio-t8__card">
					<h3 class="abio-t8__card-heading"><?php esc_html_e( 'Follows', 'author-bio' ); ?></h3>
					<ul class="abio-chips abio-t8__follows">
						<?php foreach ( $d['follows'] as $h ) : ?>
							<li><a href="<?php echo esc_url( $h['url'] ); ?>" rel="nofollow ugc noopener" target="_blank"><?php echo esc_html( $h['handle'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $d['others'] ) ) : ?>
				<div class="abio-t8__card">
					<h3 class="abio-t8__card-heading"><?php esc_html_e( 'Other authors', 'author-bio' ); ?></h3>
					<ul class="abio-t8__others">
						<?php foreach ( $d['others'] as $o ) : ?>
							<li>
								<a href="<?php echo esc_url( $o['url'] ); ?>"><?php echo esc_html( $o['name'] ); ?></a>
								<span><?php echo esc_html( $o['role'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

		</aside>

		<main class="abio-t8__main">

			<section class="abio-t8__card">
				<h2 class="abio-t8__card-heading"><?php esc_html_e( 'About', 'author-bio' ); ?></h2>

				<?php if ( $a['bio'] ) : ?>
					<div class="abio-t8__bio"><?php echo wp_kses_post( wpautop( $a['bio'] ) ); ?></div>
				<?php endif; ?>

				<?php if ( ! empty( $d['gallery']['items'] ) ) : ?>
					<ul class="abio-t8__gallery">
						<?php foreach ( $d['gallery']['items'] as $g ) : ?>
							<li>
								<?php echo ABIO_View::media( $g['image'], 'medium', $g['label'], 'abio-t8__gallery-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span class="abio-t8__gallery-caption"><?php echo esc_html( $g['caption'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>

			<?php if ( ! empty( $d['focus'] ) ) : ?>
				<section id="abio-focus" class="abio-t8__card">
					<h2 class="abio-t8__card-heading"><?php esc_html_e( 'Coverage', 'author-bio' ); ?></h2>
					<ul class="abio-t8__coverage">
						<?php foreach ( $d['focus'] as $f ) : ?>
							<li>
								<h3><?php echo esc_html( $f['title'] ); ?></h3>
								<p><?php echo esc_html( $f['body'] ); ?></p>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $d['edits'] ) ) : ?>
				<section id="abio-edits" class="abio-t8__card">
					<h2 class="abio-t8__card-heading"><?php esc_html_e( 'Activity', 'author-bio' ); ?></h2>
					<ul class="abio-t8__activity">
						<?php foreach ( $d['edits'] as $e ) : ?>
							<li>
								<span class="abio-t8__activity-date"><?php echo esc_html( $e['date'] ); ?></span>
								<div class="abio-t8__activity-body">
									<a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a>
									<p><?php echo esc_html( $e['summary'] ); ?></p>
								</div>
								<span class="abio-t8__activity-status"><?php echo esc_html( $e['status'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $d['experience'] ) ) : ?>
				<section id="abio-experience" class="abio-t8__card">
					<h2 class="abio-t8__card-heading"><?php esc_html_e( 'Experience', 'author-bio' ); ?></h2>
					<ul class="abio-t8__exp">
						<?php foreach ( $d['experience'] as $x ) : ?>
							<li>
								<span class="abio-t8__exp-years"><?php echo esc_html( $x['years'] ); ?></span>
								<div>
									<h3><?php echo esc_html( $x['title'] ); ?></h3>
									<span class="abio-t8__exp-org"><?php echo esc_html( $x['org'] ); ?></span>
									<p><?php echo esc_html( $x['body'] ); ?></p>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

		</main>

	</div>
</div>
