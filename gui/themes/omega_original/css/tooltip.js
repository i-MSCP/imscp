
function showTip(id, e) {
	x = (document.all) ? window.event.x + document.body.scrollLeft : e.pageX;
	y = (document.all) ? window.event.y + document.body.scrollTop  : e.pageY;

	tip = document.getElementById(id);

	tip.style.left = (x + 10) + "px";
	tip.style.top  = (y + 10) + "px";
	tip.style.display = "block";
}

function hideTip(id) {
	document.getElementById(id).style.display = "none";
}
