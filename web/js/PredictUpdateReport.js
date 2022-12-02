let chart;
$(function () {
    drawChart();
    $('#search').click(updateData);
    $('#begin-date-group').datetimepicker({
        format: 'YYYY-MM-DD',
        useCurrent: true,
        allowInputToggle: true
    });
    $('#end-date-group').datetimepicker({
        format: 'YYYY-MM-DD',
        useCurrent: true,
        allowInputToggle: true
    });
});

function dataSeriesArray(real_data, forecast_data){
    let return_array = [], real_data_array = [],
        title_table_name_assoc = {
            'prediction_1': '1st Prediction',
            'prediction_2': '2nd Prediction',
            'prediction_3': '3rd Prediction',
            'prediction_4': '4th Prediction'
        },
		line_color_array = {
			'prediction_1': '#99ccff',
            'prediction_2': '#ffff00',
            'prediction_3': '#00cc00',
            'prediction_4': '#0033cc'
        };
        /*dash_type_array = {
            'stacked_demand_predict': 'longdash',
            'bidirectional_demand_predict': 'shortdot',
            'vanilla_demand_predict': 'DashDot'
        };*/
		dash_type_array = {
			'prediction_1': 'Solid',
            'prediction_2': 'Solid',
            'prediction_3': 'Solid',
            'prediction_4': 'Solid'
        };
    // 實際值陣列 [B]
	columnName = dashboard_type == 'london' ? 'energy' : 'demand_quarter';
    $.each(real_data, function(key, value) {
        real_data_array.push([
            (new Date(value['datetime'])).getTime(),
            value[columnName]
        ]);
    });
    return_array.push({
        name: 'demand',
        data: real_data_array,
		color: '#000000',
        dashStyle: 'Solid'
    });
    // 實際值陣列 [E]
	// 預測值陣列 [B]
	for(i=1;i<5;i++){
		let data = [];
		$.each(forecast_data['vanilla_demand_predict_self_attention_1min'], function(key, value) {
			let forecast_value = value['prediction_'+i] === null ? null : value['prediction_'+i] * 1;
			data.push([
				(new Date(value['created_at'])).getTime(),
				forecast_value
			]);
		});
		return_array.push({
			name: title_table_name_assoc['prediction_'+i],
			color: line_color_array['prediction_'+i],
			dashStyle: dash_type_array['prediction_'+i],
			data: data
		});
	}
	
    // 預測值陣列 [B]
    return return_array;
}

function updateData() {
    let begin_date = $('#begin-date').val(),
        end_date = $('#end-date').val();
    $.ajax({
        type: 'POST',
        url: './?a=PredictUpdateReport&b=updateData',
        data: {
            'begin-date': begin_date,
            'end-date': end_date
        },
        dataType: 'json'
    }).then(function(result) {
        let update_data = result['data'],
            series_data_array = dataSeriesArray(update_data['demand_info'], update_data['forecast_data']);
        chart.update({
            series: series_data_array
        })
    });
}

function drawChart() {
    // Create the chart
    let series_data_array = dataSeriesArray(demandInfo, allForecastDataList);
     chart = Highcharts.stockChart('container', {
		chart: {
			height: 500,
		},
        accessibility: {
            enabled: false
        },

        time: {
            useUTC: false
        },

        rangeSelector: {
            buttons: [ {
                type: 'all',
                text: 'All'
            }],
            inputEnabled: false,
            selected: 0
        },
        title: {
            text: 'Demand Forecast'
        },

        exporting: {
            enabled: false
        },
         legend: {
             enabled: true,
             align: 'left',
             verticalAlign: 'top',
             borderWidth: 1
         },
         plotOptions: {
             series: {
                 lineWidth: 2
             }
         },
        series: series_data_array
    });
}
