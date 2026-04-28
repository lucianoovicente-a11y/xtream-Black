<?php include "./includes/inc.php"; ?>
<?php 

if ($user_nivel != 1 && $user_dados["user_info"]["id"] != 1) {
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
                <div class="page-titles">
                    <h4>Codificar domínio</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                    </ol>
                </div>
                <div class="row">
                    <div class="col-xl-12 col-lg-12">
                        <div class="progress rounded-0" style="height:4px;">
                            <div class="progress-bar rounded-0 bg-primary progress-animated" style="width: 100%; height:4px;" role="progressbar"></div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h4>Domínio a ser codificado:</h4>
                                <small>O domínio não pode ter http e https, apenas o nome do domínio é aceito.</small>
                                <div class="basic-form mt-3">
                                    <div class="form-group input-primary">
                                        <textarea class="form-control dominio" rows="4" placeholder="Ex: meudominio.com"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <a href="javascript:void(0);" class="btn btn-primary ml-2 codificar">Encode</a>
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
        
        $(document).on("click", ".codificar", function(e){
            e.preventDefault();

            var dominio = $(".dominio").val();

            if (dominio == "") {
                messageConfirm("Insira o domínio a ser criptografado", "error");
            }else{
                $.post("./actions/encode/dominio", {dominio: dominio}, function(res){
                    if (res.status) {



                        var dialog = bootbox.dialog({
                            size: 'large',
                            title: "DOMÍNIO CRIPTOGRAFADO COM SUCESSO!",
                            message: `<h4 class="text-danger"><strong>Domínio criptografado com sucesso!</strong></h4><br><div class="form-group input-primary">
                            <label>Encode: </label>
                            <textarea class="form-control crypt_text" readonly rows="1">`+res.crypt+`</textarea>
                            </div>`,
                            className: "modal-success",
                            buttons: {
                                noclose: {
                                    label: "Copiar",
                                    className: 'btn btn-sm btn-primary',
                                    callback: function() {
                                        let textArea = document.querySelector('.crypt_text');
                                        textArea.select();
                                        document.execCommand('copy');
                                        messageConfirm("Copiado para área de transferência!!", "success");
                                        return false;
                                    }
                                },
                                noclose2: {
                                    label: "Fechar",
                                    className: 'btn btn-sm btn-danger',
                                    callback: function() {
                                        $(this).modal('hide');
                                        return false;
                                    }
                                }

                            }

                        }).on('shown.bs.modal', function (e) {

                        }).find(".modal-dialog").addClass("modal-dialog-centered").find('.modal-header').css({
                            'background-color': '#007A64'
                        }).find('.modal-title').css({
                            'color': '#fff'
                        }).find('.modal-header .close').css({
                            'color': '#fff'
                        });
                    }
                },"JSON");
            }
            


        });
    </script>
    
    <script type="text/javascript">
       
        /* messagebox personalizado */
        function messageConfirm(msg, cor){
            toastr.options.positionClass = 'toast-bottom-full-width';
            if (cor == "success") {
                toastr.success(msg);
            }else{
                if (cor == "info") {
                    toastr.info(msg);
                }else{
                    if (cor == "error") {
                        toastr.error(msg);
                    }else{
                        toastr.warning(msg);
                    }
                }
            }
        }
    </script>

</body>
</html>