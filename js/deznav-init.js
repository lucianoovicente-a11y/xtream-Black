/* Deznav Init - Inicialização da navegação */
$(document).ready(function(){
    // Inicializa componentes de navegação
    console.log("Deznav inicializado");
    
    // Ativa tooltips
    $("[data-toggle='tooltip']").tooltip();
    
    // Ativa popovers
    $("[data-toggle='popover']").popover();
    
    // Menu ativo
    $(".nav-link").on("click", function(){
        $(".nav-link").removeClass("active");
        $(this).addClass("active");
    });
    
    // Dropdown toggle
    $(".dropdown-toggle").dropdown();
});
