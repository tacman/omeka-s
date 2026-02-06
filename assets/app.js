import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

import Sortable from 'sortablejs';
import lightGallery from 'lightgallery';

// Don't import jQuery from importmap - use the legacy jQuery loaded in base.html.twig
// which has Chosen and other plugins already attached. jQuery 4 from importmap
// doesn't have these plugins and would break legacy functionality.
// import $ from 'jquery';
// window.$ = $;
// window.jQuery = $;

window.Sortable = Sortable;
window.lightGallery = lightGallery;

// Admin menu: toggle submenus on click
document.addEventListener('DOMContentLoaded', function() {
    // Find all menu items with children (nested ul)
    const menuItems = document.querySelectorAll('#menu .navigation > li');

    menuItems.forEach(function(li) {
        const submenu = li.querySelector('ul');
        if (submenu) {
            // Add click handler to the parent link
            const link = li.querySelector(':scope > a');
            if (link) {
                link.addEventListener('click', function(e) {
                    // If we're already on this page (active), navigate normally
                    if (li.classList.contains('active')) {
                        return;
                    }
                    // Otherwise toggle the submenu
                    e.preventDefault();
                    li.classList.toggle('active');
                });
            }
        }
    });
});
