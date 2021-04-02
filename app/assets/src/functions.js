// on scroll events
const $nav = document.querySelector("#navbar");
const $scrollTopBtn = document.querySelector("#scrollTopBtn");

function runOnScroll() {
	var currentScrollPos = window.pageYOffset;
	if (currentScrollPos > 240 && window.innerWidth > 992) {
		$nav.classList.add("navbar-bg");
	} else if (currentScrollPos < 240) {
		$nav.classList.remove("navbar-bg");
	}

	if (currentScrollPos > window.innerHeight) {
		$scrollTopBtn.style.display = "block";
	} else {
		$scrollTopBtn.style.display = "none";
	}

};
