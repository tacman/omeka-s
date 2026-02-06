import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  connect() {
    if (!window.Omeka) {
      return;
    }

    this.bindSectionNav();

    this.overridePopulateSidebarContent();

    // Ensure sidebar space is reserved on page load (needed for Turbo navigation)
    if (typeof window.Omeka.reserveSidebarSpace === 'function') {
      window.Omeka.reserveSidebarSpace();
    }

    if (typeof window.Omeka.warnIfUnsaved === 'function') {
      window.Omeka.warnIfUnsaved();
    }

    if (typeof window.Omeka.fixIframeAspect === 'function') {
      window.Omeka.fixIframeAspect();
    }
  }

  bindSectionNav() {
    document.querySelectorAll('.section-nav a[href^="#"]').forEach((link) => {
      link.addEventListener('click', (event) => {
        event.preventDefault();
        const target = document.querySelector(link.getAttribute('href'));
        if (target && typeof window.Omeka.switchActiveSection === 'function') {
          window.Omeka.switchActiveSection(window.jQuery ? window.jQuery(target) : target);
        }
      });
    });

    document.querySelectorAll('.section > legend').forEach((legend) => {
      legend.addEventListener('click', () => {
        legend.parentElement?.classList.toggle('mobile-active');
      });
    });
  }

  overridePopulateSidebarContent() {
    window.Omeka.populateSidebarContent = async (sidebar, url, data) => {
      const sidebarEl = sidebar?.jquery ? sidebar[0] : sidebar;
      if (!sidebarEl) {
        return;
      }
      const sidebarContent = sidebarEl.querySelector('.sidebar-content');
      if (!sidebarContent) {
        return;
      }
      sidebarEl.classList.add('loading');
      sidebarContent.innerHTML = '';

      let fetchUrl = url;
      if (data) {
        fetchUrl += `${url.includes('?') ? '&' : '?'}${data}`;
      }
      const response = await fetch(fetchUrl, { credentials: 'same-origin' });
      if (response.ok) {
        sidebarContent.innerHTML = await response.text();
        const event = new Event('o:sidebar-content-loaded');
        sidebarEl.dispatchEvent(event);
        if (window.jQuery) {
          window.jQuery(sidebarEl).trigger('o:sidebar-content-loaded');
        }
      } else {
        sidebarContent.innerHTML = `<p>${window.Omeka.jsTranslate ? window.Omeka.jsTranslate('Something went wrong') : 'Something went wrong'}</p>`;
      }
      sidebarEl.classList.remove('loading');
    };
  }
}
