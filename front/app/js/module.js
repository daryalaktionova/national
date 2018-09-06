/*start OPS */
const sections = $('.section');
const display = $('.content');
console.log(sections);

let inscroll = false;

const md = new MobileDetect(window.navigator.userAgent);
const isItMobile = md.mobile();

const performTransition = sectionEq => {
	
	if(inscroll) return;

	inscroll = true;

	sectionEq = parseInt(sectionEq);

	const position = (sectionEq * -100) + '%';


	sections.eq(sectionEq)
		.addClass('active')
		.siblings()
        .removeClass('active');
        
        setTimeout(() => {
            inscroll = false;
        }, 1300);
        display.css({
            'transform' : 'translateY(' + (position) + ')'
        });
        
    }

    const defineSections = sections => {
        const activeSection = sections.filter('.active');
        return {
            activeSection: activeSection,
            nextSection: activeSection.next(),
            prevSection: activeSection.prev()
        }
    
    }

    const scrollToSection = direction => {
        const section = defineSections(sections);
    
        if(direction == 'up' && section.nextSection.length) {
            performTransition(section.nextSection.index());
        }
    
        if(direction == "down" && section.prevSection.length) {
            performTransition(section.prevSection.index());
        }
    }
    

    $('.wrapper').on({
        wheel: e => {
            console.log(e);
            const deltaY = e.originalEvent.deltaY;
            const direction = (deltaY>0) 
            ? 'up' 
            : 'down';
            scrollToSection(direction);
        },
        touchmove: e => (e.preventDefault())
    });


    /*OPS keyboard */
$(document).on('keydown', e => {

	console.log(e.keyCode);
	switch(e.keyCode) {
		case 40: //up
			scrollToSection('up')
			break;

		case 38: //down
			scrollToSection('down')
			break;
	}

});

$('[data-scroll-to]').on('click', e => {
	e.preventDefault();
	const sectionNum = $(e.currentTarget).attr('data-scroll-to');
	performTransition(sectionNum);
})

/*start mobile ops */
if(isItMobile) {
	$(window).swipe( {
		swipe:function(
			event,
			direction
		) {
			scrollToSection(direction);
		}
	});
}


/*end OPS */
