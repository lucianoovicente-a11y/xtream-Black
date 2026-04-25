/* DezNav Initialization Script */
(function($) {
    "use strict";
    
    // Initialize navigation menu
    var currentUrl = window.location.href;
    $('.metismenu a').each(function() {
        if ($(this).attr('href') && currentUrl.indexOf($(this).attr('href')) !== -1) {
            $(this).addClass('mm-active');
            $(this).parents('li').addClass('mm-active');
            $(this).parents('ul').addClass('in');
        }
    });
    
    // Sidebar toggle
    $('.hamburger').on('click', function() {
        $('#sidebar').toggleClass('collapsed');
        $('.content-body').toggleClass('expanded');
    });
    
    // Mobile sidebar
    $('.mobile-hamburger').on('click', function() {
        $('#sidebar').toggleClass('mobile-open');
    });
    
    // Close sidebar on mobile when clicking outside
    $(document).on('click', function(e) {
        if ($(window).width() < 768) {
            if (!$(e.target).closest('#sidebar, .mobile-hamburger').length) {
                $('#sidebar').removeClass('mobile-open');
            }
        }
    });
    
    // Initialize perfect scrollbar
    if (typeof PerfectScrollbar !== 'undefined') {
        new PerfectScrollbar('.menu-scroll', {
            wheelSpeed: 2,
            wheelPropagation: true,
            minScrollbarLength: 20
        });
    }
    
    // Auto-hide notification
    setTimeout(function() {
        $('.alert-auto-hide').fadeOut('slow');
    }, 5000);
    
})(jQuery);
