import sys
from pypdf import PdfReader

try:
    reader = PdfReader("CHAPTER 1-3.pdf")
    text = ""
    for page in reader.pages:
        text += page.extract_text() + "\n\n"
    
    with open("pdf_text.txt", "w", encoding="utf-8") as f:
        f.write(text)
    print("Extracted successfully.")
except Exception as e:
    print("Error:", e)
