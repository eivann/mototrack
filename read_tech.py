import sys
from pypdf import PdfReader

try:
    reader = PdfReader("CHAPTER 1-3.pdf")
    text = ""
    # Extract pages 20 to 29 (0-indexed 19 to 28)
    for i in range(19, min(29, len(reader.pages))):
        text += f"\n--- PAGE {i+1} ---\n"
        text += reader.pages[i].extract_text()
    
    with open("tech_pages.txt", "w", encoding="utf-8") as f:
        f.write(text)
    print("Extracted tech pages successfully.")
except Exception as e:
    print("Error:", e)
