<!DOCTYPE html>
<html lang="en">
	<head>

		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Document</title>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
		<script src="frameworks/canvasjs.stock.min.js"></script>
		
		<style>
			a.canvasjs-chart-credit{
				display: none !important;
			}
			.dropdown-menu{overflow-y:auto; max-height: 30rem;}
		</style>

	</head>

	<!-- API -->

	<?php
		$symbol = "A"; //prendere il valore dalla query string
		$key = "0244";
		$ch = curl_init();
		$url = "https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=$symbol&interval=5min&apikey=$key";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = json_decode(curl_exec($ch), true)["Time Series (Daily)"];
		curl_close($ch);

		$chart_data = [];
		$navigator_data = [];

		foreach($result as $date => $value){
			$chart_data[] = ["x" => $date, "y" => [(double)$value["1. open"], (double)$value["2. high"], (double)$value["3. low"], (double)$value["4. close"]]];
			$navigator_data[] = ["x" => $date, "y" => (double)$value["4. close"]];
		}

		$chart_data = json_encode($chart_data);
		$navigator_data = json_encode($navigator_data);

		$data = file_get_contents("https://www.alphavantage.co/query?function=LISTING_STATUS&date=2022-01-05&state=active&apikey=$key");
		$rows = explode("\n", $data);

		$stocks_list = [];

		for($i = 1; $i < count($rows) - 1; $i++){
			$fields = explode(",", $rows[$i]);
			$stocks_list[] = ["symbol" => $fields[0], "name" => $fields[1]];
		}

		$stocks_list_json = json_encode($stocks_list);
	?>

	<!-- Chart -->

	<script>
		window.onload = function(){
			stocks_list_json = <?php echo "$stocks_list_json";?>;
			var chart_data = formatDate(<?php echo "$chart_data";?>);
			var navigator_data = formatDate(<?php echo "$navigator_data"?>);

			var text = "<?php echo "$symbol Price (in USD)";?>";

			var stockChart = new CanvasJS.StockChart("stock", {
				theme: "light2",

				exportEnabled: true,

				title: {
					text: text
				},

				charts: [{
					toolTip: {
						shared: true
					},

					axisX: {
						crosshair: {
							enabled: true,
							snapToDataPoint: true
						}
					},

					axisY: {
						prefix: "$"
					},

					data: [{
						name: text,
						type: "candlestick",
						risingColor: "green",
						fallingColor: "red",
						yValueFormatString: "$#,###.##",
						dataPoints : chart_data
					}]
				}],

				navigator: {
					data: [{
						color: "grey",
						dataPoints: navigator_data
					}],

					slider: {
						minimum: new Date(chart_data[chart_data.length-1].x),
						maximum: new Date(chart_data[0].x)
					}
				}
			});

			stockChart.render();
		}

		function formatDate(struct){
			for(let element of struct){
				element.x = new Date(element.x);
			}

			return struct;
		}
	</script>

	<!-- Search filtering -->
	<script>
		$(document).ready(function(){
			$("#searchBox").on("keyup", function() {
				var value = $(this).val().toLowerCase();
				$(".dropdown-menu li").filter(function() {
				$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
				});
			});
		});
	</script>

	<body>
		
		<div class="container">
			<h2>Choose your stock</h2>
			<div class="dropdown">
				<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
					Stock: <span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
					<input class="form-control" id="searchBox" type="text" placeholder="Search..">
					<?php 
						foreach($stocks_list as $stock){?>
							<li>
								<a href="#
									<?php echo "$stock[symbol]" ?>
								">
									<?php
										echo "$stock[name] ($stock[symbol])";
									?>
								</a>
							</li>
						<?php
						}
					?>
				</ul>
			</div>
		</div>

		<br/>

		<div id="stock"></div>
	</body>

</html>
