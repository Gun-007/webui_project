(function () {
	var root = document.documentElement;

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
