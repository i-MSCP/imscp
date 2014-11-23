/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Layout
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      iMSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */

var iMSCP = function () {
    // Function to initialize page messages
    var initPageMessages = function () {
        $(".body").on("message_timeout", ".success,.info,.warning,.error", function () {
            $(this).hide().slideDown('fast').delay(5000).slideUp("normal", function () {
                $(this).remove();
            });
        });

        $(".success,.info,.warning,.error").trigger("message_timeout");
    };

    // Function to initialize tooltips
    var initTooltips = function ($context) {
        if ($context == 'simple') {
            $("a").tooltip(
                {
                    tooltipClass: 'ui-tooltip-notice',
                    track: true
                }
            );
        } else {
            $(".main_menu a").tooltip({ track: true });
            $(".body a,.body span,.body input,.dataTables_paginate div").tooltip(
                {
                    tooltipClass: 'ui-tooltip-notice',
                    track: true
                }
            );
        }
    };

    // Function to initialize buttons
    var initButtons = function (context) {
        if (context == 'simple') {
            $('.link_as_button,button').button({icons: { secondary: "ui-icon-triangle-1-e"} });
            $('input').first().focus();
        } else {
            $("input:submit, input:button, input:reset, button, .link_as_button").button();
            $(".radio, .checkbox").buttonset();
        }
    };

    // Function to initialize tables
    var initTables = function () {
        // Override some built-in jQuery method to trigger the i-MSCP updateTable event
        (function ($) {
            var origShow = $.fn.show;
            var origHide = $.fn.hide;
            var origAppendTo = $.fn.appendTo;
            var origPrependTo = $.fn.prependTo;
            var origHtml = $.fn.html;
            $.fn.show = function () {
                return origShow.apply(this, arguments).trigger("updateTable");
            };
            $.fn.hide = function () {
                return origHide.apply(this, arguments).trigger("updateTable");
            };
            $.fn.appendTo = function () {
                return origAppendTo.apply(this, arguments).trigger("updateTable");
            };
            $.fn.prependTo = function () {
                return origPrependTo.apply(this, arguments).trigger("updateTable");
            };
            $.fn.html = function () {
                var ret = origHtml.apply(this, arguments);
                $('tbody').trigger("updateTable");
                return ret;
            };
        })(jQuery);

        $("body").on("updateTable", "tbody", function () {
            $(this).find("tr:visible:odd").removeClass("odd").addClass("even");
            $(this).find("tr:visible:even").removeClass("even").addClass("odd");
            $(this).find('th').parent().removeClass("even odd");
        });
        $("tbody").trigger('updateTable');
    };

    // Function to initialize password generator
    // To enable the password generator for an password input field, just add the .pwd_generator class to it
    // Only password input fields with the password and cpassword identifier are filled
    var passwordGenerator = function() {
        if($(".pwd_generator").length) {
            $("<span/>", {
                style:"display:inline-block;margin-left:5px",
                html: [
                    $("<button/>", { id: "pwd_generate", text: "Generate" }),
                    $("<button/>", { id: "pwd_show", text: "Show" })
                ]
            }).insertAfter(".pwd_generator");

            $("#pwd_generate").pGenerator({
                'bind': 'click',
                'passwordElement': $("#password,#cpassword"),
                'displayElement': null,
                'passwordLength': 8,
                'uppercase': true,
                'lowercase': true,
                'numbers':   true,
                'specialChars': true
            });

            // Prefill password field if needed
            if($(".pwd_prefill").length) {
                $("#pwd_generate").trigger('click');
            } else {
                $("#password,#cpassword").val("");
            }

            $("#pwd_show").click(function(e) {
                e.preventDefault();
                var password = $("#password").val();
                if (password != '') {
                    $('<div/>', { html: $("<strong/>", { text: password }) }).dialog({
                        modal: true,
                        hide: "blind",
                        show: "blind",
                        title: "Your new password",
                        buttons: { Ok: function () { $(this).dialog("destroy").remove(); } }
                    });
                } else {
                    alert("You must first generate a password by clicking on the generate button.");
                }
            });
        }
    };

    // Function to fix bad jQuery UI behaviors
    var fixJqueryUI = function () {
        // Dirty fix for http://bugs.jqueryui.com/ticket/7856
        $('[type=checkbox]').on("change", function () {
            if (!$(this).is(":checked")) {
                $(this).blur();
            }
        });

        $(document).on("click", "button,input", function () {
            $(this).removeClass("ui-state-focus ui-state-hover");
        });
    };

    // Function to initialize layout
    var initLayout = function (context) {
        initPageMessages();
        initTooltips(context);

        if (context == 'simple') {
            $(".no_header #header").hide();
        } else {
            initTables();
            passwordGenerator();
        }

        initButtons(context);
    };

    return {
        // Main function to initialize application
        initApplication: function (context) {
            initLayout(context);
            fixJqueryUI();
        }
    };
}();

function sbmt(form, uaction) {
    form.uaction.value = uaction;
    form.submit();
    return false;
}

