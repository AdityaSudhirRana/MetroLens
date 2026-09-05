/* ==========================================================================
   AI PACKAGE INSPECTOR - NAVIGATION & UI HELPERS (js/navigation.js)
   ========================================================================== */

document.addEventListener("DOMContentLoaded", function () {
  const user = window.requireAuth();
  if (!user) return; // Unauthenticated pages don't render layout

  // Populate User Info in Topbar
  const userNameEl = document.getElementById("nav-user-name");
  const userRoleEl = document.getElementById("nav-user-role");
  const userAvatarEl = document.getElementById("nav-user-avatar");

  if (userNameEl) userNameEl.textContent = user.name;
  if (userRoleEl) userRoleEl.textContent = user.designation || (user.role === "admin" ? "System Administrator" : "Metrology Inspector");
  if (userAvatarEl && user.avatar) userAvatarEl.src = user.avatar;

  // Active Link Highlighting in Sidebar
  const currentPath = window.location.pathname.toLowerCase();
  const navLinks = document.querySelectorAll(".sidebar-nav .nav-item");
  navLinks.forEach((link) => {
    const href = link.getAttribute("href");
    if (href && currentPath.includes(href.toLowerCase().replace("../", ""))) {
      link.classList.add("active");
    }
  });

  // Mobile Hamburger Sidebar Toggle
  const hamburgerBtn = document.getElementById("hamburger-btn");
  const sidebar = document.querySelector(".sidebar");
  let backdrop = document.querySelector(".mobile-sidebar-backdrop");

  if (!backdrop) {
    backdrop = document.createElement("div");
    backdrop.className = "mobile-sidebar-backdrop";
    document.body.appendChild(backdrop);
  }

  if (hamburgerBtn && sidebar) {
    hamburgerBtn.addEventListener("click", function () {
      sidebar.classList.toggle("mobile-open");
      backdrop.classList.toggle("active");
    });

    backdrop.addEventListener("click", function () {
      sidebar.classList.remove("mobile-open");
      backdrop.classList.remove("active");
    });
  }

  // Logout Buttons Binding
  const logoutBtns = document.querySelectorAll(".logout-btn, #btn-logout");
  logoutBtns.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      window.showModal({
        title: "Confirm Logout",
        body: "Are you sure you want to sign out of the Legal Metrology AI Inspection system?",
        confirmText: "Logout",
        cancelText: "Cancel",
        danger: true,
        onConfirm: function () {
          window.logoutUser();
        }
      });
    });
  });
});

/* Global Toast Notification System */
window.showToast = function (message, type = "info") {
  let container = document.getElementById("toast-container");
  if (!container) {
    container = document.createElement("div");
    container.id = "toast-container";
    document.body.appendChild(container);
  }

  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;

  let icon = "ℹ️";
  if (type === "success") icon = "✓";
  if (type === "danger") icon = "✕";
  if (type === "warning") icon = "⚠️";

  toast.innerHTML = `<span style="font-size:1.1rem;">${icon}</span><span>${message}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = "0";
    toast.style.transform = "translateX(100%)";
    toast.style.transition = "all 0.3s ease";
    setTimeout(() => toast.remove(), 300);
  }, 3500);
};

/* Global Confirmation Modal Utility */
window.showModal = function (options) {
  let overlay = document.getElementById("global-modal-overlay");
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.id = "global-modal-overlay";
    overlay.className = "modal-overlay";
    overlay.innerHTML = `
      <div class="modal-card">
        <h3 class="modal-title" id="global-modal-title">Notification</h3>
        <div class="modal-body" id="global-modal-body">Modal body content</div>
        <div class="modal-actions">
          <button class="btn btn-secondary" id="global-modal-cancel">Cancel</button>
          <button class="btn btn-primary" id="global-modal-confirm">Confirm</button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);
  }

  const titleEl = document.getElementById("global-modal-title");
  const bodyEl = document.getElementById("global-modal-body");
  const confirmBtn = document.getElementById("global-modal-confirm");
  const cancelBtn = document.getElementById("global-modal-cancel");

  titleEl.textContent = options.title || "Confirm Action";
  bodyEl.textContent = options.body || "";
  confirmBtn.textContent = options.confirmText || "Confirm";
  cancelBtn.textContent = options.cancelText || "Cancel";

  if (options.danger) {
    confirmBtn.className = "btn btn-danger";
  } else {
    confirmBtn.className = "btn btn-primary";
  }

  overlay.classList.add("active");

  const closeHandler = function () {
    overlay.classList.remove("active");
    confirmBtn.removeEventListener("click", onConfirmHandler);
    cancelBtn.removeEventListener("click", closeHandler);
  };

  const onConfirmHandler = function () {
    closeHandler();
    if (typeof options.onConfirm === "function") {
      options.onConfirm();
    }
  };

  confirmBtn.addEventListener("click", onConfirmHandler);
  cancelBtn.addEventListener("click", closeHandler);
};
