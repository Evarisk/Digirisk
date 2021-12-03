<div class="chart-container" style=" width:50px">
	<div class="wpeo-gridlayout grid-2">
		<canvas class="" id="advancementGauge" width="40" height="40" style="width:50px !important"></canvas>
		<h3 class="">
			<?php echo bcdiv((($counter / $maxnumber) * 100), 1, 2) . '%' ?>
		</h3>
	</div>

	<script>
		const ctx = document.getElementById('advancementGauge').getContext('2d');
		const advancementGauge = new Chart(ctx, {
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
</div>
