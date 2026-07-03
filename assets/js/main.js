console.log("SIPARK GBK Loaded");

/* NAVBAR HIDE */
const navbar = document.querySelector(".navbar-custom");
let lastScroll = 0;

window.addEventListener("scroll",()=>{
    const current = window.scrollY;
    if(current > lastScroll && current > 120){
        navbar.classList.add("navbar-hide");
        navbar.classList.remove("navbar-show");
    } else{
        navbar.classList.remove("navbar-hide");
        navbar.classList.add("navbar-show");
    }
    lastScroll=current;
});

navbar.classList.add("navbar-show");

const links = document.querySelectorAll(".nav-link");
const indicator = document.querySelector(".nav-indicator");
let current = 0;

function moveIndicator(index){
    const item = links[index];
    anime.timeline()

    .add({
        targets:indicator,
        scaleX:1.15,
        duration:180,
        easing:"easeOutQuad"
    })

    .add({
        targets:indicator,
        left:item.offsetLeft,
        width:item.offsetWidth,
        duration:520,
        easing:"easeOutElastic(1,.8)"
    },0)

    .add({
        targets:indicator,
        scaleX:1,
        duration:250,
        easing:"easeOutQuad"
    });
}
moveIndicator(0);

const sections = [
    document.querySelector(".hero"),
    document.querySelector("#tentang"),
    document.querySelector("#fitur"),
    document.querySelector("#statistik"),
    document.querySelector("#kontak")
];

window.addEventListener("scroll",()=>{
    let current = 0;
    const scrollY = window.scrollY + 180;
    sections.forEach((section,index)=>{
        if(section && scrollY >= section.offsetTop){
            current = index;
        }
    });
    moveIndicator(current);
});