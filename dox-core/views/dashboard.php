<?php
/**
 * Portada del menú Dox Plugins: lo que hay instalado y lo que no.
 *
 * @var Dox_Core $this
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$installed = $this->get_plugins();
$catalog   = $this->catalog();
$missing   = array_diff_key( $catalog, $installed );
?>
<div class="wrap dox-core-wrap">

	<h1 class="dox-core-title"><?php esc_html_e( 'Dox Plugins', 'dox-core' ); ?></h1>
	<p class="dox-core-lead"><?php esc_html_e( 'Every Dox Studio plugin on this site, in one place.', 'dox-core' ); ?></p>

	<div class="dox-core-grid">
		<?php foreach ( $installed as $slug => $plugin ) :
			$info    = isset( $catalog[ $slug ] ) ? $catalog[ $slug ] : [];
			$summary = $plugin['summary'] ?: ( isset( $info['summary'] ) ? $info['summary'] : '' );
			$link    = ! empty( $plugin['page'] ) ? admin_url( 'admin.php?page=' . $plugin['page']['menu_slug'] ) : '';
			?>
			<div class="dox-core-card">
				<div class="dox-core-card-head">
					<h2><?php echo esc_html( $plugin['name'] ); ?></h2>
					<?php if ( $plugin['version'] ) : ?>
						<span class="dox-core-version"><?php echo esc_html( $plugin['version'] ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( $summary ) : ?>
					<p><?php echo esc_html( $summary ); ?></p>
				<?php endif; ?>
				<?php if ( $link ) : ?>
					<a class="button button-primary" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Settings', 'dox-core' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( $missing ) : ?>
		<h2 class="dox-core-subtitle"><?php esc_html_e( 'More from Dox Studio', 'dox-core' ); ?></h2>
		<div class="dox-core-grid">
			<?php foreach ( $missing as $slug => $info ) : ?>
				<div class="dox-core-card dox-core-card-muted">
					<div class="dox-core-card-head">
						<h2><?php echo esc_html( $info['name'] ); ?></h2>
					</div>
					<p><?php echo esc_html( $info['summary'] ); ?></p>
					<a class="button" href="<?php echo esc_url( $info['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Learn more', 'dox-core' ); ?></a>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<style>
.dox-core-wrap { --dox-accent: #ff8d27; --dox-ink: #141313; max-width: 1120px; }
.dox-core-title { font-size: 23px; font-weight: 600; color: var(--dox-ink); margin: 18px 0 2px; }
.dox-core-lead { color: #646970; margin: 0 0 24px; font-size: 14px; }
.dox-core-subtitle { font-size: 15px; font-weight: 600; color: var(--dox-ink); margin: 34px 0 14px; text-transform: uppercase; letter-spacing: .04em; }
.dox-core-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 16px; }
.dox-core-card { background: #fff; border: 1px solid #e2e4e7; border-radius: 10px; padding: 20px; display: flex; flex-direction: column; gap: 10px; }
.dox-core-card-head { display: flex; align-items: center; gap: 10px; }
.dox-core-card h2 { font-size: 15px; font-weight: 600; margin: 0; color: var(--dox-ink); }
.dox-core-card p { margin: 0; color: #646970; font-size: 13px; line-height: 1.6; flex: 1; }
.dox-core-card .button { align-self: flex-start; }
.dox-core-card .button-primary { background: var(--dox-accent); border-color: var(--dox-accent); color: #fff; }
.dox-core-card .button-primary:hover { background: #ea780f; border-color: #ea780f; color: #fff; }
.dox-core-version { font-size: 11px; font-weight: 600; color: var(--dox-accent); background: rgba(255,141,39,.12); border-radius: 20px; padding: 2px 9px; }
.dox-core-card-muted { background: #fbfbfc; border-style: dashed; }
</style>
