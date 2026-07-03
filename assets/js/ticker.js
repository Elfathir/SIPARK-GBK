document.addEventListener("DOMContentLoaded", () => {

    const track = document.querySelector('.ticker-track');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');

    if (!track) return;

    const stepScroll = 642;
    const speed = 1.2;

    let currentX = 0;
    let isManual = false;
    let resumeTimeout = null;
    let animationFrameId = null;

    track.style.animation = "none";

    function loopOtomatis(){
        if(isManual) return;

        const halfWidth = track.scrollWidth/2;
        currentX -= speed;

        if(Math.abs(currentX)>=halfWidth){
            currentX=0;
        }

        track.style.transform=`translateX(${currentX}px)`;
        animationFrameId=requestAnimationFrame(loopOtomatis);
    }

    function aktifkanModeManual(){
        clearTimeout(resumeTimeout);
        cancelAnimationFrame(animationFrameId);
        isManual=true;
    }

    function pasangTimerResume(){
        clearTimeout(resumeTimeout);
        resumeTimeout=setTimeout(()=>{
            isManual=false;
            loopOtomatis();
        },1000);
    }

    function ratakanPosisi(pos){
        return Math.round(pos/stepScroll)*stepScroll;
    }


    /* tombol kanan */
    nextBtn?.addEventListener("click",()=>{
        aktifkanModeManual();
        const half=track.scrollWidth/2;
        let target=ratakanPosisi(currentX)-stepScroll;
        if(Math.abs(target)>=half){
            target=0;
        }

        currentX=target;
        track.style.transform=`translateX(${currentX}px)`;
        pasangTimerResume();
    });

    /* tombol kiri */
    prevBtn?.addEventListener("click",()=>{
        aktifkanModeManual();
        const half=track.scrollWidth/2;
        let target=ratakanPosisi(currentX)+stepScroll;
        if(target>0){
            target=-half+stepScroll;
        }

        currentX=target;
        track.style.transform=`translateX(${currentX}px)`;
        pasangTimerResume();
    });

    /* drag */
    let drag=false;
    let startX=0;
    let base=0;

    track.addEventListener("mousedown",e=>{
        aktifkanModeManual();
        drag=true;
        startX=e.clientX;
        base=currentX;
        track.style.cursor="grabbing";
    });

    window.addEventListener("mousemove",e=>{
        if(!drag) return;
        currentX=base+(e.clientX-startX);
        track.style.transform=`translateX(${currentX}px)`;
    });

    window.addEventListener("mouseup",()=>{
        if(!drag) return;
        drag=false;
        track.style.cursor="grab";
        currentX=ratakanPosisi(currentX);
        track.style.transform=`translateX(${currentX}px)`;
        pasangTimerResume();
    });
    loopOtomatis();
});