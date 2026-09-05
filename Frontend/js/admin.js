/* ==========================================================================
   AI PACKAGE INSPECTOR - ADMIN DASHBOARD & INSPECTOR CONTROL (js/admin.js)
   ========================================================================== */

(function () {
  document.addEventListener("DOMContentLoaded", function () {
    const adminUser = window.requireAdmin();
    if (!adminUser) return;

    // ADMIN PAGE 1: DASHBOARD OVERVIEW
    const adminDashboardContainer = document.getElementById("admin-dashboard-container");
    if (adminDashboardContainer) {
      renderAdminDashboard();
    }

    // ADMIN PAGE 2: INSPECTORS DIRECTORY TABLE
    const inspectorsTableBody = document.getElementById("admin-inspectors-tbody");
    if (inspectorsTableBody) {
      renderInspectorsTable();
    }

    // ADMIN PAGE 3: REGISTER INSPECTOR FORM
    const regInspectorForm = document.getElementById("admin-register-inspector-form");
    if (regInspectorForm) {
      initRegisterInspectorForm();
    }

    // ADMIN PAGE 4: INSPECTOR DETAILS VIEW
    const inspectorDetailsContainer = document.getElementById("admin-inspector-details-container");
    if (inspectorDetailsContainer) {
      renderInspectorDetailsPage();
    }
  });

  // Admin Dashboard Render
  function renderAdminDashboard() {
    const users = JSON.parse(localStorage.getItem("users") || "[]");
    const inspections = window.getInspections ? window.getInspections() : JSON.parse(localStorage.getItem("inspections") || "[]");

    const inspectors = users.filter((u) => u.role === "inspector");
    const activeInspectors = inspectors.filter((u) => u.status === "Active");

    const totalInspections = inspections.length;
    const compliantCount = inspections.filter((i) => i.status === "Compliant").length;
    let totalViolations = 0;
    inspections.forEach((i) => {
      if (i.violations) totalViolations += i.violations.length;
    });

    const passRate = totalInspections > 0 ? Math.round((compliantCount / totalInspections) * 100) : 0;

    // Set KPI Values
    document.getElementById("adm-stat-inspectors").textContent = inspectors.length;
    document.getElementById("adm-stat-active").textContent = activeInspectors.length;
    document.getElementById("adm-stat-inspections").textContent = totalInspections;
    document.getElementById("adm-stat-violations").textContent = totalViolations;
    document.getElementById("adm-stat-passrate").textContent = `${passRate}%`;

    // Render Inspector Activity Summary Table
    const tbody = document.getElementById("adm-inspector-activity-tbody");
    if (tbody) {
      tbody.innerHTML = "";
      inspectors.forEach((inspector) => {
        const inspectorInspections = inspections.filter((i) => i.inspectorId === inspector.id || i.inspectorName === inspector.name);
        const count = inspectorInspections.length;
        const passed = inspectorInspections.filter((i) => i.status === "Compliant").length;
        const rate = count > 0 ? Math.round((passed / count) * 100) : 0;

        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>
            <div class="table-product-cell">
              <img src="${inspector.avatar || "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80"}" class="table-product-img" style="border-radius:50%;" />
              <div>
                <div class="table-product-title">${inspector.name}</div>
                <div class="table-product-brand">${inspector.employeeId} • ${inspector.department}</div>
              </div>
            </div>
          </td>
          <td class="font-bold">${count}</td>
          <td>
            <div style="display:flex; align-items:center; gap:8px;">
              <div style="flex:1; height:6px; background:var(--surface-alt); border-radius:4px; min-width:60px;">
                <div style="height:100%; width:${rate}%; background:var(--success); border-radius:4px;"></div>
              </div>
              <span class="font-bold">${rate}%</span>
            </div>
          </td>
          <td><span class="badge ${inspector.status === "Active" ? "badge-active" : "badge-inactive"}">${inspector.status}</span></td>
          <td>
            <a href="inspector-details.html?id=${inspector.id}" class="btn btn-secondary btn-sm">Performance →</a>
          </td>
        `;
        tbody.appendChild(tr);
      });
    }
  }

  // Admin Inspectors Directory Render
  function renderInspectorsTable() {
    const users = JSON.parse(localStorage.getItem("users") || "[]");
    let inspectors = users.filter((u) => u.role === "inspector");
    const inspections = window.getInspections ? window.getInspections() : JSON.parse(localStorage.getItem("inspections") || "[]");

    const tbody = document.getElementById("admin-inspectors-tbody");
    const searchInput = document.getElementById("inspector-search");
    const statusSelect = document.getElementById("inspector-status-filter");

    function filterAndRender() {
      const searchVal = (searchInput?.value || "").toLowerCase().trim();
      const statusVal = statusSelect?.value || "All";

      const filtered = inspectors.filter((u) => {
        const matchesSearch = u.name.toLowerCase().includes(searchVal) || u.email.toLowerCase().includes(searchVal) || u.employeeId.toLowerCase().includes(searchVal);
        const matchesStatus = statusVal === "All" || u.status === statusVal;
        return matchesSearch && matchesStatus;
      });

      tbody.innerHTML = "";

      if (filtered.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="7">
              <div class="empty-state-box">
                <div class="empty-state-title">No Inspectors Found</div>
                <div class="empty-state-desc">Try clearing your filters or adding a new inspector.</div>
              </div>
            </td>
          </tr>
        `;
        return;
      }

      filtered.forEach((item) => {
        const inspectorInspections = inspections.filter((i) => i.inspectorId === item.id || i.inspectorName === item.name);
        const tr = document.createElement("tr");

        const isActive = item.status === "Active";

        tr.innerHTML = `
          <td class="table-id-cell">${item.employeeId}</td>
          <td>
            <div class="table-product-cell">
              <img src="${item.avatar}" class="table-product-img" style="border-radius:50%;" />
              <div>
                <div class="table-product-title">${item.name}</div>
                <div class="table-product-brand">${item.designation}</div>
              </div>
            </div>
          </td>
          <td>${item.email}</td>
          <td>${item.department}</td>
          <td class="font-bold">${inspectorInspections.length}</td>
          <td><span class="badge ${isActive ? "badge-active" : "badge-inactive"}">${item.status}</span></td>
          <td>
            <div class="d-flex gap-2">
              <a href="inspector-details.html?id=${item.id}" class="btn btn-secondary btn-sm">Profile</a>
              <button class="btn ${isActive ? "btn-danger" : "btn-success"} btn-sm toggle-status-btn" data-id="${item.id}">
                ${isActive ? "Deactivate" : "Activate"}
              </button>
            </div>
          </td>
        `;
        tbody.appendChild(tr);
      });

      // Bind Deactivate/Activate Status Toggle Buttons
      document.querySelectorAll(".toggle-status-btn").forEach((btn) => {
        btn.addEventListener("click", function () {
          const targetId = btn.getAttribute("data-id");
          const targetUser = users.find((u) => u.id === targetId);

          if (!targetUser) return;

          const actionWord = targetUser.status === "Active" ? "Deactivate" : "Activate";

          window.showModal({
            title: `${actionWord} Inspector Account`,
            body: `Are you sure you want to ${actionWord.toLowerCase()} ${targetUser.name} (${targetUser.employeeId})?`,
            confirmText: actionWord,
            danger: targetUser.status === "Active",
            onConfirm: function () {
              targetUser.status = targetUser.status === "Active" ? "Inactive" : "Active";
              localStorage.setItem("users", JSON.stringify(users));
              window.showToast(`Inspector account ${actionWord.toLowerCase()}d successfully.`, "success");
              renderInspectorsTable();
            }
          });
        });
      });
    }

    if (searchInput) searchInput.addEventListener("input", filterAndRender);
    if (statusSelect) statusSelect.addEventListener("change", filterAndRender);

    filterAndRender();
  }

  // Admin Inspector Registration Onboarding Form
  function initRegisterInspectorForm() {
    const form = document.getElementById("admin-register-inspector-form");
    if (!form) return;

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const name = document.getElementById("adm-reg-name").value.trim();
      const empId = document.getElementById("adm-reg-empid").value.trim();
      const email = document.getElementById("adm-reg-email").value.trim();
      const phone = document.getElementById("adm-reg-phone").value.trim();
      const dept = document.getElementById("adm-reg-dept").value;
      const designation = document.getElementById("adm-reg-designation").value.trim();
      const password = document.getElementById("adm-reg-password").value;
      const confirmPassword = document.getElementById("adm-reg-confirm").value;

      if (password !== confirmPassword) {
        window.showToast("Passwords do not match.", "danger");
        return;
      }

      if (password.length < 6) {
        window.showToast("Password must be at least 6 characters.", "warning");
        return;
      }

      const users = JSON.parse(localStorage.getItem("users") || "[]");
      const existing = users.find((u) => u.email === email || u.employeeId === empId);

      if (existing) {
        window.showToast("An inspector with this Email or Employee ID already exists.", "danger");
        return;
      }

      const newInspector = {
        id: "U" + String(users.length + 1).padStart(3, "0"),
        name: name,
        employeeId: empId,
        email: email,
        phone: phone,
        department: dept,
        designation: designation || "Metrology Inspector",
        role: "inspector",
        password: password,
        status: "Active",
        joiningDate: new Date().toISOString().split("T")[0],
        avatar: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80"
      };

      users.push(newInspector);
      localStorage.setItem("users", JSON.stringify(users));

      window.showToast("Inspector account created successfully.", "success");
      form.reset();
      setTimeout(() => {
        window.location.href = "inspectors.html";
      }, 1200);
    });
  }

  // Admin Inspector Details Page Render
  function renderInspectorDetailsPage() {
    const urlParams = new URLSearchParams(window.location.search);
    const inspectorId = urlParams.get("id") || "U002";

    const users = JSON.parse(localStorage.getItem("users") || "[]");
    const inspector = users.find((u) => u.id === inspectorId) || users[1];

    const inspections = window.getInspections ? window.getInspections() : JSON.parse(localStorage.getItem("inspections") || "[]");
    const inspectorInspections = inspections.filter((i) => i.inspectorId === inspector.id || i.inspectorName === inspector.name);

    const totalCount = inspectorInspections.length;
    const compliantCount = inspectorInspections.filter((i) => i.status === "Compliant").length;
    const passRate = totalCount > 0 ? Math.round((compliantCount / totalCount) * 100) : 0;

    // Bind Profile Header
    document.getElementById("insp-det-name").textContent = inspector.name;
    document.getElementById("insp-det-empid").textContent = inspector.employeeId;
    document.getElementById("insp-det-dept").textContent = inspector.department;
    document.getElementById("insp-det-email").textContent = inspector.email;
    document.getElementById("insp-det-phone").textContent = inspector.phone;
    document.getElementById("insp-det-avatar").src = inspector.avatar;

    const statusBadge = document.getElementById("insp-det-status-badge");
    if (statusBadge) {
      statusBadge.textContent = inspector.status;
      statusBadge.className = `badge ${inspector.status === "Active" ? "badge-active" : "badge-inactive"}`;
    }

    // Performance Stats Cards
    document.getElementById("insp-det-count").textContent = totalCount;
    document.getElementById("insp-det-passrate").textContent = `${passRate}%`;

    // Render Recent Inspections Table for this inspector
    const tbody = document.getElementById("insp-det-inspections-tbody");
    if (tbody) {
      tbody.innerHTML = "";
      if (inspectorInspections.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-muted text-center">No inspections recorded by this inspector yet.</td></tr>`;
      } else {
        inspectorInspections.forEach((item) => {
          const tr = document.createElement("tr");
          let badgeClass = "badge-compliant";
          if (item.status === "Non-Compliant") badgeClass = "badge-non-compliant";
          if (item.status === "Pending") badgeClass = "badge-pending";

          tr.innerHTML = `
            <td class="table-id-cell">${item.id}</td>
            <td class="font-bold">${item.productName}</td>
            <td>${item.date}</td>
            <td><span class="badge ${badgeClass}">${item.status}</span></td>
            <td>
              <a href="../inspection-result.html?id=${item.id}" class="btn btn-secondary btn-sm">Result</a>
            </td>
          `;
          tbody.appendChild(tr);
        });
      }
    }
  }
})();
