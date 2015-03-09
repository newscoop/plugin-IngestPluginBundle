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

        var modal = $('#feed-confirm-delete');
        modal
            .find('a.delete').attr('href', $(this).attr('href')).end()
            .find('a.delete').attr('href', $(this).attr('href')+'?delete_entries=true').end()
            .find('a.cancel').click(function() {
                console.log('tralala');
                modal.modal('hide');
            });

        modal.modal({ keyboard: true });

        return false;
    });

    // Execute when form for Feeds is displayed
    if ($('form.form_feed_type').length > 0) {

        var form = $('form.form_feed_type'),
            formContainer = form.parent();

        formContainer
            .on('change', '.auto-submit', function() {

                // Disabled buttons so use can't submit
                formContainer.find('button').attr('disabled', 'disabled');

                $.post(window.location, $('form.form_feed_type').serialize(), function(data) {
                    if (typeof data.html !== 'undefined' && data.html !== '') {
                        // Dynamically update form
                        $('form.form_feed_type').replaceWith($('<div/>').html(data.html).text());
                        formContainer.trigger('setup');
                    }
                });
            })
            .bind('setup', function() {

                var defaultConfig = {
                    multiple: false,
                    ajax: {
                        url: '',
                        dataType: 'jsonp',
                        data: function (term, page) {
                            return {
                                term: term, // search term
                                page_limit: 10
                            };
                        },
                        results: function (data) {
                            return {results: data.results.items};
                        }
                    },
                    formatResult: function(items) {
                        return "<div class='select2-user-result'>" + items.term + "</div>";
                    },
                    formatSelection: function(items) {
                        return items.term;
                    },
                    seperator: ',',
                    id: function(object) {
                        return object.id+':'+object.term;
                    },
                    initSelection : function (element, callback) {

                        if (element.val() !== '') {
                            var data;

                            if (element.val().indexOf(',') === -1) {

                                var id = element.val().match(/^([0-9]+)\:/);
                                var term = element.val().match(/\:(.+)$/);
                                data = {
                                    id: id[1],
                                    term: term[1]
                                };

                            } else {

                                var rawItems = element.val().split(",");
                                data = [];

                                $.each(rawItems, function(index, value) {

                                    var id = value.match(/^([0-9]+)\:/);
                                    var term = value.match(/\:(.+)$/);

                                    data.push({
                                        id: id[1],
                                        term: term[1]
                                    });
                                });
                            }

                            callback(data);
                        }
                    }
                };

                var config = {
                    feed_type_issue: {
                        minimumResultsForSearch: -1,
                        placeholder: null,
                        ajax: {
                            url: issueSearchPath,
                            data: function (term, page) {
                                return {
                                    language: $('#feed_type_language').val(),
                                    publication: $('#feed_type_publication').val()
                                };
                            }
                        },
                        initSelection : function (element, callback) {

                            var data = {
                                id: '',
                                term: issuePlaceholder
                            };

                            if (element.val() !== '') {

                                var id = element.val().match(/^([0-9]+)\:/);
                                var term = element.val().match(/\:(.+)$/);
                                data = {
                                    id: id[1],
                                    term: term[1]
                                };
                            }

                            callback(data);
                        }
                    },
                    feed_type_sections: {
                        placeholder: sectionPlaceholder,
                        multiple: ($('#feed_type_language').val() === "") ? true : false,
                        ajax: {
                            url: sectionsSearchPath,
                            data: function (term, page) {
                                var val = $('#feed_type_issue').val();
                                return {
                                    issue: val.replace(/^([0-9]+)\:.*/, '$1'),
                                    publication: $('#feed_type_publication').val(),
                                    language: $('#feed_type_language').val()
                                };
                            }
                        }
                    },
                    feed_type_topics: {
                        placeholder: topicPlaceholder,
                        minimumInputLength: 1,
                        multiple: true,
                        ajax: { url: topicsSearchPath }
                    }
                };

                $('input.enable-select2').each(function() {
                    $(this).select2($.extend(true, {}, defaultConfig, config[$(this).attr('id')]));
                });

                // Clear issue and section selections when changing language
                $('#feed_type_language').change(function(e, data) {
                    if (typeof(data) === 'undefined' || data !== "true") {
                        e.stopImmediatePropagation();
                        $('#feed_type_issue').select2('val', '');
                        $('#feed_type_sections').select2('val', '');
                        $(this).trigger('change', [ "true" ]);
                    }
                });

                // Clear section selection when changing issue
                $('#feed_type_issue').change(function() {
                    $('#feed_type_sections').select2('val', '');
                });
            }).trigger('setup');
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
});
