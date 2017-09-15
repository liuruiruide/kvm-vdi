<?php
/*
 *
 *
 */
include ('functions/config.php');
require_once ('functions/functions.php');
if (! check_session ()) {
	header ( "Location: $serviceurl/?error=1" );
	exit ();
}
slash_vars ();
set_lang ();
$h_reply=get_SQL_array("SELECT * FROM hypervisors WHERE maintenance=0 ORDER BY name,ip");

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<script>

function get_hypervisor_pci(ip){
    $.ajax({
        type : 'POST',
        url : 'list_graphics.php',
        data: {
            hypervisor: ip,
            type:'lspci',
        },
        success:function (data) {
            var src = "#lspci_info";
            $(src).children().remove();
            if (data){
                var obj = jQuery.parseJSON(data);
                $.each(obj, function(key,value) {
                    $(src).append('<option value="' + key + '">' + value + "</option>'");
                });
            }
        }
    })
}
function get_pci_info(ip,id,other){
    $.ajax({
        type : 'POST',
        url : 'list_graphics.php',
        data: {
            hypervisor: ip,
            id:id,
            type:'lspci',
        },
        success:function (data) {
            if (data){
                var obj = jQuery.parseJSON(data);
                $('#video_bus').val(obj["video"]["bus"]);
                $('#video_slot').val(obj["video"]["slot"]);
                $('#video_function').val(obj["video"]["function"]);
                $('#video_name').val(obj["video"]["name"]);
                $('#video_vendor').val(obj["video"]["vendor"]);
                $('#video_device').val(obj["video"]["device"]);
                
                $('#audio_bus').val(obj["audio"]["bus"]);
                $('#audio_slot').val(obj["audio"]["slot"]);
                $('#audio_function').val(obj["audio"]["function"]);
                $('#audio_name').val(obj["audio"]["name"]);
                $('#audio_vendor').val(obj["audio"]["vendor"]);
                $('#audio_device').val(obj["audio"]["device"]);
            }
        },
        error:function (error) {
            alert(error);
        }
    });
}
function load_graphics_list(hypervisor){
    $.getJSON("list_graphics.php?side=from&hypervisor="+hypervisor+"&type=lspci", {},  function(json){
            $('#multiselect').empty();
            $.each(json, function(key, value){
                     $('#multiselect').append($('<option>').text(value).attr('value', key));
            });
    });
    $.getJSON("list_graphics.php?side=to&hypervisor="+hypervisor+"&type=lspci", {},  function(json){
            $('#multiselect_to').empty();
            $.each(json, function(key, value){
                    $('#multiselect_to').append($('<option>').text(value).attr('value', key));
            });
    });
    
}

function save_graphics(){
	var multivalues="";
	$("#multiselect_to option").each(function(){
	    multivalues += $(this).val() + ",";
	});
    $.ajax({
        type : 'POST',
        url : 'list_graphics.php',
        data: {
        	type: 'save',
        	hypervisor: $('#hypervisor_list').val(),
			devices: multivalues
        },
        success:function (data) {
            if (data == 'SUCCESS'){
                $("#info_box").removeClass('alert-danger');
                $("#info_box").removeClass('hide');
                $("#info_box").addClass('alert-success');
                $("#info_box").html("<i class=\"fa fa-thumbs-o-up fa-fw\"></i>Success");
                
            }
        }
    });
}

$('#hypervisor_list').on('change', function(){
	hypervisor=$('#hypervisor_list').val();

    load_graphics_list(hypervisor);
});



$(document).ready(function(){
	//debugger;
    $('#multiselect').multiselect();
    hypervisor=$('#hypervisor_list').val();

    load_graphics_list(hypervisor);
    
    $("#submit").click(function(){
    	save_graphics();
    });

	$('#hypervisor_list').on('change', function(){
		//get_hypervisor_pci($('#hypervisor_list').val());
	});

	$('#lspci_info').on('change', function(){
		$('#deviceId').val($('#lspci_info').val().substring(0,8));
		$('#deviceName').val($('#lspci_info').val().split(':')[2]);

		//get_pci_info($('#hypervisor_list').val(),$('#lspci_info').val(),'');
	});

	$('#selectClass').on('change', function(){});
	$('#lspci_info_1').on('change', function(){
		$('#vendorid').val($('#lspci_info_1').val().substring(0,4));
		$('#modleid').val($('#lspci_info_1').val().split(':')[1]);
	});
});


</script>
</head>
<body>
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal"
				aria-hidden="true">&times;</button>
			<h4 class="modal-title"><?php echo _("Add graphics to hypervisors"); ?></h4>
		</div>
	</div>
	<div class="modal-body">
		<div class="form-group">
			<div class="row">
				<div class="col-md-4">
					<label for="hypervisor_list" class="text-muted"><?php echo _("Pool");?></label>
					<select class="input-small form-control dl-horizontal" id="hypervisor_list" name="hypervisor_list" >
    	    		<?php
					$x = 0;
					while ($x<sizeof($h_reply)) {
						echo '<option value="' . $h_reply[$x] ['id'] . '">' . $h_reply[$x] ['ip'] . '</option>';
						++ $x;
					}
					?>
	    			</select>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-12">
					<label for="formGroupExampleInput"><?php echo _("Available clients");?></label>
					<select name="multiselect" id="multiselect" class="form-control" size="20" multiple="multiple"></select>
				</div>
			</div>

			<div class="row">
			<div class="col-xs-4">
			</div>
			<div class="col-xs-4">
				<div class="btn-group" role="group">
						<button type="button" id="multiselect_rightAll" class="btn btn-default">
							<i class="glyphicon glyphicon-forward"></i>
						</button>
						<button type="button" id="multiselect_rightSelected" class="btn btn-default">
							<i class="glyphicon glyphicon-chevron-right"></i>
						</button>
						<button type="button" id="multiselect_leftSelected" class="btn btn-default">
							<i class="glyphicon glyphicon-chevron-left"></i>
						</button>
						<button type="button" id="multiselect_leftAll" class="btn btn-default">
							<i class="glyphicon glyphicon-backward"></i>
						</button>
				</div>
			</div>
			<div class="col-xs-4">
			</div>
			</div>

			<div class="row">
				<div class="col-xs-12">
					<label for="formGroupExampleInput"><?php echo _("Clients in pool");?></label>
					<select id="multiselect_to" name="multiselect_to" class="form-control dl-horizontal" size="20" multiple="multiple"></select>
				</div>
			</div>

			<div class="row">
				<div class="col-md-12"></div>
			</div>
			<div class="modal-footer">
	        	<div class="row">
	            <div class="col-md-7">
	                <div class="alert hide text-left" id="info_box"></div>
	            </div>
	            <div class="col-md-5">
					<button type="button" id="submit" class="btn btn-primary"
						data-dismis="modal"><?php echo _("Submit");?></button>
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
				</div>
				</div>
			</div>
		</div>
	</div>
</body>