//<![CDATA[
    function get_path()
    {
        if (document.location.pathname != undefined) {
            return document.location.pathname.replace( /[<]/g, "&lt;").replace(/[>]/g, "&gt;");
        } else {
            return "&nbsp;";
        }
    }
//]]>