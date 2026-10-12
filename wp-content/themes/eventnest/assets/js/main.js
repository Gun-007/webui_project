(function () {
	var root = document.documentElement;
	var heroVideo = document.querySelector('.hero__video');
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (heroVideo && reduceMotion) {
		heroVideo.pause();
		heroVideo.removeAttribute('autoplay');
	}

	var motionSelector = 'main > section, .hero__title > span, .ev-card, .closing-card, .en-club-card, .en-story-card, .en-dashboard-heading, .en-dashboard-review, .en-dashboard-metrics > *, .en-dashboard-metric, .en-event-calendar, .en-approval-card, .en-student-card, .en-my-story, .en-auth-card, .en-review-card, .en-profile, .facts li, .ticket-box, .en-story-highlight';
	if (!reduceMotion && 'IntersectionObserver' in window) {
		var motionItems = document.querySelectorAll(motionSelector);
		if (motionItems.length) {
			root.classList.add('en-motion-ready');
			var motionObserver = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-inview');
						motionObserver.unobserve(entry.target);
					}
				});
			}, { threshold: 0.12, rootMargin: '0px 0px -36px 0px' });
			motionItems.forEach(function (item) {
				var siblings = Array.prototype.filter.call(item.parentElement.children, function (el) { return el.matches(motionSelector); });
				item.style.setProperty('--en-motion-delay', Math.min(Math.max(siblings.indexOf(item), 0), 5) * 75 + 'ms');
				item.setAttribute('data-en-motion', item.matches('.hero__title > span') ? 'hero-line' : 'rise');
				motionObserver.observe(item);
			});
		}
	}

	var tg = document.getElementById('theme-toggle');
	if (tg) tg.addEventListener('click', function () {
		var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
		root.setAttribute('data-theme', next);
		try { localStorage.setItem('en-theme', next); } catch (e) {}
	});

	var nt = document.querySelector('.nav-toggle');
	if (nt) nt.addEventListener('click', function () {
		var open = document.body.classList.toggle('nav-open');
		nt.setAttribute('aria-expanded', open ? 'true' : 'false');
	});

	document.querySelectorAll('[data-copy]').forEach(function (b) {
		b.addEventListener('click', function () {
			var label = b.textContent;
			var done = function () { b.textContent = 'Link copied'; setTimeout(function () { b.textContent = label; }, 1800); };
			if (navigator.clipboard) navigator.clipboard.writeText(b.getAttribute('data-copy')).then(done);
		});
	});
})();
