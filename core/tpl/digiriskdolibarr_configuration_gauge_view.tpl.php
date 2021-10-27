<div class="chart-container" style=" width:150px">
	<canvas class="" id="advancementGauge" width="40" height="40" style=" float:right; width:100px !important"></canvas>
	<script>
		const ctx = document.getElementById('advancementGauge').getContext('2d');
		const advancementGauge = new Chart(ctx, {
			type: 'doughnut',
			options: {
				legend: {
					display: false
				},
			},
			data: {
				labels: ['<?php echo $langs->transnoentities('Uncompleted'); ?>',' <?php echo $langs->transnoentities('Completed') ?>'],
				datasets: [{
					label: '# of Votes',
					data: [<?php echo bcdiv(((1 - $counter / $maxnumber) * 100), 1, 2) ;?>, <?php echo bcdiv((($counter / $maxnumber) * 100), 1, 2) ;?>],
					backgroundColor: [
						'rgba(255, 99, 132, 0.2)',
						'rgba(54, 162, 235, 0.2)',
					],
					borderColor: [
						'rgba(255, 99, 132, 1)',
						'rgba(54, 162, 235, 1)',
					],
					borderWidth: 1,
					radius: 12
				}]
			},
			width: 100
		});
	</script>
	<h3 class="">
		<?php echo bcdiv((($counter / $maxnumber) * 100), 1, 2) . '%' ?>
	</h3>
</div>
