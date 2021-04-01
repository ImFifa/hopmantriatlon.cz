import "@theme/front/init.scss";

import "bootstrap/js/dist/dropdown";
import "bootstrap/js/dist/collapse";
import "bootstrap/js/dist/carousel";
import "bootstrap/js/dist/util";

// import "@/front/cookie";

import Nette from "@/front/netteForms";
Nette.initOnLoad();
window.Nette = Nette;

document.addEventListener("DOMContentLoaded", () => {

	var headerCarousel = document.getElementById("headerCarousel");
	if (headerCarousel) {
		// carousel interval
		$("#headerCarousel").carousel({
			interval: 10300
		});

		// carousel progress bar
		let i = 0;
		let progressBar = document.getElementById("progressBar");
		// eslint-disable-next-line no-unused-vars
		let progressInterval = setInterval(progress, 100); // 180

		// eslint-disable-next-line no-inner-declarations
		function progress() {
			if (i === 100) {
				i = -3;
				// reset progress bar
				progressBar.querySelector(".progress-bar__fill").style.width = 0;
			} else {
				i++;
				progressBar.querySelector(".progress-bar__fill").style.width = `${i}%`;
			}
		}
	}

	// on scroll events
	const $nav = document.querySelector("#navbar");
	const $scrollTopBtn = document.querySelector("#scrollTopBtn");

	//var prevScrollpos = window.pageYOffset;
	window.onscroll = function() {
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

	// smooth scroll
	// Add smooth scrolling to all links
	$(".scroll").on("click", function(event) {

		// Make sure this.hash has a value before overriding default behavior
		if (this.hash !== "") {
			// Prevent default anchor click behavior
			event.preventDefault();

			// Store hash
			var hash = this.hash;

			// Using jQuery's animate() method to add smooth page scroll
			// The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area
			$("html, body").animate({
				scrollTop: $(hash).offset().top
			}, 800, function(){

				// Add hash (#) to URL when done scrolling (default click behavior)
				window.location.hash = hash;
			});
		} // End if
	});
});
