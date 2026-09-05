"""
Pydantic Models and Constants for Gemini Semantic Extraction Engine.
"""

from typing import Any, Dict, List, Optional
from pydantic import BaseModel, Field

# List of 15 statutory declaration fields required under Legal Metrology Rules
DECLARATION_FIELDS = [
    "product_name",
    "manufacturer",
    "manufacturer_address",
    "packer",
    "packer_address",
    "importer",
    "importer_address",
    "net_quantity",
    "mrp",
    "manufacture_date",
    "packing_date",
    "import_date",
    "consumer_care",
    "consumer_care_phone",
    "consumer_care_email",
]


class OCRItem(BaseModel):
    """Normalized OCR input detection item."""
    text: str
    confidence: float = 1.0
    bbox: Optional[List[Any]] = None
    original_bbox: Optional[List[Any]] = None


class DeclarationItem(BaseModel):
    """Structured packaged-commodity declaration item with preserved OCR evidence."""
    field: str = Field(description="Name of the statutory declaration field.")
    value: Optional[str] = Field(default=None, description="Normalized declaration value string, or None if absent.")
    source_text: Optional[str] = Field(default=None, description="Exact raw OCR text supporting this declaration.")
    ocr_confidence: Optional[float] = Field(default=None, description="Confidence score from the OCR detection.")
    bbox: Optional[List[Any]] = Field(default=None, description="Bounding box coordinates from OCR detection.")
    extraction_confidence: float = Field(default=1.0, description="Confidence score of the semantic extraction (0.0 to 1.0).")
    extraction_method: str = Field(default="gemini", description="Extraction method used ('gemini' or 'regex_fallback').")


class ExtractionResponse(BaseModel):
    """Final structured response returned by the extraction engine."""
    declarations: List[DeclarationItem] = Field(default_factory=list)
    status: str = Field(default="SUCCESS", description="Execution status ('SUCCESS', 'FALLBACK', 'ERROR').")
    extraction_method: str = Field(default="gemini", description="Primary extraction method executed.")
    model_used: Optional[str] = Field(default=None, description="Gemini model name used if Gemini API was called.")
    error_message: Optional[str] = Field(default=None, description="Error message if Gemini call failed.")


# Models used for Structured Output Schema in google.genai SDK
class GeminiExtractedItem(BaseModel):
    field: str = Field(description="One of the supported field names from DECLARATION_FIELDS.")
    value: str = Field(description="Normalized extracted value string.")
    ocr_index: int = Field(description="0-based index of the OCR detection item supporting this extraction.")
    extraction_confidence: float = Field(default=1.0, description="Confidence of extraction between 0.0 and 1.0.")


class GeminiRawResponse(BaseModel):
    extracted_items: List[GeminiExtractedItem] = Field(default_factory=list)
