import argparse
import json
import os
import sys
from pathlib import Path
import dotenv

# Load .env file for GEMINI_API_KEY if present
dotenv.load_dotenv()

# # Set the hardcoded API key if you don't have a .env file configured
# if "GEMINI_API_KEY" not in os.environ:
#     os.environ["GEMINI_API_KEY"] = ""

# Check that the API key exists
if not os.getenv("GEMINI_API_KEY"):
    print("GEMINI_API_KEY not found. Please add it to the .env file.")
    sys.exit(1)

# Ensure the current directory is in sys.path so we can import local modules
PROJECT_ROOT = Path(__file__).resolve().parent
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

try:
    from google import genai
except ImportError:
    print("google-genai not found. Please install it: pip install google-genai")
    sys.exit(1)

# Import our custom modules
from gemini_engine.extractor import extract_declarations
from pipeline.ocr_pipeline import run_ocr_pipeline

def evaluate(image_path_str):
    image_path = Path(image_path_str)
    compliance_json_path = PROJECT_ROOT / "gemini_json_compliance" / "packaged_commodities_40_rules.json"
    
    if not image_path.exists():
        print(f"Error: {image_path} not found.")
        return
        
    print(f"Running OCR pipeline on {image_path}...")
    # 1. OCR Pipeline
    # Results will be saved to an 'outputs' folder inside Kardashev-final
    output_dir = PROJECT_ROOT / "outputs"
    output_dir.mkdir(exist_ok=True)
    
    ocr_res = run_ocr_pipeline(image_path, output_dir=str(output_dir))
    if ocr_res.get("status") != "SUCCESS":
        print(f"OCR failed: {ocr_res.get('error')}")
        return
        
    print("Extracting declarations with Gemini...")
    # 2. Extract Declarations
    ext_res = extract_declarations(ocr_res)
    ext_json = ext_res.model_dump()
    
    # Save the extracted JSON for debugging
    ext_json_path = output_dir / f"{image_path.stem}_extracted.json"
    with open(ext_json_path, "w", encoding="utf-8") as f:
        json.dump(ext_json, f, indent=2)
    print(f"Extracted JSON saved to {ext_json_path}")
    
    print("Loading compliance rules...")
    # 3. Load Compliance Rules
    with open(compliance_json_path, "r", encoding="utf-8") as f:
        compliance_rules = json.load(f)
        
    print("Calling Gemini for compliance evaluation...")
    # 4. Evaluate Compliance using Gemini
    client = genai.Client(api_key=os.environ.get("GEMINI_API_KEY") or os.environ.get("GOOGLE_API_KEY"))
    
    prompt = f"""
You are a Legal Metrology Compliance Expert.
I have extracted the following declarations from a packaged commodity using OCR and semantic extraction:
{json.dumps(ext_json, indent=2)}

Please compare these extracted declarations against the Legal Metrology (Packaged Commodities) Rules, 2011:
{json.dumps(compliance_rules, indent=2)}

Write a simple report that includes:
1. Which compliances were MET (based on what was found on the package).
2. Which compliances FAILED (what is missing or non-compliant).
3. A final verdict: "COMPLIANT" or "NON COMPLIANT".

Make the report easy to read.
"""

    model_name = os.environ.get("GEMINI_MODEL") or "gemini-3.6-flash"
    try:
        response = client.models.generate_content(
            model=model_name,
            contents=prompt,
        )
        report = response.text
    except Exception as e:
        print(f"Gemini API failed with {model_name}: {e}")
        # Try fallback model
        try:
            print("Trying fallback model gemini-3.6-pro...")
            response = client.models.generate_content(
                model="gemini-3.6-pro",
                contents=prompt,
            )
            report = response.text
        except Exception as e2:
            print(f"Fallback model failed: {e2}")
            return

    report_path = output_dir / f"{image_path.stem}_compliance_report.txt"
    with open(report_path, "w", encoding="utf-8") as f:
        f.write(report)
        
    print(f"Report saved to {report_path}")
    print("\n" + "="*50)
    print("COMPLIANCE REPORT:")
    print("="*50)
    print(report)

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Evaluate Legal Metrology Compliance on a package image.")
    parser.add_argument("image", help="Path to the product image to evaluate")
    args = parser.parse_args()
    
    evaluate(args.image)
