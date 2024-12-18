let wheelState = true;
window.addEventListener('DOMContentLoaded', function(){
    let x_posi = 0,
        y_posi = 0,
        x_current = -50,
        y_current = -50,
        masksize = 50,
        size_current = 0,
        acc = 0.11,
        acc2 = 0.15,
        afId,
        maskLayer = document.querySelectorAll(".maskLayer"),
        afState = false,
        stageNo = 1,
        mainPage = document.querySelector(".mainContents"),
        page = mainPage.querySelectorAll(".page"),
        scrollBar = document.querySelector(".mainScrollBar .thumb");

	window.addEventListener('load', function() {
		//closeLoading();
	});

    // mouse move
    document.addEventListener("mousemove",function(e){
        x_posi = e.pageX;
        y_posi = e.pageY;

        if(!afState){
            afId = requestAnimationFrame(calEase);
            afState = true;
        }
    });

    document.querySelector(".section01 .hoverLayer").addEventListener("mouseenter",function(){
        masksize = 250;
    });
    document.querySelector(".section01 .hoverLayer").addEventListener("mouseleave",function(){
        masksize = 50;
    });
	document.querySelector(".section02").addEventListener("mouseenter",function(){
        masksize = 250;
    });
    document.querySelector(".campaigns").addEventListener("mouseenter",function(){
        masksize = 250;
    });
    document.querySelector(".maskLayer .campaigns").addEventListener("mouseleave",function(){
        masksize = 50;
    });
	document.querySelector(".section05, .section06").addEventListener("mouseleave",function(){
        masksize = 50;
    });
    let productList = document.querySelectorAll(".section04 .main-product-list a");
    for(let i=0; i<productList.length; i++){
        productList[i].addEventListener("mouseenter",function(){
            masksize = 100;
        });
    }
    let productList2 = document.querySelectorAll(".section04 .maskLayer .main-product-list a");
    for(let i=0; i<productList2.length; i++){
        productList2[i].addEventListener("mouseleave",function(){
            masksize = 50;
        });
    }

    // mask
    function calEase(){
        x_current = x_current + (x_posi - x_current) * acc;
        y_current = y_current + (y_posi - y_current) * acc;
        size_current = size_current + (masksize - size_current) * acc2;

        for(let i=0; i<maskLayer.length; i++){
            maskLayer[i].setAttribute("style","--mask-size:"+size_current+"px; --mask-x:"+x_current+"px; --mask-y:"+y_current+"px;");
        }

        afId = requestAnimationFrame(calEase);

        if(Math.abs(x_posi - x_current)<1 && Math.abs(y_posi - y_current)){
            cancelAnimationFrame(afId);
            afState = false;
        }
    };

	let tl_cover = new TimelineMax();
    let pageCover = document.querySelector(".page_cover");
    let currentEle = document.querySelector(".active_scene");
    let startPosi = 0;
    
    // wheel event
    window.addEventListener("wheel",function(e){
        if(wheelState){
			maskSize = 50;
            wheelState = false;
            if(e.deltaY < 0){ //wheel up
                if(stageNo > 1){
                    --stageNo;
                    startPosi = -5;
                    pageCover.style.transformOrigin = "top left";   
                    sliderPage();
                }else{
                    wheelState = true;
                }
            }else{ //wheel down
                if(stageNo < page.length){
                    ++stageNo;
                    startPosi = 5;
                    pageCover.style.transformOrigin = "bottom left";   
                    sliderPage();
                }else{
                    wheelState = true;
                }
            }
        }
    });

    function sliderPage(){
        currentEle.classList.remove("active_scene");
        currentEle.classList.add("preEle");
        currentEle = document.querySelector(".section0"+stageNo);
        currentEle.classList.add("active_scene");
        let style_ = getComputedStyle(currentEle);
        pageCover.style.backgroundColor = style_.backgroundColor;   

        tl_cover
        .to(".preEle",0.5,{y:(startPosi*-1)+"%", ease:Power2.easeInOut})
        .fromTo(pageCover,0.5,{scaleY:"0"},{scaleY:"1", ease:Power2.easeInOut},"-=0.5")
        .fromTo(".active_scene",0.8,{y:startPosi+"%", opacity:0}, {y:"0%", opacity:1, ease:Power2.easeOut,onComplete:function(){
            document.querySelector(".preEle").classList.remove("preEle");
            wheelState = true;
        }})
        scrollBar.style.top = ((100/page.length)*(stageNo-1))+"%";
										  
		switch(stageNo){
            //case 2 : countNum("aniCount",val_total); break;
            case 4 : countNum("donCount",val_donation); break;
			default : stopCount();
        }
    }
    // top으로 이동
    document.querySelector(".main_gotoTop").addEventListener("click",function(e){
        e.preventDefault();
        scrollBar.style.top = 0+"px";
        stageNo = 1;
        startPosi = -5;
        pageCover.style.transformOrigin = "top left";   
        sliderPage();
    });

    pageCover.addEventListener('transitionend', function(e){
        wheelState = true;
        switch(stageNo){
            case 2 : masksize = 250; break;
            default : masksize = 50;
        }
    });
										  

})

