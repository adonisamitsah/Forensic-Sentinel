# 🕵️‍♂️ Forensic Sentinel v1.0
### Computational Auditing & Multi-Vector Anomaly Detection Engine

**Developer:** Amit Sah, MBA  
**Location:** York, United Kingdom  
**Version:** 1.0.0 (Production Release)

---

## 📜 Overview
**Forensic Sentinel** is a high-performance, intelligent auditing platform designed to transform raw financial ledgers into actionable forensic intelligence. Unlike traditional rule-based systems, this engine utilizes a **Probabilistic Risk Framework** to identify "leaks," "ghosts," and "shadows" within corporate financial data.

The project integrates statistical integrity testing (Benford's Law), entity resolution heuristics, and behavioral pattern recognition to provide an automated "Executive Reasoning" layer for auditors.

---

## 🚀 Core Features

### 1. Weighted Risk Index (WRI)
The engine doesn't just count errors; it calculates a weighted score based on the severity of the pattern:
- **Ghost Vendors (40 pts):** Detected via Levenshtein-based fuzzy-string matching.
- **Split-Invoicing (30 pts):** Identifies attempts to bypass authorization limits within a 24-hour window.
- **Velocity Spikes (25 pts):** Flags sudden acceleration in payment frequency.
- **Shadow Payroll (25 pts):** Detects cyclical, salary-like patterns in non-payroll categories.

### 2. Statistical Integrity (Benford’s Law)
Implements first-digit frequency analysis to detect **Human Heuristic Bias**. The system calculates a mathematical "Trust Score" by comparing the ledger's distribution against the logarithmic laws of natural data.

### 3. Cognitive AI Reasoning Engine
A heuristic inference module that synthesizes thousands of data points into a human-readable executive summary. It identifies:
- **Anomaly Gravity:** Where the bulk of the risk is concentrated.
- **Risk Vectors:** Specific departments or categories failing controls.
- **Strategic Prescriptions:** Actionable next steps for manual investigation.

### 4. Interactive Command Center
- **Temporal Velocity Charts:** volume vs. anomalies over time.
- **Category Pareto Analysis:** Instant visualization of high-leakage departments.
- **Live API Integration:** Real-time UK bank holiday compliance via the Nager.Date Registry.

---

## 🛠 Tech Stack
- **Backend:** PHP 8.x (Object-Oriented Architecture)
- **Frontend:** Tailwind CSS, Chart.js, DataTables.js
- **API Connectivity:** RESTful integration with Nager.Date API
- **Data Input:** Standardized Forensic JSON Ledgers

---

## 🔧 Installation & Setup

1. ### Clone the Repository:
   ```bash
   git clone https://github.com/adonisamitsah/Forensic-Sentinel.git

### 2. Web Server Environment
To run Forensic Sentinel, you need a local web server environment (like XAMPP, WAMP, or MAMP) or a remote web host with PHP installed.
* **Move Files:** Place the project folder into your local server directory (e.g., `C:/xampp/htdocs/` for XAMPP).
* **PHP Version:** Ensure **PHP 7.4+** is installed (**PHP 8.1+** is highly recommended for optimal performance).

### 3. Running the Audit
1.  **Launch Dashboard:** Open your browser and navigate to `http://localhost/Forensic-Sentinel`.
2.  **Get Test Data:** Download the provided `Mega_Data_Source.json` sample file from the repository or the link in the dashboard.
3.  **Execute Analysis:** Drag and drop the `.json` file into the "Forensic Dropzone." The Sentinel Engine will automatically trigger the analysis and generate the reasoning report.

---


## 🧪 Forensic Rules Matrix

The engine evaluates every transaction against a proprietary scorecard based on forensic accounting theory.

| Rule ID | Name | Forensic Theory |
| :--- | :--- | :--- |
| **R1** | **Round Numbers** | Humans naturally gravitate toward "even" numbers for fakes; organic financial data contains natural decimal noise. |
| **R2/R7** | **Temporal Analysis** | Fraudulent entries are statistically more likely to occur on weekends or bank holidays to bypass real-time oversight. |
| **R4** | **Threshold Bypass** | Detects "Splitting"—breaking a large payment into smaller pieces to stay under manager approval limits (e.g., just under £10k). |
| **R5** | **Ghost Vendors** | Utilizes Levenshtein Distance (Fuzzy Matching) to find misspellings used to divert funds into shell accounts. |
| **R6** | **Limit Proximity** | Monitors transactions sitting in the "Audit Shadow" (just below threshold limits) to avoid automatic review. |
| **R8** | **Cross-Vendor Match** | Flags identical amounts paid to different entities within a 72-hour window, indicating high-risk manual entry patterns. |
| **R9** | **Velocity Spike** | Detects abnormal frequency increases in vendor activity, a primary indicator of rapid fund extraction. |
| **R10** | **Shadow Payroll** | Identifies recurring payments on the same date to non-payroll entities, suggesting hidden salary-like outflows. |

---

## 📄 License

This project is distributed under the **MIT License**. This allows for free use, modification, and distribution, provided that the original developer is credited. See the `LICENSE` file for more information.

---
*Developed with ❤️ in York, UK.*
   
