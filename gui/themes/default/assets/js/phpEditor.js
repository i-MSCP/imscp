$(document).ready(function() {
    // Init dialog
    $('#php_editor_dialog').dialog(
        {
            hide: 'blind',
            show: 'slide',
            focus: false,
            autoOpen: false,
            width: 'auto',
            modal: true,
            buttons: {
                '{TR_CLOSE}': function () {
                    $(this).dialog('close');
                }
            }
        });

    // Add handle to make dialog at center of window on scroll
    $(window).scroll(function() {
        $("#php_editor_dialog").dialog("option", "position", { my: "center", at: "center", of: window });
    });

    // When the form is submitten, re-add the PHP Editor to the form
    $('form').submit( function () {
        $('#php_editor_dialog').parent().appendTo($(this));
    });

    // PHP Editor settings button
    if ($('#php_php_no').is(':checked')) {
        $('#php_editor_block').hide();
    }

    $('#hp_php_yes,#hp_php_no').change(
        function () {
            $('#php_editor_block').toggle();
        }
    );

    $('#php_editor_dialog_open').button({ icons:{ primary:'ui-icon-gear'} }).click(function (e) {
        $('#php_editor_dialog').dialog('open');
        return false;
    });

    // Do not show PHP Editor settings button if disabled
    if ($('#phpiniSystemNo').is(':checked')) {
        $('#php_editor_dialog_open').hide();
    }

    $('#phpiniSystemYes,#phpiniSystemNo').change(
        function () {
            $('#php_editor_dialog_open').fadeToggle();
        }
    );

    // PHP Editor reseller max values
    var phpDirectivesMaxValues = {PHP_DIRECTIVES_MAX_VALUES};

    // PHP Editor error message
    errorMessages = $('.php_editor_error');

    // Function to show a specific message when a PHP Editor setting value is wrong
    function _updateErrorMesssages(k, t) {
        if (t != undefined) {
            if (!$('#err_' + k).length) {
                $("#msg_default").remove();
                errorMessages.append('<span style="display:block" id="err_' + k + '">' + t + '</span>').
                    removeClass('success').addClass('error');
            }
        } else if ($('#err_' + k).length) {
            $('#err_' + k).remove();
        }

        if ($.trim(errorMessages.text()) == '') {
            errorMessages.empty().append('<span id="msg_default">{TR_FIELDS_OK}</span>').
                removeClass('error').addClass('success');
        }
    }

    // Adds an event on each PHP Editor settings input fields to display an
    // error message when a value is wrong
    $.each(phpDirectivesMaxValues, function (k, v) {
        $('#' + k).keyup(function () {
            var r = /^(0|[1-9]\d*)$/; // Regexp to check value syntax
            var nv = $(this).val(); // Get new value to be checked

            if (!r.test(nv) || parseInt(nv) > parseInt(v)) {
                $(this).addClass('ui-state-error');
                _updateErrorMesssages(k, sprintf('{TR_VALUE_ERROR}', k, 0, v));
            } else {
                $(this).removeClass('ui-state-error');
                _updateErrorMesssages(k);
            }
        }).trigger('keyup');
    });
});
