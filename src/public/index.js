var myform = document.getElementById("ahkform");
var myarea = document.getElementById("ahkarea");
var myedit = document.getElementById("ahkedit");
myarea.style.display = "none";
myedit.style.display = "block";

// Set up ace.js editor
var editor = ace.edit("ahkedit");
editor.setTheme("ace/theme/idle_fingers");
editor.setHighlightActiveLine(false);
editor.setShowPrintMargin(false);

// Enable visible whitespace
editor.renderer.$textLayer.EOL_CHAR_CRLF = " ";
editor.renderer.$textLayer.EOF_CHAR = " ";
editor.setShowInvisibles(true);

// Inherit readonly from textarea
if (myarea.hasAttribute("readonly")) {
	editor.setReadOnly(true);
}

// Fill in AHK paste code
var session = editor.getSession();
session.setMode("ace/mode/autohotkey");
session.setNewLineMode("windows");
session.setUseSoftTabs(false);
session.setValue(myarea.value);

// Alternating line background colors
// Note: hackier than defcon, only works for first 1k lines
var range_proto = editor.getSelectionRange().__proto__
for (i=1; i < 1000; i += 2) {
	var myline = Object.create(range_proto);
	myline.start = {row: i, column: -1};
	myline.end = {row: i, column: Infinity};
	session.addMarker(myline, "ace_alternating-lines", "screenLine");
}

// Copy ace.js content to textarea before form submission
myform.onsubmit = function() {
	myarea.value = session.getValue()
};
