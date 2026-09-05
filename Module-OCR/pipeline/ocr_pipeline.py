"""
Finalized OpenCV + EasyOCR Pipeline for Kardashev-6 (SIH26034)

Orchestrates:
1. Original image preservation (outputs/original/)
2. OpenCV preprocessing (outputs/processed/)
3. Existing Person-4 EasyOCR execution
4. Bounding box coordinate mapping (if scaled)
5. Visual annotated image generation (outputs/annotated/)
6. Raw vs OpenCV processed comparison & result selection
7. Structured OCR JSON output export (outputs/ocr/)
"""

import json
import logging
import os
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple, Union

import cv2
import numpy as np

from OpenCV.preprocessing import (
    PreprocessingConfig,
    preprocess_image_pipeline,
    save_image,
    validate_image,
)
from OCR.ocr_engine import (
    check_blur_score,
    draw_visual_annotations,
    evaluate_legal_metrology_compliance,
    run_ocr,
)

logger = logging.getLogger(__name__)


def run_ocr_pipeline(
    image_path: Union[str, Path],
    output_dir: Union[str, Path] = "outputs",
    config: Optional[PreprocessingConfig] = None,
    compare_raw: bool = True,
) -> Dict[str, Any]:
    """Executes the complete OpenCV + Existing Person-4 EasyOCR pipeline on a product image.

    Args:
        image_path: Path to the product image file.
        output_dir: Base directory to store outputs (original, processed, annotated, ocr).
        config: Optional PreprocessingConfig for OpenCV preprocessing.
        compare_raw: If True, compares raw OCR vs processed OCR to ensure preprocessing improves or maintains quality.

    Returns:
        Structured JSON dictionary following the stable OCR evidence contract.
    """
    path_obj = Path(image_path)
    if not path_obj.exists():
        return {
            "status": "FAILED",
            "error": f"Image file not found: {image_path}",
            "image_path": str(image_path),
        }

    # Setup standardized output directory structure
    base_out = Path(output_dir)
    dir_orig = base_out / "original"
    dir_proc = base_out / "processed"
    dir_annot = base_out / "annotated"
    dir_ocr = base_out / "ocr"

    for d in (dir_orig, dir_proc, dir_annot, dir_ocr):
        d.mkdir(parents=True, exist_ok=True)

    # 1. Load original image safely
    try:
        orig_img = cv2.imread(str(path_obj))
        validate_image(orig_img)
    except Exception as e:
        return {
            "status": "INVALID_IMAGE",
            "error": f"Invalid or corrupt image format: {str(e)}",
            "image_path": str(image_path),
        }

    stem = path_obj.stem
    ext = path_obj.suffix or ".jpg"

    # Save copy to outputs/original/
    saved_orig_path = dir_orig / f"{stem}_original{ext}"
    cv2.imwrite(str(saved_orig_path), orig_img)

    orig_h, orig_w = orig_img.shape[:2]
    blur_score = check_blur_score(orig_img)

    # 2. OpenCV Preprocessing
    if config is None:
        # Default safe preprocessing config
        config = PreprocessingConfig(
            max_dim=1600,
            grayscale=True,
            denoise_method="gaussian",
            contrast_method="clahe",
            sharpen_strength=0.5,
        )

    prep_result = preprocess_image_pipeline(orig_img, config=config)
    saved_proc_path = dir_proc / f"{stem}_processed{ext}"
    save_image(prep_result.processed_image, saved_proc_path)

    # 3. Existing Person-4 EasyOCR Execution
    # Run OCR on processed image
    ocr_processed_items = run_ocr(
        prep_result.processed_image, scale_metadata=prep_result.scale_metadata
    )

    # Optional Raw vs Processed Comparison
    raw_ocr_items = []
    if compare_raw:
        raw_ocr_items = run_ocr(orig_img, scale_metadata=None)

    # Select final detection set
    final_items = ocr_processed_items
    selection_strategy = "processed"

    if compare_raw and len(raw_ocr_items) > len(ocr_processed_items) and len(ocr_processed_items) == 0:
        final_items = raw_ocr_items
        selection_strategy = "raw_fallback"

    # 4. Generate Annotated Image with Bounding Boxes
    annotated_lines = []
    for item in final_items:
        bbox = item["bbox"]
        xs = [pt[0] for pt in bbox]
        ys = [pt[1] for pt in bbox]
        x_min, x_max = int(min(xs)), int(max(xs))
        y_min, y_max = int(min(ys)), int(max(ys))
        box_w = max(1, x_max - x_min)
        box_h = max(1, y_max - y_min)

        proc_h = prep_result.scale_metadata.processed_height
        font_ratio = round((box_h / proc_h) * 100, 2)
        font_status = "NORMAL" if font_ratio >= 1.2 else "TOO_SMALL"

        annotated_lines.append({
            "text": item["text"],
            "confidence": item["confidence"],
            "bbox": bbox,
            "box": {"x": x_min, "y": y_min, "w": box_w, "h": box_h},
            "font_height_px": box_h,
            "font_ratio_pct": font_ratio,
            "font_status": font_status,
        })


    annotated_img = orig_img.copy()
    if annotated_lines:
        compliance_summary = evaluate_legal_metrology_compliance(
            annotated_lines, prep_result.scale_metadata.processed_height
        )
        # Use color BGR image for visual annotation
        color_target = prep_result.processed_image
        if len(color_target.shape) == 2:
            color_target = cv2.cvtColor(color_target, cv2.COLOR_GRAY2BGR)
        annotated_img = draw_visual_annotations(
            color_target, annotated_lines, compliance_summary
        )

    saved_annot_path = dir_annot / f"{stem}_annotated{ext}"
    cv2.imwrite(str(saved_annot_path), annotated_img)

    # 5. Build Stable Final OCR JSON
    result_dict = {
        "status": "SUCCESS",
        "image_path": str(path_obj),
        "metadata": {
            "original_dimensions": {"width": orig_w, "height": orig_h},
            "processed_dimensions": {
                "width": prep_result.scale_metadata.processed_width,
                "height": prep_result.scale_metadata.processed_height,
            },
            "scale_metadata": prep_result.scale_metadata.to_dict(),
            "blur_score": blur_score,
            "is_blurry": blur_score < 60.0,
            "applied_preprocessing_steps": prep_result.applied_steps,
            "selection_strategy": selection_strategy,
            "raw_detections_count": len(raw_ocr_items),
            "processed_detections_count": len(ocr_processed_items),
        },
        "output_files": {
            "original": str(saved_orig_path),
            "processed": str(saved_proc_path),
            "annotated": str(saved_annot_path),
        },
        "detections": final_items,
        "full_text": " | ".join([it["text"] for it in final_items]),
    }

    # Export JSON to outputs/ocr/
    saved_ocr_json_path = dir_ocr / f"{stem}_ocr.json"
    saved_ocr_json_path.write_text(
        json.dumps(result_dict, indent=2, ensure_ascii=False), encoding="utf-8"
    )
    result_dict["output_files"]["ocr_json"] = str(saved_ocr_json_path)

    return result_dict
