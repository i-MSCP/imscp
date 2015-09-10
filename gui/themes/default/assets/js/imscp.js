/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq
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
 */

(function($) {
    // Initialize page messages
    var initPageMessages = function () {
        $("body").on("message_timeout", ".success,.info,.warning,.error", function () {
            $(this).hide().slideDown('fast').delay(10000).slideUp("normal", function () {
                $(this).remove();
            });
        });

        $(".success,.info,.warning,.error").trigger("message_timeout");
    };

    // Initialize tooltips
    var initTooltips = function (context) {
        if (context == "simple") {
            $("[title]").tooltip(
                {
                    tooltipClass: "ui-tooltip-notice",
                    track: true,
                    content: function() {
                        var title = $(this).attr( "title" ) || "";
                        return $(this).attr("title");
                    }
                }
            );
        } else {
            $(".main_menu [title]").tooltip({track: true});
            $(".body [title]").tooltip({
                tooltipClass: "ui-tooltip-notice",
                track: true,
                content: function() {
                    var title = $(this).attr( "title" ) || "";
                    return $(this).attr("title");
                }
            });
        }
    };

    // Initialize buttons
    var initButtons = function (context) {
        if (context == "simple") {
            $(".link_as_button, button").button({ icons: { secondary: "ui-icon-triangle-1-e" }});
            $("input").first().focus();
        } else {
            $("input:submit, input:button, input:reset, button, .link_as_button").button();
            $(".radio, .checkbox").buttonset();
        }
    };

    // Initialize updateTable event listener
    var initTables = function () {
        $(".body").on("updateTable", "tbody", function () {
            $(this).find("tr:visible:odd").css('background', "#ededed");
            $(this).find("tr:visible:even").css("background", "#ffffff");
        });
    };

    // Initialize password generator
    // To enable the password generator feature just add the 'pwd_generator' class to the first password input field
    // To enable the pre-fill feature, add the 'pwd_prefill' class to the first password input field
    // Note: It is assumed that a form has only one pair of password input fields
    var passwordGenerator = function() {
        $(".pwd_generator").each(function (index) {
            var $pwdElements = $(this).parents('form').find("[type=password]");

            $("<span>", {
                style: "display:inline-block;margin-left:1em",
                html: [
                    $("<button>", {
                        id: "pwd_generate_" + index,
                        type: "button",
                        text: imscp_i18n.core.generate
                    }).button().pGenerator({
                        'passwordElement': $pwdElements,
                        'passwordLength': imscp_i18n.core.password_length
                    }),
                    $("<button>", {
                        id: "pwd_show_" + index,
                        type: "button",
                        text: imscp_i18n.core.show,
                        click: function () {
                            var password = $pwdElements.first().val();

                            if (password.length) {
                                $('<div>', {html: $("<strong>", {text: password})}).dialog({
                                    modal: true,
                                    hide: "blind",
                                    show: "blind",
                                    title: imscp_i18n.core.your_new_password,
                                    buttons: [
                                        {
                                            text: imscp_i18n.core.close,
                                            click: function () {
                                                $(this).dialog("destroy").remove();
                                            }
                                        }
                                    ]
                                });
                            } else {
                                alert(imscp_i18n.core.password_generate_alert);
                            }
                        }
                    }).button()
                ]
            }).insertAfter($(this));

            if ($(this).hasClass('pwd_prefill')) {
                $("#pwd_generate_" + index).trigger("click");
            }
        });
    };

    // Initialize FTP chooser
    var initFtpChooser = function() {
        $("body").on("click", "a.ftp_choose_dir", function(e) {
            var href = $(this).attr("href");
            var $dialog = $("#ftp_choose_dir_dialog");

            if($dialog.length) {
                if(href == "#") { // # href means that we want set directory. Then, we remove the dialog and set the dir
                    $("#ftp_directory").val($(this).data("directory"));
                    $dialog.dialog("close");
                } else { // We have already a dialog. We just update it content
                    $.get(href, function( data ) {
                        $dialog.html(data);
                    }).fail(function() {
                        alert("Request failed");
                    });
                }
            } else { // No dialog yet. We create one
                $.get(href, function(data) {
                    $dialog = $('<div id="ftp_choose_dir_dialog"/>').html(data).dialog({
                        hide: "blind",
                        show: "slide",
                        focus: false,
                        width: 650,
                        height: 650,
                        autoOpen: false,
                        modal: true,
                        title: js_i18n_tr_ftp_directories,
                        buttons: [{
                            text: js_i18n_tr_close, click: function () {
                                $(this).dialog("close");
                            }
                        }],
                        close: function () {
                            $(this).remove();
                        }
                    });

                    $(window).resize(function () {
                        $dialog.dialog("option", "position", { my: "center", at: "center", of: window });
                    });

                    $(window).scroll(function () {
                        $dialog.dialog("option", "position", { my: "center", at: "center", of: window });
                    });

                    $dialog.dialog("open");
                }).fail(function() {
                    alert("Request failed")
                });
            }

            e.preventDefault(); // Cancel default action (navigation) on the click
        });
    };

    // Fix bad jQuery UI behaviors
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

    // Initialize layout
    var initLayout = function (context) {
        initPageMessages();
        initTooltips(context);

        if (context == "simple") {
            $(".no_header #header").hide();
        } else {
            initTables();
            passwordGenerator();
            initFtpChooser();
        }

        initButtons(context);
    };

    $(function() {
        var context = "ui";
        if($('body').hasClass('simple')) {
            context = "simple"
        }

        initLayout(context);
        fixJqueryUI(context);
    });
})(jQuery);

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
