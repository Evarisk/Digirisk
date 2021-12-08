<div class="chart-container <?php echo $morecssGauge ?>" style=" width:50px">
	<div class="wpeo-gridlayout grid-2">
		<canvas class="" id="advancementGauge<?php echo $kCounter?>" width="40" height="40" style="width:50px !important"></canvas>
		<?php if (empty($move_title_gauge)) : ?>
			<h3 class="">
				<?php echo bcdiv((($counter / $maxnumber) * 100), 1, 2) . '%' ?>
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
					data: [<?php echo bcdiv(((1 - $counter / $maxnumber) * 100), 1, 2) ;?>, <?php echo bcdiv((($counter / $maxnumber) * 100), 1, 2) ;?>],
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
			<?php echo bcdiv((($counter / $maxnumber) * 100), 1, 2) . '%' ?>
		</h3>
	<?php endif; ?>
</div>
