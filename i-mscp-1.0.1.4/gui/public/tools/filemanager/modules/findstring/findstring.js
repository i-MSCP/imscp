
function submitFindstringForm(directory, entry, state, state2, screen) {
	document.forms['FindstringForm'].directory.value=directory;
	document.forms['FindstringForm'].entry.value=entry;
	document.forms['FindstringForm'].state.value=state;
	document.forms['FindstringForm'].state2.value=state2;
	document.forms['FindstringForm'].screen.value=screen;
	document.forms['FindstringForm'].submit();
}