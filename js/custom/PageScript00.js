$("form[name='log_form']").on("submit", function(e){
    e.preventDefault();

    var formvalues = $(this).serialize();
    formvalues += "&action=login";
    $.ajax({
        url: './process_main.php?'+formvalues,
        type: 'POST',
        success: function (response) {
            var data=JSON.parse(response);
            var state = data['status'];
            var err_step = data['e_step'];
            if(!state){
                var err_msg = "<small class='form-text text-muted'>"+data['message']+"</small>";
                $("#lognHelp").html(err_msg);
                $("#lognHelp").show();
            }
            else{
                clear_errors();
                //location.href='./overview.php';
                location.href='./index.php';
            }
        }
    });
});

$("form[name='reg_form']").on("submit", function(e){
    e.preventDefault();

    var formvalues = $(this).serialize();
    formvalues += "&action=signup";
    $.ajax({
        url: './process_main.php?'+formvalues,
        type: 'POST',
        success: function (response) {
            var data=JSON.parse(response);
            var state = data['status'];
            var err_step = data['e_step'];
            if(!state){
                var err_msg = "<small class='form-text text-muted'>"+data['message']+"</small>";
                switch(err_step){
                    case 0:
                        $('#fnameHelp').html(err_msg);
                        $('#fnameHelp').show();
                        break;
                    case 1:
                        $('#lnameHelp').html(err_msg);
                        $('#lnameHelp').show();
                        break;
                    case 2:
                        $('#emailHelp').html(err_msg);
                        $('#emailHelp').show();
                        break;
                    case 3:
                        $('#passHelp').html(err_msg);
                        $('#passHelp').show();
                        break;
                }
            }
            else{
                clear_errors();
                location.href='./transact.html';
                //$("#login_btn").focus();
            }
        }
    });
});

function clear_errors() {
    $('#fnameHelp').html('');
    $('#fnameHelp').hide();

    $('#lnameHelp').html('');
    $('#lnameHelp').hide();

    $('#emailHelp').html('');
    $('#emailHelp').hide();

    $('#passHelp').html('');
    $('#passHelp').hide();
    
    $('#lognHelp').html('');
    $('#lognHelp').hide();
}