let completeCount = 0;
let val_donation = 0;
let val_total = 0;
let countTimer;
let current = 0;
let endNum = 0;

function countNum(target,total){
    let _target = document.querySelectorAll("."+target);
    let count = numberWithCommas(total).split(",");
    let txt = "";
    let lastTxt = 0;
    endNum = count[count.length - 1];

    if(count.length > 1){
        for(let i=0; i<count.length-1; i++){
            txt += count[i]+","
        }
    }

    function counting(){
        let display_num = 0;
        if(endNum>150){
            current+=10;
            lastTxt = endNum % 10;
        }else{
            current++;
        }

        display_num = current+lastTxt;
        if(count.length>1){
            if(display_num<100){
                display_num = "0"+display_num;
            }
        }

        for(let i=0; i<_target.length; i++){
            _target[i].innerHTML = txt+display_num;
        }
        
        if((current+lastTxt)>=endNum){
            stopCount();
        }
    }

    countTimer = setInterval(counting,30);
}

function stopCount(){
    clearInterval(countTimer);
    current = 0;
}

function completeLoading(){
	if(completeCount < 1){
		++completeCount;
	}else{
		closeLoading();
	}
}
				
function get_APIdata() {
    var user = "pawinhand_web";
    var pw = "777111";
    var url_ = "https://pawinhand.net/animals/today/count";

    $.ajax({
        url : url_,
        method : 'GET',
        dataType:'JSON',
        async: true,
        crossDomain: true,
        username:user,
        password:pw,      
        headers: {
            "Access-Control-Allow-Origin" : "*",
            "Access-Control-Allow-Methods" : "GET,POST,PUT,DELETE,OPTIONS",
            // "Access-Control-Max-Age" : "3600",
            "Access-Control-Allow-Headers" : "Authorization,Origin,Accept,X-Requested-With,Content-Type,Access-Control-Request-Method,Access-Control-Request-Headers",
            "Accept" : "application/json; charset=utf-8",
            "Content-Type": "application/javascript; charset=utf-8",
            "X-Content-Type-Options": "nosniff",
            "X-Application-Context": "application:5000",
            "X-Frame-Options":"DENY"
        }, 
        beforeSend : function(req) {
            req.setRequestHeader('Authorization', "Basic " + btoa(user+":"+pw)); 
        },
        success: function(data) {
			//document.querySelector(".data-donation").innerText = numberWithCommas(data.donation_amount);
			//document.querySelector(".data-donation1").innerText = numberWithCommas(data.donation_amount);
			val_donation = data.donation_amount;
			val_total = data.adopt_story_count;
			document.querySelector(".data-rescue").innerText = data.today_count;
			document.querySelector(".data-adoptaion").innerText = Math.round(data.adoption_rate);
			document.querySelector(".data-euthanasis").innerText = Math.round(data.euth_rate);
			console.log('api1');
			completeLoading();
        },
        error: function(req) {
            console.log("error",req)
        }
    });
}

get_APIdata();

