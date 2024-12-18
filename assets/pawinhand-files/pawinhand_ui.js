window.addEventListener('DOMContentLoaded', function(){
    //hero_animation();

	var controller_common = new ScrollMagic.Controller();
	$("._fadeIn").each(function(){
		var $fadeIn = $(this),
		tl_fadein_ = new TimelineMax();

		tl_fadein_
		.from($fadeIn, 1, {autoAlpha:0, top:50, ease:Power4.easeOut});

		var scene_fadeIn = new ScrollMagic.Scene({
			triggerElement: this,
			triggerHook: 0.5,
			reverse: false
		})
		.setTween(tl_fadein_)
		.addTo(controller_common);
	})
});
/*
function hero_animation(){
    if(document.querySelector(".pageTop") == null) return;
    let top_rightBar = document.querySelector(".pageTop .rightBar");
    let top_rightBarImg = top_rightBar.querySelector("img");
    let top_leftBar = document.querySelector(".pageTop .leftBar");
    let top_tit = document.querySelector(".pageTop .tit h2");
    let top_txt = document.querySelector(".pageTop .tit p");
    let top_pic = document.querySelector(".pageTop .pic");

    var tl_top = new TimelineMax();

    tl_top
    .to(top_rightBar,0.8,{x:0, ease:Power1.easeOut})
    .to(top_leftBar,0.8,{x:0, ease:Power1.easeOut},0)
    .to(top_rightBarImg,0.5,{opacity:1},"-=0.2")
    .to(top_tit,0.5,{x:0,opacity:1, ease:Power1.easeInOut},"-=0.1")
    .to(top_txt,0.5,{x:0,opacity:1, ease:Power1.easeInOut},"-=0.5")
    .to(top_pic,0.5,{x:0, opacity:0.73, ease:Power1.easeInOut},"-=0.35");
}
*/