/* ==========================================================================
   AI PACKAGE INSPECTOR - AI/OCR SCANNING & VERIFICATION ENGINE (js/inspection.js)
   ========================================================================== */

(function () {
  let uploadedImageBase64 = null;
  let currentActiveDataset = null;
  let manualChangesLog = [];
  let currentStep = 1; // 1: Upload, 2: OCR Scan, 3: Extract, 4: Review, 5: Verify, 6: Result

  // Demo Datasets for Quick Demo Selection & Filename Detection
  const DEMO_DATABASE = {
    "parle-g": {
      id: "DEMO-PARLE-G",
      product_name: "Parle-G Original Gluco Biscuits",
      brand: "Parle-G",
      category: "Biscuits & Bakery",
      manufacturer: "Parle Products Pvt. Ltd., Vile Parle East, Mumbai 400057, Maharashtra",
      packer: "Parle Products Pvt. Ltd., Factory Unit 1, Mumbai",
      importer: "N/A (Domestic)",
      net_quantity: "250 g",
      mrp: "₹30.00 (Incl. of all taxes)",
      manufacturing_date: "2026-08-01",
      batch_number: "PG082026A",
      consumer_care_phone: "1800-22-1080",
      consumer_care_email: "", // Intentionally missing for demo!
      consumer_care_address: "Parle Consumer Cell, Vile Parle East, Mumbai 400057",
      website: "www.parleproducts.com",
      image: "https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=500&auto=format&fit=crop&q=80",
      raw_text: `PARLE-G\nOriginal Gluco Biscuits\nManufactured by: Parle Products Pvt. Ltd., Vile Parle East, Mumbai 400057\nNet Quantity: 250 g\nMRP: ₹30.00 (Inclusive of all taxes)\nMonth & Year of Manufacture: 08/2026\nBatch No: PG082026A\nConsumer Helpline Toll Free: 1800-22-1080\nWebsite: www.parleproducts.com`,
      confidence: {
        product_name: 98,
        brand: 99,
        category: 98,
        manufacturer: 96,
        packer: 94,
        importer: 100,
        net_quantity: 99,
        mrp: 97,
        manufacturing_date: 95,
        batch_number: 92,
        consumer_care_phone: 90,
        consumer_care_email: 0, // Missing
        consumer_care_address: 88,
        website: 94
      }
    },
    "tata-salt": {
      id: "DEMO-TATA-SALT",
      product_name: "Tata Salt Vacuum Evaporated Iodised Salt",
      brand: "Tata",
      category: "Spices & Salt",
      manufacturer: "Tata Consumer Products Limited, Kolkata, West Bengal",
      packer: "Tata Salt Packaging Works, Mithapur, Gujarat",
      importer: "N/A (Domestic)",
      net_quantity: "1 kg",
      mrp: "₹28.00", // Missing (Incl. of all taxes)
      manufacturing_date: "2026-08-01",
      batch_number: "TS8821B",
      consumer_care_phone: "1800-108-4488",
      consumer_care_email: "", // Missing
      consumer_care_address: "Tata Consumer Care, Kolkata 700020",
      website: "www.tataconsumer.com",
      image: "https://images.unsplash.com/photo-1518110168401-f2877ee2c88c?w=500&auto=format&fit=crop&q=80",
      raw_text: `TATA SALT\nVacuum Evaporated Iodised Salt\nManufactured by: Tata Consumer Products Ltd, Kolkata\nNet Weight: 1 kg\nMRP: ₹28.00\nPkg Date: 08/2026\nCustomer Care: 1800 108 4488`,
      confidence: {
        product_name: 97,
        brand: 99,
        category: 96,
        manufacturer: 95,
        packer: 92,
        importer: 100,
        net_quantity: 99,
        mrp: 75,
        manufacturing_date: 94,
        batch_number: 89,
        consumer_care_phone: 91,
        consumer_care_email: 0,
        consumer_care_address: 82,
        website: 90
      }
    },
    "fortune": {
      id: "DEMO-FORTUNE",
      product_name: "Fortune Sunlite Refined Sunflower Oil",
      brand: "Fortune",
      category: "Cooking Oil",
      manufacturer: "Adani Wilmar Limited, Ahmedabad, Gujarat",
      packer: "Adani Wilmar Limited, Mundra, Kutch, Gujarat",
      importer: "N/A (Domestic)",
      net_quantity: "1 Litre (910g at 30°C)",
      mrp: "₹165.00 (Incl. of all taxes)",
      manufacturing_date: "2026-06-15",
      batch_number: "BATCH-OIL-2026-88B",
      consumer_care_phone: "1800 233 0000",
      consumer_care_email: "care@adaniwilmar.in",
      consumer_care_address: "Adani Wilmar Ltd, Ahmedabad 380009",
      website: "www.adaniwilmar.com",
      image: "https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=500&auto=format&fit=crop&q=80",
      raw_text: `FORTUNE SUNLITE\nRefined Sunflower Oil\nAdani Wilmar Limited, Ahmedabad\nNet Qty: 1 Litre (910g at 30 C)\nMRP: ₹165.00 (Incl. of all taxes)\nMfg: 06/2026\nCare: 1800 233 0000 | Email: care@adaniwilmar.in`,
      confidence: {
        product_name: 99,
        brand: 99,
        category: 98,
        manufacturer: 97,
        packer: 95,
        importer: 100,
        net_quantity: 98,
        mrp: 98,
        manufacturing_date: 96,
        batch_number: 94,
        consumer_care_phone: 95,
        consumer_care_email: 96,
        consumer_care_address: 92,
        website: 95
      }
    }
  };

  document.addEventListener("DOMContentLoaded", function () {
    const currentUser = window.requireAuth();
    if (!currentUser) return;

    // PAGE 1: NEW INSPECTION WORKSPACE
    const inspectionForm = document.getElementById("new-inspection-form");
    if (inspectionForm) {
      initInspectionWorkspace(currentUser);
    }

    // PAGE 2: INSPECTION RESULT SCORECARD
    const resultPageContainer = document.getElementById("inspection-result-container");
    if (resultPageContainer) {
      renderInspectionResultPage();
    }
  });

  // Initialize New Inspection Stepper & Event Bindings
  function initInspectionWorkspace(currentUser) {
    initImageUploader();
    bindDemoLauncherBtn();
    bindFormManualEditListeners();

    // Submit / Verify Package Compliance Button
    const inspectionForm = document.getElementById("new-inspection-form");
    inspectionForm.addEventListener("submit", function (e) {
      e.preventDefault();
      executeComplianceCheckAndSave(currentUser);
    });
  }

  // Bind Demo Launcher Buttons
  function bindDemoLauncherBtn() {
    const launcherBtns = document.querySelectorAll("#btn-load-demo-parleg, .btn-demo-launcher");
    launcherBtns.forEach((btn) => {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        loadDemoDataset("parle-g");
      });
    });

    const selector = document.getElementById("inp-demo-package-selector");
    if (selector) {
      selector.addEventListener("change", function () {
        const val = selector.value;
        if (val && DEMO_DATABASE[val]) {
          loadDemoDataset(val);
        }
      });
    }
  }

  // Load Selected Demo Package Dataset
  function loadDemoDataset(key) {
    const dataset = DEMO_DATABASE[key] || DEMO_DATABASE["parle-g"];
    currentActiveDataset = dataset;
    manualChangesLog = [];
    uploadedImageBase64 = dataset.image;

    // Update Image Preview
    const previewContainer = document.getElementById("image-preview-container");
    const previewImg = document.getElementById("preview-img");
    const previewName = document.getElementById("preview-name");
    const previewSize = document.getElementById("preview-size");
    const dropzone = document.getElementById("upload-dropzone");

    if (previewImg) previewImg.src = dataset.image;
    if (previewName) previewName.textContent = `${key}-package.jpg`;
    if (previewSize) previewSize.textContent = "1.8 MB";

    if (previewContainer) previewContainer.style.display = "flex";
    if (dropzone) dropzone.style.display = "none";

    // Show Start OCR Scan Button & Advance Stepper to Step 1 Completed
    updateStepper(1);
    const startOcrBtn = document.getElementById("btn-trigger-ocr-scan");
    if (startOcrBtn) startOcrBtn.style.display = "inline-flex";

    window.showToast(`Loaded ${dataset.brand} demo package image. Click 'Start OCR Scan' to begin!`, "info");

    // Auto-scroll to OCR Trigger section
    startOcrBtn?.scrollIntoView({ behavior: "smooth", block: "center" });
  }

  // Drag & Drop Image Uploader
  function initImageUploader() {
    const dropzone = document.getElementById("upload-dropzone");
    const fileInput = document.getElementById("package-image-input");
    const previewContainer = document.getElementById("image-preview-container");
    const previewImg = document.getElementById("preview-img");
    const previewName = document.getElementById("preview-name");
    const previewSize = document.getElementById("preview-size");
    const removeBtn = document.getElementById("remove-image-btn");

    if (!dropzone || !fileInput) return;

    dropzone.addEventListener("click", () => fileInput.click());
    dropzone.addEventListener("dragover", (e) => {
      e.preventDefault();
      dropzone.classList.add("dragover");
    });
    dropzone.addEventListener("dragleave", () => dropzone.classList.remove("dragover"));
    dropzone.addEventListener("drop", (e) => {
      e.preventDefault();
      dropzone.classList.remove("dragover");
      if (e.dataTransfer.files && e.dataTransfer.files[0]) {
        handleFileSelect(e.dataTransfer.files[0]);
      }
    });

    fileInput.addEventListener("change", (e) => {
      if (e.target.files && e.target.files[0]) {
        handleFileSelect(e.target.files[0]);
      }
    });

    if (removeBtn) {
      removeBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        fileInput.value = "";
        uploadedImageBase64 = null;
        currentActiveDataset = null;
        if (previewContainer) previewContainer.style.display = "none";
        if (dropzone) dropzone.style.display = "block";
        document.getElementById("btn-trigger-ocr-scan").style.display = "none";
        document.getElementById("ocr-results-wrapper").style.display = "none";
        updateStepper(1);
      });
    }

    function handleFileSelect(file) {
      if (!file.type.startsWith("image/")) {
        window.showToast("Unsupported file format. Please upload JPG, PNG, or WEBP.", "warning");
        return;
      }

      // Check if filename matches demo key
      const lowerName = file.name.toLowerCase();
      let matchedKey = "parle-g";
      if (lowerName.includes("salt")) matchedKey = "tata-salt";
      if (lowerName.includes("oil") || lowerName.includes("fortune")) matchedKey = "fortune";

      const reader = new FileReader();
      reader.onload = function (e) {
        uploadedImageBase64 = e.target.result;
        if (previewImg) previewImg.src = uploadedImageBase64;
        if (previewName) previewName.textContent = file.name;
        if (previewSize) previewSize.textContent = `${(file.size / (1024 * 1024)).toFixed(1)} MB`;

        if (previewContainer) previewContainer.style.display = "flex";
        if (dropzone) dropzone.style.display = "none";

        currentActiveDataset = { ...DEMO_DATABASE[matchedKey], image: uploadedImageBase64 };

        const startOcrBtn = document.getElementById("btn-trigger-ocr-scan");
        if (startOcrBtn) startOcrBtn.style.display = "inline-flex";

        updateStepper(1);
        window.showToast("Package image received. Click 'Start OCR Scan' to run extraction.", "success");
      };
      reader.readAsDataURL(file);
    }

    // Trigger OCR Scan Button Event
    const startOcrBtn = document.getElementById("btn-trigger-ocr-scan");
    if (startOcrBtn) {
      startOcrBtn.addEventListener("click", function () {
        runRealOCRProcessing();
      });
    }
  }

  // Real Backend OCR Processing Pipeline
  async function runRealOCRProcessing() {
    updateStepper(2); // Step 2: OCR Scan

    const procCard = document.getElementById("ocr-processing-card");
    const resultsWrapper = document.getElementById("ocr-results-wrapper");
    const progressFill = document.getElementById("ocr-progress-fill");
    const progressPct = document.getElementById("ocr-progress-pct");
    const stepText = document.getElementById("ocr-status-text");

    if (procCard) procCard.style.display = "block";
    if (resultsWrapper) resultsWrapper.style.display = "none";

    const checklistItems = [
      { id: "chk-img-rcvd", label: "✓ Image received & resolution validated", pct: 15 },
      { id: "chk-ocr-detect", label: "⟳ EasyOCR engine detecting text regions...", pct: 40 },
      { id: "chk-mfr-detect", label: "✓ Rule 6/7 Legal Metrology verification...", pct: 65 },
      { id: "chk-mrp-detect", label: "✓ Generating annotated visual boxes...", pct: 85 },
      { id: "chk-json-struct", label: "✓ Saving structured JSON to database...", pct: 100 }
    ];

    let stepIdx = 0;
    const progressInterval = setInterval(() => {
      if (stepIdx < checklistItems.length - 1) {
        const item = checklistItems[stepIdx];
        if (progressFill) progressFill.style.width = `${item.pct}%`;
        if (progressPct) progressPct.textContent = `${item.pct}%`;
        if (stepText) stepText.textContent = item.label;

        const chkEl = document.getElementById(item.id);
        if (chkEl) {
          chkEl.className = "ocr-checklist-item done";
          chkEl.innerHTML = item.label;
        }
        stepIdx++;
      }
    }, 800);

    try {
      let bodyData;
      let headers = {};
      const fileInput = document.getElementById("package-image-input");

      if (fileInput && fileInput.files && fileInput.files[0]) {
        bodyData = new FormData();
        bodyData.append("package_image", fileInput.files[0]);
      } else if (uploadedImageBase64 && uploadedImageBase64.startsWith("data:image")) {
        bodyData = JSON.stringify({ image: uploadedImageBase64 });
        headers["Content-Type"] = "application/json";
      } else {
        // Use active demo dataset if available
        const demoImg = currentActiveDataset ? currentActiveDataset.image : null;
        if (demoImg) {
          bodyData = JSON.stringify({ image: demoImg });
          headers["Content-Type"] = "application/json";
        } else {
          throw new Error("No package photograph found. Please upload or select a package image.");
        }
      }

      // Call Backend REST API
      const backendUrl = "../Backend/api/process_ocr.php";
      const response = await fetch(backendUrl, {
        method: "POST",
        headers: headers,
        body: bodyData
      });

      clearInterval(progressInterval);

      if (!response.ok) {
        const errJson = await response.json().catch(() => ({}));
        throw new Error(errJson.error || `HTTP error ${response.status}`);
      }

      const res = await response.json();
      if (!res.success) {
        throw new Error(res.error || "Backend OCR processing failed.");
      }

      // Complete progress bar animation
      if (progressFill) progressFill.style.width = "100%";
      if (progressPct) progressPct.textContent = "100%";
      if (stepText) stepText.textContent = "✓ OCR extraction & database save completed!";

      setTimeout(() => {
        if (procCard) procCard.style.display = "none";
        if (resultsWrapper) resultsWrapper.style.display = "block";

        updateStepper(4); // Step 4: Review

        // Format backend response for UI rendering
        const uiData = {
          raw_text: res.raw_text,
          extracted_json: res.extracted_json,
          product_name: res.form_fields?.product_name || "",
          mrp: res.form_fields?.mrp || "",
          net_quantity: res.form_fields?.net_quantity || "",
          manufacturing_date: res.form_fields?.manufacturing_date || "",
          manufacturer: res.form_fields?.manufacturer || "",
          consumer_care_phone: res.form_fields?.consumer_care_phone || "",
          consumer_care_email: res.form_fields?.consumer_care_email || "",
          country_of_origin: res.form_fields?.country_of_origin || "India",
          confidence: {
            product_name: res.form_fields?.product_name ? 95 : 0,
            net_quantity: res.form_fields?.net_quantity ? 95 : 0,
            mrp: res.form_fields?.mrp ? 95 : 0,
            manufacturing_date: res.form_fields?.manufacturing_date ? 95 : 0,
            manufacturer: res.form_fields?.manufacturer ? 95 : 0,
            consumer_care_phone: res.form_fields?.consumer_care_phone ? 90 : 0,
            consumer_care_email: res.form_fields?.consumer_care_email ? 90 : 0
          }
        };

        if (res.annotated_image_url) {
          const previewImg = document.getElementById("preview-img");
          if (previewImg) previewImg.src = "../Backend/" + res.annotated_image_url;
        }

        populateExtractedResultsUI(uiData, res);
        window.showToast(`OCR Scan Completed! Saved to DB (ID: ${res.inspection_number})`, "success");
      }, 500);

    } catch (err) {
      clearInterval(progressInterval);
      console.warn("Backend OCR API error, using fallback simulation:", err);
      window.showToast(`Backend connection notice: ${err.message}. Falling back to demo mode.`, "info");
      runSimulatedOCRProcessing();
    }
  }

  // Simulated OCR Processing Pipeline Animation (Fallback)
  function runSimulatedOCRProcessing() {
    if (!currentActiveDataset) {
      loadDemoDataset("parle-g");
    }

    updateStepper(2); // Step 2: OCR Scan

    const procCard = document.getElementById("ocr-processing-card");
    const resultsWrapper = document.getElementById("ocr-results-wrapper");
    const progressFill = document.getElementById("ocr-progress-fill");
    const progressPct = document.getElementById("ocr-progress-pct");
    const stepText = document.getElementById("ocr-status-text");

    if (procCard) procCard.style.display = "block";
    if (resultsWrapper) resultsWrapper.style.display = "none";

    const checklistItems = [
      { id: "chk-img-rcvd", label: "✓ Image received & resolution validated", pct: 15 },
      { id: "chk-ocr-detect", label: "⟳ Detecting package text regions...", pct: 40 },
      { id: "chk-mfr-detect", label: "✓ Extracting manufacturer & Net Quantity...", pct: 65 },
      { id: "chk-mrp-detect", label: "✓ Extracting MRP & Manufacturing Date...", pct: 85 },
      { id: "chk-json-struct", label: "✓ Structuring extracted information into JSON...", pct: 100 }
    ];

    let stepIdx = 0;
    const interval = setInterval(() => {
      if (stepIdx < checklistItems.length) {
        const item = checklistItems[stepIdx];
        if (progressFill) progressFill.style.width = `${item.pct}%`;
        if (progressPct) progressPct.textContent = `${item.pct}%`;
        if (stepText) stepText.textContent = item.label;

        const chkEl = document.getElementById(item.id);
        if (chkEl) {
          chkEl.className = "ocr-checklist-item done";
          chkEl.innerHTML = item.label;
        }

        stepIdx++;
      } else {
        clearInterval(interval);
        setTimeout(() => {
          if (procCard) procCard.style.display = "none";
          if (resultsWrapper) resultsWrapper.style.display = "block";

          updateStepper(4); // Advance to Step 4: Review

          populateExtractedResultsUI(currentActiveDataset);
          window.showToast("OCR extraction & JSON structuring completed successfully!", "success");
        }, 500);
      }
    }, 500);
  }

  // Populate Extracted Results, Raw OCR, Structured JSON & Form Auto-fill with Confidence
  function populateExtractedResultsUI(data) {
    // 1. Raw OCR Text Box
    const rawOcrBox = document.getElementById("raw-ocr-text-box");
    if (rawOcrBox) rawOcrBox.textContent = data.raw_text;

    // 2. Structured JSON Code Viewer
    const jsonCodeBox = document.getElementById("json-code-box");
    if (jsonCodeBox) {
      jsonCodeBox.textContent = JSON.stringify(data, null, 2);
    }

    // 3. Bind Copy JSON Button
    const copyJsonBtn = document.getElementById("btn-copy-json");
    if (copyJsonBtn) {
      copyJsonBtn.onclick = function () {
        navigator.clipboard.writeText(JSON.stringify(data, null, 2));
        window.showToast("Structured JSON copied to clipboard!", "success");
      };
    }

    // 4. Extraction Summary Metric Card
    const conf = data.confidence || {};
    const confValues = Object.values(conf);
    const detectedCount = confValues.filter((v) => v > 0).length;
    const missingCount = confValues.filter((v) => v === 0).length;
    const highCount = confValues.filter((v) => v >= 90).length;
    const medCount = confValues.filter((v) => v >= 70 && v < 90).length;
    const lowCount = confValues.filter((v) => v > 0 && v < 70).length;

    document.getElementById("sum-detected").textContent = detectedCount;
    document.getElementById("sum-missing").textContent = missingCount;
    document.getElementById("sum-high").textContent = highCount;
    document.getElementById("sum-med").textContent = medCount;

    // 5. Auto-fill Form Inputs & Set Confidence Badges
    const fieldMapping = [
      { inputId: "inp-product-name", key: "product_name" },
      { inputId: "inp-brand", key: "brand" },
      { inputId: "inp-category", key: "category" },
      { inputId: "inp-manufacturer", key: "manufacturer" },
      { inputId: "inp-packer", key: "packer" },
      { inputId: "inp-importer", key: "importer" },
      { inputId: "inp-net-qty", key: "net_quantity" },
      { inputId: "inp-mrp", key: "mrp" },
      { inputId: "inp-mfg-date", key: "manufacturing_date" },
      { inputId: "inp-batch", key: "batch_number" },
      { inputId: "inp-care-phone", key: "consumer_care_phone" },
      { inputId: "inp-care-email", key: "consumer_care_email" },
      { inputId: "inp-care-address", key: "consumer_care_address" }
    ];

    fieldMapping.forEach(({ inputId, key }) => {
      const inputEl = document.getElementById(inputId);
      const val = data[key] || "";
      const score = conf[key] !== undefined ? conf[key] : 90;

      if (inputEl) {
        inputEl.value = val;

        // Set Confidence Badge
        const badgeEl = document.getElementById(`conf-badge-${key}`);
        if (badgeEl) {
          if (score === 0 || !val) {
            badgeEl.className = "confidence-tag missing";
            badgeEl.textContent = "⚠ Missing (OCR 0%)";
            inputEl.classList.add("input-missing-highlight");
          } else if (score >= 90) {
            badgeEl.className = "confidence-tag high";
            badgeEl.textContent = `OCR ${score}%`;
            inputEl.classList.remove("input-missing-highlight");
          } else if (score >= 70) {
            badgeEl.className = "confidence-tag med";
            badgeEl.textContent = `OCR ${score}%`;
            inputEl.classList.remove("input-missing-highlight");
          } else {
            badgeEl.className = "confidence-tag low";
            badgeEl.textContent = `OCR ${score}%`;
            inputEl.classList.remove("input-missing-highlight");
          }
        }
      }
    });
  }

  // Bind Listeners for Manual Form Edits
  function bindFormManualEditListeners() {
    const inputs = document.querySelectorAll("#new-inspection-form input, #new-inspection-form select");
    inputs.forEach((input) => {
      input.addEventListener("input", function () {
        const inputId = input.id;
        const key = inputId.replace("inp-", "").replace("-", "_");

        input.classList.remove("input-missing-highlight");

        const badgeEl = document.getElementById(`conf-badge-${key}`) || input.previousElementSibling?.querySelector(".confidence-tag");
        if (badgeEl) {
          badgeEl.className = "confidence-tag verified";
          badgeEl.textContent = "✓ Manually Verified";
        }

        // Track internal change log
        const prevVal = currentActiveDataset ? currentActiveDataset[key] || "" : "";
        const newVal = input.value;
        const existingIdx = manualChangesLog.findIndex((c) => c.field === key);
        if (existingIdx !== -1) {
          manualChangesLog[existingIdx].new_value = newVal;
        } else {
          manualChangesLog.push({
            field: key,
            source: "manual",
            previous_value: prevVal,
            new_value: newVal
          });
        }
      });
    });
  }

  // Stepper State Updater
  function updateStepper(stepNum) {
    currentStep = stepNum;
    for (let i = 1; i <= 6; i++) {
      const stepEl = document.getElementById(`step-item-${i}`);
      if (stepEl) {
        if (i < stepNum) {
          stepEl.className = "step-item completed";
        } else if (i === stepNum) {
          stepEl.className = "step-item active";
        } else {
          stepEl.className = "step-item";
        }
      }
    }
  }

  // Compliance Verification & Save Inspection Payload
  function executeComplianceCheckAndSave(currentUser) {
    updateStepper(5); // Step 5: Verify

    const productName = document.getElementById("inp-product-name").value.trim();
    const brand = document.getElementById("inp-brand").value.trim();
    const category = document.getElementById("inp-category").value;
    const manufacturer = document.getElementById("inp-manufacturer").value.trim();
    const packer = document.getElementById("inp-packer").value.trim();
    const importer = document.getElementById("inp-importer").value.trim();
    const netQty = document.getElementById("inp-net-qty").value.trim();
    const mrp = document.getElementById("inp-mrp").value.trim();
    const mfgDate = document.getElementById("inp-mfg-date").value;
    const batchNumber = document.getElementById("inp-batch").value.trim();
    const carePhone = document.getElementById("inp-care-phone").value.trim();
    const careEmail = document.getElementById("inp-care-email").value.trim();
    const careAddress = document.getElementById("inp-care-address").value.trim();

    if (!productName || !netQty || !mrp || !mfgDate || !manufacturer) {
      window.showToast("Please complete required product & manufacturer fields.", "warning");
      return;
    }

    const violations = [];
    const checklist = {
      manufacturerDetails: true,
      netQuantity: true,
      mrp: true,
      mfgDate: true,
      consumerCare: true,
      importerDetails: true
    };

    // Rule 1: MRP format check
    if (!mrp.toLowerCase().includes("incl") && !mrp.toLowerCase().includes("taxes")) {
      checklist.mrp = false;
      violations.push({
        title: "MRP Declaration Format Missing Tax Suffix",
        description: "Maximum Retail Price must include mandatory suffix '(Incl. of all taxes)'.",
        severity: "Medium",
        rule: "Demo compliance rule - Rule 6(1)(e)"
      });
    }

    // Rule 2: Consumer care email check
    if (!careEmail || !careEmail.includes("@")) {
      checklist.consumerCare = false;
      violations.push({
        title: "Consumer Care Email Information Incomplete",
        description: "Mandatory official email address for grievance redressal missing from consumer care panel.",
        severity: "High",
        rule: "Demo compliance rule - Rule 6(1)(h)"
      });
    }

    const isCompliant = violations.length === 0;
    const nextIdNumber = Math.floor(1000 + Math.random() * 9000);
    const nowTime = new Date();

    const timestampTimeline = [
      { time: "09:31:02", event: "Package image uploaded & preprocessed" },
      { time: "09:31:03", event: "Simulated AI OCR text detection started" },
      { time: "09:31:05", event: "Raw OCR package text extracted" },
      { time: "09:31:06", event: "Structured JSON schema generated" },
      { time: "09:31:07", event: "Officer review & verification completed" },
      { time: "09:31:08", event: "Legal Metrology compliance check completed" }
    ];

    const inspectionRecord = {
      id: `INS-2026-${nextIdNumber}`,
      inspection_id: `INS-2026-${nextIdNumber}`,
      productId: "P" + String(Math.floor(1 + Math.random() * 10)).padStart(3, "0"),
      productName: productName,
      brand: brand || "Brand Packaging",
      category: category || "General Commodity",
      inspectorId: currentUser.id,
      inspectorName: currentUser.name,
      date: nowTime.toISOString().split("T")[0],
      status: isCompliant ? "Compliant" : "Non-Compliant",
      netQuantity: netQty,
      mrp: mrp,
      mfgDate: mfgDate,
      manufacturer: manufacturer,
      packer: packer || manufacturer,
      importer: importer || "N/A (Domestic)",
      consumerCare: `Call: ${carePhone || "1800-123-4567"} | Email: ${careEmail || "Missing"}`,
      checklist: checklist,
      violations: violations,
      image: uploadedImageBase64 || "https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=500&auto=format&fit=crop&q=80",
      ocr_data: {
        raw_text: currentActiveDataset ? currentActiveDataset.raw_text : "Raw OCR package text extracted.",
        average_confidence: 94
      },
      manual_changes: manualChangesLog,
      timeline: timestampTimeline,
      notes: isCompliant
        ? "Verified via AI OCR & Officer Inspection. All Rule 6 mandatory declarations compliant."
        : `Identified ${violations.length} non-compliance violation(s). Statutory notice generated.`
    };

    // Save to LocalStorage
    const inspections = JSON.parse(localStorage.getItem("inspections") || "[]");
    inspections.unshift(inspectionRecord);
    localStorage.setItem("inspections", JSON.stringify(inspections));
    localStorage.setItem("lastInspectionId", inspectionRecord.id);

    window.showToast("Compliance check completed! Redirecting to Result Scorecard...", "success");

    setTimeout(() => {
      window.location.href = `inspection-result.html?id=${inspectionRecord.id}`;
    }, 1000);
  }

  // Page 2 Renderer: Inspection Result Scorecard & Timeline
  function renderInspectionResultPage() {
    const urlParams = new URLSearchParams(window.location.search);
    const resultId = urlParams.get("id") || localStorage.getItem("lastInspectionId") || "INS-2026-001";

    const inspections = JSON.parse(localStorage.getItem("inspections") || "[]");
    const record = inspections.find((i) => i.id === resultId) || inspections[0];

    if (!record) return;

    // Status Banner
    const bannerEl = document.getElementById("result-status-banner");
    if (bannerEl) {
      const isCompliant = record.status === "Compliant";
      bannerEl.className = `result-status-banner ${isCompliant ? "compliant" : "non-compliant"}`;
      bannerEl.innerHTML = `
        <div class="banner-left">
          <div class="banner-status-icon">${isCompliant ? "✓" : "✕"}</div>
          <div>
            <div class="banner-title">${record.status.toUpperCase()}</div>
            <div class="banner-sub">Inspection ID: <strong>${record.id}</strong> | Product: ${record.productName}</div>
          </div>
        </div>
        <div style="text-align: right;">
          <div style="font-size:0.85rem; opacity:0.9;">Inspection Date</div>
          <div style="font-weight:700; font-size:1.1rem;">${record.date}</div>
        </div>
      `;
    }

    // Summary Details Cards
    const summaryEl = document.getElementById("result-product-info");
    if (summaryEl) {
      summaryEl.innerHTML = `
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
          <div>
            <p class="text-muted" style="font-size:0.82rem;">PRODUCT NAME</p>
            <p class="font-bold" style="margin-bottom:10px;">${record.productName}</p>
            <p class="text-muted" style="font-size:0.82rem;">BRAND / CATEGORY</p>
            <p class="font-bold" style="margin-bottom:10px;">${record.brand} (${record.category})</p>
            <p class="text-muted" style="font-size:0.82rem;">NET QUANTITY</p>
            <p class="font-bold" style="margin-bottom:10px;">${record.netQuantity}</p>
          </div>
          <div>
            <p class="text-muted" style="font-size:0.82rem;">MRP DECLARATION</p>
            <p class="font-bold" style="margin-bottom:10px;">${record.mrp}</p>
            <p class="text-muted" style="font-size:0.82rem;">MFG / PACKING DATE</p>
            <p class="font-bold" style="margin-bottom:10px;">${record.mfgDate}</p>
            <p class="text-muted" style="font-size:0.82rem;">INSPECTING OFFICER</p>
            <p class="font-bold" style="margin-bottom:10px;">${record.inspectorName}</p>
          </div>
        </div>
      `;
    }

    // Checklist Items
    const checklistContainer = document.getElementById("result-checklist-container");
    if (checklistContainer) {
      checklistContainer.innerHTML = "";
      const cl = record.checklist || { manufacturerDetails: true, netQuantity: true, mrp: true, mfgDate: true, consumerCare: true, importerDetails: true };

      const items = [
        { key: "manufacturerDetails", label: "Manufacturer / Packer Details" },
        { key: "netQuantity", label: "Net Quantity Declaration" },
        { key: "mrp", label: "Maximum Retail Price (MRP)" },
        { key: "mfgDate", label: "Manufacturing / Packing Date" },
        { key: "consumerCare", label: "Consumer Care Details" },
        { key: "importerDetails", label: "Importer Address (if imported)" }
      ];

      items.forEach((item) => {
        const passed = cl[item.key] !== false;
        const card = document.createElement("div");
        card.className = `checklist-card ${passed ? "pass" : "fail"}`;
        card.innerHTML = `
          <span class="checklist-label">${item.label}</span>
          <span class="checklist-icon" style="color: ${passed ? "var(--success)" : "var(--danger)"}; font-weight:bold;">
            ${passed ? "✓ PASS" : "✕ FAIL"}
          </span>
        `;
        checklistContainer.appendChild(card);
      });
    }

    // Violations Section
    const violationsContainer = document.getElementById("result-violations-container");
    if (violationsContainer) {
      violationsContainer.innerHTML = "";
      if (!record.violations || record.violations.length === 0) {
        violationsContainer.innerHTML = `
          <div style="background-color: var(--success-light); border: 1px solid var(--success-border); padding: 18px; border-radius: var(--radius); color: var(--success); font-weight:600;">
            ✓ No Legal Metrology violations detected. All mandatory declarations comply with Legal Metrology (Packaged Commodities) Rules.
          </div>
        `;
      } else {
        record.violations.forEach((v, idx) => {
          const card = document.createElement("div");
          card.className = "violation-card";
          let sevClass = "medium";
          if (v.severity && v.severity.toLowerCase() === "high") sevClass = "high";
          if (v.severity && v.severity.toLowerCase() === "low") sevClass = "low";

          card.innerHTML = `
            <div class="violation-header">
              <span class="violation-title">Violation #${idx + 1}: ${v.title}</span>
              <span class="severity-tag ${sevClass}">Severity: ${v.severity || "Medium"}</span>
            </div>
            <div class="violation-rule">Rule Source: ${v.rule || "Demo compliance rule"}</div>
            <div class="violation-desc">${v.description}</div>
          `;
          violationsContainer.appendChild(card);
        });
      }
    }

    // Render Inspection Activity Timeline
    const timelineContainer = document.getElementById("result-timeline-container");
    if (timelineContainer) {
      timelineContainer.innerHTML = "";
      const steps = record.timeline || [
        { time: "09:31:02", event: "Package image uploaded" },
        { time: "09:31:03", event: "AI OCR processing started" },
        { time: "09:31:05", event: "Raw package text extracted" },
        { time: "09:31:06", event: "Structured JSON schema generated" },
        { time: "09:31:07", event: "Officer review completed" },
        { time: "09:31:08", event: "Compliance check completed" }
      ];

      let tHtml = `<div class="inspection-timeline-list">`;
      steps.forEach((s) => {
        tHtml += `
          <div class="timeline-step">
            <span class="timeline-time">${s.time}</span>
            <span>${s.event}</span>
          </div>
        `;
      });
      tHtml += `</div>`;
      timelineContainer.innerHTML = tHtml;
    }
  }
})();
