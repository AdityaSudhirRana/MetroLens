/* ==========================================================================
   AI PACKAGE INSPECTOR - ANALYTICS & REPORTS ENGINE (js/reports.js)
   ========================================================================== */

(function () {
  document.addEventListener("DOMContentLoaded", function () {
    const user = window.requireAuth();
    if (!user) return;

    const reportsContainer = document.getElementById("reports-container");
    if (!reportsContainer) return;

    const inspections = window.getInspections ? window.getInspections() : JSON.parse(localStorage.getItem("inspections") || "[]");

    // 1. Calculate Summary Metrics
    const totalCount = inspections.length;
    const compliantCount = inspections.filter((i) => i.status === "Compliant").length;
    const nonCompliantCount = inspections.filter((i) => i.status === "Non-Compliant").length;
    const pendingCount = inspections.filter((i) => i.status === "Pending").length;
    const passRate = totalCount > 0 ? Math.round((compliantCount / totalCount) * 100) : 0;

    let totalViolationsCount = 0;
    const violationTypeCounts = {};
    const categoryViolationCounts = {};

    inspections.forEach((insp) => {
      if (insp.violations && insp.violations.length > 0) {
        totalViolationsCount += insp.violations.length;
        insp.violations.forEach((v) => {
          violationTypeCounts[v.title] = (violationTypeCounts[v.title] || 0) + 1;
        });
        categoryViolationCounts[insp.category] = (categoryViolationCounts[insp.category] || 0) + 1;
      }
    });

    // Populate Metrics UI
    const totalEl = document.getElementById("rep-total-inspections");
    const passRateEl = document.getElementById("rep-compliance-rate");
    const violationsCountEl = document.getElementById("rep-total-violations");
    const topViolationEl = document.getElementById("rep-top-violation");

    if (totalEl) totalEl.textContent = totalCount;
    if (passRateEl) passRateEl.textContent = `${passRate}%`;
    if (violationsCountEl) violationsCountEl.textContent = totalViolationsCount;

    // Find top violation title
    let topVTitle = "None";
    let maxVCount = 0;
    Object.keys(violationTypeCounts).forEach((key) => {
      if (violationTypeCounts[key] > maxVCount) {
        maxVCount = violationTypeCounts[key];
        topVTitle = key;
      }
    });
    if (topViolationEl) topViolationEl.textContent = topVTitle;

    // 2. Render Pure SVG Charts
    renderMonthlyTrendsBarChart(inspections);
    renderViolationTypesChart(violationTypeCounts);

    // 3. Export CSV Button Binding
    const exportBtn = document.getElementById("btn-export-csv");
    if (exportBtn) {
      exportBtn.addEventListener("click", function () {
        exportInspectionsCSV(inspections);
      });
    }

    // 4. Print Report Button Binding
    const printBtn = document.getElementById("btn-print-report");
    if (printBtn) {
      printBtn.addEventListener("click", function () {
        window.print();
      });
    }
  });

  // Pure SVG Bar Chart for Monthly Activity
  function renderMonthlyTrendsBarChart(inspections) {
    const container = document.getElementById("monthly-chart-container");
    if (!container) return;

    const months = ["Apr", "May", "Jun", "Jul", "Aug", "Sep"];
    const monthCounts = { Apr: 0, May: 0, Jun: 0, Jul: 0, Aug: 0, Sep: 0 };

    inspections.forEach((insp) => {
      if (insp.date) {
        const d = new Date(insp.date);
        const mName = d.toLocaleString("default", { month: "short" });
        if (monthCounts[mName] !== undefined) {
          monthCounts[mName]++;
        }
      }
    });

    const maxVal = Math.max(...Object.values(monthCounts), 5);
    const svgWidth = 500;
    const svgHeight = 220;
    const barWidth = 44;
    const gap = 32;
    const startX = 40;

    let svgHtml = `
      <svg width="100%" height="100%" viewBox="0 0 ${svgWidth} ${svgHeight}" xmlns="http://www.w3.org/2000/svg">
        <!-- Grid lines -->
        <line x1="30" y1="180" x2="480" y2="180" stroke="#e2e8f0" stroke-width="1.5" />
        <line x1="30" y1="120" x2="480" y2="120" stroke="#e2e8f0" stroke-dasharray="4" />
        <line x1="30" y1="60" x2="480" y2="60" stroke="#e2e8f0" stroke-dasharray="4" />
    `;

    months.forEach((m, idx) => {
      const count = monthCounts[m];
      const barHeight = (count / maxVal) * 140;
      const x = startX + idx * (barWidth + gap);
      const y = 180 - barHeight;

      svgHtml += `
        <!-- Bar -->
        <rect x="${x}" y="${y}" width="${barWidth}" height="${barHeight}" rx="4" fill="#1e3a8a" />
        <!-- Value Label -->
        <text x="${x + barWidth / 2}" y="${y - 8}" font-size="11" font-weight="700" fill="#1e3a8a" text-anchor="middle">${count}</text>
        <!-- Month Label -->
        <text x="${x + barWidth / 2}" y="200" font-size="12" font-weight="600" fill="#64748b" text-anchor="middle">${m}</text>
      `;
    });

    svgHtml += `</svg>`;
    container.innerHTML = svgHtml;
  }

  // Pure SVG Horizontal Bar Chart for Violation Types
  function renderViolationTypesChart(violationCounts) {
    const container = document.getElementById("violations-chart-container");
    if (!container) return;

    const keys = Object.keys(violationCounts);
    if (keys.length === 0) {
      container.innerHTML = `<p class="text-muted">No violation data available.</p>`;
      return;
    }

    let html = `<div style="display:flex; flex-direction:column; gap:14px;">`;
    const maxVal = Math.max(...Object.values(violationCounts), 1);

    keys.forEach((title) => {
      const count = violationCounts[title];
      const pct = Math.round((count / maxVal) * 100);
      html += `
        <div>
          <div style="display:flex; justify-content:space-between; font-size:0.85rem; font-weight:600; margin-bottom:4px;">
            <span>${title}</span>
            <span style="color:var(--danger); font-weight:700;">${count} Incident(s)</span>
          </div>
          <div style="height:10px; background:var(--surface-alt); border-radius:10px; overflow:hidden;">
            <div style="height:100%; width:${pct}%; background:var(--danger); border-radius:10px;"></div>
          </div>
        </div>
      `;
    });

    html += `</div>`;
    container.innerHTML = html;
  }

  // Export CSV Helper
  function exportInspectionsCSV(inspections) {
    const headers = [
      "Inspection ID",
      "Product Name",
      "Brand",
      "Category",
      "Inspector Name",
      "Date",
      "Status",
      "MRP",
      "Net Quantity",
      "Violations Count"
    ];

    const rows = inspections.map((item) => [
      `"${item.id}"`,
      `"${item.productName.replace(/"/g, '""')}"`,
      `"${item.brand}"`,
      `"${item.category}"`,
      `"${item.inspectorName}"`,
      `"${item.date}"`,
      `"${item.status}"`,
      `"${item.mrp.replace(/"/g, '""')}"`,
      `"${item.netQuantity}"`,
      item.violations ? item.violations.length : 0
    ]);

    let csvContent = "data:text/csv;charset=utf-8," + [headers.join(","), ...rows.map((e) => e.join(","))].join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `Legal_Metrology_Inspections_${new Date().toISOString().split("T")[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    window.showToast("Inspection records CSV exported successfully.", "success");
  }
})();
