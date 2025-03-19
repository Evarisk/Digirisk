<div class="chart-container <?php echo ($morecssGauge ?? '') ?>" style=" width:50px">
	<div class="wpeo-gridlayout grid-2">
		<canvas class="" id="advancementGauge<?php echo ($kCounter ?? '')?>" width="40" height="40" style="width:50px !important"></canvas>
		<?php if (empty($move_title_gauge)) : ?>
			<h3 class="">
				<?php echo price2Num(($counter / $maxnumber) * 100, 2) . '%' ?>
			</h3>
		<?php endif; ?>
	</div>

	<script>
		const ctx<?php echo ($kCounter ?? '')?> = document.getElementById('advancementGauge<?php echo ($kCounter ?? '')?>').getContext('2d');
		const advancementGauge<?php echo ($kCounter ?? '')?> = new Chart(ctx<?php echo ($kCounter ?? '')?>, {
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

<?php if (!empty($move_title_gauge)) : ?>
    <h3 class="">
        <?php
        // Ensure $counter and $maxnumber are defined to avoid warnings (default to 0 and 1 respectively)
        $counter_val = $counter ?? 0;
        $maxnumber_val = ($maxnumber ?? 0) > 0 ? $maxnumber : 1;
        // Calculate the percentage and output it formatted to 2 decimals
        echo price2Num(($counter_val / $maxnumber_val) * 100, 2) . '%';
        ?>
    </h3>
<?php endif; ?>
</div>
