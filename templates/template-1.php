<?php
/**
 * Template 1 — Classic sidebar.
 *
 * Ported from docs/design/author-page-templates.dc.html:36-177
 *
 * @var array $d    Profile data.
 * @var array $hide Suppressed section slugs.
 */

defined( 'ABSPATH' ) || exit;

$a = $d['author'];
?>
<div class="abio-t1">

	<nav class="abio-t1__crumbs">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'author-bio' ); ?></a>
		<?php if ( $d['site']['authorsUrl'] ) : ?>
			<span>/</span>
			<a href="<?php echo esc_url( $d['site']['authorsUrl'] ); ?>"><?php esc_html_e( 'Authors', 'author-bio' ); ?></a>
		<?php endif; ?>
		<span>/</span>
		<span><?php echo esc_html( $a['name'] ); ?></span>
	</nav>

	<div class="abio-t1__grid">
		<main class="abio-t1__main">

			<header class="abio-t1__header">
				<div class="abio-t1__portrait">
					<?php echo ABIO_View::media( $a['portrait'], 'medium', 'portrait 1:1', 'abio-t1__portrait-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

					<?php if ( ! empty( $d['gallery']['items'] ) ) : ?>
						<ul class="abio-t1__thumbs">
							<?php foreach ( $d['gallery']['items'] as $g ) : ?>
								<li title="<?php echo esc_attr( $g['caption'] ); ?>">
									<?php echo ABIO_View::media( $g['image'], 'thumbnail', $g['short'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<div class="abio-t1__intro">
					<span class="abio-kicker"><?php echo esc_html( $a['kicker'] ); ?></span>
					<h1 class="abio-t1__name"><?php echo esc_html( $a['name'] ); ?></h1>
					<?php if ( $a['role'] ) : ?>
						<p class="abio-t1__role"><?php echo esc_html( $a['role'] ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $a['badges'] ) ) : ?>
						<ul class="abio-chips">
							<?php foreach ( $a['badges'] as $badge ) : ?>
								<li><span><?php echo esc_html( $badge ); ?></span></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( $a['bio'] ) : ?>
						<div class="abio-prose abio-t1__bio"><?php echo wp_kses_post( wpautop( $a['bio'] ) ); ?></div>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( ! empty( $d['stats'] ) ) : ?>
				<ul class="<?php echo esc_attr( 'abio-t1__stats' . ( count( $d['stats'] ) < 4 ? ' abio-t1__stats--n' . count( $d['stats'] ) : '' ) ); ?>">
					<?php foreach ( $d['stats'] as $s ) : ?>
						<li>
							<span class="abio-t1__stat-value"><?php echo esc_html( $s['value'] ); ?></span>
							<span class="abio-t1__stat-label"><?php echo esc_html( $s['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $d['focus'] ) ) : ?>
				<section id="abio-focus" class="abio-t1__section">
					<h2 class="abio-t1__h2"><?php esc_html_e( 'Areas of focus', 'author-bio' ); ?></h2>
					<ul class="abio-t1__focus">
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
				<section id="abio-edits" class="abio-t1__section">
					<h2 class="abio-t1__h2"><?php esc_html_e( 'Latest edits', 'author-bio' ); ?></h2>
					<ul class="abio-t1__edits">
						<?php foreach ( $d['edits'] as $e ) : ?>
							<li>
								<div class="abio-t1__edit-meta">
									<span><?php echo esc_html( $e['date'] ); ?></span>
									<span class="abio-t1__edit-status"><?php echo esc_html( $e['status'] ); ?></span>
								</div>
								<div class="abio-t1__edit-body">
									<span class="abio-t1__edit-type"><?php echo esc_html( $e['type'] ); ?></span>
									<h3><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a></h3>
									<p><?php echo esc_html( $e['summary'] ); ?></p>
									<span class="abio-t1__edit-time"><?php echo esc_html( $e['readTime'] ); ?></span>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $d['experience'] ) ) : ?>
				<section id="abio-experience" class="abio-t1__section">
					<h2 class="abio-t1__h2"><?php esc_html_e( 'Experience', 'author-bio' ); ?></h2>
					<ul class="abio-t1__exp">
						<?php foreach ( $d['experience'] as $x ) : ?>
							<li>
								<span class="abio-t1__exp-years"><?php echo esc_html( $x['years'] ); ?></span>
								<div>
									<h3><?php echo esc_html( $x['title'] ); ?></h3>
									<span class="abio-t1__exp-org"><?php echo esc_html( $x['org'] ); ?></span>
									<p><?php echo esc_html( $x['body'] ); ?></p>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

		</main>

		<aside class="abio-t1__rail">

			<?php if ( ! empty( $d['nav'] ) ) : ?>
				<nav class="abio-t1__toc">
					<h3 class="abio-eyebrow"><?php esc_html_e( 'On this page', 'author-bio' ); ?></h3>
					<ol>
						<?php foreach ( $d['nav'] as $n ) : ?>
							<li>
								<span class="abio-t1__toc-num"><?php echo esc_html( $n['num'] ); ?></span>
								<a href="<?php echo esc_url( $n['href'] ); ?>"><?php echo esc_html( $n['label'] ); ?></a>
							</li>
						<?php endforeach; ?>
					</ol>
				</nav>
			<?php endif; ?>

			<?php if ( ! empty( $d['credentials'] ) ) : ?>
				<div class="abio-t1__block">
					<h3><?php esc_html_e( 'Credentials', 'author-bio' ); ?></h3>
					<ul class="abio-t1__list">
						<?php foreach ( $d['credentials'] as $c ) : ?>
							<li><?php echo esc_html( $c ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $d['follows'] ) ) : ?>
				<div class="abio-t1__block">
					<h3><?php esc_html_e( 'Follows', 'author-bio' ); ?></h3>
					<ul class="abio-chips">
						<?php foreach ( $d['follows'] as $h ) : ?>
							<li><a href="<?php echo esc_url( $h['url'] ); ?>" rel="nofollow ugc noopener" target="_blank"><?php echo esc_html( $h['handle'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $d['others'] ) ) : ?>
				<div class="abio-t1__block">
					<h3><?php esc_html_e( 'Other authors', 'author-bio' ); ?></h3>
					<ul class="abio-t1__others">
						<?php foreach ( $d['others'] as $o ) : ?>
							<li>
								<span class="abio-t1__others-dot"></span>
								<span class="abio-t1__others-text">
									<?php echo ABIO_View::optional_link( $o['url'], $o['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<span><?php echo esc_html( $o['role'] ); ?></span>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( $d['pitch']['title'] ) : ?>
				<div class="abio-t1__pitch">
					<h3><?php echo esc_html( $d['pitch']['title'] ); ?></h3>
					<div class="abio-t1__pitch-body"><?php echo wp_kses_post( $d['pitch']['body'] ); ?></div>
					<?php if ( $d['site']['contactUrl'] && $d['pitch']['cta'] ) : ?>
						<a class="abio-cta" href="<?php echo esc_url( $d['site']['contactUrl'] ); ?>"><?php echo esc_html( $d['pitch']['cta'] ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</aside>
	</div>
</div>
