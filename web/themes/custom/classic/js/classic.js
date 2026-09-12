/**
 * @file
 * Primary navigation disclosure.
 *
 * The panel carries [hidden] in the markup so it is collapsed before CSS or JS
 * arrive on a phone. Above 62rem the stylesheet overrides [hidden] back to
 * flex, so the attribute is only ever meaningful on small screens.
 */

((Drupal, once) => {
  'use strict';

  const DESKTOP = window.matchMedia('(min-width: 62rem)');

  Drupal.behaviors.classicNav = {
    attach(context) {
      once('classic-nav', '[data-classic-nav-toggle]', context).forEach((toggle) => {
        const panel = document.getElementById(toggle.getAttribute('aria-controls'));
        if (!panel) {
          return;
        }

        const setOpen = (open) => {
          toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
          panel.hidden = !open;
        };

        toggle.addEventListener('click', () => {
          setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });

        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            setOpen(false);
            toggle.focus();
          }
        });

        // Crossing the breakpoint with the panel open would otherwise leave
        // [hidden] set on a menu the CSS has already put back on one line.
        DESKTOP.addEventListener('change', () => setOpen(false));
      });
    },
  };
})(Drupal, once);
