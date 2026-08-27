# 🎓 CampusFlow – Digital Leave Approval Portal

> **Cloud-Native Digital Leave Management System** with Multilingual Parent Text-to-Speech (TTS), SMS OTP Authorization, Automated Approval Hierarchy, Smart Hostel Branching, and Scannable QR Code Outpass Verification.

---

## 🌐 Official Live Production Deployment

👉 **Live Portal URL:** [https://campus-flow1-ncbx.vercel.app/](https://campus-flow1-ncbx.vercel.app/)  
👉 **Live Security Gate Scanner:** [https://campus-flow1-ncbx.vercel.app/security_gate.html](https://campus-flow1-ncbx.vercel.app/security_gate.html)  
👉 **Live Sign In:** [https://campus-flow1-ncbx.vercel.app/login.html](https://campus-flow1-ncbx.vercel.app/login.html)  
👉 **GitHub Repository:** [https://github.com/divi76180/CampusFlow1](https://github.com/divi76180/CampusFlow1)

---

## ⚡ Live Cloud Database Integration

CampusFlow connects directly to **Supabase PostgreSQL**:
* **Supabase Project URL:** `https://rnwegrpgmkgfkahguaeu.supabase.co`
* **Tables Live in Cloud:** `users`, `students`, `parents`, `faculty`, `leave_requests`, `approvals`, `voice_samples`, `voice_verifications`, `notifications`.

---

## 🏛️ System Roles & Demo Credentials

> **Universal Password for all demo accounts:** `password123`

| Role | Account Name | Login ID | Password | Portal Features |
|---|---|---|---|---|
| 🎓 **Student (Hosteller)** | Rahul Sharma | `21CS101` | `password123` | Applies for leave; routes to Warden; generates **Digital QR Outpass** |
| 🎓 **Student (Day Scholar)** | Priya Patel | `21CS102` | `password123` | Direct completion after HOD approval |
| 👨‍👩‍👧 **Parent (Tamil TTS)** | Saranya | `9003497761` | `password123` | Tamil TTS letter playback; **SMS OTP Approval** |
| 👨‍👩‍👧 **Parent (Tamil TTS)** | Suresh Sharma | `9876543210` | `password123` | Tamil TTS letter playback; SMS OTP Approval |
| 👨‍👩‍👧 **Parent (English TTS)** | Ramesh Patel | `9876543220` | `password123` | English TTS letter playback; SMS OTP Approval |
| 📋 **Class Advisor** | Dr. Ramanathan | `FAC-CS-01` | `password123` | Reviews attendance & parent OTP verification; forwards to HOD |
| 🏛️ **HOD** | Dr. Meenakshi | `HOD-CSE-01` | `password123` | Department sanction; smart hostel branching (Hosteller $\to$ Warden) |
| 🏢 **Hostel Warden** | Col. Balaji | `WARDEN-BH-01` | `password123` | Issues gate clearance; generates **Scannable QR Gate Passes** |
| 🛡️ **Gate Security** | Main Gate | *Public* | *None* | Scans QR code & verifies clearance status |

---

## 📂 Project Structure

```
d:/Letter approval/
├── index.html                   # Landing page
├── login.html                   # Unified login with 1-click test pills
├── signup.html                  # Multi-role registration (Student/Parent/Faculty)
├── security_gate.html           # Main Campus Gate QR Scanner
├── student/
│   └── dashboard.html           # Student leave applications & QR Outpass
├── parent/
│   └── dashboard.html           # Parent portal with TTS & Voice Biometrics
├── advisor/
│   └── dashboard.html           # Class Advisor review portal
├── hod/
│   └── dashboard.html           # HOD authorization portal
├── warden/
│   └── dashboard.html           # Warden gate clearance portal
├── assets/
│   ├── css/
│   │   ├── main.css             # Design tokens & core typography
│   │   ├── dashboard.css        # Dashboard layouts & outpass styles
│   │   └── auth.css             # Auth cards & tabs
│   └── js/
│       ├── supabase_client.js   # Supabase JS SDK client & data layer
│       ├── voice_verify.js      # Web Audio API voice biometrics & visualizer
│       ├── tts.js               # Web Speech API multilingual audio synthesizer
│       ├── qrcode.js            # Dynamic QR code generator
│       ├── auth.js              # Auth forms & role switcher
│       └── dashboard.js         # Modal controllers & letterhead renderers
├── database/
│   └── supabase_schema.sql      # Supabase PostgreSQL DDL & Seed Data
├── vercel.json                  # Vercel routing & security headers
├── package.json                 # Project scripts & dependencies
└── .gitignore                   # Ignored files
```

---

## 📜 License
MIT License &copy; 2026 CampusFlow.
