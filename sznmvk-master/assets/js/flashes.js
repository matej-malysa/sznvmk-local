$(function () {
    flashesControl();
});

function flashesControl() {
    // FLASHES
    var __flashesDirection = '-100%';

    // Close single flash
    $('.closeFlash').on('click', function () {
        $(this).parent().siblings().animate({
            right: __flashesDirection,
        }, function () {
            $(this).parent().remove();
        });

        $(this).parent().animate({
            right: __flashesDirection,
        }, function () {
            $(this).parent().remove();
        });

        setTimeout(function () {
            if ($('.flashes .flash').length < 1) {
                $('.closeAllFlashes').fadeOut();
            }
        }, 500);
    });

    // Close all flashes
    $('.closeAllFlashes').on('click', function () {
        $('.flashes .flash').animate({
            right: __flashesDirection,
        }, function () {
            $(this).parent().remove();
        });

        $(this).fadeOut();
    });

    // Hide flashes after time
    if ($('.flashes').length > 0) {
        setTimeout(function () {
            $('.flashes .alert').animate({
                right: __flashesDirection,
            }, function () {
                $(this).parent().remove();
            });

            $('.closeAllFlashes').fadeOut();
        }, 5000);
    }
}