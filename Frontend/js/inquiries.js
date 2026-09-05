/* ==========================================================================
   AI PACKAGE INSPECTOR - INQUIRY MANAGEMENT ENGINE (js/inquiries.js)
   ========================================================================== */

(function () {
  const DEFAULT_INQUIRIES = [
    {
      id: "INQ-2026-001",
      inquiryId: "INQ-2026-001",
      subject: "Missing Consumer Care Address on Sunflower Oil Batch",
      product: "Fortune Sunlite Refined Sunflower Oil",
      manufacturer: "Adani Wilmar Limited",
      date: "2026-09-02",
      priority: "High",
      status: "Open",
      description: "Retail outlet inspection in North Zone revealed batch 88B missing complete postal address in the consumer care declaration block.",
      assignedOfficer: "Rahul Sharma"
    },
    {
      id: "INQ-2026-002",
      inquiryId: "INQ-2026-002",
      subject: "MRP Font Size Discrepancy Verification",
      product: "Aashirvaad Shudh Chakki Whole Wheat Atta",
      manufacturer: "ITC Limited",
      date: "2026-08-29",
      priority: "Medium",
      status: "Under Review",
      description: "Inquiry initiated regarding font height of MRP on 5kg bags. Sample sent to Metrology Standard Laboratory for digital micrometer height check.",
      assignedOfficer: "Priya Verma"
    },
    {
      id: "INQ-2026-003",
      inquiryId: "INQ-2026-003",
      subject: "Missing Toll-Free Helpline Number Complaint",
      product: "Tata Salt Vacuum Evaporated Iodised Salt",
      manufacturer: "Tata Consumer Products Limited",
      date: "2026-08-16",
      priority: "Critical",
      status: "Open",
      description: "Public grievance report received stating customer care helpline number printed on 1kg salt packs is invalid.",
      assignedOfficer: "Rahul Sharma"
    },
    {
      id: "INQ-2026-004",
      inquiryId: "INQ-2026-004",
      subject: "Net Quantity Font Height Deficiency Notice",
      product: "Surf Excel Easy Wash Detergent Powder",
      manufacturer: "Hindustan Unilever Limited",
      date: "2026-08-20",
      priority: "High",
      status: "Resolved",
      description: "Manufacturer submitted revised packaging artwork showing 4.2mm font height for net weight declaration as per Rule 7(1). Notice satisfied.",
      assignedOfficer: "Amit Patel"
    },
    {
      id: "INQ-2026-005",
      inquiryId: "INQ-2026-005",
      subject: "BIS Mark & License Number Verification",
      product: "Bisleri Premium Packaged Drinking Water",
      manufacturer: "Bisleri International Pvt Ltd",
      date: "2026-09-01",
      priority: "Low",
      status: "Closed",
      description: "Verified BIS certification IS 14543 CML number on 1L bottles. All documentation matched central registry.",
      assignedOfficer: "Priya Verma"
    },
    {
      id: "INQ-2026-006",
      inquiryId: "INQ-2026-006",
      subject: "Smudged Manufacturing Date Code Inquiry",
      product: "Dettol Liquid Handwash Original Pump",
      manufacturer: "Reckitt Benckiser (India) Pvt Ltd",
      date: "2026-07-24",
      priority: "Critical",
      status: "Under Review",
      description: "Distributor show-cause notice issued regarding 15 bottles with unreadable mfg date stamps at regional depot.",
      assignedOfficer: "Sunita Rao"
    },
    {
      id: "INQ-2026-007",
      inquiryId: "INQ-2026-007",
      subject: "Unit Sale Price Declaration Compliance",
      product: "Britannia Good Day Cashew Cookies",
      manufacturer: "Britannia Industries Limited",
      date: "2026-07-10",
      priority: "Medium",
      status: "Resolved",
      description: "Verified unit sale price (₹/g) printed on multi-pack outer carton in accordance with 2022 Legal Metrology Amendment.",
      assignedOfficer: "Rahul Sharma"
    },
    {
      id: "INQ-2026-008",
      inquiryId: "INQ-2026-008",
      subject: "Spice Net Weight Tare Weight Calibration Inquiry",
      product: "Everest Pure Turmeric Powder",
      manufacturer: "Everest Spices Ltd",
      date: "2026-08-12",
      priority: "Low",
      status: "Closed",
      description: "Random sampling verified gross and tare weight of 200g foil pouches. Zero discrepancy found.",
      assignedOfficer: "Amit Patel"
    },
    {
      id: "INQ-2026-009",
      inquiryId: "INQ-2026-009",
      subject: "Butter Refrigeration Density Weight Standard Verification",
      product: "Amul Pasteurised Salted Butter",
      manufacturer: "GCMMF Anand",
      date: "2026-09-04",
      priority: "Low",
      status: "Open",
      description: "Routine cold chain inspection check of 500g butter packs at central distribution cold storage.",
      assignedOfficer: "Rahul Sharma"
    },
    {
      id: "INQ-2026-010",
      inquiryId: "INQ-2026-010",
      subject: "Importer Name & Address Labeling Inquiry",
      product: "Sunsilk Thick & Long Shampoo",
      manufacturer: "Hindustan Unilever Limited",
      date: "2026-06-05",
      priority: "Medium",
      status: "Closed",
      description: "Domestic product confirmation completed. Product verified as manufactured in Baddi unit (Not imported).",
      assignedOfficer: "Sunita Rao"
    }
  ];

  window.getInquiries = function () {
    if (!localStorage.getItem("inquiries")) {
      localStorage.setItem("inquiries", JSON.stringify(DEFAULT_INQUIRIES));
    }
    return JSON.parse(localStorage.getItem("inquiries"));
  };

  let inquiries = [];

  document.addEventListener("DOMContentLoaded", function () {
    const user = window.requireAuth();
    if (!user) return;

    const tbody = document.getElementById("inquiries-tbody");
    if (!tbody) return;

    inquiries = window.getInquiries();

    // Bind Controls
    const searchInput = document.getElementById("inquiry-search");
    const statusSelect = document.getElementById("inquiry-status-filter");
    const prioritySelect = document.getElementById("inquiry-priority-filter");

    if (searchInput) searchInput.addEventListener("input", renderInquiriesTable);
    if (statusSelect) statusSelect.addEventListener("change", renderInquiriesTable);
    if (prioritySelect) prioritySelect.addEventListener("change", renderInquiriesTable);

    // New Inquiry Form Handler
    const newInquiryForm = document.getElementById("new-inquiry-form");
    if (newInquiryForm) {
      newInquiryForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const subject = document.getElementById("inq-subject").value.trim();
        const product = document.getElementById("inq-product").value.trim();
        const mfr = document.getElementById("inq-mfr").value.trim();
        const priority = document.getElementById("inq-priority").value;
        const description = document.getElementById("inq-desc").value.trim();

        if (!subject || !product || !description) {
          window.showToast("Please complete all required fields.", "warning");
          return;
        }

        const newId = `INQ-2026-${String(inquiries.length + 1).padStart(3, "0")}`;
        const newObj = {
          id: newId,
          inquiryId: newId,
          subject: subject,
          product: product,
          manufacturer: mfr || "Not Specified",
          date: new Date().toISOString().split("T")[0],
          priority: priority,
          status: "Open",
          description: description,
          assignedOfficer: user.name
        };

        inquiries.unshift(newObj);
        localStorage.setItem("inquiries", JSON.stringify(inquiries));

        window.showToast(`Inquiry ${newId} created successfully.`, "success");
        newInquiryForm.reset();

        const modal = document.getElementById("new-inquiry-modal");
        if (modal) modal.classList.remove("active");

        renderInquiriesTable();
      });
    }

    renderInquiriesTable();
  });

  function renderInquiriesTable() {
    const tbody = document.getElementById("inquiries-tbody");
    if (!tbody) return;

    const searchVal = (document.getElementById("inquiry-search")?.value || "").toLowerCase().trim();
    const statusVal = document.getElementById("inquiry-status-filter")?.value || "All";
    const priorityVal = document.getElementById("inquiry-priority-filter")?.value || "All";

    const filtered = inquiries.filter((item) => {
      const matchesSearch =
        item.inquiryId.toLowerCase().includes(searchVal) ||
        item.subject.toLowerCase().includes(searchVal) ||
        item.product.toLowerCase().includes(searchVal) ||
        item.assignedOfficer.toLowerCase().includes(searchVal);

      const matchesStatus = statusVal === "All" || item.status === statusVal;
      const matchesPriority = priorityVal === "All" || item.priority === priorityVal;

      return matchesSearch && matchesStatus && matchesPriority;
    });

    tbody.innerHTML = "";

    if (filtered.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7">
            <div class="empty-state-box">
              <div class="empty-state-title">No Inquiries Found</div>
              <div class="empty-state-desc">Try clearing your filters or create a new inquiry record.</div>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    filtered.forEach((item) => {
      const tr = document.createElement("tr");

      let prioClass = "medium";
      if (item.priority.toLowerCase() === "critical") prioClass = "high";
      if (item.priority.toLowerCase() === "high") prioClass = "high";
      if (item.priority.toLowerCase() === "low") prioClass = "low";

      let statusBadge = "badge-pending";
      if (item.status === "Open") statusBadge = "badge-danger";
      if (item.status === "Resolved" || item.status === "Closed") statusBadge = "badge-compliant";
      if (item.status === "Under Review") statusBadge = "badge-warning";

      tr.innerHTML = `
        <td class="table-id-cell">${item.inquiryId}</td>
        <td>
          <div class="font-bold" style="color:var(--primary-dark);">${item.subject}</div>
          <div class="table-product-brand">${item.product} • Mfr: ${item.manufacturer}</div>
        </td>
        <td>${item.date}</td>
        <td><span class="severity-tag ${prioClass}">${item.priority}</span></td>
        <td><span class="badge ${statusBadge}">${item.status}</span></td>
        <td>${item.assignedOfficer}</td>
        <td>
          <button class="btn btn-secondary btn-sm view-inquiry-btn" data-id="${item.id}">View Inquiry</button>
        </td>
      `;
      tbody.appendChild(tr);
    });

    // View Inquiry Detail Modal Listener
    document.querySelectorAll(".view-inquiry-btn").forEach((btn) => {
      btn.addEventListener("click", function () {
        const inqId = btn.getAttribute("data-id");
        const target = inquiries.find((i) => i.id === inqId);
        if (target) openInquiryDetailModal(target);
      });
    });
  }

  function openInquiryDetailModal(item) {
    let modal = document.getElementById("inquiry-detail-modal");
    if (!modal) {
      modal = document.createElement("div");
      modal.id = "inquiry-detail-modal";
      modal.className = "modal-overlay";
      modal.innerHTML = `
        <div class="modal-card" style="max-width: 580px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid var(--border); padding-bottom:12px;">
            <h3 class="modal-title" id="inq-modal-title">Inquiry Details</h3>
            <button class="btn btn-secondary btn-sm" id="close-inq-modal">✕</button>
          </div>
          <div id="inq-modal-body"></div>
          <div class="modal-actions" style="margin-top:20px;">
            <label style="font-size:0.85rem; font-weight:600; margin-right:auto;">Update Status:
              <select id="inq-update-status-select" class="filter-select" style="margin-left:8px;">
                <option value="Open">Open</option>
                <option value="Under Review">Under Review</option>
                <option value="Resolved">Resolved</option>
                <option value="Closed">Closed</option>
              </select>
            </label>
            <button class="btn btn-primary" id="save-inq-status-btn">Save Status</button>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
    }

    const titleEl = document.getElementById("inq-modal-title");
    const bodyEl = document.getElementById("inq-modal-body");
    const selectEl = document.getElementById("inq-update-status-select");
    const saveBtn = document.getElementById("save-inq-status-btn");
    const closeBtn = document.getElementById("close-inq-modal");

    if (titleEl) titleEl.textContent = `${item.inquiryId}: ${item.subject}`;
    if (selectEl) selectEl.value = item.status;

    if (bodyEl) {
      bodyEl.innerHTML = `
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; font-size:0.9rem;">
          <div><strong>Product:</strong> ${item.product}</div>
          <div><strong>Manufacturer:</strong> ${item.manufacturer}</div>
          <div><strong>Date Logged:</strong> ${item.date}</div>
          <div><strong>Assigned Officer:</strong> ${item.assignedOfficer}</div>
          <div><strong>Priority:</strong> <span class="severity-tag ${item.priority.toLowerCase() === "low" ? "low" : "high"}">${item.priority}</span></div>
          <div><strong>Current Status:</strong> ${item.status}</div>
        </div>
        <div style="background:var(--background); padding:14px; border-radius:var(--radius); border:1px solid var(--border);">
          <div style="font-weight:700; font-size:0.85rem; color:var(--text-muted); margin-bottom:4px;">INQUIRY DESCRIPTION & INVESTIGATION NOTES</div>
          <p style="font-size:0.92rem; color:var(--text);">${item.description}</p>
        </div>
      `;
    }

    modal.classList.add("active");

    const closeModal = () => modal.classList.remove("active");
    closeBtn.onclick = closeModal;

    saveBtn.onclick = function () {
      item.status = selectEl.value;
      localStorage.setItem("inquiries", JSON.stringify(inquiries));
      window.showToast(`Inquiry ${item.inquiryId} status updated to ${item.status}.`, "success");
      closeModal();
      renderInquiriesTable();
    };
  }
})();
