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
$.timer = function(loopTime,milSec,loopFn,endFn){
	var i = -1, t = setInterval(function(){
		if(i++ >= loopTime-1) return endFn(),clearInterval(t);
		else return(loopFn(i));
	},milSec);
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
		param.hasOwnProperty('text') && (el.innerHTML = param.text) && delete param.text;
		this[0].appendChild(el);
		if(param.hasOwnProperty('css') && ('object' == typeof param.css)){
			for(var i in param.css)	el.style[i] = param.css[i];
			delete param.css;
		}
		for(var i in param) el.setAttribute(i,param[i]);
	},
    remove: function() {
    	this[0] !== undefined && this[0].parentNode.removeChild(this[0]);
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
	},
    focus: function() {
		this[0].focus();
	},
    class: function(cmd,val) {
		this[0].classList[cmd](val);
	},
	toggle: function(key,val1,val2){
		this.css(key,this.css(key)==val1?val2:val1);
	}
};

window.onload = function(){
	caret();
	window.scrollTo(0,0);
	originKeyword = $('#search').val();
	$('body').on('keydown',function(e) {
		if((e.which||window.event.keyCode)==27) $('#search').val(''); $('#search').focus(); window.scrollTo(0,0); closeSideBar();
	});
	$('dl').on('click',function(){
		this.className != 'warning' && setSideBar(this.getElementsByTagName('dt')[0]);
	});
	$('form').on('submit',function(e){
		originKeyword == $('#search').val() && e.preventDefault();
	});
};

var originKeyword, height = parseInt(window.innerHeight),
topScroll = parseInt(window.pageYOffset),
topPop = parseInt(topScroll+(height*0.35))
gap = [150,70,30,10];

submit = function(){
	$('form')[0].submit();
},
selectCat = function(el){
	var prevSel = $('#cat').val();
	if(el.id == "year" || el.id == "lang") return false;
	
	$('#'+prevSel).class('remove','selCat');
	$(el).class('add','selCat');
	$('#cat').val(el.id);
	$('#'+prevSel).val(el.innerHTML);
	submit();	
},
caret = function(){
	setTimeout(
		function(){
			var a = $('#search')[0];
			a.focus();
			'number'==typeof a.selectionStart?a.selectionStart=a.selectionEnd=a.value.length:'undefined'!=typeof a.createTextRange&&(r=a.createTextRange(),r.collapse(!1),r.select())}
	,10);
},
strip = function(txt){
	return txt.replace(/(<([^>]+)>)/gi, "");
},
setSideBar = function(el){
	$('#header').text(strip(el.innerHTML));
	$('#additional').text(strip(el.parentNode.getElementsByClassName('info')[0].innerHTML));
	$('#location').text(strip(el.parentNode.getElementsByClassName('itemLoc')[0].innerHTML));
	$('#category').text(strip(el.parentNode.dataset.cat));
	$('#pubyear').text(strip(el.parentNode.dataset.year));
	$('#language').text(strip(el.parentNode.dataset.lang));
	if(screen.width < 767){
		$('body').css('overflow','hidden');
		$('body').append({
			'element':'div',
			'id':'popBase',
			'css': {
				'position':'absolute',
				'top':topScroll+'px',
				'left':0,
				'width':'100%',
				'height':'100%',
				'background':'rgba(0, 0, 0, 0.5)',
				'z-index':1
			}
		});
		$('#sideBar').css({
			'display'	:'block',
			'height'	:'100%',
			'top'		: (topPop+200)+'px'
		});
		$.timer(gap.length,40,
			function(i){
				$('#sideBar').css('top',(topPop+gap[i])+'px');
			},
			function(){
				$('#sideBar').css('top',topPop+'px');
				$('#popBase').on('click',function(){
					closeSideBar();
				});
			}
		);
	}
},
closeSideBar = function(){
	if($('#popBase').length < 1) return false;
	gap.reverse();
	$.timer(gap.length,30,
		function(i){
			$('#sideBar').css('top',(topPop+gap[i])+'px');
		},
		function(){
			$('#sideBar').css('display','none');
			$('#popBase').remove();
			$('body').css('overflow','auto');
			gap.reverse();
		}
	);
}
