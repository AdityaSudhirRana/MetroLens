/* ==========================================================================
   AI PACKAGE INSPECTOR - INSPECTION HISTORY ENGINE (js/history.js)
   ========================================================================== */

(function () {
  let allInspections = [];
  let filteredInspections = [];
  let currentPage = 1;
  const pageSize = 8;
  let currentSort = { field: "date", dir: "desc" };

  document.addEventListener("DOMContentLoaded", function () {
    const currentUser = window.requireAuth();
    if (!currentUser) return;

    const historyTableBody = document.getElementById("history-table-tbody");
    if (!historyTableBody) return;

    allInspections = window.getInspections ? window.getInspections() : JSON.parse(localStorage.getItem("inspections") || "[]");
    filteredInspections = [...allInspections];

    // Bind Search Input
    const searchInput = document.getElementById("history-search");
    if (searchInput) {
      searchInput.addEventListener("input", function () {
        applyFilters();
      });
    }

    // Bind Status Filter
    const statusFilter = document.getElementById("history-status-filter");
    if (statusFilter) {
      statusFilter.addEventListener("change", function () {
        applyFilters();
      });
    }

    // Bind Sort Select
    const sortSelect = document.getElementById("history-sort-select");
    if (sortSelect) {
      sortSelect.addEventListener("change", function () {
        const val = sortSelect.value; // e.g. "date-desc", "date-asc", "id-asc"
        const [field, dir] = val.split("-");
        currentSort = { field, dir };
        applyFilters();
      });
    }

    // Initial Render
    applyFilters();
  });

  function applyFilters() {
    const searchVal = (document.getElementById("history-search")?.value || "").toLowerCase().trim();
    const statusVal = document.getElementById("history-status-filter")?.value || "All";

    filteredInspections = allInspections.filter((item) => {
      // Search Matching
      const matchesSearch =
        item.id.toLowerCase().includes(searchVal) ||
        item.productName.toLowerCase().includes(searchVal) ||
        item.brand.toLowerCase().includes(searchVal) ||
        item.inspectorName.toLowerCase().includes(searchVal);

      // Status Matching
      const matchesStatus = statusVal === "All" || item.status === statusVal;

      return matchesSearch && matchesStatus;
    });

    // Apply Sorting
    filteredInspections.sort((a, b) => {
      let valA = a[currentSort.field] || "";
      let valB = b[currentSort.field] || "";

      if (valA < valB) return currentSort.dir === "asc" ? -1 : 1;
      if (valA > valB) return currentSort.dir === "asc" ? 1 : -1;
      return 0;
    });

    currentPage = 1;
    renderTable();
  }

  function renderTable() {
    const tbody = document.getElementById("history-table-tbody");
    const countInfo = document.getElementById("pagination-count-info");
    const paginationControls = document.getElementById("pagination-controls");

    if (!tbody) return;

    tbody.innerHTML = "";

    if (filteredInspections.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7">
            <div class="empty-state-box">
              <div class="empty-state-title">No Inspection Records Found</div>
              <div class="empty-state-desc">Try adjusting your search keywords or filter options.</div>
            </div>
          </td>
        </tr>
      `;
      if (countInfo) countInfo.textContent = "Showing 0 of 0 inspections";
      if (paginationControls) paginationControls.innerHTML = "";
      return;
    }

    // Pagination calculations
    const totalPages = Math.ceil(filteredInspections.length / pageSize);
    const startIdx = (currentPage - 1) * pageSize;
    const pageItems = filteredInspections.slice(startIdx, startIdx + pageSize);

    pageItems.forEach((item) => {
      const tr = document.createElement("tr");

      let badgeClass = "badge-compliant";
      if (item.status === "Non-Compliant") badgeClass = "badge-non-compliant";
      if (item.status === "Pending") badgeClass = "badge-pending";

      const violationsCount = item.violations ? item.violations.length : 0;

      tr.innerHTML = `
        <td class="table-id-cell">${item.id}</td>
        <td>
          <div class="table-product-cell">
            <div>
              <div class="table-product-title">${item.productName}</div>
              <div class="table-product-brand">${item.brand} • ${item.category}</div>
            </div>
          </div>
        </td>
        <td>${item.inspectorName}</td>
        <td>${item.date}</td>
        <td><span class="badge ${badgeClass}">${item.status}</span></td>
        <td>
          ${
            violationsCount > 0
              ? `<span style="color:var(--danger); font-weight:700;">${violationsCount} Violation(s)</span>`
              : `<span class="text-muted">None</span>`
          }
        </td>
        <td>
          <a href="inspection-result.html?id=${item.id}" class="btn btn-secondary btn-sm">View Result</a>
        </td>
      `;
      tbody.appendChild(tr);
    });

    // Update Pagination Count
    if (countInfo) {
      const endIdx = Math.min(startIdx + pageSize, filteredInspections.length);
      countInfo.textContent = `Showing ${startIdx + 1} to ${endIdx} of ${filteredInspections.length} inspections`;
    }

    // Render Pagination Controls
    if (paginationControls) {
      paginationControls.innerHTML = "";

      const prevBtn = document.createElement("button");
      prevBtn.className = "page-btn";
      prevBtn.textContent = "‹";
      prevBtn.disabled = currentPage === 1;
      prevBtn.addEventListener("click", () => {
        if (currentPage > 1) {
          currentPage--;
          renderTable();
        }
      });
      paginationControls.appendChild(prevBtn);

      for (let i = 1; i <= totalPages; i++) {
        const pBtn = document.createElement("button");
        pBtn.className = `page-btn ${i === currentPage ? "active" : ""}`;
        pBtn.textContent = i;
        pBtn.addEventListener("click", () => {
          currentPage = i;
          renderTable();
        });
        paginationControls.appendChild(pBtn);
      }

      const nextBtn = document.createElement("button");
      nextBtn.className = "page-btn";
      nextBtn.textContent = "›";
      nextBtn.disabled = currentPage === totalPages;
      nextBtn.addEventListener("click", () => {
        if (currentPage < totalPages) {
          currentPage++;
          renderTable();
        }
      });
      paginationControls.appendChild(nextBtn);
    }
  }
})();
