function orbit(selector,duration,rotate,origin){
    if(!document.querySelector(selector)) return;

    anime({
        targets:selector,
        rotate:rotate,
        duration:duration,
        easing:"linear",
        loop:true,
        transformOrigin:origin
    });
}

orbit(".orbit1",5000,360,"250px 250px");
orbit(".orbit2",6500,-360,"-200px 0");
orbit(".orbit3",8000,360,"0 -210px");
orbit(".orbit4",9000,-360,"230px 0");