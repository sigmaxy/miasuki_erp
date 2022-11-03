(function ($, Drupal, drupalSettings,Chart) {
	if($('#order_report_chart').length){
		console.log(drupalSettings.chart_config);
		var report_chart = new Chart($('#order_report_chart'), {
		    type: 'bar',
		    showTooltips: false,
		    data: {
		        labels: drupalSettings.chart_config.labels,
		        datasets: drupalSettings.chart_config.datasets,
		    },
		    options: {
		        scales: {
		            yAxes: [{
		                ticks: {
		                    beginAtZero: true
		                }
		            }]
		        },
		        animation: {
			        duration: 1,
			        onComplete: function () {
			            var chartInstance = this.chart,
			                ctx = chartInstance.ctx;
			            ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, Chart.defaults.global.defaultFontStyle, Chart.defaults.global.defaultFontFamily);
			            ctx.textAlign = 'center';
			            ctx.textBaseline = 'bottom';

			            this.data.datasets.forEach(function (dataset, i) {
			                var meta = chartInstance.controller.getDatasetMeta(i);
			                meta.data.forEach(function (bar, index) {
			                    var data = dataset.data[index];
			                    if (data>0) {
			                    	ctx.fillText(data, bar._model.x, bar._model.y - 5);
			                    }                        
			                    
			                });
			            });
			        }
			    }
		    },
		});	
	}
	if($('#location_report_chart').length){
		var report_chart = new Chart($('#location_report_chart'), {
		    type: 'pie',
		    data: {
		    	labels: drupalSettings.chart_config.labels,
		        datasets: [{
		            data: drupalSettings.chart_config.data,
		            backgroundColor: drupalSettings.chart_config.color,
		        }]
		    },
		    options: {}
		});	
	}
	if($('#category_report_chart').length){
		var report_chart = new Chart($('#category_report_chart'), {
		    type: 'pie',
		    data: {
		    	labels: drupalSettings.chart_config.labels,
		        datasets: [{
		            data: drupalSettings.chart_config.data,
		            backgroundColor: drupalSettings.chart_config.color,
		        }]
		    },
		    options: {}
		});	
	}
})(jQuery, Drupal, drupalSettings,Chart);