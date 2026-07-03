console.log("hero.js loaded");

document.addEventListener("DOMContentLoaded",()=>{
    console.log("Typewriter jalan");

const hero=document.querySelector(".hero-circle");
const visualizer=document.querySelector(".stagger-visualizer");
const heroImage=document.querySelector(".hero-image");
const glow=document.querySelector(".circle-glow");

if(!hero||!visualizer) return;

/* membuat dot */
const fragment=document.createDocumentFragment();
const grid=[20,20];
for(let i=0;i<400;i++){
    fragment.appendChild(document.createElement("div"));
}
visualizer.appendChild(fragment);

/* animasi dot */
anime({
    targets:".stagger-visualizer div",
    translateX:()=>anime.random(-8,8),
    translateY:()=>anime.random(-8,8),
    scale:[.5,1.2],
    opacity:[.2,.8],
    delay:anime.stagger(40,{grid:grid,from:"center"}),
    easing:"easeInOutQuad",
    direction:"alternate",
    loop:true
});

/* parallax */
hero.addEventListener("mousemove",(e)=>{
    const rect=hero.getBoundingClientRect();
        anime({
        targets:visualizer,
        translateX:(e.clientX-rect.left-rect.width/2)/30,
        translateY:(e.clientY-rect.top-rect.height/2)/30,
        duration:300,
        easing:"easeOutQuad"
    });
});

hero.addEventListener("mouseleave",()=>{
    anime({
        targets:visualizer,
        translateX:0,
        translateY:0,
        duration:700,
        easing:"easeOutExpo"
    });
});

/* hero image */
if(heroImage){
    hero.addEventListener("mouseenter",()=>{
        anime({
        targets:heroImage,
        scale:1.05,
        duration:400,
        easing:"easeOutQuad"
    });
});

hero.addEventListener("mouseleave",()=>{
anime({
targets:heroImage,
scale:1,
duration:600,
easing:"easeOutExpo"
});

});

}

/* glow */

if(glow){

anime({

targets:glow,

scale:[1,1.08],

opacity:[.6,1],

duration:3500,

direction:"alternate",

loop:true,

easing:"easeInOutSine"

});

}

});

/*==========================================
TYPEWRITER + HACKER EFFECT
==========================================*/

document.addEventListener("DOMContentLoaded",()=>{

    const textEl=document.getElementById("typing-text");

    if(!textEl) return;

    const text="Gelora Bung Karno";

    const binary="010101101001010101010010110101001010101010010101010101001010101001101010";

    async function loop(){

        while(true){

            /* mengetik */

            textEl.textContent="";

            for(let i=0;i<=text.length;i++){
                textEl.textContent=text.substring(0,i);
                await delay(90);
            }

            await delay(500);

            for(let j=0;j<28;j++){
            textEl.textContent = randomBinary(Math.min(text.length, 17));
            await delay(50);
            }

            /* hilang */
            textEl.textContent="";
            await delay(500);

        }

    }

    function randomBinary(length){

        let out="";

        for(let i=0;i<length;i++){

            out+=Math.random()>.5?"1":"0";

        }

        return out;

    }

    function delay(ms){

        return new Promise(resolve=>setTimeout(resolve,ms));

    }

    loop();

});