# MetroLens

### Autonomous Compliance Auditor for Packaged Commodities

> **Inspect. Analyze. Validate. Comply.**

MetroLens is an AI-powered compliance auditing system designed to inspect packaged commodities for potential violations under the **Legal Metrology (Packaged Commodities) Rules, 2011**.

By combining computer vision, spatial OCR, AI-assisted interpretation, and a deterministic compliance rule engine, MetroLens extracts product declarations, evaluates compliance, identifies potential violations, and generates structured compliance reports.

---

# Problem Statement

Inspecting packaged commodities for Legal Metrology compliance is a largely manual process. Inspectors may need to verify multiple declarations on product packaging, including:

- Manufacturer, packer, and importer details
- Maximum Retail Price (MRP)
- Net quantity and units of measurement
- Consumer care information
- Mandatory declarations
- Font height and declaration visibility

Manual inspection can become time-consuming and difficult to standardize when dealing with large numbers of products.

MetroLens aims to streamline this process through automated, evidence-based inspection.

---

# Our Solution

MetroLens acts as an **autonomous compliance auditor**, processing product images through image preprocessing, spatial OCR, AI-assisted interpretation, and deterministic rule validation.

```text
Product Image
      ↓
OpenCV Preprocessing
      ↓
EasyOCR
      ↓
OCR Evidence
(Text + Confidence + Bounding Boxes)
      ↓
Gemini AI
      ↓
Deterministic Rule Engine
      ↓
Compliance Score + Violations
      ↓
PDF Compliance Report
```

> **AI interprets the evidence; deterministic rules perform the compliance validation.**

---

# Key Features

### 1. Spatial OCR
Extracts text, confidence scores, and bounding-box coordinates to preserve the spatial context of declarations on product packaging.

### 2. Image Preprocessing
Uses OpenCV to prepare product images for OCR and improve text readability.

### 3. AI-Assisted Declaration Analysis
Uses Gemini AI to interpret OCR output and organize extracted declarations into structured information.

### 4. Deterministic Compliance Engine
Evaluates extracted declarations against predefined compliance rules, supporting consistent and explainable validation.

### 5. Compliance Scoring
Summarizes inspection results through an overall compliance score based on the implemented evaluation logic.

### 6. Violation Detection
Identifies potential compliance issues and associates them with relevant OCR evidence.

### 7. Automated PDF Reporting
Generates structured compliance or violation reports for inspection and review.

---

# System Architecture

```text
┌──────────────────────────────┐
│        Product Image         │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│     OpenCV Preprocessing     │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│           EasyOCR            │
│ Text • Confidence • Bounding │
│             Boxes            │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│       OCR Evidence Layer     │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│          Gemini AI           │
│ Declaration Interpretation   │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│    Deterministic Rule Engine │
│    Compliance Validation     │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│     Compliance Analyzer      │
│ Score • Violations • Evidence│
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│       PDF Report Generator   │
└──────────────────────────────┘
```

---

# Technology Stack

| Component | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Image Processing | OpenCV |
| OCR Engine | EasyOCR |
| AI Interpretation | Google Gemini API |
| Compliance Validation | Deterministic Rule Engine |
| Backend Processing | Python |
| Version Control | Git & GitHub |

---

# Why Spatial OCR?

Traditional OCR primarily answers:

> **What text is present?**

MetroLens also considers:

> **Where is the text located, and what does the declaration represent?**

Preserving bounding-box coordinates provides spatial evidence that can support the analysis of declaration placement, visibility, and dimensions. Such information is particularly relevant when evaluating packaging requirements involving font height or layout.

---

# AI + Rule Engine Architecture

MetroLens separates AI-assisted interpretation from compliance validation.

```text
OCR Evidence
     ↓
Gemini AI
     ↓
Structured Declarations
     ↓
Deterministic Rule Engine
     ↓
Explainable Compliance Results
```

This separation helps make compliance decisions reproducible and traceable to explicit rules and available evidence, rather than relying solely on AI-generated judgments.

---

# Project Structure

```text
MetroLens/
├── Frontend/
├── Module-OCR/
├── .gitignore
└── README.md
```

