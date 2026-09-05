"""
SIH Hackathon - Legal Metrology Packaged Commodities Compliance Scanner
Module: OCR-CV Module / ocr_engine.py

Statutory compliance verification engine under the Legal Metrology
(Packaged Commodities) Rules, 2011.

Standardized output JSON contract:
- image_id: Product / Image identifier
- source_image: path, width, height
- processed_image: path, width, height (CLAHE & auto-cropped)
- annotated_image: path (with color-coded bounding boxes)
- ocr: engine, language, detections list with id, text, confidence, bbox [[x1,y1],[x2,y2],[x3,y3],[x4,y4]]
- legal_metrology_compliance: Statutory rule evaluation & violations
"""

import os
import re
import sys

# Ensure UTF-8 output encoding across Windows consoles to avoid charmap UnicodeEncodeError
if sys.platform == "win32":
    try:
        sys.stdout.reconfigure(encoding="utf-8")
        sys.stderr.reconfigure(encoding="utf-8")
    except Exception:
        pass

import cv2
import easyocr
import numpy as np

# Import OpenCV auto-orientation helper
try:
    from OpenCV.preprocessing import auto_orient_image
except ImportError:
    auto_orient_image = None


print("[OCR-CV] Initializing EasyOCR Engine (English)...")
# CPU mode for universal compatibility; verbose=False prevents Windows charmap crashes
reader = easyocr.Reader(["en"], gpu=False, verbose=False)
print("[OCR-CV] EasyOCR Engine Loaded Successfully!")


def run_ocr(image, scale_metadata=None, languages=None, gpu=False):
    """
    Standardized interface matching the OCR evidence JSON contract.
    
    Returns:
        List[Dict]: [
            {
                "text": "...",
                "confidence": 0.95,
                "bbox": [[x1,y1], [x2,y2], [x3,y3], [x4,y4]],
                "original_bbox": [[x1_orig,y1_orig], ...] (optional)
            }
        ]
    """
    if isinstance(image, str) and not os.path.exists(image):
        raise ValueError(f"Image file not found: {image}")

    if isinstance(image, str):
        img_input = cv2.imread(image)
    else:
        img_input = image

    raw_results = reader.readtext(img_input)

    formatted_items = []
    for item in raw_results:
        raw_bbox, text, conf = item
        bbox = [[round(float(pt[0]), 2), round(float(pt[1]), 2)] for pt in raw_bbox]
        
        orig_bbox = None
        if scale_metadata is not None and getattr(scale_metadata, "scale_x", 0) > 0 and getattr(scale_metadata, "scale_y", 0) > 0:
            orig_bbox = []
            for pt in bbox:
                x_orig = round(pt[0] / scale_metadata.scale_x, 2)
                y_orig = round(pt[1] / scale_metadata.scale_y, 2)
                orig_bbox.append([x_orig, y_orig])

        item_dict = {
            "text": str(text).strip(),
            "confidence": round(float(conf), 4),
            "bbox": bbox,
        }
        if orig_bbox is not None:
            item_dict["original_bbox"] = orig_bbox
        formatted_items.append(item_dict)

    return formatted_items



def check_blur_score(image_cv):
    """
    Measures image sharpness using Laplacian variance.
    A score < 60.0 indicates a blurry image unsuitable for regulatory validation.
    """
    gray = cv2.cvtColor(image_cv, cv2.COLOR_BGR2GRAY)
    score = cv2.Laplacian(gray, cv2.CV_64F).var()
    return round(float(score), 2)


