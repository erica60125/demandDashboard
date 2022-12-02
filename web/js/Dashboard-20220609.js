let chart;
$(function () {
    drawChart();
});

function dataSeriesArray(real_data, forecast_data){
    let return_array = [], real_data_array = [],
        title_table_name_assoc = {
            'demand_predict': 'Vanilla LSTM',
            'simple_lstm_kmeans_demand_predict': 'Vanilla LSTM with K-means',
            'simple_lstm_kmeans_extend_demand_predict': 'Vanilla LSTM with K-means (Extend)',
            'stacked_demand_predict': 'Stacked LSTM',
            'stacked_kmeans_demand_predict': 'Stacked LSTM with K-means',
            'stacked_extend_demand_predict': 'Stacked LSTM with K-means (Extend)',
            'bidirectional_demand_predict': 'Bidirectional LSTM',
            'bidirectional_kmeans_demand_predict': 'Bidirectional LSTM with K-means',
            'bidirectional_extend_demand_predict': 'Bidirectional LSTM with K-means (Extend)',
            'vanilla_demand_predict': 'Vanilla LSTM (2)',
            'vanilla_kmeans_demand_predict': 'Vanilla LSTM with K-means (2)',
            'vanilla_extend_demand_predict': 'Vanilla LSTM with K-means (Extend) (2)',
        };
    // 實際值陣列 [B]
    $.each(real_data, function(key, value) {
        real_data_array.push([
            (new Date(value['datetime'])).getTime(),
            value['demand_quarter']
        ]);
    });
    return_array.push({
        name: 'demand',
        data: real_data_array
    });
    // 實際值陣列 [E]
    // 預測值陣列 [B]
    $.each(forecast_data, function(table_name, update_data) {
        let data = [];
        $.each(update_data, function(key, value) {
            let forecast_value = value['prediction_4'] === null ? null : value['prediction_4'] * 1;
            data.push([
                (new Date(value['created_at'])).getTime(),
                forecast_value
            ]);
        });
        return_array.push({
            name: title_table_name_assoc[table_name],
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
        let update_data = result['data'];

        chart.update({
            series: [
                {
                    name: 'demand',
                    data: (function() {
                        // // generate an array of random data
                        let our_data = [];
                        $.each(update_data['demand_info'], function(key, value) {
                            our_data.push([
                                (new Date(value['datetime'])).getTime(),
                                value['demand_quarter']
                            ]);
                        });
                        return our_data;
                    }())
                },
                {
                    name: 'Vanilla LSTM',
                    data: (function() {
                        // // generate an array of random data
                        let data = [];
                        $.each(update_data['demand_predict'], function(key, value) {
                            let forecast_value = value['prediction_4'] === null ? null : value['prediction_4'] * 1;
                            data.push([
                                (new Date(value['created_at'])).getTime(),
                                forecast_value
                            ]);
                        });
                        return data;
                    }())
                },
                {
                    name: 'Stacked LSTM',
                    data: (function() {
                        // // generate an array of random data
                        let data = [];
                        $.each(update_data['stacked_demand_predict'], function(key, value) {
                            let forecast_value = value['prediction_4'] === null ? null : value['prediction_4'] * 1;
                            data.push([
                                (new Date(value['created_at'])).getTime(),
                                forecast_value
                            ]);
                        });
                        return data;
                    }())
                },
                {
                    name: 'Bidirectional LSTM',
                    data: (function() {
                        // // generate an array of random data
                        let data = [];
                        $.each(update_data['bidirectional_demand_predict'], function(key, value) {
                            let forecast_value = value['prediction_4'] === null ? null : value['prediction_4'] * 1;
                            data.push([
                                (new Date(value['created_at'])).getTime(),
                                forecast_value
                            ]);
                        });
                        return data;
                    }())
                },
                {
                    name: 'Vanilla LSTM with K-means',
                    data: (function() {
                        // // generate an array of random data
                        let data = [];
                        $.each(update_data['simple_lstm_kmeans_demand_predict'], function(key, value) {
                            let forecast_value = value['prediction_4'] === null ? null : value['prediction_4'] * 1;
                            data.push([
                                (new Date(value['created_at'])).getTime(),
                                forecast_value
                            ]);
                        });
                        return data;
                    }())
                },
                {
                    name: 'Vanilla LSTM with K-means (Extend)',
                    data: (function() {
                        // // generate an array of random data
                        let data = [];
                        $.each(update_data['simple_lstm_kmeans_extend_demand_predict'], function(key, value) {
                            let forecast_value = value['prediction_4'] === null ? null : value['prediction_4'] * 1;
                            data.push([
                                (new Date(value['created_at'])).getTime(),
                                forecast_value
                            ]);
                        });
                        return data;
                    }())
                }
            ]
        })
    });
}

function drawChart() {
    // Create the chart
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
        series: [
            {
            name: 'demand',
            data: (function() {
                    // // generate an array of random data
                    let our_data = [];
                    $.each(demandInfo, function(key, value) {
                        our_data.push([
                            (new Date(value['datetime'])).getTime(),
                            value['demand_quarter']
                        ]);
                    });
                    return our_data;
                }())
            },
            {
                name: 'Vanilla LSTM',
                data: (function() {
                    // // generate an array of random data
                    let data = [];
                    $.each(allForecastDataList['demand_predict'], function(key, value) {
                        let forecast_value = value['prediction_4'] === null ? null : value['prediction_4'] * 1;
                        data.push([
                            (new Date(value['created_at'])).getTime(),
                            forecast_value
                        ]);
                    });
                    return data;
                }())
            },
            {
                name: 'Stacked LSTM',
                data: (function() {
                    // // generate an array of random data
                    let data = [];
                    $.each(allForecastDataList['stacked_demand_predict'], function(key, value) {
                        let forecast_value = value['prediction_4'] === null ? null : value['prediction_4'] * 1;
                        data.push([
                            (new Date(value['created_at'])).getTime(),
                            forecast_value
                        ]);
                    });
                    return data;
                }())
            },
            {
                name: 'Bidirectional LSTM',
                data: (function() {
                    // // generate an array of random data
                    let data = [];
                    $.each(allForecastDataList['bidirectional_demand_predict'], function(key, value) {
                        let forecast_value = value['prediction_4'] === null ? null : value['prediction_4'] * 1;
                        data.push([
                            (new Date(value['created_at'])).getTime(),
                            forecast_value
                        ]);
                    });
                    return data;
                }())
            },
            {
                name: 'Vanilla LSTM with K-means',
                data: (function() {
                    // // generate an array of random data
                    let data = [];
                    $.each(allForecastDataList['simple_lstm_kmeans_demand_predict'], function(key, value) {
                        let forecast_value = value['prediction_4'] === null ? null : value['prediction_4'] * 1;
                        data.push([
                            (new Date(value['created_at'])).getTime(),
                            forecast_value
                        ]);
                    });
                    return data;
                }())
            },
            {
                name: 'Vanilla LSTM with K-means (Extend)',
                data: (function() {
                    // // generate an array of random data
                    let data = [];
                    $.each(allForecastDataList['simple_lstm_kmeans_extend_demand_predict'], function(key, value) {
                        let forecast_value = value['prediction_4'] === null ? null : value['prediction_4'] * 1;
                        data.push([
                            (new Date(value['created_at'])).getTime(),
                            forecast_value
                        ]);
                    });
                    return data;
                }())
            }]
    });
}
