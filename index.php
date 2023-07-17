<html>
<head>
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js" integrity="sha512-T/tUfKSV1bihCnd+MxKD0Hm1uBBroVYBOYSk1knyvQ9VyZJpc/ALb4P0r6ubwVPSGB2GvjeoMAJJImBG12TiaQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" integrity="sha512-mSYUmp1HYZDFaVKK//63EcZq4iFWFjxSL+Z3T/aCt4IO9Cejm03q3NKKYN6pFQzY0SBOr8h+eCIAZHPXcpZaNw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.1/js/tempusdominus-bootstrap-4.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.1/css/tempusdominus-bootstrap-4.min.css" />
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.1/css/all.css" integrity="sha384-vp86vTRFVJgpjF9jiIGPEEqYqlDwgyBgEF109VFjmqGmIY/Y4HV4d3Gp2irVfcrp" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/gh/farhadmammadli/bootstrap-select@main/js/bootstrap-select.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">

<link rel="stylesheet" type="text/css" href="style.css">
<link rel="icon" href="img/favicon_conta.png">
<title>Listado e.firma</title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light" style="background-color: #707B7C;">
	<div class="container-fluid" id="my_nav"></div>
</nav>
<center>
	<img src="img/logo.png" width="300" class="my-4" alt="Logo">
	<div id="alert_pop" class="w-75"></div>
</center>
<div id="menu_opt" class="my-4 ps-5"></div>
<div id="workspace" class="my-4 ps-5"></div>

<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    	<div class="modal-content">
      		<div class="modal-header">
				<h4 class="modal-title text-white" id="staticBackdropLabel" style="margin: 0 auto;"><b>Requests History</b></h4>
      		</div>
			<div class="modal-body"></div>
			<div class="modal-footer"></div>
    	</div>
  	</div>
