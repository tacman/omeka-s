import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller for sidebar functionality.
 * Replaces legacy jQuery handlers for better Turbo compatibility.
 */
export default class extends Controller {
  static targets = ['sidebar', 'trigger'];
  static values = {
    sidebarId: String,
  };

  connect() {
    // Bind to any delete buttons on the page
    this.bindDeleteButtons();
    // Bind sidebar close buttons
    this.bindCloseButtons();
  }

  bindDeleteButtons() {
    document.querySelectorAll('.button.delete, button.delete').forEach((button) => {
      // Skip if already bound by Stimulus
      if (button.dataset.sidebarBound) return;
      button.dataset.sidebarBound = 'true';

      button.addEventListener('click', (e) => {
        e.preventDefault();
        const sidebar = document.getElementById('delete');
        if (sidebar) {
          this.openSidebar(sidebar);
        }
      });
    });
  }

  bindCloseButtons() {
    document.querySelectorAll('.sidebar-close').forEach((button) => {
      if (button.dataset.sidebarBound) return;
      button.dataset.sidebarBound = 'true';

      button.addEventListener('click', (e) => {
        e.preventDefault();
        const sidebar = button.closest('.sidebar');
        if (sidebar) {
          this.closeSidebar(sidebar);
        }
      });
    });
  }

  openSidebar(sidebar) {
    sidebar.classList.add('active');
    document.body.classList.add('sidebar-open');

    // Use Omeka's function if available (for event compatibility)
    if (window.Omeka && typeof window.Omeka.openSidebar === 'function' && window.jQuery) {
      window.Omeka.openSidebar(window.jQuery(sidebar));
    }
  }

  closeSidebar(sidebar) {
    sidebar.classList.remove('active');
    document.body.classList.remove('sidebar-open');

    // Use Omeka's function if available
    if (window.Omeka && typeof window.Omeka.closeSidebar === 'function' && window.jQuery) {
      window.Omeka.closeSidebar(window.jQuery(sidebar));
    }
  }

  // Action to open a specific sidebar by ID
  open(event) {
    event.preventDefault();
    const sidebarId = this.sidebarIdValue || event.params.sidebarId || 'delete';
    const sidebar = document.getElementById(sidebarId);
    if (sidebar) {
      this.openSidebar(sidebar);
    }
  }

  // Action to close the current sidebar
  close(event) {
    event.preventDefault();
    const sidebar = event.target.closest('.sidebar');
    if (sidebar) {
      this.closeSidebar(sidebar);
    }
  }
}
