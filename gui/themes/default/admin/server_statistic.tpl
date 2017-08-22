<script>
    $(function() {
       $("#day, #month, #year").on("change", function() {
           $(this).closest("form").submit();
           return false;
       });
    });
</script>
<form>
    <label for="month">{TR_DAY}</label>
    <select name="day" id="day">
        <!-- BDP: day_list -->
        <option value="{VALUE}"{OPTION_SELECTED}>{HUMAN_VALUE}</option>
        <!-- EDP: day_list -->
    </select>
    <label for="month">{TR_MONTH}</label>
    <select name="month" id="month">
        <!-- BDP: month_list -->
        <option{OPTION_SELECTED}>{MONTH_VALUE}</option>
        <!-- EDP: month_list -->
    </select>
    <label for="year">{TR_YEAR}</label>
    <select name="year" id="year">
        <!-- BDP: year_list -->
        <option{OPTION_SELECTED}>{YEAR_VALUE}</option>
        <!-- EDP: year_list -->
    </select>
</form>
<!-- BDP: server_stats_by_month -->
<table>
    <thead>
    <tr>
        <th>{TR_DAY}</th>
        <th>{TR_WEB_IN}</th>
        <th>{TR_WEB_OUT}</th>
        <th>{TR_SMTP_IN}</th>
        <th>{TR_SMTP_OUT}</th>
        <th>{TR_POP_IN}</th>
        <th>{TR_POP_OUT}</th>
        <th>{TR_OTHER_IN}</th>
        <th>{TR_OTHER_OUT}</th>
        <th>{TR_ALL_IN}</th>
        <th>{TR_ALL_OUT}</th>
        <th>{TR_ALL}</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <td>{TR_ALL}</td>
        <td>{WEB_IN_ALL}</td>
        <td>{WEB_OUT_ALL}</td>
        <td>{SMTP_IN_ALL}</td>
        <td>{SMTP_OUT_ALL}</td>
        <td>{POP_IN_ALL}</td>
        <td>{POP_OUT_ALL}</td>
        <td>{OTHER_IN_ALL}</td>
        <td>{OTHER_OUT_ALL}</td>
        <td>{ALL_IN_ALL}</td>
        <td>{ALL_OUT_ALL}</td>
        <td>{ALL_ALL}</td>
    </tr>
    </tfoot>
    <tbody>
    <!-- BDP: server_stats_day -->
    <tr>
        <td>{DAY}</td>
        <td>{WEB_IN}</td>
        <td>{WEB_OUT}</td>
        <td>{SMTP_IN}</td>
        <td>{SMTP_OUT}</td>
        <td>{POP_IN}</td>
        <td>{POP_OUT}</td>
        <td>{OTHER_IN}</td>
        <td>{OTHER_OUT}</td>
        <td>{ALL_IN}</td>
        <td>{ALL_OUT}</td>
        <td>{ALL}</td>
    </tr>
    <!-- EDP: server_stats_day -->
    </tbody>
</table>
<!-- EDP: server_stats_by_month -->
<!-- BDP: server_stats_by_day -->
<table>
    <thead>
    <tr>
        <th>{TR_HOUR}</th>
        <th>{TR_WEB_IN}</th>
        <th>{TR_WEB_OUT}</th>
        <th>{TR_SMTP_IN}</th>
        <th>{TR_SMTP_OUT}</th>
        <th>{TR_POP_IN}</th>
        <th>{TR_POP_OUT}</th>
        <th>{TR_OTHER_IN}</th>
        <th>{TR_OTHER_OUT}</th>
        <th>{TR_ALL_IN}</th>
        <th>{TR_ALL_OUT}</th>
        <th>{TR_ALL}</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <td>{TR_ALL}</td>
        <td>{WEB_IN_ALL}</td>
        <td>{WEB_OUT_ALL}</td>
        <td>{SMTP_IN_ALL}</td>
        <td>{SMTP_OUT_ALL}</td>
        <td>{POP_IN_ALL}</td>
        <td>{POP_OUT_ALL}</td>
        <td>{OTHER_IN_ALL}</td>
        <td>{OTHER_OUT_ALL}</td>
        <td>{ALL_IN_ALL}</td>
        <td>{ALL_OUT_ALL}</td>
        <td>{ALL_ALL}</td>
    </tr>
    </tfoot>
    <tbody>
    <!-- BDP: server_stats_hour -->
    <tr>
        <td>{HOUR}</td>
        <td>{WEB_IN}</td>
        <td>{WEB_OUT}</td>
        <td>{SMTP_IN}</td>
        <td>{SMTP_OUT}</td>
        <td>{POP_IN}</td>
        <td>{POP_OUT}</td>
        <td>{OTHER_IN}</td>
        <td>{OTHER_OUT}</td>
        <td>{ALL_IN}</td>
        <td>{ALL_OUT}</td>
        <td>{ALL}</td>
    </tr>
    <!-- EDP: server_stats_hour -->
    </tbody>
</table>
<!-- EDP: server_stats_by_day -->
