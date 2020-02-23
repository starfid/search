var $ = function(expr, context) {
	return new $.init(expr, context);
};
$.ajax = function(id,url,fn) {
	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.async = true;
	script.src = url;
	script.setAttribute('id',id);
	var newElement = document.getElementsByTagName('script')[0];
	newElement.parentNode.insertBefore(script,newElement);
	$('#'+id).on('load',fn);
};
$.init = function(expr,context) {
	if(expr.nodeName && !context) this[0] = expr;
	else {
		try { var el = (context||document)['querySelectorAll'](expr); }
		catch(err){ var el = expr; }
		[].push.apply(this,[].slice.call(el));
	}
};
$.init.prototype = {
	each: function(el) {
		for (var l=0;l<this.length;l++) el(this[l]);
	},
	css: function(prop, val) {
		if ('object' == typeof prop) this.each(function(val) {
			for(var i in prop) val.style[i] = prop[i];
		});
		else {
			prop = prop.replace(/-([a-z])/g, function (g) { return g[1].toUpperCase(); });
			var el = this[0], lt = /(left||top)/.test(prop);
			if(arguments.length>1) return 1 < this.length ? this.each(function(o){o.style[prop] = val;}) : el.style[prop] = val,"";
			else return el.style[prop]==''?(
				el.currentStyle && !lt?
				el.currentStyle[prop]:(lt?
					Math.round(el.getBoundingClientRect()[prop]):
					document.defaultView.getComputedStyle(el, null).getPropertyValue(prop)
				)
			):el.style[prop];
		}
	},
	on: function(evt, fn) {
		for (var l = this.length; l--;) {
			var el = this[l];
			el.addEventListener ? el.addEventListener(evt, fn, !1) : el.attachEvent("on" + evt, function() {
				return fn.call(el, window.event);
			});
		}
	},
	append: function(param) {
		var el = document.createElement(param.element||'div');
		param.hasOwnProperty('element') && delete param.element;
		el.innerHTML = param.hasOwnProperty('text')?param.text:null;
		this[0].appendChild(el);
		for(var i in param) el.setAttribute(i,param[i]);
	},
	remove: function() {
		this[0].parentNode.removeChild(this[0]);
	},
	attr: function(key,val) {
		var el = this[0];
		return val ? (el.setAttribute(key, val), '') : el.getAttribute(key);
	},
    val: function(dat){
	    var el = this[0];
	    return arguments.length ? (el.value = dat, '') : el.value;
	},
	text: function(dat){
	    var el = this[0];
	    return arguments.length ? (el.innerHTML = dat, '') : el.innerHTML;
	}
};

window.onload = function(){
	setTimeout(
		function(){
			var a = document.getElementById('search');
			a.focus();
			"number"==typeof a.selectionStart?a.selectionStart=a.selectionEnd=a.value.length:"undefined"!=typeof a.createTextRange&&(r=a.createTextRange(),r.collapse(!1),r.select())}
	,1);
	$('#search').on('keydown',function(e){
		27 == (e.which||window.event.keyCode ) && $('#search').val('');
		
	});
};

var submit = function(){
	$('form')[0].submit();
},
show = function(){
	$('#search').val(
		$('#samples').val()
	);
	submit();
},
add = function(cat){
	var cats = [], placeholder = $('#search').val();
	if(placeholder=='') return false;

	var firstWord = placeholder.replace(/ .*/,'');
	$('.cat').each(function(li){
		cats.push($(li).text().toLowerCase());
	});
	cats.indexOf(firstWord)>-1 && $('#search').val(placeholder.substr(placeholder.indexOf(' ') + 1));

	$('#search').val(cat + ' ' + $('#search').val());
	submit();
};