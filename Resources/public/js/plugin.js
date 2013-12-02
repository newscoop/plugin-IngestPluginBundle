$(document).ready(function() {

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
        var button = $(this),
            dialogId = (button.data('dialog-id')) ? button.data('dialog-id') : 'dialog-confirm',
            buttons = (typeof(confirmButtonOverride) !== 'undefined')
                ? confirmButtonOverride
                : [
                    {
                        text : Translator.get('plugin.ingest.dialog.delete'),
                        click : function() {
                            window.location = button.attr('href');
                            $( this ).dialog( "close" );
                        }
                    }, {
                        text : Translator.get('plugin.ingest.dialog.cancel'),
                        click : function() {
                            $( this ).dialog( "close" );
                        }
                    }
                ];

         $('#'+dialogId)
         .data('dialogTriggerButton', button)
         .dialog({
            modal: true,
            buttons: buttons
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
