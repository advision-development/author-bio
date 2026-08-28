<?php
/**
 * Template 2 — Résumé.
 *
 * Ported from docs/design/author-page-templates.dc.html:179-289
 *
 * @var array $d    Profile data.
 * @var array $hide Suppressed section slugs.
 */

defined( 'ABSPATH' ) || exit;

$a = $d['author'];
?>
<div class="abio-t2">
	<div class="abio-t2__grid">

		<aside class="abio-t2__aside">
			<div class="abio-t2__portrait">
				<?php echo ABIO_View::media( $a['portrait'], 'medium', 'portrait 1:1', 'abio-t2__portrait-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

				<?php if ( ! empty( $d['gallery']['items'] ) ) : ?>
					<ul class="abio-t2__thumbs">
						<?php foreach ( $d['gallery']['items'] as $g ) : ?>
							<li title="<?php echo esc_attr( $g['caption'] ); ?>">
								<?php echo ABIO_View::media( $g['image'], 'thumbnail', $g['short'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="abio-t2__intro">
				<h1 class="abio-t2__name"><?php echo esc_html( $a['name'] ); ?></h1>
				<?php if ( $a['role'] ) : ?>
					<p class="abio-t2__role"><?php echo esc_html( $a['role'] ); ?></p>
				<?php endif; ?>
				<?php if ( $a['location'] || $a['since'] ) : ?>
					<p class="abio-t2__meta">
						<?php if ( $a['location'] ) : ?><?php echo esc_html( $a['location'] ); ?><?php endif; ?>
						<?php if ( $a['location'] && $a['since'] ) : ?> · <?php endif; ?>
						<?php if ( $a['since'] ) : ?><?php esc_html_e( 'Since', 'author-bio' ); ?> <?php echo esc_html( $a['since'] ); ?><?php endif; ?>
					</p>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $d['credentials'] ) ) : ?>
				<div class="abio-t2__block">
					<h2 class="abio-eyebrow"><?php esc_html_e( 'Credentials', 'author-bio' ); ?></h2>
					<ul class="abio-t2__list">
						<?php foreach ( $d['credentials'] as $c ) : ?>
							<li><?php echo esc_html( $c ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $a['badges'] ) ) : ?>
				<div class="abio-t2__block">
					<h2 class="abio-eyebrow"><?php esc_html_e( 'Verified', 'author-bio' ); ?></h2>
					<ul class="abio-t2__badges">
						<?php foreach ( $a['badges'] as $badge ) : ?>
							<li><?php echo esc_html( $badge ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $d['follows'] ) ) : ?>
				<div class="abio-t2__block">
					<h2 class="abio-eyebrow"><?php esc_html_e( 'Follows', 'author-bio' ); ?></h2>
					<ul class="abio-t2__follows">
						<?php foreach ( $d['follows'] as $h ) : ?>
							<li><a href="<?php echo esc_url( $h['url'] ); ?>" rel="nofollow ugc noopener" target="_blank"><?php echo esc_html( $h['handle'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $d['others'] ) ) : ?>
				<div class="abio-t2__block">
					<h2 class="abio-eyebrow"><?php esc_html_e( 'Other authors', 'author-bio' ); ?></h2>
					<ul class="abio-t2__others">
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

		<main class="abio-t2__main">

			<section class="abio-t2__lede">
				<span class="abio-kicker"><?php echo esc_html( $a['kicker'] ); ?> <?php esc_html_e( 'profile', 'author-bio' ); ?></span>
				<?php if ( $a['bio'] ) : ?>
					<div class="abio-t2__bio"><?php echo wp_kses_post( wpautop( $a['bio'] ) ); ?></div>
				<?php endif; ?>
			</section>

			<?php if ( ! empty( $d['stats'] ) ) : ?>
				<ul class="abio-t2__stats">
					<?php foreach ( $d['stats'] as $s ) : ?>
						<li>
							<span class="abio-t2__stat-value"><?php echo esc_html( $s['value'] ); ?></span>
							<span class="abio-t2__stat-label"><?php echo esc_html( $s['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $d['focus'] ) ) : ?>
				<section id="abio-focus" class="abio-t2__section">
					<h2 class="abio-eyebrow"><?php esc_html_e( 'Areas of focus', 'author-bio' ); ?></h2>
					<ul class="abio-t2__focus">
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
				<section id="abio-experience" class="abio-t2__section abio-t2__section--rule">
					<h2 class="abio-eyebrow"><?php esc_html_e( 'Experience', 'author-bio' ); ?></h2>
					<ul class="abio-t2__exp">
						<?php foreach ( $d['experience'] as $x ) : ?>
							<li>
								<div class="abio-t2__exp-head">
									<h3><?php echo esc_html( $x['title'] ); ?></h3>
									<span class="abio-t2__exp-years"><?php echo esc_html( $x['years'] ); ?></span>
								</div>
								<span class="abio-t2__exp-org"><?php echo esc_html( $x['org'] ); ?></span>
								<p><?php echo esc_html( $x['body'] ); ?></p>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $d['edits'] ) ) : ?>
				<section id="abio-edits" class="abio-t2__section abio-t2__section--rule">
					<h2 class="abio-eyebrow"><?php esc_html_e( 'Latest edits', 'author-bio' ); ?></h2>
					<ul class="abio-t2__edits">
						<?php foreach ( $d['edits'] as $e ) : ?>
							<li>
								<div class="abio-t2__edit-meta">
									<span><?php echo esc_html( $e['date'] ); ?></span>
									<span><?php echo esc_html( $e['type'] ); ?></span>
									<span><?php echo esc_html( $e['status'] ); ?></span>
								</div>
								<h3><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a></h3>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

		</main>
	</div>
</div>
