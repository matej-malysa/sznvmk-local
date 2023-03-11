$(function(){
    WebFont.load({
        google: {
            families: ["Archivo:regular,500,600,700","Archivo Narrow:regular,500,600"]
        }
    });

    // Contributte live form validation
    LiveForm.setOptions({
        showMessageClassOnParent: false,
        controlValidClass: 'is-valid',
        controlErrorClass: 'is-invalid',
        messageErrorClass: 'invalid-feedback',
        showValid: true,
    });
});

$.fancybox.defaults.loop = true;
