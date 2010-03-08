/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *  Used for replication support
 *
 * @version $Id: mootools_common.js 12905 2009-08-30 17:06:12Z lem9 $
 */

function divShowHideFunc(ahref, id) {
      $(ahref).addEvent('click', function() {
      if ($(id).getStyle('display')=="none")
	$(id).tween('display', 'block');
      else
	$(id).tween('display', 'none');
    });
}