function sbmt_details(form, uaction) {
    form.details.value = uaction;
    form.submit();
    return false;
}

/**
 *
 * Javascript sprintf by http://jan.moesen.nu/
 * This code is in the public domain.
 *
 * %% - Returns a percent sign
 * %b - Binary number
 * %c - The character according to the ASCII value
 * %d - Signed decimal number
 * %u - Unsigned decimal number
 * %f - Floating-point number
 * %o - Octal number
 * %s - String
 * %x - Hexadecimal number (lowercase letters)
 * %X - Hexadecimal number (uppercase letters)
 *
 * @todo check use of radix parameter of parseInt for (pType == 'o')
 * @todo check use of radix parameter of parseInt for (pType == 'x')
 * @todo check use of radix parameter of parseInt for (pType == 'X')
 */
function sprintf() {
    if (!arguments || arguments.length < 1 || !RegExp) {
        return;
    }

    var str = arguments[0];
    var re = /([^%]*)%('.|0|\x20)?(-)?(\d+)?(\.\d+)?(%|b|c|d|u|f|o|s|x|X)(.*)/;
    var a = [], b = [], numSubstitutions = 0, numMatches = 0;

    while ((a = re.exec(str))) {
        var leftpart = a[1], pPad = a[2], pJustify = a[3], pMinLength = a[4];
        var pPrecision = a[5], pType = a[6], rightPart = a[7];

        numMatches++;
        var subst;

        if (pType == '%') {
            subst = '%';
        } else {
            numSubstitutions++;
            if (numSubstitutions >= arguments.length) {
                alert('Error! Not enough function arguments (' + (arguments.length - 1) + ', excluding the string)\nfor the number of substitution parameters in string (' + numSubstitutions + ' so far).');
            }

            var param = arguments[numSubstitutions];
            var pad = '';
            if (pPad && pPad.substr(0, 1) == "'") {
                pad = leftpart.substr(1, 1);
            } else if (pPad) {
                pad = pPad;
            }

            var justifyRight = true;
            if (pJustify && pJustify === "-") {
                justifyRight = false;
            }

            var minLength = -1;
            if (pMinLength) {
                minLength = parseInt(pMinLength, 10);
            }

            var precision = -1;
            if (pPrecision && pType == 'f') {
                precision = parseInt(pPrecision.substring(1), 10);
            }

            subst = param;
            if (pType == 'b') {
                subst = parseInt(param, 10).toString(2);
            } else if (pType == 'c') {
                subst = String.fromCharCode(parseInt(param, 10));
            } else if (pType == 'd') {
                subst = parseInt(param, 10) ? parseInt(param, 10) : 0;
            } else if (pType == 'u') {
                subst = Math.abs(param);
            } else if (pType == 'f') {
                subst = (precision > -1) ? Math.round(parseFloat(param) * Math.pow(10, precision)) / Math.pow(10, precision) : parseFloat(param);
            } else if (pType == 'o') {
                subst = parseInt(param).toString(8);
            } else if (pType == 's') {
                subst = param;
            } else if (pType == 'x') {
                subst = ('' + parseInt(param).toString(16)).toLowerCase();
            } else if (pType == 'X') {
                subst = ('' + parseInt(param).toString(16)).toUpperCase();
            }
        }
        str = leftpart + subst + rightPart;
    }

    return str;
}

/**
 * Display dialog box allowing to choose ftp directory
 *
 * @return false
 */
function chooseFtpDir() {
    var dialog1 = $('<div id="dial_ftp_dir" style="overflow: hidden;"/>').append($('<iframe scrolling="auto" height="100%"/>').
        attr("src", "ftp_choose_dir.php")).dialog(
        {
            hide: 'blind',
            show: 'slide',
            focus: false,
            width: 650,
            height: 650,
            autoOpen: false,
            modal: true,
            title: js_i18n_tr_ftp_directories,
            buttons: [{
                text: js_i18n_tr_close, click: function () {
                    $(this).dialog('close');
                }
            }],
            close: function (e, ui) {
                $(this).remove();
            }
        }
    );

    $(window).resize(function () {
        dialog1.dialog("option", "position", { my: "center", at: "center", of: window });
    });

    $(window).scroll(function () {
        dialog1.dialog("option", "position", { my: "center", at: "center", of: window });
    });

    dialog1.dialog('open');

    return false;
}

/*******************************************************************************
 *
 * Ajax related functions
 *
 * Note: require JQUERY
 */

/**
 * Jquery XMLHttpRequest Error Handling
 */

/**
 * Must be documented
 *
 * Note: Should be used as error callback funct of the jquery ajax request
 * @since r2587
 */
function iMSCPajxError(xhr, settings, exception) {

    switch (xhr.status) {
        // We receive this status when the session is expired
        case 403:
            window.location = '/index.php';
            break;
        default:
            alert('HTTP ERROR: An Unexpected HTTP Error occurred during the request');
    }
}
