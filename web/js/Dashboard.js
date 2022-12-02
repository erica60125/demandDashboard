let chart, weather_clock, raining_rate_clock, time_clock, demand_clock;
const monthNames = ["Jan.", "Feb.", "Mar.", "Apr.", "May.", "Jun.",
    "Jul.", "Aug.", "Sep.", "Oct.", "Nov.", "Dec."
],
weekdayName = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

$(function () {
    getDemandInfo();
    drawChart();
    getWeather();
    getRainingRate();
    updateTime();
});

function getDemandInfo() {
    clearTimeout(demand_clock);
    $.ajax({
        type: 'POST',
        url: './?a=Dashboard&b=getCurrentDemand',
        data: {
            limit: 1
        },
        dataType: 'json'
    }).then(function(result) {
        let result_data = result['data'];
        $('#current-demand').text(result_data['current_demand']);
        $('#max-demand').text(result_data['max_demand']);
    });
    demand_clock = setTimeout(getDemandInfo, 10000);
}

function updateTime() {
    clearTimeout(time_clock);
    let now = new Date();
    $('#month').text(monthNames[now.getMonth()]);
    $('#day').text(now.getDate());
    $('#weekday').text(weekdayName[now.getDay()]);
    $('#hour').text(('00' + now.getHours()).slice(-2));
    $('#minutes').text(('00' +now.getMinutes()).slice(-2));

    time_clock = setTimeout(updateTime, 1000);
}

function getRainingRate() {
    clearTimeout(raining_rate_clock);
    $.ajax({
        type: 'POST',
        url: './?a=Dashboard&b=getRainingRate',
        data: {
            limit: 1
        },
        dataType: 'json'
    }).then(function(result) {
        $('#raining-rate').text(result['data']);
    });
    raining_rate_clock = setTimeout(getRainingRate, 600000);
}

function getWeather() {
    clearTimeout(weather_clock);
    $.ajax({
        type: 'POST',
        url: './?a=Dashboard&b=getWeatherData',
        data: {
            limit: 300
        },
        dataType: 'json'
    }).then(function(result) {
        // 天氣 icon [B]
        let now_weather_icon,
            result_data = result['data'],
            weather_brief = result_data['Weather'],
            org_weather_icon = $('#weather-icon').attr('data-weather-type');
        if(weather_brief.indexOf('晴') !== -1){
            now_weather_icon = 'sunny';
        } else if(weather_brief.indexOf('雨') !== -1)  {
            now_weather_icon = 'wi-rain';
        } else {
            now_weather_icon = 'wi-cloudy';
        }
        $('#weather-icon').removeClass(org_weather_icon);
        $('#weather-icon').attr('data-weather-type', now_weather_icon);
        $('#weather-icon').addClass(now_weather_icon);
        // 天氣 icon [E]
        // 其他天氣資訊 [B]
        $('#temperature').text(Math.round(result_data['TEMP']));
        $('#t-max').text(Math.round(result_data['D_TX']));
        $('#t-min').text(Math.round(result_data['D_TN']));
        // 其他天氣資訊 [E]
    });

    weather_clock = setTimeout(getWeather, 300000);
}

