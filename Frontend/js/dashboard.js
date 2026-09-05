/* ==========================================================================
   AI PACKAGE INSPECTOR - INSPECTOR DASHBOARD LOGIC (js/dashboard.js)
   ========================================================================== */

(function () {
  const DEFAULT_INSPECTIONS = [
    {
      id: "INS-2026-001",
      productId: "P001",
      productName: "Fortune Sunlite Refined Sunflower Oil",
      brand: "Fortune",
      category: "Cooking Oil",
      inspectorId: "U002",
      inspectorName: "Rahul Sharma",
      date: "2026-09-01",
      status: "Compliant",
      netQuantity: "1 Litre (910g at 30°C)",
      mrp: "₹165.00 (Incl. of all taxes)",
      mfgDate: "2026-06-15",
      manufacturer: "Adani Wilmar Limited, Ahmedabad, Gujarat",
      packer: "Adani Wilmar Limited, Mundra, Kutch, Gujarat",
      importer: "N/A (Domestic)",
      consumerCare: "Call: 1800 233 0000 | Email: care@adaniwilmar.in",
      checklist: { manufacturerDetails: true, netQuantity: true, mrp: true, mfgDate: true, consumerCare: true, importerDetails: true },
      violations: [],
      notes: "All mandatory declarations printed clearly on front and back packaging panel in compliance with Rule 6 of Legal Metrology (Packaged Commodities) Rules 2011."
    },
    {
      id: "INS-2026-002",
      productId: "P003",
      productName: "Tata Salt Vacuum Evaporated Iodised Salt",
      brand: "Tata",
      category: "Spices & Salt",
      inspectorId: "U002",
      inspectorName: "Rahul Sharma",
      date: "2026-08-15",
      status: "Non-Compliant",
      netQuantity: "1 kg",
      mrp: "₹28.00",
      mfgDate: "2026-08-01",
      manufacturer: "Tata Consumer Products Limited, Kolkata",
      packer: "Tata Salt Packaging Works, Mithapur",
      importer: "N/A (Domestic)",
      consumerCare: "Missing Email address",
      checklist: { manufacturerDetails: true, netQuantity: true, mrp: false, mfgDate: true, consumerCare: false, importerDetails: true },
      violations: [
        { title: "Consumer Care Email Declaration Missing", description: "The Consumer Care details missing official email address for grievance redressal.", severity: "High", rule: "Rule 6(1)(h)" },
        { title: "MRP Format Violation", description: "MRP missing required suffix '(Incl. of all taxes)'.", severity: "Medium", rule: "Rule 6(1)(e)" }
      ],
      notes: "Non-compliance notice issued to distributor. Mandatory email missing from customer care block."
    },
    {
      id: "INS-2026-003",
      productId: "P004",
      productName: "Britannia Good Day Cashew Cookies",
      brand: "Britannia",
      category: "Biscuits & Bakery",
      inspectorId: "U003",
      inspectorName: "Priya Verma",
      date: "2026-09-03",
      status: "Compliant",
      netQuantity: "600 g",
      mrp: "₹120.00 (Incl. of all taxes)",
      mfgDate: "2026-08-10",
      manufacturer: "Britannia Industries Limited, Kolkata",
      packer: "Britannia Factory Unit 4, Ramanagara, Karnataka",
      importer: "N/A (Domestic)",
      consumerCare: "Call: 1800 425 2584 | Email: feedback@britindia.com",
      checklist: { manufacturerDetails: true, netQuantity: true, mrp: true, mfgDate: true, consumerCare: true, importerDetails: true },
      violations: [],
      notes: "Verified font height for net quantity and MRP declarations. Fully compliant."
    },
    {
      id: "INS-2026-004",
      productId: "P006",
      productName: "Surf Excel Easy Wash Detergent Powder",
      brand: "Surf Excel",
      category: "Detergent & Laundry",
      inspectorId: "U004",
      inspectorName: "Amit Patel",
      date: "2026-08-19",
      status: "Non-Compliant",
      netQuantity: "1 kg",
      mrp: "₹140.00",
      mfgDate: "2026-07-05",
      manufacturer: "Hindustan Unilever Limited, Mumbai",
      packer: "Hindustan Unilever Ltd, Sumerpur",
      importer: "N/A (Domestic)",
      consumerCare: "Call: 1800 102 2221 | Email: lever.care@unilever.com",
      checklist: { manufacturerDetails: true, netQuantity: false, mrp: true, mfgDate: true, consumerCare: true, importerDetails: true },
      violations: [
        { title: "Font Size Non-Compliance for Net Quantity", description: "Font height of Net Quantity declaration is 2mm, which is below the mandatory 4mm requirement for packages exceeding 500g.", severity: "Medium", rule: "Rule 7(1)" }
      ],
      notes: "Small font size used for net weight on outer package wrapper."
    },
    {
      id: "INS-2026-005",
      productId: "P005",
      productName: "Bisleri Premium Packaged Drinking Water",
      brand: "Bisleri",
      category: "Packaged Water",
      inspectorId: "U003",
      inspectorName: "Priya Verma",
      date: "2026-09-02",
      status: "Compliant",
      netQuantity: "1 Litre",
      mrp: "₹20.00 (Incl. of all taxes)",
      mfgDate: "2026-08-25",
      manufacturer: "Bisleri International Pvt Ltd, Mumbai",
      packer: "Bisleri Bottling Plant, Ghaziabad",
      importer: "N/A (Domestic)",
      consumerCare: "Call: 1800 121 1007 | Email: customercare@bisleri.co.in",
      checklist: { manufacturerDetails: true, netQuantity: true, mrp: true, mfgDate: true, consumerCare: true, importerDetails: true },
      violations: [],
      notes: "Standard 1L PET bottle verified. Batch info, IS 14543 BIS mark and declarations verified."
    },
    {
      id: "INS-2026-006",
      productId: "P002",
      productName: "Aashirvaad Shudh Chakki Whole Wheat Atta",
      brand: "Aashirvaad",
      category: "Atta & Flour",
      inspectorId: "U002",
      inspectorName: "Rahul Sharma",
      date: "2026-08-28",
      status: "Compliant",
      netQuantity: "5 kg",
      mrp: "₹245.00 (Incl. of all taxes)",
      mfgDate: "2026-07-20",
      manufacturer: "ITC Limited, Kolkata",
      packer: "ITC Limited Foods Division, Haridwar",
      importer: "N/A (Domestic)",
      consumerCare: "Call: 1800 425 4444 | Email: itccares@itc.in",
      checklist: { manufacturerDetails: true, netQuantity: true, mrp: true, mfgDate: true, consumerCare: true, importerDetails: true },
      violations: [],
      notes: "5kg bag inspected at wholesale outlet. All declarations present and prominent."
    },
    {
      id: "INS-2026-007",
      productId: "P010",
      productName: "Dettol Liquid Handwash Original Pump",
      brand: "Dettol",
      category: "Shampoo & Personal Care",
      inspectorId: "U005",
      inspectorName: "Sunita Rao",
      date: "2026-07-22",
      status: "Non-Compliant",
      netQuantity: "200 ml",
      mrp: "₹99.00 (Incl. of all taxes)",
      mfgDate: "Unclear / Smudged",
      manufacturer: "Reckitt Benckiser (India) Pvt Ltd, Gurugram",
      packer: "Reckitt Benckiser Unit 2, Baddi",
      importer: "N/A (Domestic)",
      consumerCare: "Call: 1800 102 7325 | Email: IndiaConsumerCare@reckitt.com",
      checklist: { manufacturerDetails: true, netQuantity: true, mrp: true, mfgDate: false, consumerCare: true, importerDetails: true },
      violations: [
        { title: "Manufacturing Date Smudged & Unreadable", description: "Month and Year of manufacture inkjet printing is smudged and unreadable on the bottle shoulder.", severity: "High", rule: "Rule 6(1)(d)" }
      ],
      notes: "Defaced mfg date code batch on 15 bottles in sample batch."
    },
    {
      id: "INS-2026-008",
      productId: "P008",
      productName: "Everest Pure Turmeric Powder",
      brand: "Everest",
      category: "Spices & Salt",
      inspectorId: "U004",
      inspectorName: "Amit Patel",
      date: "2026-08-11",
      status: "Compliant",
      netQuantity: "200 g",
      mrp: "₹62.00 (Incl. of all taxes)",
      mfgDate: "2026-06-30",
      manufacturer: "Everest Spices Ltd, Mumbai",
      packer: "Everest Spices Plant 2, Umbergaon",
      importer: "N/A (Domestic)",
      consumerCare: "Call: 1800 229 988 | Email: customercare@everestspices.com",
      checklist: { manufacturerDetails: true, netQuantity: true, mrp: true, mfgDate: true, consumerCare: true, importerDetails: true },
      violations: [],
      notes: "Inspected at superstore shelf. Package text legibility high."
    },
    {
      id: "INS-2026-009",
      productId: "P009",
      productName: "Amul Pasteurised Salted Butter",
      brand: "Amul",
      category: "Dairy & Butter",
      inspectorId: "U002",
      inspectorName: "Rahul Sharma",
      date: "2026-09-04",
      status: "Compliant",
      netQuantity: "500 g",
      mrp: "₹275.00 (Incl. of all taxes)",
      mfgDate: "2026-08-20",
      manufacturer: "GCMMF Anand, Gujarat",
      packer: "Amul Dairy Plant 1, Anand",
      importer: "N/A (Domestic)",
      consumerCare: "Call: 1800 258 3333 | Email: customercare@amul.coop",
      checklist: { manufacturerDetails: true, netQuantity: true, mrp: true, mfgDate: true, consumerCare: true, importerDetails: true },
      violations: [],
      notes: "Cold storage packaging verified. Clear date stamp and net weight statement."
    },
    {
      id: "INS-2026-010",
      productId: "P007",
      productName: "Sunsilk Thick & Long Shampoo",
      brand: "Sunsilk",
      category: "Shampoo & Personal Care",
      inspectorId: "U003",
      inspectorName: "Priya Verma",
      date: "2026-08-30",
      status: "Compliant",
      netQuantity: "340 ml",
      mrp: "₹285.00 (Incl. of all taxes)",
      mfgDate: "2026-05-18",
      manufacturer: "Hindustan Unilever Limited, Mumbai",
      packer: "Hindustan Unilever Ltd, Baddi",
      importer: "N/A (Domestic)",
      consumerCare: "Call: 1800 102 2221 | Email: lever.care@unilever.com",
      checklist: { manufacturerDetails: true, netQuantity: true, mrp: true, mfgDate: true, consumerCare: true, importerDetails: true },
      violations: [],
      notes: "All front and back labels verified."
    },
    {
      id: "INS-2026-011",
      productId: "P001",
      productName: "Fortune Sunlite Refined Sunflower Oil",
      brand: "Fortune",
      category: "Cooking Oil",
      inspectorId: "U005",
      inspectorName: "Sunita Rao",
      date: "2026-07-14",
      status: "Pending",
      netQuantity: "1 Litre",
      mrp: "₹165.00",
      mfgDate: "2026-06-15",
      manufacturer: "Adani Wilmar Limited",
      packer: "Adani Wilmar Limited",
      importer: "N/A",
      consumerCare: "Call: 1800 233 0000",
      checklist: { manufacturerDetails: true, netQuantity: true, mrp: true, mfgDate: true, consumerCare: false, importerDetails: true },
      violations: [{ title: "Awaiting Official Distributor Clarification", description: "Distributor requested time to submit laboratory weight verification certificate.", severity: "Low", rule: "Rule 18 Verification" }],
      notes: "Sample sent for lab density calculation at 30 degrees C."
    }
  ];

  window.getInspections = function () {
    if (!localStorage.getItem("inspections")) {
      localStorage.setItem("inspections", JSON.stringify(DEFAULT_INSPECTIONS));
    }
    return JSON.parse(localStorage.getItem("inspections"));
  };

  document.addEventListener("DOMContentLoaded", function () {
    const user = window.requireAuth();
    if (!user) return;

    // Greeting Message
    const welcomeHeader = document.getElementById("welcome-heading");
    if (welcomeHeader) {
      const hrs = new Date().getHours();
      let timeOfDay = "Morning";
      if (hrs >= 12 && hrs < 17) timeOfDay = "Afternoon";
      if (hrs >= 17) timeOfDay = "Evening";
      welcomeHeader.textContent = `Good ${timeOfDay}, ${user.name}`;
    }

    const inspections = window.getInspections();

    // Stats Calculations
    const totalCount = inspections.length;
    const compliantCount = inspections.filter((i) => i.status === "Compliant").length;
    const nonCompliantCount = inspections.filter((i) => i.status === "Non-Compliant").length;
    const pendingCount = inspections.filter((i) => i.status === "Pending").length;

    // Render Stats
    const totalEl = document.getElementById("stat-total");
    const compliantEl = document.getElementById("stat-compliant");
    const nonCompliantEl = document.getElementById("stat-non-compliant");
    const pendingEl = document.getElementById("stat-pending");

    if (totalEl) totalEl.textContent = totalCount;
    if (compliantEl) compliantEl.textContent = compliantCount;
    if (nonCompliantEl) nonCompliantEl.textContent = nonCompliantCount;
    if (pendingEl) pendingEl.textContent = pendingCount;

    // Compliance Gauge Render
    const passRate = totalCount > 0 ? Math.round((compliantCount / totalCount) * 100) : 0;
    const gaugeValEl = document.getElementById("gauge-percentage");
    const donutCircle = document.getElementById("donut-fill-circle");

    if (gaugeValEl) gaugeValEl.textContent = `${passRate}%`;
    if (donutCircle) {
      // Circumference of r=70 is ~440
      const strokeDashoffset = 440 - (440 * passRate) / 100;
      donutCircle.style.strokeDashoffset = strokeDashoffset;
    }

    // Render Recent Inspections Table (Top 5)
    const recentTableBody = document.getElementById("recent-inspections-tbody");
    if (recentTableBody) {
      recentTableBody.innerHTML = "";
      const recentList = inspections.slice(0, 5);

      recentList.forEach((item) => {
        const tr = document.createElement("tr");

        let badgeClass = "badge-compliant";
        if (item.status === "Non-Compliant") badgeClass = "badge-non-compliant";
        if (item.status === "Pending") badgeClass = "badge-pending";

        tr.innerHTML = `
          <td class="table-id-cell">${item.id}</td>
          <td>
            <div class="table-product-cell">
              <span class="table-product-title">${item.productName}</span>
            </div>
          </td>
          <td>${item.date}</td>
          <td><span class="badge ${badgeClass}">${item.status}</span></td>
          <td>${item.inspectorName}</td>
          <td>
            <a href="inspection-result.html?id=${item.id}" class="btn btn-secondary btn-sm">View</a>
          </td>
        `;
        recentTableBody.appendChild(tr);
      });
    }
  });
})();
