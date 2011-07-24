<!-- BDP: purchase_header -->
<!-- EDP: purchase_header -->
<table style="width:550px;">
    <tr>
        <th colspan="2">Hosting Plans Availables</th>
    </tr>
    <tr style="background:none;border: none;">
        <td colspan="2">&nbsp;</td>
    </tr>
    <!-- BDP: purchase_list -->
    <tr>
        <td>
            <a href="package_info.php?id={PACK_ID}&amp;user_id={USER_ID}"><strong>{PACK_NAME}</strong></a> {PRICE}
        </td>
        <td>
            <a href="addon.php?id={PACK_ID}&amp;user_id={USER_ID}">{PURCHASE}</a>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <div>{PACK_INFO}</div>
        </td>
    </tr>
    <tr style="border: none;background: none;">
        <td colspan="2" style="border: none;">
            &nbsp;
        </td>
    </tr>
    <!-- EDP: purchase_list -->
</table>
<!-- BDP: purchase_footer -->
<!-- EDP: purchase_footer -->