function dataSeriesArray(real_data, forecast_data){
    let return_array = [], real_data_array = [],
        title_table_name_assoc = {
            'demand_predict': 'Predicted value',
            'simple_lstm_kmeans_demand_predict': 'Vanilla LSTM with K-means',
            'simple_lstm_kmeans_extend_demand_predict': 'Vanilla LSTM with K-means (Extend)',
            'stacked_demand_predict': 'Stacked LSTM',
            'stacked_kmeans_demand_predict': 'Stacked LSTM with K-means',
            'stacked_extend_demand_predict': 'Stacked LSTM with K-means (Extend)',
            'stacked_period_type_predict': 'Stacked LSTM (Period Type)',
            'bidirectional_demand_predict': 'Bidirectional LSTM',
            'bidirectional_kmeans_demand_predict': 'Bidirectional LSTM with K-means',
            'bidirectional_extend_demand_predict': 'Bidirectional LSTM with K-means (Extend)',
            'bidirectional_period_type_predict': 'Bidirectional LSTM (Period Type)',
            'vanilla_demand_predict': 'Vanilla LSTM (2)',
            'vanilla_kmeans_demand_predict': 'Vanilla LSTM with K-means (2)',
            'vanilla_extend_demand_predict': 'Vanilla LSTM with K-means (Extend) (2)',
            'vanilla_period_type_predict': 'Vanilla LSTM (2) (Period Type)',
        },
		line_color_array = {
            'demand_predict': '#cc9900',
            'simple_lstm_kmeans_demand_predict': '#f7a35c',
            'simple_lstm_kmeans_extend_demand_predict': '#e4d354',
            'stacked_demand_predict': '#90ed7d',
            'stacked_kmeans_demand_predict': '#8085e9',
            'stacked_extend_demand_predict': '#2b908f',
            'stacked_period_type_predict': '#ff99ff',
            'bidirectional_demand_predict': '#9900cc',
            'bidirectional_kmeans_demand_predict': '#800000',
            'bidirectional_extend_demand_predict': '#3399ff',
            'bidirectional_period_type_predict': '#99ffe6',
            'vanilla_demand_predict': '#f8a0a0',
            'vanilla_kmeans_demand_predict': '#6600cc',
            'vanilla_extend_demand_predict': '#006600',
            'vanilla_period_type_predict': '#ffcc99',
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
        name: 'Actual value',
        data: real_data_array,
		color: '#000000'
    });
    // 實際值陣列 [E]
    // 預測值陣列 [B]
    $.each(forecast_data, function(table_name, update_data) {
        let data = [];
        // 第四次預測 [B]
        $.each(update_data, function(key, value) {
            let forecast_value
			if (value['prediction_4']) {
				forecast_value = value['prediction_4'] * 1;
			} else if (value['prediction_3']) {
				forecast_value = value['prediction_3'] * 1;
			} else if (value['prediction_2']) {
				forecast_value = value['prediction_2'] * 1;
			} else if (value['prediction_1']) {
				forecast_value = value['prediction_1'] * 1;
			} else {
				forecast_value = null;
			}
            data.push([
                (new Date(value['created_at'])).getTime(),
                forecast_value
            ]);
        });
        // 第四次預測 [B]
        if (update_data[update_data.length - 4]['prediction_4'] > 450 || real_data[real_data.length -1]['demand_quarter'] > 450) {
            $('#alert-border').removeClass('border-success');
			$('#alert-div').removeClass('text-green');
            $('#alert-icon').removeClass('fa-check-circle');
			$('#alert-border:not(.border-danger)').addClass('border-danger');
			$('#alert-div:not(.text-danger)').addClass('text-danger');
			$('#alert-icon:not(.fa-exclamation-triangle)').addClass('fa-exclamation-triangle');
			$('#alert-note').text('Warning');
        } else {
			$('#alert-border').removeClass('border-danger');
			$('#alert-div').removeClass('text-danger');
			$('#alert-icon').removeClass('fa-exclamation-triangle');
			$('#alert-border:not(.border-success)').addClass('border-success');
			$('#alert-div:not(.text-green)').addClass('text-green');
            $('#alert-icon:not(.fa-check-circle)').addClass('fa-check-circle');
			$('#alert-note').text('Warning');
		}
        return_array.push({
            name: title_table_name_assoc[table_name],
			color: line_color_array[table_name],
            data: data
        });
    });
    // 預測值陣列 [B]
    return return_array;
}

function updateData() {

    $.ajax({
        type: 'POST',
        url: './?a=Dashboard&b=updateData',
        data: {
            limit: 300
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
            events: {
                load: function () {
                    setInterval(updateData, 10000);
                }
            }
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
             borderWidth: 0
         },
         yAxis: {
             plotLines: [{
                 value: 450,
                 width: 1,
                 color: 'red',
                 dashStyle: 'dash',
                 label: {
                     style: {
                         fontSize: '0.7rem',
                     },
                     text: 'Upper Limit',
                     align: 'right',
                     y: 12,
                     x: 0
                 }
             }]
         },
        series: series_data_array
    });
}
