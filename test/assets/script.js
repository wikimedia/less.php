
$(function(){

	$('#options input').on('change',function(){
		var $this = $(this);
		if( $this.prop('checked') ){
			createCookie($this.attr('name'),1);
		}else{
			createCookie($this.attr('name'),0);
		}
	});


	function createCookie(name,value,days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
	}

	function readCookie(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
		}
		return null;
	}

	function eraseCookie(name) {
		createCookie(name,"",-1);
	}

});


function diffUsingJS(viewType) {
	"use strict";

	var byId = function (id) { return document.getElementById(id); },
		base = difflib.stringAsLines(byId("lessphp_textarea").value),
		newtxt = difflib.stringAsLines(byId("lessjs_textarea").value),
		sm = new difflib.SequenceMatcher(base, newtxt),
		opcodes = sm.get_opcodes(),
		diffoutputdiv = byId("diffoutput");
		//contextSize = byId("contextSize").value;

	diffoutputdiv.innerHTML = "";
	//contextSize = contextSize || null;

	diffoutputdiv.appendChild(diffview.buildView({
		baseTextLines: base,
		newTextLines: newtxt,
		opcodes: opcodes,
		baseTextName: "Less.php",
		newTextName: "Less.js",
		//contextSize: contextSize,
		viewType: viewType
	}));
}