def auto_crop_package(image_cv, min_area_ratio=0.15):
    """
    Auto-detects the commodity package boundary from the background using
    edge detection and largest contour approximation.
    Returns (cropped_image, was_cropped, bounding_box_dict).
    """
    h, w = image_cv.shape[:2]
    gray = cv2.cvtColor(image_cv, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (5, 5), 0)

    edges = cv2.Canny(blurred, 40, 140)
    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (7, 7))
    dilated = cv2.dilate(edges, kernel, iterations=2)

    contours, _ = cv2.findContours(dilated, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    if not contours:
        return image_cv, False, {"x": 0, "y": 0, "w": w, "h": h}

    largest = max(contours, key=cv2.contourArea)
    area = cv2.contourArea(largest)
    total_area = h * w

    if area > (total_area * min_area_ratio) and area < (total_area * 0.98):
        bx, by, bw, bh = cv2.boundingRect(largest)
        margin_x = int(bw * 0.03)
        margin_y = int(bh * 0.03)
        x1 = max(0, bx - margin_x)
        y1 = max(0, by - margin_y)
        x2 = min(w, bx + bw + margin_x)
        y2 = min(h, by + bh + margin_y)
        cropped = image_cv[y1:y2, x1:x2]
        return cropped, True, {"x": x1, "y": y1, "w": x2 - x1, "h": y2 - y1}

    return image_cv, False, {"x": 0, "y": 0, "w": w, "h": h}


def enhance_contrast_clahe(image_cv):
    """
    Applies CLAHE in LAB color space to suppress glare on plastic wrappers/bottles
    and enhance text contrast before OCR.
    """
    lab = cv2.cvtColor(image_cv, cv2.COLOR_BGR2LAB)
    l_channel, a_channel, b_channel = cv2.split(lab)

    clahe = cv2.createCLAHE(clipLimit=2.5, tileGridSize=(8, 8))
    cl = clahe.apply(l_channel)

    enhanced_lab = cv2.merge((cl, a_channel, b_channel))
    return cv2.cvtColor(enhanced_lab, cv2.COLOR_LAB2BGR)


def evaluate_legal_metrology_compliance(detections, img_height):
    """
    Audits detected lines against Legal Metrology (Packaged Commodities) Rules, 2011:
    - Rule 6(1)(da): MRP & "inclusive of all taxes"
    - Rule 6(1)(c): Net Quantity with standard units
    - Rule 6(1)(d): Month & Year of manufacture / packing
    - Rule 6(1)(a): Manufacturer / Packer name and address
    - Rule 6(1)(e): Consumer Care helpline & email
    - Rule 7: Minimum font size and proportion check
    """
    full_text = " | ".join([d["text"] for d in detections])
    violations = []
    rules = {}

    # 1. Rule 6(1)(da): MRP & Inclusive of all taxes
    mrp_regex = re.search(
        r"(?:MRP|M\.R\.P\.?|MAX(?:IMUM)?\s*RETAIL\s*PRICE|Rs\.?|₹)\s*[:.\-]?\s*(?:Rs\.?|₹)?\s*([0-9]+(?:\.[0-9]{1,2})?)",
        full_text,
        re.IGNORECASE,
    )
    tax_regex = re.search(
        r"(?:INCL(?:USIVE)?\.?\s*OF\s*ALL\s*TAXES|INCL\.?\s*TAXES)",
        full_text,
        re.IGNORECASE,
    )

    if mrp_regex:
        has_tax_phrase = bool(tax_regex)
        status = "COMPLIANT" if has_tax_phrase else "NON_COMPLIANT"
        rules["rule_6_1_da_mrp"] = {
            "status": status,
            "rule_name": "Rule 6(1)(da) - Retail Sale Price (MRP)",
            "found": True,
            "amount": mrp_regex.group(1).strip(),
            "inclusive_of_all_taxes": has_tax_phrase,
            "raw_text": mrp_regex.group(0).strip(),
        }
        if not has_tax_phrase:
            violations.append(
                "Rule 6(1)(da) Violation: MRP is specified, but mandatory declaration 'inclusive of all taxes' is missing."
            )
    else:
        rules["rule_6_1_da_mrp"] = {
            "status": "NON_COMPLIANT",
            "rule_name": "Rule 6(1)(da) - Retail Sale Price (MRP)",
            "found": False,
            "amount": None,
            "inclusive_of_all_taxes": False,
            "raw_text": None,
        }
        violations.append(
            "Rule 6(1)(da) Violation: Maximum Retail Price (MRP) declaration is missing."
        )

    # 2. Rule 6(1)(c): Net Quantity
    net_qty_regex = re.search(
        r"(?:(?:NET\s*(?:WT|WEIGHT|QTY|QUANTITY)?)\s*[:.\-]?\s*)?([0-9]+(?:\.[0-9]+)?\s*(g|gm|gms|kg|ml|l|ltr|litres?|units?|pieces?|N))\b",
        full_text,
        re.IGNORECASE,
    )
    if net_qty_regex:
        unit = net_qty_regex.group(2).lower()
        is_standard_unit = unit in ["g", "gm", "gms", "kg", "ml", "l", "ltr", "litres", "n", "units", "pieces"]
        status = "COMPLIANT" if is_standard_unit else "NON_COMPLIANT"

        rules["rule_6_1_c_net_quantity"] = {
            "status": status,
            "rule_name": "Rule 6(1)(c) - Net Quantity",
            "found": True,
            "quantity": net_qty_regex.group(1).strip(),
            "unit": unit,
            "standard_unit_valid": is_standard_unit,
            "raw_text": net_qty_regex.group(0).strip(),
        }
        if not is_standard_unit:
            violations.append(
                f"Rule 6(1)(c) Violation: Net quantity unit '{unit}' is non-standard under the Rules."
            )
    else:
        rules["rule_6_1_c_net_quantity"] = {
            "status": "NON_COMPLIANT",
            "rule_name": "Rule 6(1)(c) - Net Quantity",
            "found": False,
            "quantity": None,
            "unit": None,
            "standard_unit_valid": False,
            "raw_text": None,
        }
        violations.append("Rule 6(1)(c) Violation: Net quantity declaration is missing.")

    # 3. Rule 6(1)(d): Month & Year of Packing/Mfg
    date_regex = re.search(
        r"(?:MFG|MFD|PKD|PACKED|DATE|DOM|DOP)\s*[:.\-]?\s*([0-9]{1,2}[/\-\.][0-9]{2,4}|(?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)[a-z]*\s*[0-9]{2,4})",
        full_text,
        re.IGNORECASE,
    )
    if date_regex:
        rules["rule_6_1_d_mfg_date"] = {
            "status": "COMPLIANT",
            "rule_name": "Rule 6(1)(d) - Month & Year of Packing/Mfg",
            "found": True,
            "date": date_regex.group(1).strip(),
            "raw_text": date_regex.group(0).strip(),
        }
    else:
        rules["rule_6_1_d_mfg_date"] = {
            "status": "NON_COMPLIANT",
            "rule_name": "Rule 6(1)(d) - Month & Year of Packing/Mfg",
            "found": False,
            "date": None,
            "raw_text": None,
        }
        violations.append(
            "Rule 6(1)(d) Violation: Month and Year of manufacture/packing is missing."
        )

    # 4. Rule 6(1)(a): Manufacturer / Packer Details
    mfr_regex = re.search(
        r"(?:MFD\s*BY|MFG\s*BY|PACKED\s*BY|MARKETED\s*BY|MANUFACTURED\s*(?:&|AND)?\s*PACKED\s*BY)\s*[:.\-]?\s*([A-Za-z0-9\s,\.\-]{6,80})",
        full_text,
        re.IGNORECASE,
    )
    if mfr_regex:
        rules["rule_6_1_a_manufacturer"] = {
            "status": "COMPLIANT",
            "rule_name": "Rule 6(1)(a) - Manufacturer / Packer Details",
            "found": True,
            "details": mfr_regex.group(0).strip()[:70],
        }
    else:
        rules["rule_6_1_a_manufacturer"] = {
            "status": "NON_COMPLIANT",
            "rule_name": "Rule 6(1)(a) - Manufacturer / Packer Details",
            "found": False,
            "details": None,
        }
        violations.append(
            "Rule 6(1)(a) Violation: Name and complete address of the manufacturer/packer is missing."
        )

    # 5. Rule 6(1)(e): Consumer Care Helpline / Email
    email_regex = re.search(r"([\w\.-]+@[\w\.-]+\.\w+)", full_text)
    phone_regex = re.search(
        r"(?:(?:CARE|HELPLINE|PHONE|TEL|CONTACT|TOLL\s*FREE)\s*[:.\-]?\s*)?(\+?[0-9\-\s]{8,14})\b",
        full_text,
        re.IGNORECASE,
    )

    contact_found = bool(email_regex or phone_regex)
    contact_val = []
    if email_regex:
        contact_val.append(f"Email: {email_regex.group(1).strip()}")
    if phone_regex:
        contact_val.append(f"Phone: {phone_regex.group(1).strip()}")

    if contact_found:
        rules["rule_6_1_e_consumer_care"] = {
            "status": "COMPLIANT",
            "rule_name": "Rule 6(1)(e) - Consumer Care Helpline / Email",
            "found": True,
            "contact": " | ".join(contact_val),
        }
    else:
        rules["rule_6_1_e_consumer_care"] = {
            "status": "NON_COMPLIANT",
            "rule_name": "Rule 6(1)(e) - Consumer Care Helpline / Email",
            "found": False,
            "contact": None,
        }
        violations.append(
            "Rule 6(1)(e) Violation: Consumer care details (helpline or email) is missing."
        )

    # 6. Rule 7: Minimum Font Size
    too_small_count = sum(1 for d in detections if d.get("font_status") == "TOO_SMALL")
    compliant_font_count = len(detections) - too_small_count
    font_heights = [d.get("font_height_px", 0) for d in detections]

    rule_7_status = "COMPLIANT" if too_small_count == 0 else "NON_COMPLIANT"
    rules["rule_7_font_size"] = {
        "status": rule_7_status,
        "rule_name": "Rule 7 - Minimum Font Size & Proportionality",
        "compliant_lines_count": compliant_font_count,
        "too_small_lines_count": too_small_count,
        "min_font_height_px": min(font_heights) if font_heights else 0,
        "avg_font_height_px": round(float(np.mean(font_heights)), 1) if font_heights else 0,
    }
    if too_small_count > 0:
        violations.append(
            f"Rule 7 Violation: {too_small_count} text segment(s) detected with microscopic font height (<1.2% of display area)."
        )

    # Calculate overall compliance statistics
    total_rules = len(rules)
    passed_rules = sum(1 for r in rules.values() if r["status"] == "COMPLIANT")
    compliance_score = round((passed_rules / total_rules) * 100, 1)
    overall_status = "COMPLIANT" if compliance_score >= 85.0 and len(violations) == 0 else "NON_COMPLIANT"

    return {
        "compliance_score_pct": compliance_score,
        "overall_status": overall_status,
        "total_rules_checked": total_rules,
        "rules_passed_count": passed_rules,
        "rules_evaluation": rules,
        "violations": violations,
    }


def draw_visual_annotations(image_cv, detections, compliance_report):
    """
    Annotates the package with color-coded 4-point bounding polygons:
    - Green (46, 204, 113): Rule-compliant / Normal font
    - Red (52, 73, 235): Non-compliant / Microscopic font / Rule 7 violation
    Overlays a HUD header banner with compliance statistics.
    """
    annotated = image_cv.copy()
    h_img, w_img, _ = annotated.shape

    # 1. Draw 4-point Bounding Boxes
    for det in detections:
        bbox = det["bbox"]
        pts = np.array(bbox, np.int32).reshape((-1, 1, 2))
        is_normal = det.get("font_status") == "NORMAL"
        color = (46, 204, 113) if is_normal else (52, 73, 235)  # Green vs Red

        cv2.polylines(annotated, [pts], isClosed=True, color=color, thickness=2)

        # Label tag above bounding box
        x, y = int(bbox[0][0]), int(bbox[0][1])
        label = f"{det['text'][:20]} [{det.get('font_status', 'NORMAL')}]"
        (lbl_w, lbl_h), _ = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 0.42, 1)
        lbl_y = max(lbl_h + 6, y - 4)

        cv2.rectangle(
            annotated,
            (x, lbl_y - lbl_h - 4),
            (x + lbl_w + 6, lbl_y + 2),
            color,
            -1,
        )

        cv2.putText(
            annotated,
            label,
            (x + 3, lbl_y - 2),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.42,
            (255, 255, 255),
            1,
            cv2.LINE_AA,
        )

    # 2. HUD Header Banner
    banner_h = 50
    overlay = annotated.copy()
    cv2.rectangle(overlay, (0, 0), (w_img, banner_h), (18, 22, 28), -1)
    cv2.addWeighted(overlay, 0.85, annotated, 0.15, 0, annotated)

    title_text = "LEGAL METROLOGY SCANNER (SIH)"
    cv2.putText(
        annotated,
        title_text,
        (12, 22),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.58,
        (255, 255, 255),
        2,
        cv2.LINE_AA,
    )

    score = compliance_report["compliance_score_pct"]
    v_count = len(compliance_report["violations"])
    status = compliance_report["overall_status"]
    status_color = (46, 204, 113) if status == "COMPLIANT" else (52, 73, 235)

    stats_text = f"Status: {status} | Score: {score}% | Violations: {v_count}"
    cv2.putText(
        annotated,
        stats_text,
        (12, 42),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.48,
        status_color,
        2,
        cv2.LINE_AA,
    )

    return annotated


