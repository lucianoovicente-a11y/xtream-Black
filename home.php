<?php include "./includes/inc.php"; ?>
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
                <div class="page-titles">
                    <h4>Aplicativos</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                    </ol>
                </div>
                <div class="row">
                    <div class="col-xl-12">
                        <div class="progress rounded-0" style="height:4px;">
                            <div class="progress-bar rounded-0 bg-primary progress-animated" style="width: 100%; height:4px;" role="progressbar"></div>
                        </div>
                        <?php 
                            $app = new Aplicativos();
                            $code = new Encode();

                            $protocolo = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS']=="on") ? "https" : "http");
                            $url = $protocolo.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                                
                                if (parse_url($url, PHP_URL_PATH) != NULL) {

                                    $path = explode("/", parse_url($url, PHP_URL_PATH));
                                    if (count($path) > 2) {
                                        $dominio   = parse_url($url, PHP_URL_HOST)."/".$path[1];

                                        if (parse_url($url, PHP_URL_PORT) != NULL) {
                                            $dominio   = parse_url($url, PHP_URL_HOST).":".parse_url($url, PHP_URL_PORT)."/".$path[1];
                                        }
                                    }else{
                                        $dominio   = parse_url($url, PHP_URL_HOST);

                                        if (parse_url($url, PHP_URL_PORT) != NULL) {
                                            $dominio   = parse_url($url, PHP_URL_HOST).":".parse_url($url, PHP_URL_PORT);
                                        }
                                    }

                                }else{
                                    $dominio   = parse_url($url, PHP_URL_HOST)."/";
                                    if (parse_url($url, PHP_URL_PORT) != NULL) {
                                        $dominio   = parse_url($url, PHP_URL_HOST).":".parse_url($url, PHP_URL_PORT);
                                    }
                                }

                            $apps = $app->buscar_apps();
                            if ($apps["status"]) {
                                foreach ($apps["app_info"] as $info) {

                                    if ($info["status"] == 1) {
                                        $status = '<h4 class="text-primary">ATIVO</h4>';
                                    }else{
                                        $status = '<h4 class="text-danger">INATIVO</h4>';
                                    }


                                    $logotipo = !empty($info["app_img_logotipo"]) ? $info["app_img_logotipo"] : "./images/avatar/1.jpg";
                            ?>
                        <div class="tab-content">
                            <div class="tab-pane active show fade">
                                <div class="card review-table">
                                    <div class="media">
                                        <img class="mr-3 img-fluid" width="60" src="<?php echo $logotipo; ?>" alt="<?php echo $info["nome"]; ?>">
                                        <div class="media-body">
                                            <h3 class="fs-20 text-black font-w600 mb-3"><?php echo $info["nome"]; ?></h3>
                                        </div>
                                        <div class="media-footer d-flex align-self-center">
                                            <?php if ($user_nivel === 1) { ?>
                                            <div class="disease mr-5">
                                                <p class="mb-1 fs-14">DNS APP</p>
                                                <h4 class="text-primary"><?php echo $code->criptografar_dominio($dominio); ?></h4>
                                            </div>
                                            <?php }  ?>
                                            <div class="disease mr-5">
                                                <p class="mb-1 fs-14">STATUS</p>
                                                <?php echo $status; ?>
                                            </div>
                                            <div class="edit ml-auto">
                                                <a href="javascript:void(0);" class="btn btn-outline-primary ml-2 editar" data-id="<?php echo $info["id"]; ?>">EDITAR</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php }}  ?>
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
    
    <!-- Toastr -->
    <script src="./vendor/toastr/js/toastr.min.js"></script>

    <!-- Popper Tooltips -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>

    <!-- Bootbox -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/4.4.0/bootbox.min.js"></script>

    <!-- asColorPicker -->
    <script src="./vendor/jquery-asColor/jquery-asColor.min.js"></script>
    <script src="./vendor/jquery-asGradient/jquery-asGradient.min.js"></script>
    <script src="./vendor/jquery-asColorPicker/js/jquery-asColorPicker.min.js"></script>

    <script type="text/javascript">
        const nivel = "<?php echo $user_nivel; ?>";
    </script>
    <script src="./js/dashboard/dashboard-2.js"></script>
    

</body>
</html>