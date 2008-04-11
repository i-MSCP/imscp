<?php
/**
 * $Id: gpg_err.php,v 1.7 2005/11/09 14:17:12 brian Exp $
 */

if (is_array($err) and count($err))
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

if (is_array($info) and count($info))
{
    echo _("Your request returned this information:");
    echo '<br>';

    foreach ($info as $line)
    {
        echo '<pre>' . htmlspecialchars($line) . '</pre>';
    }

}

/**
 * $Log: gpg_err.php,v $
 * Revision 1.7  2005/11/09 14:17:12  brian
 * - changed to use is_array and count instead of empty, as empty may return 1 when not set
 * Bug 228
 *
 */
?>