// Site header: a soft shadow once the page scrolls, and the mobile menu drawer
// (a native <dialog>: focus stays inside, Esc closes it).

const header = document.querySelector('[data-site-header]');

if (header) {
    const onScroll = () => header.toggleAttribute('data-scrolled', window.scrollY > 4);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    const menu = document.getElementById('site-menu');
    const opener = header.querySelector('[data-menu-open]');

    if (menu && opener) {
        const close = () => menu.close();

        opener.addEventListener('click', () => {
            menu.showModal();
            opener.setAttribute('aria-expanded', 'true');
        });
        menu.addEventListener('close', () => {
            opener.setAttribute('aria-expanded', 'false');
            opener.focus();
        });
        menu.querySelector('[data-menu-close]')?.addEventListener('click', close);
        // A click on the backdrop (outside the panel) closes the menu.
        menu.addEventListener('click', (event) => {
            if (event.target === menu) {
                close();
            }
        });
        // Closing on wide screens, where the drawer is not used.
        window.matchMedia('(min-width: 80rem)').addEventListener('change', (event) => event.matches && close());
    }
}
