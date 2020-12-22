window.onload = function(){
	caret();
	document.getElementById('search').addEventListener('focus',function() {		
		caret();
	});
	document.getElementById('search').addEventListener('keydown',function(e) {
		window.scrollTo(0,0);
		if((e.which||window.event.keyCode)==27) document.getElementById('search').value = '';
	});
};

var submit = function(){
	document.getElementsByTagName('form')[0].submit();
},
selectCat = function(el){
	var prevSel = document.getElementById('cat');
	document.getElementById(prevSel.value).classList.remove('selCat');
	el.classList.add('selCat');
	prevSel.value = el.innerHTML.toLowerCase();
	submit();
},
caret = function(){
	setTimeout(
		function(){
			var a = document.getElementById('search');
			a.focus();
			'number'==typeof a.selectionStart?a.selectionStart=a.selectionEnd=a.value.length:'undefined'!=typeof a.createTextRange&&(r=a.createTextRange(),r.collapse(!1),r.select())}
	,1);
}
