/* ==========================================================================
   AI PACKAGE INSPECTOR - PRODUCTS CATALOG & DETAILS ENGINE (js/products.js)
   ========================================================================== */

(function () {
  const DEFAULT_PRODUCTS = [
    {
      id: "P001",
      name: "Fortune Sunlite Refined Sunflower Oil",
      brand: "Fortune",
      category: "Cooking Oil",
      manufacturer: "Adani Wilmar Limited, Ahmedabad, Gujarat",
      packer: "Adani Wilmar Limited, Kutch, Gujarat",
      importer: "N/A (Domestic)",
      netQuantity: "1 Litre (910g at 30°C)",
      mrp: "₹165.00 (Incl. of all taxes)",
      mfgDate: "2026-06-15",
      packingDate: "2026-06-16",
      importDate: "N/A",
      batchNumber: "BATCH-OIL-2026-88B",
      consumerCare: "Call: 1800 233 0000 | Email: care@adaniwilmar.in",
      lastInspection: "2026-09-01",
      complianceStatus: "Compliant",
      image: "https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=400&auto=format&fit=crop&q=80"
    },
    {
      id: "P002",
      name: "Aashirvaad Shudh Chakki Whole Wheat Atta",
      brand: "Aashirvaad",
      category: "Atta & Flour",
      manufacturer: "ITC Limited, Kolkata, West Bengal",
      packer: "ITC Limited Foods Division, Haridwar",
      importer: "N/A (Domestic)",
      netQuantity: "5 kg",
      mrp: "₹245.00 (Incl. of all taxes)",
      mfgDate: "2026-07-20",
      packingDate: "2026-07-21",
      importDate: "N/A",
      batchNumber: "BATCH-ATTA-9921A",
      consumerCare: "Call: 1800 425 4444 | Email: itccares@itc.in",
      lastInspection: "2026-08-28",
      complianceStatus: "Compliant",
      image: "https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400&auto=format&fit=crop&q=80"
    },
    {
      id: "P003",
      name: "Tata Salt Vacuum Evaporated Iodised Salt",
      brand: "Tata",
      category: "Spices & Salt",
      manufacturer: "Tata Consumer Products Limited, Kolkata",
      packer: "Tata Salt Packaging Works, Mithapur",
      importer: "N/A (Domestic)",
      netQuantity: "1 kg",
      mrp: "₹28.00 (Incl. of all taxes)",
      mfgDate: "2026-08-01",
      packingDate: "2026-08-02",
      importDate: "N/A",
      batchNumber: "BATCH-SALT-554C",
      consumerCare: "Call: 1800 108 4488 | Email: customercare@tataconsumer.com",
      lastInspection: "2026-08-15",
      complianceStatus: "Non-Compliant",
      image: "https://images.unsplash.com/photo-1518110168401-f2877ee2c88c?w=400&auto=format&fit=crop&q=80"
    },
    {
      id: "P004",
      name: "Britannia Good Day Cashew Cookies",
      brand: "Britannia",
      category: "Biscuits & Bakery",
      manufacturer: "Britannia Industries Limited, Kolkata",
      packer: "Britannia Factory Unit 4, Ramanagara",
      importer: "N/A (Domestic)",
      netQuantity: "600 g",
      mrp: "₹120.00 (Incl. of all taxes)",
      mfgDate: "2026-08-10",
      packingDate: "2026-08-10",
      importDate: "N/A",
      batchNumber: "BATCH-GD-7741",
      consumerCare: "Call: 1800 425 2584 | Email: feedback@britindia.com",
      lastInspection: "2026-09-03",
      complianceStatus: "Compliant",
      image: "https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=400&auto=format&fit=crop&q=80"
    },
    {
      id: "P005",
      name: "Bisleri Premium Packaged Drinking Water",
      brand: "Bisleri",
      category: "Packaged Water",
      manufacturer: "Bisleri International Pvt Ltd, Mumbai",
      packer: "Bisleri Bottling Plant, Ghaziabad",
      importer: "N/A (Domestic)",
      netQuantity: "1 Litre",
      mrp: "₹20.00 (Incl. of all taxes)",
      mfgDate: "2026-08-25",
      packingDate: "2026-08-25",
      importDate: "N/A",
      batchNumber: "BATCH-BIS-339A",
      consumerCare: "Call: 1800 121 1007 | Email: customercare@bisleri.co.in",
      lastInspection: "2026-09-02",
      complianceStatus: "Compliant",
      image: "https://images.unsplash.com/photo-1548839140-29a749e1bc4e?w=400&auto=format&fit=crop&q=80"
    },
    {
      id: "P006",
      name: "Surf Excel Easy Wash Detergent Powder",
      brand: "Surf Excel",
      category: "Detergent & Laundry",
      manufacturer: "Hindustan Unilever Limited, Mumbai",
      packer: "Hindustan Unilever Ltd, Sumerpur",
      importer: "N/A (Domestic)",
      netQuantity: "1 kg",
      mrp: "₹140.00 (Incl. of all taxes)",
      mfgDate: "2026-07-05",
      packingDate: "2026-07-06",
      importDate: "N/A",
      batchNumber: "BATCH-SURF-1029",
      consumerCare: "Call: 1800 102 2221 | Email: lever.care@unilever.com",
      lastInspection: "2026-08-19",
      complianceStatus: "Non-Compliant",
      image: "https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=400&auto=format&fit=crop&q=80"
    },
    {
      id: "P007",
      name: "Sunsilk Thick & Long Shampoo",
      brand: "Sunsilk",
      category: "Shampoo & Personal Care",
      manufacturer: "Hindustan Unilever Limited, Mumbai",
      packer: "Hindustan Unilever Ltd, Baddi",
      importer: "N/A (Domestic)",
      netQuantity: "340 ml",
      mrp: "₹285.00 (Incl. of all taxes)",
      mfgDate: "2026-05-18",
      packingDate: "2026-05-19",
      importDate: "N/A",
      batchNumber: "BATCH-SUN-551X",
      consumerCare: "Call: 1800 102 2221 | Email: lever.care@unilever.com",
      lastInspection: "2026-08-30",
      complianceStatus: "Compliant",
      image: "https://images.unsplash.com/photo-1535585209827-a15fcdbc4c2d?w=400&auto=format&fit=crop&q=80"
    },
    {
      id: "P008",
      name: "Everest Pure Turmeric Powder",
      brand: "Everest",
      category: "Spices & Salt",
      manufacturer: "Everest Spices Ltd, Mumbai",
      packer: "Everest Spices Plant 2, Umbergaon",
      importer: "N/A (Domestic)",
      netQuantity: "200 g",
      mrp: "₹62.00 (Incl. of all taxes)",
      mfgDate: "2026-06-30",
      packingDate: "2026-07-01",
      importDate: "N/A",
      batchNumber: "BATCH-EV-TURM-404",
      consumerCare: "Call: 1800 229 988 | Email: customercare@everestspices.com",
      lastInspection: "2026-08-11",
      complianceStatus: "Compliant",
      image: "https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=400&auto=format&fit=crop&q=80"
    },
    {
      id: "P009",
      name: "Amul Pasteurised Salted Butter",
      brand: "Amul",
      category: "Dairy & Butter",
      manufacturer: "GCMMF Anand, Gujarat",
      packer: "Amul Dairy Plant 1, Anand",
      importer: "N/A (Domestic)",
      netQuantity: "500 g",
      mrp: "₹275.00 (Incl. of all taxes)",
      mfgDate: "2026-08-20",
      packingDate: "2026-08-20",
      importDate: "N/A",
      batchNumber: "BATCH-AMUL-B-998",
      consumerCare: "Call: 1800 258 3333 | Email: customercare@amul.coop",
      lastInspection: "2026-09-04",
      complianceStatus: "Compliant",
      image: "https://images.unsplash.com/photo-1589985270826-4b7bb135bc9d?w=400&auto=format&fit=crop&q=80"
    },
    {
      id: "P010",
      name: "Dettol Liquid Handwash Original Pump",
      brand: "Dettol",
      category: "Shampoo & Personal Care",
      manufacturer: "Reckitt Benckiser (India) Pvt Ltd, Gurugram",
      packer: "Reckitt Benckiser Unit 2, Baddi",
      importer: "N/A (Domestic)",
      netQuantity: "200 ml",
      mrp: "₹99.00 (Incl. of all taxes)",
      mfgDate: "2026-04-12",
      packingDate: "2026-04-14",
      importDate: "N/A",
      batchNumber: "BATCH-DET-7720",
      consumerCare: "Call: 1800 102 7325 | Email: IndiaConsumerCare@reckitt.com",
      lastInspection: "2026-07-22",
      complianceStatus: "Non-Compliant",
      image: "https://images.unsplash.com/photo-1608248597260-29177a64fa49?w=400&auto=format&fit=crop&q=80"
    }
  ];

  window.getProducts = function () {
    if (!localStorage.getItem("products")) {
      localStorage.setItem("products", JSON.stringify(DEFAULT_PRODUCTS));
    }
    return JSON.parse(localStorage.getItem("products"));
  };

  document.addEventListener("DOMContentLoaded", function () {
    const user = window.requireAuth();
    if (!user) return;

    // PAGE 1: PRODUCTS LIST
    const productGrid = document.getElementById("products-grid-container");
    if (productGrid) {
      renderProductsList();
    }

    // PAGE 2: PRODUCT DETAILS
    const productDetailsContainer = document.getElementById("product-details-container");
    if (productDetailsContainer) {
      renderProductDetailsPage();
    }
  });

  function renderProductsList() {
    const products = window.getProducts();
    const grid = document.getElementById("products-grid-container");
    const searchInput = document.getElementById("products-search");
    const categorySelect = document.getElementById("products-category-filter");
    const statusSelect = document.getElementById("products-status-filter");

    function filterAndRender() {
      const searchVal = (searchInput?.value || "").toLowerCase().trim();
      const catVal = categorySelect?.value || "All";
      const statusVal = statusSelect?.value || "All";

      const filtered = products.filter((p) => {
        const matchesSearch = p.name.toLowerCase().includes(searchVal) || p.brand.toLowerCase().includes(searchVal) || p.manufacturer.toLowerCase().includes(searchVal);
        const matchesCat = catVal === "All" || p.category === catVal;
        const matchesStatus = statusVal === "All" || p.complianceStatus === statusVal;
        return matchesSearch && matchesCat && matchesStatus;
      });

      grid.innerHTML = "";

      if (filtered.length === 0) {
        grid.innerHTML = `
          <div style="grid-column: 1 / -1;" class="empty-state-box">
            <div class="empty-state-title">No Packaged Commodities Found</div>
            <div class="empty-state-desc">Try changing your search keywords or filter dropdowns.</div>
          </div>
        `;
        return;
      }

      filtered.forEach((p) => {
        const card = document.createElement("div");
        card.className = "card";
        card.style.display = "flex";
        card.style.flexDirection = "column";
        card.style.justifySpaceBetween = "space-between";

        let badgeClass = "badge-compliant";
        if (p.complianceStatus === "Non-Compliant") badgeClass = "badge-non-compliant";
        if (p.complianceStatus === "Pending") badgeClass = "badge-pending";

        card.innerHTML = `
          <div style="position:relative; margin:-24px -24px 16px -24px; height:160px; overflow:hidden; border-radius: 10px 10px 0 0;">
            <img src="${p.image}" alt="${p.name}" style="width:100%; height:100%; object-fit:cover;" />
            <span class="badge ${badgeClass}" style="position:absolute; top:12px; right:12px; box-shadow: var(--shadow-sm);">${p.complianceStatus}</span>
          </div>

          <div style="flex:1;">
            <div class="text-muted" style="font-size:0.78rem; text-transform:uppercase; font-weight:700;">${p.category} • ${p.brand}</div>
            <h4 style="font-size:1.05rem; font-weight:700; color:var(--primary-dark); margin:4px 0 10px 0; line-height:1.3;">${p.name}</h4>
            <p style="font-size:0.84rem; color:var(--text-muted); margin-bottom:12px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
              <strong>Mfr:</strong> ${p.manufacturer}
            </p>
          </div>

          <div style="border-top:1px solid var(--border); padding-top:12px; margin-top:12px; display:flex; align-items:center; justify-content:space-between; font-size:0.82rem;">
            <span class="text-muted">MRP: <strong>${p.mrp.split("(")[0]}</strong></span>
            <a href="product-details.html?id=${p.id}" class="btn btn-secondary btn-sm">Product Profile →</a>
          </div>
        `;
        grid.appendChild(card);
      });
    }

    if (searchInput) searchInput.addEventListener("input", filterAndRender);
    if (categorySelect) categorySelect.addEventListener("change", filterAndRender);
    if (statusSelect) statusSelect.addEventListener("change", filterAndRender);

    filterAndRender();
  }

  function renderProductDetailsPage() {
    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get("id") || "P001";

    const products = window.getProducts();
    const product = products.find((p) => p.id === productId) || products[0];

    const inspections = window.getInspections ? window.getInspections() : JSON.parse(localStorage.getItem("inspections") || "[]");
    const productInspections = inspections.filter((i) => i.productId === product.id || i.productName.toLowerCase() === product.name.toLowerCase());

    // Title & Specs
    const nameEl = document.getElementById("p-detail-name");
    const brandEl = document.getElementById("p-detail-brand");
    const categoryEl = document.getElementById("p-detail-category");
    const statusBadgeEl = document.getElementById("p-detail-status-badge");
    const imgEl = document.getElementById("p-detail-img");

    if (nameEl) nameEl.textContent = product.name;
    if (brandEl) brandEl.textContent = product.brand;
    if (categoryEl) categoryEl.textContent = product.category;
    if (imgEl) imgEl.src = product.image;

    if (statusBadgeEl) {
      statusBadgeEl.textContent = product.complianceStatus;
      let badgeClass = "badge-compliant";
      if (product.complianceStatus === "Non-Compliant") badgeClass = "badge-non-compliant";
      statusBadgeEl.className = `badge ${badgeClass}`;
    }

    // Specifications Grid
    const specsEl = document.getElementById("p-detail-specs");
    if (specsEl) {
      specsEl.innerHTML = `
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
          <div>
            <p class="text-muted" style="font-size:0.8rem;">MANUFACTURER</p>
            <p class="font-bold" style="margin-bottom:12px;">${product.manufacturer}</p>
            <p class="text-muted" style="font-size:0.8rem;">PACKER DETAILS</p>
            <p class="font-bold" style="margin-bottom:12px;">${product.packer || "Same as Manufacturer"}</p>
            <p class="text-muted" style="font-size:0.8rem;">NET QUANTITY</p>
            <p class="font-bold" style="margin-bottom:12px;">${product.netQuantity}</p>
          </div>
          <div>
            <p class="text-muted" style="font-size:0.8rem;">MRP DECLARATION</p>
            <p class="font-bold" style="margin-bottom:12px;">${product.mrp}</p>
            <p class="text-muted" style="font-size:0.8rem;">MFG / PACKING DATE</p>
            <p class="font-bold" style="margin-bottom:12px;">${product.mfgDate}</p>
            <p class="text-muted" style="font-size:0.8rem;">CONSUMER CARE</p>
            <p class="font-bold" style="margin-bottom:12px;">${product.consumerCare}</p>
          </div>
        </div>
      `;
    }

    // Inspection History Timeline Render
    const timelineEl = document.getElementById("p-detail-timeline");
    if (timelineEl) {
      timelineEl.innerHTML = "";
      if (productInspections.length === 0) {
        timelineEl.innerHTML = `<p class="text-muted">No past inspection records registered for this commodity.</p>`;
      } else {
        productInspections.forEach((insp) => {
          const item = document.createElement("div");
          item.style.paddingLeft = "20px";
          item.style.borderLeft = "3px solid var(--primary)";
          item.style.marginBottom = "20px";
          item.style.position = "relative";

          let isPass = insp.status === "Compliant";

          item.innerHTML = `
            <div style="font-size:0.82rem; font-weight:700; color:var(--text-muted);">${insp.date} • ID: ${insp.id}</div>
            <div style="display:flex; align-items:center; gap:10px; margin:4px 0;">
              <span class="badge ${isPass ? "badge-compliant" : "badge-non-compliant"}">${insp.status}</span>
              <span style="font-size:0.9rem; font-weight:600;">Inspector: ${insp.inspectorName}</span>
            </div>
            <p style="font-size:0.88rem; color:var(--text); margin-top:4px;">${insp.notes || "Inspection completed."}</p>
          `;
          timelineEl.appendChild(item);
        });
      }
    }
  }
})();
