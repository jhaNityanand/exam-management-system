export function initDropdowns() {
    document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        if (!trigger || !menu) {
            return;
        }

        const close = () => {
            dropdown.dataset.open = '0';
            menu.classList.add('hidden');
        };

        const open = () => {
            dropdown.dataset.open = '1';
            menu.classList.remove('hidden');
        };

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const isOpen = dropdown.dataset.open === '1';
            document.querySelectorAll('[data-dropdown]').forEach((other) => {
                if (other !== dropdown) {
                    other.dataset.open = '0';
                    other.querySelector('[data-dropdown-menu]')?.classList.add('hidden');
                }
            });

            if (isOpen) {
                close();
            } else {
                open();
            }
        });

        document.addEventListener('click', (event) => {
            if (!dropdown.contains(event.target)) {
                close();
            }
        });
    });
}
