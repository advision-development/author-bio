<?php
/**
 * Template 3 — Editorial masthead.
 *
 * Ported from docs/design/author-page-templates.dc.html:291-397
 *
 * @var array $d    Profile data.
 * @var array $hide Suppressed section slugs.
 */

defined( 'ABSPATH' ) || exit;

$a = $d['author'];
?>
<div class="abio-t3">

	<header class="abio-t3__masthead">
		<?php if ( $a['kicker'] || $a['location'] ) : ?>
			<span class="abio-kicker abio-t3__kicker">
				<?php if ( $a['kicker'] ) : ?><?php echo esc_html( $a['kicker'] ); ?><?php endif; ?>
				<?php if ( $a['kicker'] && $a['location'] ) : ?> · <?php endif; ?>
				<?php if ( $a['location'] ) : ?><?php echo esc_html( $a['location'] ); ?><?php endif; ?>
			</span>
		<?php endif; ?>

		<h1 class="abio-t3__name"><?php echo esc_html( $a['name'] ); ?></h1>

		<?php if ( $a['role'] ) : ?>
			<p class="abio-t3__role"><?php echo esc_html( $a['role'] ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $a['badges'] ) ) : ?>
			<ul class="abio-t3__badges">
				<?php foreach ( $a['badges'] as $badge ) : ?>
					<li><?php echo esc_html( $badge ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</header>

	<?php if ( ! empty( $d['gallery']['items'] ) ) : ?>
		<div class="abio-t3__gallery-wrap">
			<ul class="abio-t3__gallery">
				<?php foreach ( $d['gallery']['items'] as $g ) : ?>
					<li>
						<?php echo ABIO_View::media( $g['image'], 'medium', $g['label'], 'abio-t3__gallery-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span class="abio-t3__gallery-caption"><?php echo esc_html( $g['caption'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<div class="abio-t3__body">

		<?php if ( $a['bio'] ) : ?>
			<div class="abio-t3__bio"><?php echo wp_kses_post( wpautop( $a['bio'] ) ); ?></div>
		<?php endif; ?>

		<?php if ( ! empty( $d['stats'] ) ) : ?>
			<ul class="abio-t3__stats">
				<?php foreach ( $d['stats'] as $s ) : ?>
					<li>
						<span class="abio-t3__stat-value"><?php echo esc_html( $s['value'] ); ?></span>
						<span class="abio-t3__stat-label"><?php echo esc_html( $s['label'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! empty( $d['focus'] ) ) : ?>
			<section id="abio-focus" class="abio-t3__section">
				<h2 class="abio-t3__h2"><?php esc_html_e( 'Areas of focus', 'author-bio' ); ?></h2>
				<ul class="abio-t3__focus">
					<?php foreach ( $d['focus'] as $f ) : ?>
						<li>
							<h3><?php echo esc_html( $f['title'] ); ?></h3>
							<p><?php echo esc_html( $f['body'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $d['experience'] ) ) : ?>
			<section id="abio-experience" class="abio-t3__section">
				<h2 class="abio-t3__h2"><?php esc_html_e( 'Experience', 'author-bio' ); ?></h2>
				<ul class="abio-t3__exp">
					<?php foreach ( $d['experience'] as $x ) : ?>
						<li>
							<span class="abio-t3__exp-years"><?php echo esc_html( $x['years'] ); ?></span>
							<h3><?php echo esc_html( $x['title'] ); ?></h3>
							<span class="abio-t3__exp-org"><?php echo esc_html( $x['org'] ); ?></span>
							<p><?php echo esc_html( $x['body'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $d['edits'] ) ) : ?>
			<section id="abio-edits" class="abio-t3__section">
				<h2 class="abio-t3__h2"><?php esc_html_e( 'Latest edits', 'author-bio' ); ?></h2>
				<ul class="abio-t3__edits">
					<?php foreach ( $d['edits'] as $e ) : ?>
						<li>
							<div class="abio-t3__edit-meta">
								<span><?php echo esc_html( $e['date'] ); ?></span>
								<span><?php echo esc_html( $e['type'] ); ?> · <?php echo esc_html( $e['status'] ); ?></span>
							</div>
							<h3><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a></h3>
							<p><?php echo esc_html( $e['summary'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

	</div>

	<?php if ( ! empty( $d['credentials'] ) || ! empty( $d['follows'] ) || ! empty( $d['others'] ) ) : ?>
		<div class="abio-t3__footer-wrap">
			<div class="abio-t3__footer">

				<?php if ( ! empty( $d['credentials'] ) ) : ?>
					<div class="abio-t3__footer-cell">
						<h3 class="abio-eyebrow"><?php esc_html_e( 'Credentials', 'author-bio' ); ?></h3>
						<ul class="abio-t3__list">
							<?php foreach ( $d['credentials'] as $c ) : ?>
								<li><?php echo esc_html( $c ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $d['follows'] ) ) : ?>
					<div class="abio-t3__footer-cell">
						<h3 class="abio-eyebrow"><?php esc_html_e( 'Follows', 'author-bio' ); ?></h3>
						<ul class="abio-chips">
							<?php foreach ( $d['follows'] as $h ) : ?>
								<li><a href="<?php echo esc_url( $h['url'] ); ?>" rel="nofollow ugc noopener" target="_blank"><?php echo esc_html( $h['handle'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $d['others'] ) ) : ?>
					<div class="abio-t3__footer-cell">
						<h3 class="abio-eyebrow"><?php esc_html_e( 'Other authors', 'author-bio' ); ?></h3>
						<ul class="abio-t3__others">
							<?php foreach ( $d['others'] as $o ) : ?>
								<li>
									<a href="<?php echo esc_url( $o['url'] ); ?>"><?php echo esc_html( $o['name'] ); ?></a>
									<span><?php echo esc_html( $o['role'] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

			</div>
		</div>
	<?php endif; ?>

</div>
