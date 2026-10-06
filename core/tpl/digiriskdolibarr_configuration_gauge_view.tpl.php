<?php
/**
 * \file    core/tpl/digiriskdolibarr_configuration_gauge_view.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Template page for the configuration advancement gauge
 */

/**
 * The following vars must be defined:
 * Variables : $counter, $maxnumber
 * Optional  : $kCounter (gauge suffix), $morecssGauge, $move_title_gauge
 */

// Aucune page appelante ne fournit ces variables : sans valeur par defaut, PHP emet un warning
// pour chacune et, display_errors actif, son HTML est ecrit dans le <script> ci-dessous, qui
// devient invalide. La jauge n'est alors jamais dessinee
$morecssGauge     = $morecssGauge ?? '';
$move_title_gauge = $move_title_gauge ?? 0;

// Le suffixe rend uniques l'identifiant du canevas et les const du script : deux jauges sur une
// meme page redeclareraient les memes const et le script s'arreterait
if (!isset($kCounter)) {
    $GLOBALS['digiriskGaugeCounter'] = ($GLOBALS['digiriskGaugeCounter'] ?? 0) + 1;
    $kCounter                        = $GLOBALS['digiriskGaugeCounter'];
}
?>
<div class="chart-container <?php echo $morecssGauge ?>" style=" width:50px">
	<div class="wpeo-gridlayout grid-2">
		<canvas class="" id="advancementGauge<?php echo $kCounter?>" width="40" height="40" style="width:50px !important"></canvas>
		<?php if (empty($move_title_gauge)) : ?>
			<h3 class="">
				<?php echo price2Num(($counter / $maxnumber) * 100, 2) . '%' ?>
			</h3>
		<?php endif; ?>
	</div>

	<script>
		const ctx<?php echo $kCounter?> = document.getElementById('advancementGauge<?php echo $kCounter?>').getContext('2d');
		const advancementGauge<?php echo $kCounter?> = new Chart(ctx<?php echo $kCounter?>, {
			type: 'doughnut',
			options: {
				legend: {
					display: false
				},
				tooltips: {
					enabled: false
				},
			},
			data: {
				datasets: [{
					label: '# of Votes',
					data: [<?php echo price2Num((1 - $counter / $maxnumber) * 100, 2);?>, <?php echo price2Num(($counter / $maxnumber) * 100, 2);?>],
					backgroundColor: [
						'rgba(108, 108, 108, 0.4)',
						'rgba(13, 138, 255, 0.8)',
					],
					borderColor: [
						'rgba(108, 108, 108, 0.8)',
						'rgba(13, 138, 255, 1)',
					],
					borderWidth: 1,
					radius: 12
				}]
			},
			width: 100
		});
	</script>

	<?php if ($move_title_gauge) : ?>
		<h3 class="">
			<?php echo price2Num(($counter / $maxnumber) * 100, 2) . '%' ?>
		</h3>
	<?php endif; ?>
</div>
