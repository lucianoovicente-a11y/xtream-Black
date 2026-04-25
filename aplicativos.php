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
                        <?php 
                            $app = new Aplicativos();

                            $apps = $app->buscar_apps();
                            if ($apps["status"]) {
                                foreach ($apps["app_all"] as $info) {

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
                                            <div class="disease mr-5">
                                                <p class="mb-1 fs-14">STATUS</p>
                                                <?php echo $status; ?>
                                            </div>
                                            <div class="edit ml-auto">
                                                <a href="javascript:void(0);" class="btn btn-outline-primary ml-2 editar" data-id="<?php echo $info["id"]; ?>">EDITAR</a>
                                                <a href="javascript:void(0);" class="btn btn-outline-danger excluir" data-id="<?php echo $info["id"]; ?>">EXCLUIR</a>
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
    <!-- Material color picker -->
    <script src="./vendor/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js"></script>



    <script type="text/javascript">
        $(document).on("click", ".editar", function(e){
            e.preventDefault();

            var app_id = $(this).data("id");


            $.post("./actions/aplicativos/info", {id: app_id}, function(res){
                if (res.status) {

                    // Banner 1
                    var banner1_select = "";
                    if (res.app_info.tipo1 == 0) {
                        banner1_select += `
                            <option selected value="0">Nenhum lugar</option>
                            <option value="1">Menu de canais</option>
                            <option value="2">Menu de Filmes</option>
                        `;
                    }else if (res.app_info.tipo1 == 1) {
                        banner1_select += `
                            <option value="0">Nenhum lugar</option>
                            <option selected value="1">Menu de canais</option>
                            <option value="2">Menu de Filmes</option>
                        `;
                    }else if (res.app_info.tipo1 == 2) {
                        banner1_select += `
                            <option value="0">Nenhum lugar</option>
                            <option value="1">Menu de canais</option>
                            <option selected value="2">Menu de Filmes</option>
                        `;
                    }else{
                        banner1_select += `
                            <option value="0">Nenhum lugar</option>
                            <option selected value="1">Menu de canais</option>
                            <option value="2">Menu de Filmes</option>
                        `;
                    }

                    // Banner 2
                    var banner2_select = "";
                    if (res.app_info.tipo2 == 0) {
                        banner2_select += `
                            <option selected value="0">Nenhum lugar</option>
                            <option value="1">Menu de canais</option>
                            <option value="2">Menu de Filmes</option>
                        `;
                    }else if (res.app_info.tipo2 == 1) {
                        banner2_select += `
                            <option value="0">Nenhum lugar</option>
                            <option selected value="1">Menu de canais</option>
                            <option value="2">Menu de Filmes</option>
                        `;
                    }else if (res.app_info.tipo2 == 2) {
                        banner2_select += `
                            <option value="0">Nenhum lugar</option>
                            <option value="1">Menu de canais</option>
                            <option selected value="2">Menu de Filmes</option>
                        `;
                    }else{
                        banner2_select += `
                            <option value="0">Nenhum lugar</option>
                            <option selected value="1">Menu de canais</option>
                            <option value="2">Menu de Filmes</option>
                        `;
                    }

                    // Banner 3
                    var banner3_select = "";
                    if (res.app_info.tipo3 == 0) {
                        banner3_select += `
                            <option selected value="0">Nenhum lugar</option>
                            <option value="1">Menu de canais</option>
                            <option value="2">Menu de Filmes</option>
                        `;
                    }else if (res.app_info.tipo3 == 1) {
                        banner3_select += `
                            <option value="0">Nenhum lugar</option>
                            <option selected value="1">Menu de canais</option>
                            <option value="2">Menu de Filmes</option>
                        `;
                    }else if (res.app_info.tipo3 == 2) {
                        banner3_select += `
                            <option value="0">Nenhum lugar</option>
                            <option value="1">Menu de canais</option>
                            <option selected value="2">Menu de Filmes</option>
                        `;
                    }else{
                        banner3_select += `
                            <option value="0">Nenhum lugar</option>
                            <option selected value="1">Menu de canais</option>
                            <option value="2">Menu de Filmes</option>
                        `;
                    }

                    // Banner 4
                    var banner4_select = "";
                    if (res.app_info.tipo4 == 0) {
                        banner4_select += `
                            <option selected value="0">Nenhum lugar</option>
                            <option value="1">Menu de canais</option>
                            <option value="2">Menu de Filmes</option>
                        `;
                    }else if (res.app_info.tipo4 == 1) {
                        banner4_select += `
                            <option value="0">Nenhum lugar</option>
                            <option selected value="1">Menu de canais</option>
                            <option value="2">Menu de Filmes</option>
                        `;
                    }else if (res.app_info.tipo4 == 2) {
                        banner4_select += `
                            <option value="0">Nenhum lugar</option>
                            <option value="1">Menu de canais</option>
                            <option selected value="2">Menu de Filmes</option>
                        `;
                    }else{
                        banner4_select += `
                            <option value="0">Nenhum lugar</option>
                            <option selected value="1">Menu de canais</option>
                            <option value="2">Menu de Filmes</option>
                        `;
                    }

                    // Banner 1
                    var checked_status1 = "";
                    if (res.app_info.status1 == 1) {
                        var checked_status1 = "checked";
                    }

                    // Banner 2
                    var checked_status2 = "";
                    if (res.app_info.status2 == 1) {
                        var checked_status2 = "checked";
                    }

                    // Banner 3
                    var checked_status3 = "";
                    if (res.app_info.status3 == 1) {
                        var checked_status3 = "checked";
                    }

                    // Banner 4
                    var checked_status4 = "";
                    if (res.app_info.status4 == 1) {
                        var checked_status4 = "checked";
                    }


                    var form = `
                            <form id="editar_aplicativo">
                                <div class="default-tab">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-toggle="tab" href="#configuracoes"><i class="la la-wrench mr-2"></i> Configurações</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#conexao"><i class="la la-wifi mr-2"></i> Conexão</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#bannners"><i class="la la-camera mr-2"></i> Banners</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#avisoss"><i class="la la-envelope mr-2"></i> Avisos</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#cores"><i class="la la-tint mr-2"></i> Cores</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="configuracoes" role="tabpanel">
                                            <div class="pt-4">
                                                <div class="basic-form">
                                                    <div class="form-row">
                                                        <div class="form-group col-md-12 input-primary">
                                                            <label><strong>Nome do aplicativo*</strong></label>
                                                            <input type="text" class="form-control" placeholder="Ex: Aplicativo 01" name="nome_app" value="`+res.app_info.nome+`" required>
                                                        </div>
                                                        <div class="form-group col-md-6 input-primary">
                                                            <label><strong>Logo do aplicativo*</strong></label>
                                                            <small>Formato .png</small>
                                                            <input type="text" class="form-control" placeholder="Formato .png" name="logo_app" value="`+res.app_info.app_img_logotipo+`" required>
                                                        </div>
                                                        <div class="form-group col-md-6 input-primary">
                                                            <label><strong>Logo do menu*</strong></label>
                                                            <small>Formato .png</small>
                                                            <input type="text" class="form-control" placeholder="Formato .png" name="sidebar_app" value="`+res.app_info.app_img_sidebar+`" required>
                                                        </div>
                                                        <div class="form-group col-md-6 input-primary">
                                                            <label><strong>Background do aplicativo*</strong></label>
                                                            <small>Formato .png</small>
                                                            <input type="text" class="form-control" placeholder="Formato .png" name="background_app" value="`+res.app_info.app_img_background+`" required>
                                                        </div>
                                                        <div class="form-group col-md-6 input-primary">
                                                            <label><strong>Favoritos*</strong></label>
                                                            <small>Formato .png</small>
                                                            <input type="text" class="form-control" placeholder="Formato .png" name="favorito_app" value="`+res.app_info.app_img_favoritos+`" required>
                                                        </div>
                                                        <div class="form-group col-md-12 input-primary">
                                                            <label><strong>WhatsApp suporte*</strong></label>
                                                            <small>Formato .png</small>
                                                            <input type="text" class="form-control" placeholder="35 9 9999-9999" name="whatsapp_app" value="`+res.app_info.app_msg_suporte+`" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="conexao">
                                            <div class="pt-4">
                                                <h4 class="text-center mb-3">Obrigatório ter o certificado <strong class="text-primary">SSL ATIVO</strong></h4><hr>
                                                <div class="basic-form">
                                                
                                                    <div class="form-row">
                                                        <div class="form-group col-md-6 input-primary">
                                                            <label><strong>Psp binstream*</strong></label>
                                                            <input type="text" class="form-control" placeholder="psp.srvper.com" name="psp_app" value="`+res.app_info.binstream_psp+`" required>
                                                        </div>
                                                        <div class="form-group col-md-6 input-primary">
                                                            <label><strong>Email binstream*</strong></label>
                                                            <input type="text" class="form-control" placeholder="@srvper.com" name="email_app" value="`+res.app_info.binstream_email+`" required>
                                                        </div>
                                                        <div class="form-group col-md-6 input-primary">
                                                            <label><strong>Auth binstream*</strong></label>
                                                            <input type="text" class="form-control" placeholder="https://auth1.srvper.com/api/v2/auth" name="auth_app" value="`+res.app_info.binstream_auth+`" required>
                                                        </div>
                                                        <div class="form-group col-md-6 input-primary">
                                                            <label><strong>Register binstream*</strong></label>
                                                            <input type="text" class="form-control" placeholder="https://auth1.srvper.com/api/v2/register" name="register_app" value="`+res.app_info.binstream_register+`" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="bannners">
                                            <div class="pt-4">
                                                <div class="basic-form">
                                                    
                                                    <div class="form-row">
                                                        <div class="form-group col-md-4 input-primary">
                                                            <label><strong>Nome*</strong></label>
                                                            <input type="text" class="form-control" placeholder="Ex: Novidades" name="nome1_banner" value="`+res.app_info.nome1+`" required>
                                                        </div>
                                                        <div class="form-group col-md-5 input-primary">
                                                            <label><strong>Imagem*</strong></label>
                                                            <input type="text" class="form-control" placeholder="Imagem em .png" name="url1_banner" value="`+res.app_info.url1+`" required>
                                                        </div>
                                                        <div class="form-group col-md-3 input-primary">
                                                            <label><strong>Redirecionar para*</strong></label>
                                                            <select id="inputState" class="form-control default-select" name="tipo1_banner" required>
                                                                `+banner1_select+`
                                                            </select>
                                                        </div>
                                                        <div class="form-group col-md-12">
                                                            <div class="form-check">
                                                                <input class="form-check-input" id="status1_banner" type="checkbox" `+checked_status1+` name="status1_banner">
                                                                <label class="form-check-label" for="status1_banner">
                                                                    Marque para ativar o banner
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr>




                                                    <div class="form-row">
                                                        <div class="form-group col-md-4 input-primary">
                                                            <label><strong>Nome*</strong></label>
                                                            <input type="text" class="form-control" placeholder="Ex: Jogos hoje" name="nome2_banner" value="`+res.app_info.nome2+`" required>
                                                        </div>
                                                        <div class="form-group col-md-5 input-primary">
                                                            <label><strong>Imagem*</strong></label>
                                                            <input type="text" class="form-control" placeholder="Imagem em .png" name="url2_banner" value="`+res.app_info.url2+`" required>
                                                        </div>
                                                        <div class="form-group col-md-3 input-primary">
                                                            <label><strong>Redirecionar para*</strong></label>
                                                            <select id="inputState" class="form-control default-select" name="tipo2_banner" required>
                                                                `+banner2_select+`
                                                            </select>
                                                        </div>
                                                        <div class="form-group col-md-12">
                                                            <div class="form-check">
                                                                <input class="form-check-input" id="status2_banner" type="checkbox" `+checked_status2+` name="status2_banner">
                                                                <label class="form-check-label" for="status2_banner">
                                                                    Marque para ativar o banner
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr>




                                                    <div class="form-row">
                                                        <div class="form-group col-md-4 input-primary">
                                                            <label><strong>Nome*</strong></label>
                                                            <input type="text" class="form-control" placeholder="Ex: Suporte" name="nome3_banner" value="`+res.app_info.nome3+`" required>
                                                        </div>
                                                        <div class="form-group col-md-5 input-primary">
                                                            <label><strong>Imagem*</strong></label>
                                                            <input type="text" class="form-control" placeholder="Imagem em .png" name="url3_banner" value="`+res.app_info.url3+`" required>
                                                        </div>
                                                        <div class="form-group col-md-3 input-primary">
                                                            <label><strong>Redirecionar para*</strong></label>
                                                            <select id="inputState" class="form-control default-select" name="tipo3_banner" required>
                                                                `+banner3_select+`
                                                            </select>
                                                        </div>
                                                        <div class="form-group col-md-12">
                                                            <div class="form-check">
                                                                <input class="form-check-input" id="status3_banner" type="checkbox" `+checked_status3+` name="status3_banner">
                                                                <label class="form-check-label" for="status3_banner">
                                                                    Marque para ativar o banner
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr>




                                                    <div class="form-row">
                                                        <div class="form-group col-md-4 input-primary">
                                                            <label><strong>Nome*</strong></label>
                                                            <input type="text" class="form-control" placeholder="Ex: Promoção" name="nome4_banner" value="`+res.app_info.nome4+`" required>
                                                        </div>
                                                        <div class="form-group col-md-5 input-primary">
                                                            <label><strong>Imagem*</strong></label>
                                                            <input type="text" class="form-control" placeholder="Imagem em .png" name="url4_banner" value="`+res.app_info.url4+`" required>
                                                        </div>
                                                        <div class="form-group col-md-3 input-primary">
                                                            <label><strong>Redirecionar para*</strong></label>
                                                            <select id="inputState" class="form-control default-select" name="tipo4_banner" required>
                                                                `+banner4_select+`
                                                            </select>
                                                        </div>
                                                        <div class="form-group col-md-12">
                                                            <div class="form-check">
                                                                <input class="form-check-input" id="status4_banner" type="checkbox" `+checked_status4+` name="status4_banner">
                                                                <label class="form-check-label" for="status4_banner">
                                                                    Marque para ativar o banner
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                     
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="avisoss">
                                            <div class="pt-4">
                                                <div class="form-group col-md-12 input-primary">
                                                    <label><strong>Aviso:</strong></label>
                                                    <input type="text" class="form-control" name="aviso_app" value="`+res.app_info.texto+`" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="cores">
                                            <div class="pt-4">
                                                <div class="row">
                                                    <div class="col-xl-4 col-lg-6 mb-3">
                                                        <div class="example">
                                                            <p class="mb-1"><strong>Esquerdo*</strong></p>
                                                            <input type="text" class="as_colorpicker form-control" name="cor_esquerda" value="`+res.app_info.app_cor_esquerdo+`">
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-4 col-lg-6 mb-3">
                                                        <div class="example">
                                                            <p class="mb-1"><strong>Centro*</strong></p>
                                                            <input type="text" class="complex-colorpicker form-control" name="cor_centro" value="`+res.app_info.app_cor_centro+`">
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-4 col-lg-6 mb-3">
                                                        <div class="example">
                                                            <p class="mb-1"><strong>Direita*</strong></p>
                                                            <input type="text" class="gradient-colorpicker form-control" name="cor_direita" value="`+res.app_info.app_cor_direito+`">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" value="`+app_id+`" name="app_id">
                            </form>
                            `;


                   var dialog = bootbox.dialog({
                        size: 'large',
                        title: "<img class='mr-3 img-fluid' width='60' src='"+res.app_info.app_img_logotipo+"' alt='"+res.app_info.nome+"'> Editar o aplicativo: <strong>"+res.app_info.nome+"</strong>",
                        message: form,
                        className: "modal-success",
                        buttons: {
                            noclose: {
                                label: "Salvar informações",
                                className: 'btn btn-sm btn-primary',
                                callback: function() {
                                    var form_response = $("#editar_aplicativo").serialize();
                                    $.post("./actions/aplicativos/editar", form_response, function(res) {

                                        if (res.status) {
                                            messageConfirm(res.msg, "success");
                                        }else{
                                            messageConfirm(res.msg, "error");
                                        }  
                                    }, "json");
                                    $(this).modal('hide');
                                    return false;
                                }
                            },
                            noclose2: {
                                label: "Cancelar",
                                className: 'btn btn-sm btn-danger',
                                callback: function() {
                                    $(this).modal('hide');
                                    return false;
                                }
                            }

                        }

                    }).on('shown.bs.modal', function (e) {
                        // Colorpicker
                        $(".as_colorpicker").asColorPicker();
                        $(".complex-colorpicker").asColorPicker({
                            mode: 'complex'
                        });
                        $(".gradient-colorpicker").asColorPicker({
                            mode: 'gradient'
                        });
                    }).find(".modal-dialog").addClass("modal-dialog-centered").find('.modal-header').css({
                        'background-color': '#007A64'
                    }).find('.modal-title').css({
                        'color': '#fff'
                    }).find('.modal-header .close').css({
                        'color': '#fff'
                    });
                }
            }, "JSON");
        });



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