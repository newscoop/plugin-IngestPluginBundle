$(document).ready(function() {

    // Execute when form for Feeds is displayed
    if ($('form.form_feed').length > 0) {

        var form = $('form.form_feed'),
            formContainer = form.parent();

        formContainer.on('change', 'select.publication', function() {
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
