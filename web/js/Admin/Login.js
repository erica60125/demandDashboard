$(function () {
    $('#login-panel').submit(function (e){
        ClickLogin(e);
    });
});

function ClickLogin(e) {
    e.preventDefault();
    var form_data = $('#login-panel').serializeArray();
    $.ajax({
        type: 'POST',
        url: './?a=Admin/Login&b=Process',
        data: form_data,
        dataType: 'json'
    }).then(function(result) {
        if (!result['result']) {
            alert(result['message']);
            window.location.replace('')
            return false;
        }
        window.location.replace(result['data']['location']);
    });
}