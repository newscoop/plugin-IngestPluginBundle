$(document).ready(function() {

    alert('dom ready');

    // Button with btn-js-click should be executed through JS
    $('button.btn-js-click').click(function() {
        if ($(this).data('url') === '') {
            alert('Attribute data-url not set.');
            return false;
        }
        window.location = $(this).data('url');
    });

    // Buttons with a class confirm-delete should receiver a confirm dialog
    // before continue
    $('.btn.confirm-delete').click(function(e) {

        e.stopImmediatePropagation();
        var button = $(this);

         $('#dialog-confirm').dialog({
            resizable: false,
            height:140,
            modal: true,
            buttons: {
                "Delete all items": function() {
                    window.location = button.attr('href');
                    $( this ).dialog( "close" );
                },
                Cancel: function() {
                    $( this ).dialog( "close" );
                }
            }
        });

        return false;
    });

    // Execute when form for Feeds is displayed
    if ($('form.form_feed').length > 0) {

        var form = $('form.form_feed'),
            formContainer = form.parent();

        formContainer.on('change', 'select.publication', function() {

            // Disabled buttons so use can't submit
            formContainer.find('button').attr('disabled', 'disabled');

            $.post(window.location, $('form.form_feed').serialize(), function(data) {
                if (typeof data.html !== 'undefined' && data.html !== '') {
                    // Dynamically update form
                    $('form.form_feed').replaceWith($('<div/>').html(data.html).text());
                }
            });
        });
    }

    // TODO: add modal with confirmation to button/a with class confirm-delete
});
