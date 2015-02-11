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
    if ($('form.form_feed_type').length > 0) {

        var form = $('form.form_feed_type'),
            formContainer = form.parent();

        formContainer.on('change', '.auto-submit', function() {

            // Disabled buttons so use can't submit
            formContainer.find('button').attr('disabled', 'disabled');

            $.post(window.location, $('form.form_feed_type').serialize(), function(data) {
                if (typeof data.html !== 'undefined' && data.html !== '') {
                    // Dynamically update form
                    $('form.form_feed_type').replaceWith($('<div/>').html(data.html).text());
                }
            });
        });

        $("#feed_type_topics").select2({
            placeholder: "Search ",
            multiple: true,
            minimumInputLength: 1,
            ajax: {
                url: topicSearchPath,
                dataType: 'jsonp',
                data: function (term, page) {
                    return {
                        term: term, // search term
                        page_limit: 10
                    };
                },
                results: function (data) {
                    return {results: data.results.topics};
                }
            },
            formatResult: function(topics) {
                return "<div class='select2-user-result'>" + topics.term + "</div>";
            },
            formatSelection: function(topics) {
                return topics.term;
            },
            seperator: ',',
            id: function(object) {
                return object.id+':'+object.term;
            },
            initSelection : function (element, callback) {

                var data = [];
                var rawTopics = element.val().split(",");

                $.each(rawTopics, function(index, value) {

                    var id = value.match(/^([0-9]+)\:/);
                    var term = value.match(/\:(.+)$/);

                    data.push({
                        id: id[1],
                        term: term[1]
                    });
                });

                callback(data);
            }
        });
    }

    // Parser list page
    if ($('#parsers_list').length > 0) {
        $('#parsers_list').on('change', 'input:checkbox', function() {
            var id = $(this).attr('id').replace(/[^0-9]+/, '');
            $.get('/admin/ingest/parser/change_status/'+id, function(data) {
                if (!data.status) {
                    alert(data.message);
                }
            });
        });
    }

    // TODO: add modal with confirmation to button/a with class confirm-delete
});