- **`Frontend/`** — User-facing interface.
- **`Module-OCR/`** — OCR and image-processing functionality.

---

# Processing Pipeline

1. **Capture:** Provide an image of a packaged commodity.
2. **Preprocess:** Prepare the image using OpenCV.
3. **Extract:** Use EasyOCR to obtain text, confidence scores, and bounding boxes.
4. **Structure:** Organize OCR output into structured evidence.
5. **Interpret:** Use Gemini AI to interpret extracted declarations.
6. **Validate:** Evaluate declarations through the deterministic rule engine.
7. **Score:** Summarize the compliance evaluation.
8. **Report:** Generate a compliance or violation report.

---

# Example Output

The following illustrates the intended format of an inspection result.

```text
PRODUCT COMPLIANCE REPORT
────────────────────────────────
Compliance Score: 82 / 100

DECLARATIONS
✓ MRP
✓ Manufacturer Information
✓ Net Quantity
✓ Consumer Care Information

POTENTIAL VIOLATIONS
✗ Font Height
✗ Unit Declaration Format

EVIDENCE
OCR Text: "Net Qty. 500g"
Confidence: 94.8%

Status: Potential Non-Compliance
```

*Illustrative example only; actual results depend on the implemented rules and inspection input.*

---

# Getting Started

### Prerequisites

- Python 3.x
- Git
- Required project dependencies
- Google Gemini API access

### Clone the Repository

```bash
git clone https://github.com/AdityaSudhirRana/MetroLens.git
cd MetroLens
```

### Install Dependencies

Navigate to the relevant module and install its dependencies using the applicable requirements file:

```bash
pip install -r requirements.txt
```

### Environment Configuration

Configure the Gemini API key through an environment variable:

```env
GEMINI_API_KEY=your_api_key_here
```

Never commit API keys or other secrets to the repository.

*Refer to the individual module documentation for the exact installation and execution commands.*

---

# Design Principles

- **Evidence First:** Associate compliance findings with observable evidence.
- **AI-Assisted:** Use AI for interpretation, with explicit rules for validation.
- **Explainability:** Make the reasoning behind potential violations understandable.
- **Automation:** Reduce repetitive manual inspection work.
- **Modularity:** Keep image processing, OCR, AI interpretation, validation, and reporting logically separated.

---

# Future Scope

- Improved perspective correction and image quality handling
- More robust font-height estimation
- Multilingual OCR support
- Expanded Legal Metrology rule coverage
- Versioned compliance rules
- Batch product inspection
- Inspection history and analytics
- Enhanced evidence visualization
- Offline inspection capabilities
- Mobile-based inspection

---

# Hackathon

MetroLens was developed as part of the **Smart India Hackathon 2026** preparation process and was presented during the internal hackathon at **Sheth L.U.J. & Sir M.V. College of Arts & Commerce, Mumbai**.

**Kardashev-6 secured 5th Position out of 30 Teams** at the **M.V.L.U. Hackathon 2026**.

---

# Team Kardashev-6

| Member | Contribution |
|---|---|
| **Aditya Rana** | System Architecture & AI Integration |
| **Dhruv Choubey** | Backend & OCR Development |
| **Mohid Pathan** | Backend & Database Development |
| **Akshat Halwai** | Backend–Frontend Integration |
| **Pratik Rajbhar** | Frontend Development & UI/UX Design |
| **Srushti Vedak** | Frontend Development & Research |

---

# Repository

**GitHub:** [AdityaSudhirRana/MetroLens](https://github.com/AdityaSudhirRana/MetroLens)

---

# Project Status

MetroLens is an **academic and hackathon prototype** demonstrating an automated approach to packaged-commodity compliance inspection.

It is intended for technical demonstration and does not replace official regulatory inspection or legal advice.

---

# Acknowledgements

Developed by **Team Kardashev-6** for the M.V.L.U. Hackathon 2026, in the context of the team's Smart India Hackathon 2026 preparation.

---

# License

MetroLens is distributed under a **proprietary license**. All rights are reserved except those expressly granted by the applicable license.

See [`LICENSE`](LICENSE) for the complete terms.
