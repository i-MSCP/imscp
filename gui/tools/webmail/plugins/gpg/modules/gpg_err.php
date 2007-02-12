<?php


if (! empty($err))
{
	echo '<center>';
	echo '<table><tr><td>';
	echo '<font color="red">';

	echo _("There was a problem with your request");

	echo '<ul>';

        foreach ($err as $line)
		{
			echo '<li>' . htmlspecialchars($line) . '</li>';
        } 

	echo '</ul>';
	echo '</font>';
	echo '</td></tr></table>';
	echo '</center>';
}

if (! empty($info))
{
	echo _("Your request returned this information:");
	echo '<br>';

	foreach ($info as $line)
	{
		echo '<pre>' . htmlspecialchars($line) . '</pre>';
	}

}

?>