def extract_packaging_info(image_path, output_dir=None):
    """
    Main entry point for SIH Legal Metrology AI Pipeline.
    Returns JSON formatted according to the agreed backend/frontend schema contract.
    """
    if not os.path.exists(image_path):
        return {"error": f"Image file not found: {image_path}"}

    original_img = cv2.imread(image_path)
    if original_img is None:
        return {"error": "Invalid or corrupt image format"}

    orig_h, orig_w = original_img.shape[:2]
    image_base = os.path.splitext(os.path.basename(image_path))[0]
    target_dir = output_dir or os.path.dirname(image_path) or "."

    # Step 1: Preprocessing (Auto-crop, Auto-orientation & CLAHE glare suppression)
    cropped_img, was_cropped, _ = auto_crop_package(original_img)
    
    # Auto-orient if image is rotated
    if auto_orient_image is not None:
        oriented_img, rot_angle = auto_orient_image(cropped_img, ocr_func=lambda img: reader.readtext(img))
    else:
        oriented_img = cropped_img

    proc_h, proc_w = oriented_img.shape[:2]
    enhanced_img = enhance_contrast_clahe(oriented_img)

    # Save processed image
    processed_image_path = os.path.join(target_dir, f"{image_base}_processed.jpg")
    cv2.imwrite(processed_image_path, enhanced_img)

    # Step 2: EasyOCR Extraction
    raw_results = reader.readtext(enhanced_img)


    detections = []
    font_heights = []

    for idx, (raw_bbox, text, conf) in enumerate(raw_results):
        text = text.strip()
        if conf < 0.25 or not text:
            continue

        # Standard 4-point polygon format: [[x1, y1], [x2, y2], [x3, y3], [x4, y4]]
        bbox_pts = [[int(round(pt[0])), int(round(pt[1]))] for pt in raw_bbox]

        ys = [p[1] for p in bbox_pts]
        box_h = max(1, max(ys) - min(ys))
        font_heights.append(box_h)

        # Rule 7 Font Size Proportion Check
        font_ratio = round((box_h / proc_h) * 100, 2)
        font_status = "NORMAL" if font_ratio >= 1.2 else "TOO_SMALL"

        detections.append({
            "id": idx + 1,
            "text": text,
            "confidence": round(float(conf), 2),
            "bbox": bbox_pts,
            "font_height_px": box_h,
            "font_ratio_pct": font_ratio,
            "font_status": font_status,
        })

    # Step 3: Statutory Compliance Evaluation
    compliance_report = evaluate_legal_metrology_compliance(detections, proc_h)

    # Step 4: Visual Annotation
    annotated_image_path = os.path.join(target_dir, f"{image_base}_annotated.jpg")
    annotated_img = draw_visual_annotations(cropped_img, detections, compliance_report)
    cv2.imwrite(annotated_image_path, annotated_img)

    # Step 5: Construct Exact Target JSON Schema
    # Normalize paths to use forward slashes for clean cross-platform JSON
    norm_source_path = image_path.replace("\\", "/")
    norm_processed_path = processed_image_path.replace("\\", "/")
    norm_annotated_path = annotated_image_path.replace("\\", "/")

    # Clean detections for the pure OCR contract (removing internal helper fields)
    ocr_detections = [
        {
            "id": d["id"],
            "text": d["text"],
            "confidence": d["confidence"],
            "bbox": d["bbox"],
        }
        for d in detections
    ]

    result = {
        "image_id": image_base,
        "source_image": {
            "path": norm_source_path,
            "width": orig_w,
            "height": orig_h,
        },
        "processed_image": {
            "path": norm_processed_path,
            "width": proc_w,
            "height": proc_h,
        },
        "annotated_image": {
            "path": norm_annotated_path,
        },
        "ocr": {
            "engine": "EasyOCR",
            "language": ["en"],
            "detections": ocr_detections,
        },
        "legal_metrology_compliance": compliance_report,
    }

    # Automatically save result JSON file to disk as well
    json_path = os.path.join(target_dir, f"{image_base}_result.json")
    try:
        import json
        with open(json_path, "w", encoding="utf-8") as f:
            json.dump(result, f, indent=2)
    except Exception:
        pass

    return result


if __name__ == "__main__":
    import json

    input_img = "sample.jpg"
    if len(sys.argv) > 1:
        input_img = sys.argv[1]

    if os.path.exists(input_img):
        print(f"[OCR-CV] Processing '{input_img}'...")
        result = extract_packaging_info(input_img)
        print(json.dumps(result, indent=2))
        print(f"\n[OCR-CV] Processed image saved: {result['processed_image']['path']}")
        print(f"[OCR-CV] Annotated image saved: {result['annotated_image']['path']}")
    else:
        print(f"[OCR-CV] File not found: {input_img}. Run with: python ocr_engine.py <image_path>")