</div>
</body>
<script>
$(document).ready(function()
{
	//Type formats
	var dateFormat_short = "YYYY-MM-DD";
    var dateFormat_long = "YYYY-MM-DD HH:mm:ss";
	var today_stamp = moment().format(dateFormat_short);

	//To display action buttons
	function load_my_action_buttons()
	{
		var ts = moment().format(dateFormat_long);
		$.ajax(
		{  
			url:"process_fiel.php", 
			method:"POST",  
			data:{"stamp_action_btn":ts},  
			dataType:"text",
			beforeSend: function()
			{
				console.log("Printing page buttons at: "+ ts);
				$("#workspace").html('');
				$("#menu_opt").html('<img src= "img/loading.gif" alt="Loading..." style="width:300px;">');
			},
			success:function(data)
			{   
				//console.log(data);
				$("#menu_opt").html(data);
			}  
		});
	}

	//To invoke navbar
	function fillNavbar()
	{
		var go = 2;
		$.ajax(
		{  
			url:"process_fiel.php", 
			method:"POST",  
			data:{"go_navbar":go},  
			dataType:"text",
			success:function(data)
			{   
				//console.log(data);
				/*if(data == 'no_session')
				{
					jQuery("body").html('<center><br><br><img src="../Image/logo_corp.png" alt="Site Logo"><br><br><h1><b>You don\'t have an active session!</b></h1><br><h1><b>Sending to login page...</b></h1></center>');
					setTimeout(function () { window.location.href = "../login.php";}, 4500); //will call the function after X secs.
				}
				else
				{
					$("#my_nav").html(data);
					load_my_action_buttons();
				}*/
				$("#my_nav").html(data);
				load_my_action_buttons();		
			}  
		});
	}

	//To display content of pem_store inventory in table format
	function pem_inventory_table()
	{
		var ts = moment().format(dateFormat_long);
		$.ajax(
		{  
			url:"process_fiel.php", 
			method:"POST",  
			data:{"list_inventory":ts},  
			dataType:"text",
			beforeSend: function()
			{
				console.log("Printing pem_store in table format at: "+ ts);
				$("#workspace").html('<img src= "img/loading.gif" alt="Loading..." style="width:300px;">');
			},
			success:function(data)
			{   
				//console.log(data);
				$("#workspace").html(data);
			}  
		});
	}

	//To display certificate details based on a certificate file name
	function show_cert_details_in_modal(cert_filename)
	{
		var ts = moment().format(dateFormat_long);
		$.ajax(
		{  
			url:"process_fiel.php", 
			method:"POST",  
			data:{"cert_details_modal":cert_filename, "details_ts": ts},
			dataType:"text",
			beforeSend: function()
			{
				console.log("Getting details for certificate: "+ cert_filename + " at: "+ ts);
				$(".modal-header").html('<h4 class="modal-title text-white" id="staticBackdropLabel" style="margin: 0 auto;"><b>Working on it...</b><i class="fas fa-cog fa-spin ms-2"></i></h4>');
                $(".modal-body").html('<center><img src= "img/loading.gif" alt="Loading..." style="width:300px;"></center>');
                $(".modal-footer").html('<button type="button" class="btn btn-dark" data-bs-dismiss="modal"><b>Cerrar</b><i class="fas fa-times ms-2"></i></button>');                                           
                $('#infoModal').modal('show');
			},
			success:function(data)
			{   
				//console.log(data);
				$(".modal-header").html('<h4 class="modal-title text-white" id="staticBackdropLabel" style="margin: 0 auto;"><b>Detalles de la e.firma...</b></h4>');
				$(".modal-body").html(data);
			}  
		});
	}

	//To trigger .cer conversion (we show modal with validation)
	function trigger_cert_conversion()
	{
		var ts = moment().format(dateFormat_long);
		$.ajax(
		{  
			url:"process_fiel.php", 
			method:"POST",  
			data:{"cert_conv_start":ts},
			dataType:"text",
			beforeSend: function()
			{
				console.log("Starting conversion at: "+ ts);
				$(".modal-header").html('<h4 class="modal-title text-white" id="staticBackdropLabel" style="margin: 0 auto;"><b>Working on it...</b><i class="fas fa-cog fa-spin ms-2"></i></h4>');
                $(".modal-body").html('<center><img src= "img/loading.gif" alt="Loading..." style="width:300px;"></center>');
                $(".modal-footer").html('<button type="button" class="btn btn-dark" data-bs-dismiss="modal"><b>Cerrar</b><i class="fas fa-times ms-2"></i></button>');
                $('#infoModal').modal('show');
			},
			success:function(data)
			{   
				//console.log(data);
				$(".modal-footer").html('<button type="button" class="btn btn-danger" data-bs-dismiss="modal"><b>Cancelar</b><i class="fas fa-times ms-2"></i></button><button type="button" class="btn btn-success" id="modal_conversion_OK"><b>Continuar</b><i class="fas fa-check ms-2"></i></button>');
				$(".modal-body").html(data);
			}  
		});
	}

	//To complete cer ingress
	function complete_cert_conversion()
	{
		var ts = moment().format(dateFormat_long);
		$.ajax(
		{  
			url:"process_fiel.php", 
			method:"POST",  
			data:{"cert_conversion":ts},
			dataType:"text",
			beforeSend: function()
			{
				console.log("Completing conversion at: "+ ts);
				$(".modal-header").html('<h4 class="modal-title text-white" id="staticBackdropLabel" style="margin: 0 auto;"><b>Working on conversion...</b><i class="fas fa-cog fa-spin ms-2"></i></h4>');
                $(".modal-body").html('<center><img src= "img/loading.gif" alt="Loading..." style="width:300px;"></center>');
                $(".modal-footer").html('');
                //$('#infoModal').modal('show');
			},
			success:function(data)
			{   
				//console.log(data);
				$(".modal-footer").html('<button type="button" class="btn btn-danger" data-bs-dismiss="modal"><b>Cerrar</b><i class="fas fa-times ms-2"></i></button>');
				$(".modal-body").html(data);
				//we re-paint the table of certs!
				pem_inventory_table();
			}  
		});
	}


	fillNavbar();


	//Event listener for outline-dark buttons
	$(document).on('click', '.btn-outline-dark', function()
	{ 
		var check_action = $(this).data("id");
		if(check_action == 'action_panel_btn')
		{
			var menu_display = $(this).data("id1");
			switch(menu_display)
			{
				case 'inventory':
					pem_inventory_table();
				break;
				case 'ingress':
					trigger_cert_conversion();
				break;
			}
		}
	});

	//Event listener for sm buttons
	$(document).on('click', '.btn-sm', function()
	{ 
		var check_action = $(this).data("id");
		switch(check_action)
		{
			case 'details_btn':
				var file_nm = $(this).data("id1");
				show_cert_details_in_modal(file_nm);
			break;
		}
	});

	//To finish the cert conversion (after approval in modal)
	$(document).on('click', '#modal_conversion_OK', function()
	{ 
		complete_cert_conversion();
	});


	
	
	//To filter results with search box on e.firma table
	$(document).on("keyup", "#myInput", function()
	{
		var value = $(this).val().toLowerCase();
		$("#myTable tr").filter(function() 
		{
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
		});
	});

});
</script>
<footer class="footer">
<div class="my-3">
	<span class="text-white"><strong>Tracking de e.firmas SAT</strong></span><br><span>Powered by <strong><a class="link" href="https://github.com/LaloMedel">Eduardo MEDEL</a></strong><br></span>
</div>
</footer>
</html>