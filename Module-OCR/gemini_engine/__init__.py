"""
Gemini Semantic Extraction Engine Package.

Provides AI semantic extraction for Legal Metrology statutory declarations.
"""

from .extractor import GeminiExtractionEngine, extract_declarations
from .fallback import regex_fallback_extract
from .models import (
    DECLARATION_FIELDS,
    DeclarationItem,
    ExtractionResponse,
    OCRItem,
)

__all__ = [
    "GeminiExtractionEngine",
    "extract_declarations",
    "regex_fallback_extract",
    "DECLARATION_FIELDS",
    "DeclarationItem",
    "ExtractionResponse",
    "OCRItem",
]
