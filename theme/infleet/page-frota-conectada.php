<?php
/**
 * Landing: Frota Conectada.
 *
 * @package infleet
 */

get_header();
?>

<style>
	.lp-frota-hero {
		position: relative;
		height: 460px;
		overflow: hidden;
	}
	.lp-frota-hero img {
		position: absolute;
		inset: 0;
		display: block;
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	.lp-frota-hero__texto {
		position: relative;
		z-index: 1;
		max-width: 1120px;
		margin: 0 auto;
		padding: 0 24px;
		height: 100%;
		display: flex;
		flex-direction: column;
		justify-content: center;
		color: #fff;
	}
	.lp-frota-hero__texto h1 {
		font-size: 44px;
		line-height: 1.12;
		max-width: 18ch;
		margin: 0 0 14px;
		text-shadow: 0 2px 18px rgba(6, 24, 47, 0.45);
	}
	.lp-frota-hero__texto p {
		font-size: 19px;
		max-width: 46ch;
		margin: 0;
		color: rgba(255, 255, 255, 0.92) !important;
		text-shadow: 0 1px 12px rgba(6, 24, 47, 0.45);
	}
</style>

<main class="landing">

	<section class="lp-frota-hero">
		<img src="<?php echo get_template_directory_uri(); ?>/assets/img/hero-frota-conectada.png" alt="">
		<div class="lp-frota-hero__texto">
			<h1>Sua frota conectada de ponta a ponta</h1>
			<p>Telemetria, checklist e manutenção num painel só, com alerta no momento em que o desvio acontece.</p>
		</div>
	</section>

	<div class="landing__copy entry-content">
		<?php
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>
	</div>

</main>

<?php
get_footer();
