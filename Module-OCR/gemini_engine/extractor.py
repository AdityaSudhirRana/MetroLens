"""
Gemini Semantic Extraction Engine for Kardashev-6 (SIH26034).

Converts raw EasyOCR detections into structured Legal Metrology declarations.
"""

import json
import logging
import os
from pathlib import Path
from typing import Any, Dict, List, Optional, Union

import dotenv
from pydantic import BaseModel

from .fallback import regex_fallback_extract
from .models import (
    DECLARATION_FIELDS,
    DeclarationItem,
    ExtractionResponse,
    GeminiExtractedItem,
    GeminiRawResponse,
    OCRItem,
)
from .prompt import SYSTEM_PROMPT

# Auto-load environment variables from .env if available
dotenv.load_dotenv()

logger = logging.getLogger(__name__)


class GeminiExtractionEngine:
    """Semantic Extraction Engine using Google GenAI SDK (google.genai)."""

    def __init__(
        self,
        api_key: Optional[str] = None,
        model_name: Optional[str] = None,
    ):
        """Initialize the Gemini extraction engine.

        Args:
            api_key: Optional Gemini API key. Defaults to GEMINI_API_KEY env var.
            model_name: Optional Gemini model name. Defaults to GEMINI_MODEL env var or 'gemini-2.5-flash'.
        """
        self.api_key = api_key or os.environ.get("GEMINI_API_KEY") or os.environ.get("GOOGLE_API_KEY")
        self.model_name = model_name or os.environ.get("GEMINI_MODEL") or "gemini-3.6-flash"
        self._fallback_models = ["gemini-3.6-flash", "gemini-3.6-pro", "gemini-2.5-flash", "gemini-1.5-flash"]

    @staticmethod
    def parse_ocr_input(ocr_input: Union[Dict[str, Any], List[Dict[str, Any]], str, Path]) -> List[OCRItem]:
        """Parse various formats of OCR JSON input into a list of normalized OCRItem objects.

        Supported inputs:
        - Dict with 'ocr_results' key
        - Dict with 'detections' key
        - Direct List of dicts
        - Path / string file path to OCR JSON
        """
        raw_list: List[Dict[str, Any]] = []

        if isinstance(ocr_input, (str, Path)):
            p = Path(ocr_input)
            if p.exists() and p.is_file():
                content = json.loads(p.read_text(encoding="utf-8"))
                return GeminiExtractionEngine.parse_ocr_input(content)
            else:
                return []

        if isinstance(ocr_input, dict):
            if "ocr_results" in ocr_input and isinstance(ocr_input["ocr_results"], list):
                raw_list = ocr_input["ocr_results"]
            elif "detections" in ocr_input and isinstance(ocr_input["detections"], list):
                raw_list = ocr_input["detections"]
            else:
                raw_list = []
        elif isinstance(ocr_input, list):
            raw_list = ocr_input
        else:
            raw_list = []

        ocr_items: List[OCRItem] = []
        for item in raw_list:
            if not isinstance(item, dict):
                continue
            text = str(item.get("text") or "").strip()
            if not text:
                continue

            try:
                confidence = float(item.get("confidence", 1.0))
            except (ValueError, TypeError):
                confidence = 1.0

            bbox = item.get("bbox")
            original_bbox = item.get("original_bbox") or bbox

            ocr_items.append(
                OCRItem(
                    text=text,
                    confidence=confidence,
                    bbox=bbox,
                    original_bbox=original_bbox,
                )
            )

        return ocr_items

    def extract(
        self,
        ocr_input: Union[Dict[str, Any], List[Dict[str, Any]], str, Path],
        force_fallback: bool = False,
    ) -> ExtractionResponse:
        """Extract structured declaration items from OCR detection input.

        Args:
            ocr_input: OCR detection JSON input (dict, list, or file path).
            force_fallback: If True, bypasses Gemini API and uses deterministic regex fallback.

        Returns:
            ExtractionResponse containing structured declarations and metadata.
        """
        ocr_items = self.parse_ocr_input(ocr_input)

        # 1. Handle Empty OCR Input
        if not ocr_items:
            empty_declarations = [
                DeclarationItem(
                    field=f,
                    value=None,
                    source_text=None,
                    ocr_confidence=None,
                    bbox=None,
                    extraction_confidence=0.0,
                    extraction_method="gemini" if not force_fallback else "regex_fallback",
                )
                for f in DECLARATION_FIELDS
            ]
            return ExtractionResponse(
                declarations=empty_declarations,
                status="SUCCESS",
                extraction_method="gemini" if not force_fallback else "regex_fallback",
                model_used=self.model_name if not force_fallback else None,
            )

        # 2. Check API Key or Forced Fallback
        if force_fallback or not self.api_key:
            reason = "Forced fallback requested" if force_fallback else "GEMINI_API_KEY environment variable not set"
            logger.info(f"Using deterministic regex fallback extractor: {reason}")
            fallback_items = regex_fallback_extract(ocr_items)
            return ExtractionResponse(
                declarations=fallback_items,
                status="FALLBACK",
                extraction_method="regex_fallback",
                model_used=None,
                error_message=reason if not force_fallback else None,
            )

        # 3. Execute Gemini Extraction using google.genai SDK
        try:
            from google import genai
            from google.genai import types

            client = genai.Client(api_key=self.api_key)

            # Format OCR detections into indexed payload for Gemini prompt
            ocr_payload = [
                {
                    "index": idx,
                    "text": item.text,
                    "confidence": item.confidence,
                    "bbox": item.bbox,
                }
                for idx, item in enumerate(ocr_items)
            ]

            user_prompt = f"Extract statutory declarations from these OCR detections:\n\n{json.dumps(ocr_payload, indent=2)}"

            # Call Gemini API with structured response schema
            raw_gemini_resp = None
            last_err = None
            models_to_try = [self.model_name] + [m for m in self._fallback_models if m != self.model_name]

            for mod in models_to_try:
                try:
                    response = client.models.generate_content(
                        model=mod,
                        contents=user_prompt,
                        config=types.GenerateContentConfig(
                            system_instruction=SYSTEM_PROMPT,
                            response_mime_type="application/json",
                            response_schema=GeminiRawResponse,
                            temperature=0.0,
                        ),
                    )
                    raw_gemini_resp = response
                    self.model_name = mod
                    break
                except Exception as mod_e:
                    last_err = mod_e
                    logger.warning(f"Failed to generate content with model {mod}: {str(mod_e)}")

            if raw_gemini_resp is None:
                raise RuntimeError(f"All Gemini models failed. Last error: {str(last_err)}")

            # Parse structured response
            gemini_raw_data: Optional[GeminiRawResponse] = getattr(raw_gemini_resp, "parsed", None)
            if gemini_raw_data is None and getattr(raw_gemini_resp, "text", None):
                gemini_raw_data = GeminiRawResponse.model_validate_json(raw_gemini_resp.text)

            if gemini_raw_data is None:
                raise ValueError("Gemini returned empty or unparseable structured response.")

            # Map extracted items back to original OCR items and evidence
            extracted_dict: Dict[str, DeclarationItem] = {}
            for ext in gemini_raw_data.extracted_items:
                field = ext.field.strip().lower()
                if field not in DECLARATION_FIELDS:
                    continue

                val = (ext.value or "").strip()
                if not val:
                    continue

                idx = ext.ocr_index
                source_text = None
                ocr_conf = None
                bbox = None

                if 0 <= idx < len(ocr_items):
                    matching_ocr = ocr_items[idx]
                    source_text = matching_ocr.text
                    ocr_conf = matching_ocr.confidence
                    bbox = matching_ocr.bbox
                else:
                    # Search by text match fallback if index out of bounds
                    for item in ocr_items:
                        if val.lower() in item.text.lower() or item.text.lower() in val.lower():
                            source_text = item.text
                            ocr_conf = item.confidence
                            bbox = item.bbox
                            break

                # Low-confidence / Ambiguous OCR handling
                calc_ext_conf = float(ext.extraction_confidence)
                if ocr_conf is not None:
                    # If OCR confidence is very low (< 0.40), preserve uncertainty
                    if ocr_conf < 0.40:
                        calc_ext_conf = min(calc_ext_conf, ocr_conf * 1.2)
                        # If OCR confidence is too low to be reliable (< 0.30), drop value to null
                        if ocr_conf < 0.30:
                            val = None

                if val is not None:
                    extracted_dict[field] = DeclarationItem(
                        field=field,
                        value=val,
                        source_text=source_text,
                        ocr_confidence=ocr_conf,
                        bbox=bbox,
                        extraction_confidence=round(calc_ext_conf, 2),
                        extraction_method="gemini",
                    )

            # Build full declaration list for all 15 fields
            final_declarations: List[DeclarationItem] = []
            for field in DECLARATION_FIELDS:
                if field in extracted_dict:
                    final_declarations.append(extracted_dict[field])
                else:
                    final_declarations.append(
                        DeclarationItem(
                            field=field,
                            value=None,
                            source_text=None,
                            ocr_confidence=None,
                            bbox=None,
                            extraction_confidence=0.0,
                            extraction_method="gemini",
                        )
                    )

            return ExtractionResponse(
                declarations=final_declarations,
                status="SUCCESS",
                extraction_method="gemini",
                model_used=self.model_name,
            )

        except Exception as e:
            logger.error(f"Gemini API extraction failed: {str(e)}. Falling back to deterministic regex extraction.")
            fallback_items = regex_fallback_extract(ocr_items)
            return ExtractionResponse(
                declarations=fallback_items,
                status="FALLBACK",
                extraction_method="regex_fallback",
                model_used=None,
                error_message=f"Gemini API Error: {str(e)}",
            )


# Standalone function interface per requirement #20
def extract_declarations(
    ocr_input: Union[Dict[str, Any], List[Dict[str, Any]], str, Path],
    api_key: Optional[str] = None,
    model_name: Optional[str] = None,
    force_fallback: bool = False,
) -> ExtractionResponse:
    """Clean callable entry point for declaration extraction from OCR data.

    Args:
        ocr_input: OCR detection JSON (dict, list, or file path).
        api_key: Optional API key.
        model_name: Optional model name.
        force_fallback: If True, uses regex fallback without calling Gemini.

    Returns:
        ExtractionResponse structured object.
    """
    engine = GeminiExtractionEngine(api_key=api_key, model_name=model_name)
    return engine.extract(ocr_input=ocr_input, force_fallback=force_fallback)
