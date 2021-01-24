var $ = function(expr, context) {
	return new $.init(expr, context);
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
	},
	
};

window.onload = function(){
	originKeyword = $('#search').val();
	originKeyword != '' && caret();
	$('body').on('keydown',function(e) {
		if((e.which||window.event.keyCode)==27) $('#search').val(''); $('#search').focus(); window.scrollTo(0,0); closeSideBar();
	});
	$('#magnify').on('click',function(){
		submitting();
	});
	$('#tools').on('click',function(){
		if($('#toolbar > select').length<1) return false;
		$('#toolbar').toggle('display','block','none');
		$('#benchmark').toggle('display','none','block');
		if($('#year').length==1 && screen.width > 768){
			$('figure').toggle('display','block','none');
		}
	});
	$('form').on('submit',function(e){
		submitting();
	});
	$('dl').on('click',function(){
		this.className != 'warning' && setSideBar(this); //setSideBar(this.getElementsByTagName('dt')[0]);
	});
	$('#year,#lang').on('change',function(){
		toolbar(this);
	});
	($('#campaign').length == 1) && $('#search').on('keydown',function(){
		($('#campaign').length == 1) && $('#campaign').remove() && $('#catswrap').css('display','block');
	});
	$('html,body,#wrapper').css('height','100%');
	if($('text').length>0){
		$('text').on('mouseover',function(){
			linemov(this);
		});
		$('text').on('mouseout',function(){
			linemot();
		});
		$('text').on('click',function(){
			lineclick(this);
		});
	}
};

var originKeyword, gap = [70,30,10], fromCat = !!0;
submitting = function(){
	window.event.preventDefault();
	!fromCat && $('#cat').val('all');
	!(originKeyword == $('#search').val() && !fromCat) && $('form')[0].submit();
},
selectCat = function(el){
	var prevSel = $('#cat').val();
	if(el.id == "tools") return false;	
	$('#'+prevSel).class('remove','selCat');
	$(el).class('add','selCat');
	$('#cat').val(el.id);
	$('#'+prevSel).val(el.innerHTML);
	fromCat = !0;
	submitting();
},
caret = function(){
	setTimeout(
		function(){
			var a = $('#search')[0];
			a.focus();
			'number'==typeof a.selectionStart?a.selectionStart=a.selectionEnd=a.value.length:'undefined'!=typeof a.createTextRange&&(r=a.createTextRange(),r.collapse(!1),r.select())}
	,100);
},
strip = function(txt){
	return txt.replace(/(<([^>]+)>)/gi, "");
},
setSideBar = function(el){
	$('#header').text(strip($('dt',el).text()));
	$('#additional').text(strip($('.info',el).text()));
	$('#location').text(strip($('.itemLoc',el).text()));
	$('#category').text(strip($(el).attr('data-cat')));
	$('#pubyear').text(strip($(el).attr('data-year')));
	$('#language').text(strip($(el).attr('data-lang')));
	if(screen.width < 767){
		document.addEventListener('touchmove', preventTouch, { passive: false });
		$('body').css('overflow','hidden');
		var scrollY = window.scrollY, topScroll = parseInt(window.pageYOffset), topPop = parseInt(topScroll+(parseInt(window.innerHeight)*0.35))
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
preventTouch = function(e) {
    e.preventDefault();
}
closeSideBar = function(){
	if($('#popBase').length < 1) return false;
	var topScroll = parseInt(window.pageYOffset), topPop = parseInt(topScroll+(parseInt(window.innerHeight)*0.35))
	gap.reverse();
	$.timer(gap.length,30,
		function(i){
			$('#sideBar').css('top',(topPop+gap[i])+'px');
		},
		function(){
			$('#sideBar').css('display','none');
			$('#popBase').remove();
			gap.reverse();
			document.removeEventListener('touchmove', preventTouch, false);
			$('body').css('overflow','auto');
		}
	);
},
toolbar = function(el){
	var id = el.id, val = el.value.toLowerCase(), first = true;
	$('#results dl').each(function(el){
		$(el).css('display',
			($(el).attr('data-'+id) == val) || val == '' ?'block':'none'
		);
		if(first && $(el).css('display')=='block'){
			setSideBar(el);
			first = false;
		}
	});
	if($('#'+  (id=='year'?'lang':'year') +' option').length>0){
		$('#'+  (id=='year'?'lang':'year') +' option')[0].selected = true;
	}
},
linemov = function(el){
	if($('#hover').length>0) return false;
	txt = el.textContent;
	$('figure').append({
		'element':'div',
		'text':txt,
		'id':'hover',
		'css':{
			'left':(window.event.clientX+7)+'px',
			'top':(window.event.clientY-10)+'px'
		}
	});
},
linemot = function(){
	$('#hover').remove();
},
lineclick = function(el){
	if($('#lang option') > 0) $('#lang option')[0].selected = true;
	var txt = el.textContent, first = true;
	$('#results dl').each(function(el){
		$(el).css('display',
			($(el).attr('data-year') == txt) || el.value == '' ?'block':'none'
		);
		if(first && $(el).css('display')=='block'){
			setSideBar(el);
			first = false;
		}
	});
}
