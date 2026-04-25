<?php include "./includes/inc.php"; ?>
<?php 

if ($user_nivel != 1) {
    header("Location: home"); exit();
} 
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php include "./includes/head.php"; ?>
</head>
<body>

    <!--*******************
        Preloader start
    ********************-->
    <div id="preloader">
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
        </div>
    </div>
    <!--*******************
        Preloader end
    ********************-->

    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">

        <!--**********************************
            Nav header start
        ***********************************-->
        <div class="nav-header">
            <?php include "./includes/nav_header.php"; ?>
        </div>
        <!--**********************************
            Nav header end
        ***********************************-->

		
		<!--**********************************
            Header start
        ***********************************-->
        <div class="header">
            <?php include "./includes/header.php"; ?>
        </div>
        <!--**********************************
            Header end ti-comment-alt
        ***********************************-->

        <!--**********************************
            Sidebar start
        ***********************************-->
        <div class="deznav">
            <?php include "./includes/menu.php"; ?>
        </div>
        <!--**********************************
            Sidebar end
        ***********************************-->
		
		<!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="form-head align-items-center d-flex mb-sm-4 mb-3">
					<div class="mr-auto">
						<h4>Usuários</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                        </ol>
					</div>
					<div>
                        <a href="javascript:void(0)" class="btn btn-primary mr-3 add_user"><i class="fa fa-plus-circle mr-1" aria-hidden="true"></i> Novo Usuário</a>
                    </div>
				</div>
				<div class="row">
                    <div class="col-xl-12 col-sm-12">
                        <div class="progress rounded-0" style="height:4px;">
                            <div class="progress-bar rounded-0 bg-primary progress-animated" style="width: 100%; height:4px;" role="progressbar"></div>
                        </div>
                        <div class="card">
                            <div class="col-xl-12">
                                <div class="table-responsive card-table">
                                    <table id="usuarios_table" class="table table-striped dt-responsive nowrap" style="width:100%"></table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--**********************************
            Content body end
        ***********************************-->

        <!--**********************************
            Footer start
        ***********************************-->
        <div class="footer">
            <?php include "./includes/footer.php"; ?>
        </div>
        <!--**********************************
            Footer end
        ***********************************-->

		<!--**********************************
           Support ticket button start
        ***********************************-->

        <!--**********************************
           Support ticket button end
        ***********************************-->


    </div>
    <!--**********************************
        Main wrapper end
    ***********************************-->

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <script src="./vendor/global/global.min.js"></script>
    <script src="./vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script src="./js/custom.min.js"></script>
    <script src="./js/deznav-init.js"></script>
    
    <!-- Popper Tooltips -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>

    <!-- Bootbox -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/4.4.0/bootbox.min.js"></script>

    <!-- Toastr -->
    <script src="./vendor/toastr/js/toastr.min.js"></script>

    <!-- Datatable -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

    <script src="./js/plugins-init/users.js"></script>
</body>
</html>