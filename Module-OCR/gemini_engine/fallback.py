"""
Deterministic Regex Fallback Extraction Engine for Kardashev-6.

Used when Gemini API is unavailable or fails, or for baseline pattern extraction.
Extracted fields use extraction_method="regex_fallback".
"""

import re
from typing import Any, Dict, List, Optional
from .models import DECLARATION_FIELDS, DeclarationItem, OCRItem


def regex_fallback_extract(ocr_items: List[OCRItem]) -> List[DeclarationItem]:
    """Perform deterministic pattern extraction on OCR items.

    Args:
        ocr_items: List of normalized OCRItem objects.

    Returns:
        List of DeclarationItem objects for all 15 DECLARATION_FIELDS.
    """
    extracted_map: Dict[str, DeclarationItem] = {}

    # Standard & fuzzy regex patterns for packaged commodity declarations
    mrp_pattern = re.compile(
        r"(?:m\.?r\.?p\.?|u\.?r\.?p\.?|h\.?r\.?p\.?|price|rs\.?|₹)\s*[:.\-]?\s*(?:rs\.?|₹|[a-z])?\s*([0-9]+(?:\.[0-9]{1,2})?)",
        re.IGNORECASE,
    )
    net_qty_pattern = re.compile(
        r"(?:net\s*(?:qty|quantity|wt|weight|cuantity)?|content[s]?)\s*[:.\-]?\s*([0-9]+(?:\.[0-9]+)?\s*(?:g|gm|gms|kg|ml|l|ltr|liter|litres|n|units|pcs|pieces)?)\b",
        re.IGNORECASE,
    )
    net_qty_standalone_pattern = re.compile(
        r"\b([0-9]+(?:\.[0-9]+)?\s*(?:g|gm|gms|kg|ml|l|ltr|liter|litres))\b",
        re.IGNORECASE,
    )
    mfg_date_pattern = re.compile(
        r"(?:mfg|manufactured|mfd|date|dom|dop)\.?:?\s*([0-9]{1,2}[/\.-][0-9]{2,4}|[0-9]{6}|[a-z]{3}\s*[0-9]{2,4})",
        re.IGNORECASE,
    )
    pkg_date_pattern = re.compile(
        r"(?:pkd|packed|packing\s*date|date\s*of\s*pkg)\.?:?\s*([0-9]{1,2}[/\.-][0-9]{2,4}|[a-z]{3}\s*[0-9]{2,4})",
        re.IGNORECASE,
    )
    import_date_pattern = re.compile(
        r"(?:imported|import\s*date|date\s*of\s*import)\.?:?\s*([0-9]{1,2}[/\.-][0-9]{2,4}|[a-z]{3}\s*[0-9]{2,4})",
        re.IGNORECASE,
    )
    phone_pattern = re.compile(
        r"(?:tel|phone|contact|call|helpline|customer\s*care|consumer\s*care|care|toll\s*free)?\s*[:.\-]?\s*([0-9]{4}[-\s]?[0-9]{3}[-\s]?[0-9]{3,4}|1800[-\s]?[0-9]{3}[-\s]?[0-9]{3,4}|\+91[-\s]?[0-9]{10})",
        re.IGNORECASE,
    )
    email_pattern = re.compile(
        r"([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})",
        re.IGNORECASE,
    )
    mfr_pattern = re.compile(
        r"(?:mfd|manufactured|mfg|mktd|manufac\s*tufed)\s*(?:by|&|and)?\.?:?\s*(.+)",
        re.IGNORECASE,
    )
    packer_pattern = re.compile(
        r"(?:packed\s*by|pkd\s*by)\.?:?\s*(.+)",
        re.IGNORECASE,
    )
    importer_pattern = re.compile(
        r"(?:imported\s*by|imp\s*by)\.?:?\s*(.+)",
        re.IGNORECASE,
    )
    product_name_pattern = re.compile(
        r"(?:product\s*name|pfoductnahie|product|item)\s*[:.\-]?\s*(.+)",
        re.IGNORECASE,
    )

    for item in ocr_items:
        text = item.text.strip()
        if not text:
            continue

        # 0. Product Name
        if "product_name" not in extracted_map:
            m = product_name_pattern.search(text)
            if m:
                extracted_map["product_name"] = DeclarationItem(
                    field="product_name",
                    value=m.group(1).strip(),
                    source_text=text,
                    ocr_confidence=item.confidence,
                    bbox=item.bbox,
                    extraction_confidence=0.85 if item.confidence > 0.6 else 0.50,
                    extraction_method="regex_fallback",
                )

        # 1. MRP
        if "mrp" not in extracted_map:
            m = mrp_pattern.search(text)
            if m and (any(k in text.lower() for k in ["mrp", "urp", "hrp", "price", "rs", "₹", "tax", "incl"])):
                extracted_map["mrp"] = DeclarationItem(
                    field="mrp",
                    value=m.group(1),
                    source_text=text,
                    ocr_confidence=item.confidence,
                    bbox=item.bbox,
                    extraction_confidence=0.90 if item.confidence > 0.6 else 0.50,
                    extraction_method="regex_fallback",
                )

        # 2. Net Quantity
        if "net_quantity" not in extracted_map:
            m = net_qty_pattern.search(text)
            if m and any(k in text.lower() for k in ["net", "qty", "quantity", "wt", "weight", "cuantity", "content"]):
                extracted_map["net_quantity"] = DeclarationItem(
                    field="net_quantity",
                    value=m.group(1),
                    source_text=text,
                    ocr_confidence=item.confidence,
                    bbox=item.bbox,
                    extraction_confidence=0.90 if item.confidence > 0.6 else 0.50,
                    extraction_method="regex_fallback",
                )
            else:
                m_stand = net_qty_standalone_pattern.search(text)
                if m_stand:
                    extracted_map["net_quantity"] = DeclarationItem(
                        field="net_quantity",
                        value=m_stand.group(1),
                        source_text=text,
                        ocr_confidence=item.confidence,
                        bbox=item.bbox,
                        extraction_confidence=0.80 if item.confidence > 0.6 else 0.45,
                        extraction_method="regex_fallback",
                    )

        # 3. Manufacture Date
        if "manufacture_date" not in extracted_map:
            m = mfg_date_pattern.search(text)
            if m:
                extracted_map["manufacture_date"] = DeclarationItem(
                    field="manufacture_date",
                    value=m.group(1),
                    source_text=text,
                    ocr_confidence=item.confidence,
                    bbox=item.bbox,
                    extraction_confidence=0.85 if item.confidence > 0.6 else 0.50,
                    extraction_method="regex_fallback",
                )

        # 4. Packing Date
        if "packing_date" not in extracted_map:
            m = pkg_date_pattern.search(text)
            if m:
                extracted_map["packing_date"] = DeclarationItem(
                    field="packing_date",
                    value=m.group(1),
                    source_text=text,
                    ocr_confidence=item.confidence,
                    bbox=item.bbox,
                    extraction_confidence=0.85 if item.confidence > 0.6 else 0.50,
                    extraction_method="regex_fallback",
                )

        # 5. Import Date
        if "import_date" not in extracted_map:
            m = import_date_pattern.search(text)
            if m:
                extracted_map["import_date"] = DeclarationItem(
                    field="import_date",
                    value=m.group(1),
                    source_text=text,
                    ocr_confidence=item.confidence,
                    bbox=item.bbox,
                    extraction_confidence=0.85 if item.confidence > 0.6 else 0.50,
                    extraction_method="regex_fallback",
                )

        # 6. Consumer Care Phone
        if "consumer_care_phone" not in extracted_map:
            if any(k in text.lower() for k in ["care", "help", "customer", "consumer", "1800", "call", "phone"]):
                m = phone_pattern.search(text)
                if m:
                    extracted_map["consumer_care_phone"] = DeclarationItem(
                        field="consumer_care_phone",
                        value=m.group(1),
                        source_text=text,
                        ocr_confidence=item.confidence,
                        bbox=item.bbox,
                        extraction_confidence=0.85 if item.confidence > 0.6 else 0.50,
                        extraction_method="regex_fallback",
                    )

        # 7. Consumer Care Email
        if "consumer_care_email" not in extracted_map:
            m = email_pattern.search(text)
            if m:
                extracted_map["consumer_care_email"] = DeclarationItem(
                    field="consumer_care_email",
                    value=m.group(1),
                    source_text=text,
                    ocr_confidence=item.confidence,
                    bbox=item.bbox,
                    extraction_confidence=0.90 if item.confidence > 0.6 else 0.50,
                    extraction_method="regex_fallback",
                )

        # 8. Manufacturer
        if "manufacturer" not in extracted_map:
            m = mfr_pattern.search(text)
            if m:
                extracted_map["manufacturer"] = DeclarationItem(
                    field="manufacturer",
                    value=m.group(1).strip(),
                    source_text=text,
                    ocr_confidence=item.confidence,
                    bbox=item.bbox,
                    extraction_confidence=0.80 if item.confidence > 0.6 else 0.45,
                    extraction_method="regex_fallback",
                )

        # 9. Packer
        if "packer" not in extracted_map:
            m = packer_pattern.search(text)
            if m:
                extracted_map["packer"] = DeclarationItem(
                    field="packer",
                    value=m.group(1).strip(),
                    source_text=text,
                    ocr_confidence=item.confidence,
                    bbox=item.bbox,
                    extraction_confidence=0.80 if item.confidence > 0.6 else 0.45,
                    extraction_method="regex_fallback",
                )

        # 10. Importer
        if "importer" not in extracted_map:
            m = importer_pattern.search(text)
            if m:
                extracted_map["importer"] = DeclarationItem(
                    field="importer",
                    value=m.group(1).strip(),
                    source_text=text,
                    ocr_confidence=item.confidence,
                    bbox=item.bbox,
                    extraction_confidence=0.80 if item.confidence > 0.6 else 0.45,
                    extraction_method="regex_fallback",
                )

    # Build final complete declaration list covering all 15 DECLARATION_FIELDS
    results: List[DeclarationItem] = []
    for field in DECLARATION_FIELDS:
        if field in extracted_map:
            results.append(extracted_map[field])
        else:
            results.append(
                DeclarationItem(
                    field=field,
                    value=None,
                    source_text=None,
                    ocr_confidence=None,
                    bbox=None,
                    extraction_confidence=0.0,
                    extraction_method="regex_fallback",
                )
            )

    return results

