<?php
function ParseBookmarkString( $sBookmark, $delimiter, $nSegment ) {

	if ($nSegment > 0) {
		$explode = explode( $delimiter, $sBookmark );
		return ($explode[ $nSegment - 1 ]);
	} elseif ($nSegment < 0) {
		if (strpos( $sBookmark, $delimiter ) === false ) {
			return ('');
		} else {
			return (substr($sBookmark,0,strrpos($sBookmark, $delimiter)));
		}
	} else {
		if (strpos( $sBookmark, $delimiter ) === false ) {
			return ($sBookmark);
		} else {
			return (substr($sBookmark,strrpos('|' . $sBookmark, $delimiter)));
		}
	}
}

function CookieTrail( $sString, $nLinks ) {

	return (str_replace( '|', ' -> ', $sString ));

}

?>