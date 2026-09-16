const toggle = document.querySelector('[data-nav-toggle]');
const navigation = document.querySelector('[data-navigation]');

if (toggle && navigation) {
	toggle.addEventListener('click', () => {
		const isOpen = toggle.getAttribute('aria-expanded') === 'true';
		toggle.setAttribute('aria-expanded', String(!isOpen));
		navigation.toggleAttribute('data-open', !isOpen);
	});

	navigation.addEventListener('click', (event) => {
		if (event.target instanceof HTMLAnchorElement) {
			toggle.setAttribute('aria-expanded', 'false');
			navigation.removeAttribute('data-open');
		}
	});
}
