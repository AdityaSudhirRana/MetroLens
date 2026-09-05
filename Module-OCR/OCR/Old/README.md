# OCR-CV Module: Legal Metrology AI Packaging Compliance

This module forms the Computer Vision and OCR foundation for the **Kardashev-6** Packaging Compliance System. It inspects product packaging images, detects text, computes blur & font proportionality, verifies statutory mandatory declarations under the **Legal Metrology (Packaged Commodities) Rules, 2011**, and outputs annotated visuals.

---

## 🌟 Key Features

1. **Text & Bounding Box Detection**:
   - EasyOCR-powered text recognition with exact `(x, y, w, h)` coordinate mapping and confidence scores.
2. **Rule 7 Font Size Compliance**:
   - Evaluates font height against Principal Display Panel (PDP) / package height proportions.
   - Flags text as `NORMAL` or `TOO_SMALL`.
3. **Statutory Mandatory Declarations Parser**:
   - Automatically checks for 7 required declarations under Legal Metrology Rules:
     - [x] **MRP** (Maximum Retail Price, incl. of all taxes)
     - [x] **Net Quantity** (Weight, Volume, or Count e.g. `g`, `kg`, `ml`, `N`)
     - [x] **Dates** (Date of Manufacture / Packing)
     - [x] **Best Before / Expiry Date**
     - [x] **Consumer Care Details** (Helpline phone / Email)
     - [x] **Country of Origin** (`Made in India`)
     - [x] **Manufacturer / Packer Details**
4. **CV Preprocessing & Image Quality Check**:
   - Laplacian variance sharpness check to reject or flag blurry photos (`blur_score < 60`).
   - CLAHE contrast enhancement for plastic packaging with glare.
5. **Visual Bounding Box Annotator**:
   - Generates `annotated_<image_name>.jpg` with color-coded bounding boxes:
     - 🟢 **Green**: Compliant font size (`NORMAL`)
     - 🔴 **Red**: Non-compliant font size (`TOO_SMALL`)
     - Top status HUD banner with overall compliance score.

---

## 📦 Installation

Ensure Python 3.10+ is installed, then run:

```bash
pip install -r requirements.txt
```

*(Dependencies: `easyocr`, `opencv-python`, `pillow`, `numpy`)*

---

## 🚀 Usage

### 1. Command Line Run
Inside the `OCR-CV Module` directory:

```bash
python ocr_engine.py sample.jpg
```

### 2. Python Integration (For Website / Backend Module)
```python
from ocr_engine import extract_packaging_info

# Analyze image and generate annotated visual
result = extract_packaging_info("sample.jpg", save_annotated=True)

print(f"Compliance Score: {result['legal_metrology_compliance']['compliance_score_pct']}%")
print(f"MRP: {result['legal_metrology_compliance']['fields']['mrp']}")
print(f"Net Qty: {result['legal_metrology_compliance']['fields']['net_quantity']}")
print(f"Annotated Image Saved: {result['annotated_image_path']}")
```

---

## 📋 Sample Output JSON
```json
{
  "image_size": { "width": 600, "height": 400 },
  "blur_score": 1746.54,
  "is_blurry": false,
  "total_detections": 4,
  "avg_font_px": 29.5,
  "legal_metrology_compliance": {
    "total_fields_checked": 7,
    "found_fields_count": 3,
    "compliance_score_pct": 42.9,
    "critical_mandatory_passed": true,
    "fields": {
      "mrp": { "found": true, "raw_value": "MRP: Rs. 20.00", "amount": "20.00", "status": "COMPLIANT" },
      "net_quantity": { "found": true, "raw_value": "Net Weight: 50g", "quantity": "50g", "status": "COMPLIANT" },
      "dates": { "found": true, "raw_value": "Mfg Date: 01/2026", "date": "01/2026", "status": "COMPLIANT" }
    }
  },
  "annotated_image_path": "annotated_sample.jpg"
}
```
