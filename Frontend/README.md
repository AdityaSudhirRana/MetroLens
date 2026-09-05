# AI Package Inspector (Role-Based Officer & Inspector System)

> **Legal Metrology AI Enforcement, Company Inspection Drilldown & Package Compliance Platform**

AI Package Inspector is a centralized full-stack Legal Metrology inspection management platform built with **PHP 8+**, **MySQL/MariaDB**, **HTML5**, **CSS3**, and **Vanilla JavaScript** running on **XAMPP**.

---

## 1. Core User Roles & Permissions

```text
                        SYSTEM ROLES
                             │
             ┌───────────────┴───────────────┐
             ▼                               ▼
     CHIEF OFFICER                  FIELD INSPECTOR
     (`role = 'officer'`)           (`role = 'inspector'`)
```

### **A. Chief Officer (`role = 'officer'`)**
* **Access Level**: High-level administrative and system-wide oversight.
* **Capabilities**:
  - View officer dashboard (`pages/officer-dashboard.php`) with system-wide KPI statistics and charts.
  - Access central Companies Catalog (`pages/companies.php`) with expandable inspection dates drilldown (`COMPANY ➔ INSPECTION DATES ➔ SELECT DATE ➔ FULL INSPECTION DETAILS`).
  - View all registered companies, products, reports, inquiries, and audit logs.
  - Manage Field Inspectors (`pages/manage-inspectors.php`): view activity, activate/deactivate officer accounts, and onboard new inspectors (`pages/register-inspector.php`).
  - Run AI WebRTC camera OCR package scans.

### **B. Field Inspector (`role = 'inspector'`)**
* **Access Level**: Operational field inspection workspace.
* **Capabilities**:
  - View personal inspector dashboard (`pages/inspector-dashboard.php`).
  - Run WebRTC live camera package scans or file uploads (`pages/new-inspection.php`).
  - Review OCR extracted declarations, edit missing/low-confidence fields (marked as `✓ MANUALLY VERIFIED`).
  - Submit inspection reports to MySQL database.
  - View own inspection history, assigned show-cause legal notices, and personal reports.

---

## 2. Core Feature: Company Inspection History Drilldown

The system provides a seamless 4-tier drilldown for every manufacturer:

```text
COMPANIES CATALOG (pages/companies.php)
       │
       ▼
Select / Expand Company (e.g. ▼ Parle Products Pvt. Ltd.)
       │
       ▼
INSPECTION DATES LIST (05 Sep 2026, 30 Jul 2026, 15 Jul 2026)
       │
       ▼
Select Inspection Date (e.g. 05 September 2026)
       │
       ▼
FULL INSPECTION DETAILS (pages/inspection-details.php)
```

### Full Inspection Details Record (`pages/inspection-details.php`):
* **Inspection Metadata**: Report ID, timestamp, inspecting officer, office/region, compliance status badge, risk level badge, and compliance rating score %.
* **Commodity Profile**: Product name, brand name, category, GTIN barcode, net quantity, MRP.
* **Manufacturer Details**: Manufacturer, packer, importer name, postal address, phone, email.
* **Original Package Captured Image**: Embedded photograph saved in `uploads/inspections/INS-CAM-YYYYMMDD-XXXX.jpg`.
* **Raw OCR Stream & Confidence Table**: Raw label OCR text, extraction confidence rating per field (`Manufacturer 96%`, `Net Qty 99%`, `MRP 97%`).
* **Structured JSON Payload**: Synthesized JSON schema with copy-to-clipboard button.
* **Manual Corrections Log**: Tracks fields edited by the officer (`MANUALLY VERIFIED` vs `OCR 98%`).
* **Rule 6 Verification Scorecard & Violations**: Checklist of 7 mandatory declarations and statutory penalty details.
* **Audit Timeline**: Timestamped action log.

---

## 3. WebRTC Live Camera & Real OCR Pipeline

```text
Live WebRTC Viewfinder (navigator.mediaDevices.getUserMedia)
       │
       ▼
Capture Photo Frame to Canvas / File Upload (JPG, PNG, WEBP)
       │
       ▼
POST /api/ocr.php (OCRService & ExtractionService)
       │
       ▼
Actual Image Binary Processed -> Raw OCR Text & Field Extraction
       │
       ▼
Auto-Filled Officer Review Form (Confidence Badges & Verification Tags)
       │
       ▼
Rule 6 Compliance Engine (ComplianceService) -> Save to MySQL (package_inspector)
```

---

## 4. Database Setup & phpMyAdmin

1. **Start XAMPP Control Panel**:
   - Start **Apache** and **MySQL** services.

2. **Automatic Database Initialization**:
   - Simply navigate to `http://localhost/H1/index.php` in your browser.
   - `config/database.php` will automatically create the `package_inspector` database and import `database/schema.sql` if it does not exist.

3. **Pre-Seeded Credentials**:
   - **Chief Officer**: `admin@example.com` / `admin123`
   - **Field Inspector**: `officer@example.com` / `123456`

---

&copy; Legal Metrology Department, Govt of India &bull; AI Package Inspector System