function show_story(){
    let user = "pawinhand_web",
		pw = "777111",
		offset = 0,
		limit_offset = 4,
		_type = "입양/재회 후기", // 후기
	    _sort = "story_idx", // idx
		url_ = 'https://pawinhand.net/stories?type=' + encodeURIComponent(_type) + '&sort=' + encodeURIComponent(_sort) + '&offset=' + offset + '&limit='+limit_offset;
									  
    $.ajax({
        url : url_,
        method : 'GET',
        dataType:'JSON',
        async: true,
        crossDomain: true,
        username:user,
        password:pw,      
        headers: {
            "Access-Control-Allow-Origin" : "*",
            "Access-Control-Allow-Methods" : "GET,POST,PUT,DELETE,OPTIONS",
            // "Access-Control-Max-Age" : "3600",
            "Access-Control-Allow-Headers" : "Authorization,Origin,Accept,X-Requested-With,Content-Type,Access-Control-Request-Method,Access-Control-Request-Headers",
            "Accept" : "application/json; charset=utf-8",
            "Content-Type": "application/javascript; charset=utf-8",
            "X-Content-Type-Options": "nosniff",
            "X-Application-Context": "application:5000",
            "X-Frame-Options":"DENY"
        }, 
        beforeSend : function(req) {
            req.setRequestHeader('Authorization', "Basic " + btoa(user+":"+pw)); 
        },
        success: function(data) {
			for(let i=0; i<data.length; i++){
				let imgPath = data[i].story_image1;
				if(data[i].story_image2 != undefined){
					imgPath += ","+data[i].story_image2;
				}
				if(data[i].story_image3 != undefined){
					imgPath += ","+data[i].story_image3;
				}
				let list_item1 = document.createElement("li");
				let list_item2 = document.createElement("li");
				list_item1.innerHTML = "<div class='thumbBox'><img src='http://d12l2mexpetzlh.cloudfront.net/images/"+data[i].story_image1+"' alt=''></div><div class='desc'><strong class='ellipsis'>"+data[i].story_title+"</strong><p>"+data[i].story_contents1+"</p></div><div class='imgPath'>"+imgPath+"</div>";
				list_item2.innerHTML = "<div class='thumbBox'><img src='http://d12l2mexpetzlh.cloudfront.net/images/"+data[i].story_image1+"' alt=''></div><div class='desc'><strong class='ellipsis'>"+data[i].story_title+"</strong><p>"+data[i].story_contents1+"</p></div><div class='imgPath'>"+imgPath+"</div>";
				
				$(".main_review_list01").append(list_item1);
				$(".main_review_list02").append(list_item2);
			}
			completeLoading();
			// 입양후기 상세 팝업
			let owl = $('.owl-carousel');
			let $detailReview = $(".detailReview");
			let $loading = $detailReview.find(".popupLoading");

			$(".maskLayer .main-postscript-list li").on("click",function(){
				wheelState = false;
				let descCont = $(this).find(".desc").clone();
				let imgs = $(this).find(".imgPath").text().split(",");
				$detailReview.find(".desc").html(descCont);
				for(let i=0; i<imgs.length; i++){
					$detailReview.find(".owl-carousel").append("<div class='item'><div class='thumbBox'><img src='http://d12l2mexpetzlh.cloudfront.net/images/"+imgs[i]+"' alt=''></div></div>");
				}
				owl.owlCarousel({
					nav:true,
					dots:false,
					items:1,
					onRefreshed:function(){
						if(window.innerHeight<$detailReview.find(".innerLayer").outerHeight()){
							$detailReview.addClass("scroll-box");
						}
						$loading.hide();
						TweenMax.fromTo(".innerLayer",0.5,{y:10, autoAlpha:0},{y:0, autoAlpha:1});
					}
				})
				$("body").addClass("scrollHidden");
				$detailReview.css("display","flex");
				$loading.show();
				$detailReview.find(".innerLayer").css("opacity",0);
			})

			$(".close_reviewLayer").on("click",function(){
				owl.trigger("destroy.owl.carousel");
				$detailReview.find(".owl-carousel").html("");
				$detailReview.css("display","none");
				$detailReview.removeClass("scroll-box");
				$("body").removeClass("scrollHidden");
				wheelState = true;
			})

			$(window).on("resize",function(){
				if(window.innerHeight<$detailReview.find(".innerLayer").outerHeight()){
					$detailReview.addClass("scroll-box");
				}else{
					$detailReview.removeClass("scroll-box");
				}
			})
			console.log('api2');
        },
        error: function(req) {
            console.log("error",req)
        }
    });
}

show_story();