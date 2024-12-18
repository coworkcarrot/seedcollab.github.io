
    window.addEventListener('DOMContentLoaded', function(){
        let cursor = document.querySelector(".cursor"),
            cursor_x = 0,
            cursor_y = 0,
            current_cursor_x = 0,
            current_cursor_y = 0,
            acc_cusor = 0.11,
            cursor_afId,
            cursor_afState = false,
            open_menu = document.querySelector(".btn-open-gnb"),
            close_menu = document.querySelector(".btn-close-gnb"),
            menubg = document.querySelector(".menuAnimationLayer"),
            menu = document.querySelector(".menuLayer"),
            open_search = document.querySelector(".btn-open-search"),
            topSearch = document.querySelector(".top_search"),
            tl_menu = new TimelineMax;

        document.addEventListener("mousemove",function(e){
            // TweenMax.to(cursor,0.3,{"left":e.clientX,"top":e.clientY,ease:Power1.easeOut});
            cursor_x = e.clientX;
            cursor_y = e.clientY;

            if(!cursor_afState){
                cursor_afId = requestAnimationFrame(cursorMove);
                cursor_afState = true;
            }
        })

        open_menu.addEventListener("click",function(){
            tl_menu
            .to(menubg,1,{scale:6000,ease:Power1.easeOut})
            .fromTo(menu,0.3,{display:"block",y:10,autoAlpha:0},{y:0, autoAlpha:1, ease:Power1.easeOut},"-=0.5");
        });

        close_menu.addEventListener("click",function(){
            TweenMax.to(menubg,0.8,{scale:1,ease:Power1.easeInOut});
            TweenMax.to(menu,0.5,{y:-10, autoAlpha:0, ease:Power1.easeInOut, onComplete:function(){
                menu.style.display = "none";
            }});
        });

        open_search.addEventListener("click",function(e){
            e.preventDefault();
            this.classList.toggle("on");
            topSearch.classList.toggle("on");
        });

        function cursorMove(){
            current_cursor_x = current_cursor_x + (cursor_x - current_cursor_x) * acc_cusor;
            current_cursor_y = current_cursor_y + (cursor_y - current_cursor_y) * acc_cusor;

            cursor.setAttribute("style","top:"+current_cursor_y+"px; left:"+current_cursor_x+"px;");

            cursor_afId = requestAnimationFrame(cursorMove);

            if(Math.abs(cursor_x - current_cursor_x)<1 && Math.abs(cursor_y - current_cursor_y)){
                cancelAnimationFrame(cursor_afId);
                cursor_afState = false;
            }
        }


            cursorMove();

    });

												   
function numberWithCommas(x) {
	return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
												   
window.addEventListener('load', function() {
    //closeLoading();
    setTimeout(hero_animation,1000);
});

function closeLoading(){
    TweenMax.to(".loading",0.5,{autoAlpha:0, delay:0.5, onComplete:function(){
        document.querySelector(".loading").remove();
    }});
}
function openLoading(){
    let loading = document.createElement("div");
    loading.innerHTML = "<div class='lds-dual-ring'></div>";
    loading.classList.add("loading");
    document.querySelector(".wrapper").append(loading);
}

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
    .to(top_pic,0.5,{x:0, opacity:0.73, ease:Power1.easeInOut, onComplete:contentShow},"-=0.35");
}
												   
function contentShow(){
	let cont_ = document.querySelector(".cont");
	if(cont_ != null){
		TweenMax.fromTo(cont_,0.8,{autoAlpha:0, y:30},{autoAlpha:1, y:0});
	}
}