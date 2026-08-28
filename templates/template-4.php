<?php
/**
 * Template 4 — Bento.
 *
 * Ported from docs/design/author-page-templates.dc.html:399-511
 *
 * @var array $d    Profile data.
 * @var array $hide Suppressed section slugs.
 */

defined( 'ABSPATH' ) || exit;

$a = $d['author'];
?>
<div class="abio-t4">
	<div class="abio-t4__grid">

		<div class="abio-t4__cell abio-t4__portrait">
			<?php echo ABIO_View::media( $a['portrait'], 'medium', 'portrait 1:1', 'abio-t4__portrait-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

			<?php if ( ! empty( $a['badges'] ) ) : ?>
				<ul class="abio-t4__badges">
					<?php foreach ( $a['badges'] as $badge ) : ?>
						<li><?php echo esc_html( $badge ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="abio-t4__cell abio-t4__intro">
			<span class="abio-kicker"><?php echo esc_html( $a['kicker'] ); ?></span>
			<h1 class="abio-t4__name"><?php echo esc_html( $a['name'] ); ?></h1>
			<?php if ( $a['role'] ) : ?>
				<p class="abio-t4__role"><?php echo esc_html( $a['role'] ); ?></p>
			<?php endif; ?>
			<?php if ( $a['bio'] ) : ?>
				<p class="abio-t4__bio"><?php echo esc_html( $a['bio'] ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $d['stats'] ) ) : ?>
			<div class="abio-t4__stats-wrap">
				<?php foreach ( $d['stats'] as $s ) : ?>
					<div class="abio-t4__cell abio-t4__stat">
						<span class="abio-t4__stat-value"><?php echo esc_html( $s['value'] ); ?></span>
						<span class="abio-t4__stat-label"><?php echo esc_html( $s['label'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $d['gallery']['items'] ) ) : ?>
			<div class="abio-t4__gallery">
				<?php foreach ( $d['gallery']['items'] as $g ) : ?>
					<figure class="abio-t4__cell">
						<?php echo ABIO_View::media( $g['image'], 'medium', $g['label'], 'abio-t4__gallery-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<figcaption><?php echo esc_html( $g['caption'] ); ?></figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $d['focus'] ) ) : ?>
			<section id="abio-focus" class="abio-t4__cell abio-t4__focus">
				<h2 class="abio-eyebrow"><?php esc_html_e( 'Areas of focus', 'author-bio' ); ?></h2>
				<ul class="abio-t4__focus-list">
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
			<section id="abio-edits" class="abio-t4__cell abio-t4__edits">
				<h2 class="abio-eyebrow"><?php esc_html_e( 'Latest edits', 'author-bio' ); ?></h2>
				<ul class="abio-t4__edits-list">
					<?php foreach ( $d['edits'] as $e ) : ?>
						<li>
							<div class="abio-t4__edit-meta">
								<span><?php echo esc_html( $e['date'] ); ?></span>
								<span><?php echo esc_html( $e['status'] ); ?></span>
							</div>
							<h3><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a></h3>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $d['experience'] ) ) : ?>
			<section id="abio-experience" class="abio-t4__cell abio-t4__experience">
				<h2 class="abio-eyebrow"><?php esc_html_e( 'Experience', 'author-bio' ); ?></h2>
				<ul class="abio-t4__exp-list">
					<?php foreach ( $d['experience'] as $x ) : ?>
						<li>
							<span class="abio-t4__exp-years"><?php echo esc_html( $x['years'] ); ?></span>
							<h3><?php echo esc_html( $x['title'] ); ?></h3>
							<span class="abio-t4__exp-org"><?php echo esc_html( $x['org'] ); ?></span>
							<p><?php echo esc_html( $x['body'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $d['credentials'] ) ) : ?>
			<div class="abio-t4__cell abio-t4__block">
				<h2 class="abio-eyebrow"><?php esc_html_e( 'Credentials', 'author-bio' ); ?></h2>
				<ul class="abio-t4__list">
					<?php foreach ( $d['credentials'] as $c ) : ?>
						<li><?php echo esc_html( $c ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $d['follows'] ) ) : ?>
			<div class="abio-t4__cell abio-t4__block">
				<h2 class="abio-eyebrow"><?php esc_html_e( 'Follows on X', 'author-bio' ); ?></h2>
				<ul class="abio-chips">
					<?php foreach ( $d['follows'] as $h ) : ?>
						<li><a href="<?php echo esc_url( $h['url'] ); ?>" rel="nofollow ugc noopener" target="_blank"><?php echo esc_html( $h['handle'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $d['others'] ) ) : ?>
			<div class="abio-t4__cell abio-t4__block">
				<h2 class="abio-eyebrow"><?php esc_html_e( 'Other authors', 'author-bio' ); ?></h2>
				<ul class="abio-t4__others">
					<?php foreach ( $d['others'] as $o ) : ?>
						<li>
							<a href="<?php echo esc_url( $o['url'] ); ?>"><?php echo esc_html( $o['name'] ); ?></a>
							<span><?php echo esc_html( $o['role'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( $d['pitch']['title'] ) : ?>
			<div class="abio-t4__pitch abio-panel--dark">
				<h2><?php echo esc_html( $d['pitch']['title'] ); ?></h2>
				<p><?php echo esc_html( $d['pitch']['body'] ); ?></p>
				<?php if ( $d['site']['contactUrl'] && $d['pitch']['cta'] ) : ?>
					<a class="abio-cta" href="<?php echo esc_url( $d['site']['contactUrl'] ); ?>"><?php echo esc_html( $d['pitch']['cta'] ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div>
</div>
