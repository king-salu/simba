$("#transc_dep").on('change',function(){
    //alert(this.checked);
    if(this.checked){
        $("[name='transfer_div']").hide();
    }
    else{
        $("[name='transfer_div']").show();
    }
});

$("#transc_transf").on('change',function(){
    //alert(this.checked);
    if(this.checked){
        $("[name='transfer_div']").show();
    }
});

$("form[name='transc_form']").on("submit", function(e){
    e.preventDefault();

    var formvalues = $(this).serialize();
    $.ajax({
        url: './process_main.php?'+formvalues,
        type: 'POST',
        success: function (response) {
            var data=JSON.parse(response);
            var state = data['status'];
            if(state){
                //reload to overview
                //location.href='./overview.php';
                location.href='./index.php';
            }
            else{
                var err_msg = "<small class='form-text text-muted'>"+data['message']+"</small>";
                $("#feedbk").html(err_msg);
            }
        }
    });
});

function Page_setup(){

    $pend_data = getCookie("SIMBA_PENDU");
    //alert($pend_data);
    if(($pend_data!=null) &&($pend_data!="")){
        $('#transc_dep').prop('checked',true);
        $('#transc_dep').change();
        $('#transc_transf').attr('disabled',true);
        $("[name='cur_value']").val(1000);
        $("[name='cur_value']").attr('readonly',true);
        $("[name='src_cur'] option[value='NGN']").remove();
        $("[name='src_cur'] option[value='EUR']").remove();
    }
    else{
        var usr_logged = getCookie("SIMBA_logU");
        //alert(usr_logged)
        if((usr_logged==null) || (usr_logged=="")){
            location.href = './login.html';
        }
    }
}

window.onload = Page_setup();