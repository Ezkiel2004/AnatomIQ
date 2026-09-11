# AnatomIQ – Interactive Human Anatomy Learning System
## Capstone Project Documentation

**Project Title:** A Web-Based System for Interactive Human Anatomy Model for Science Subject  
**System Name:** AnatomIQ  
**Institution:** Lubang National High School, Lubang, Occidental Mindoro  
**Department:** Science Department  
**School Year:** 2024–2025  

---

## 📁 Project File Structure

```
Prototype2/
├── index.html                    ← Login Page (Entry Point)
│
├── assets/
│   ├── css/
│   │   ├── global.css            ← Design System (colors, tokens, utilities)
│   │   ├── login.css             ← Login Page Styles
│   │   ├── sidebar.css           ← Sidebar & Topbar Navigation
│   │   ├── teacher.css           ← Teacher Dashboard Components
│   │   └── student.css           ← Student Module & Anatomy Viewer
│   └── js/
│       ├── login.js              ← Login Auth Logic
│       ├── app.js                ← Shared Utilities (auth, charts, toasts)
│       └── anatomy.js            ← Anatomy Data & Canvas 3D Viewer
│
├── teacher/
│   ├── dashboard.html            ← Teacher Dashboard (Charts, Stats, Activity)
│   ├── modules.html              ← Module & Lesson Management
│   ├── students.html             ← Student Profiles & Records
│   ├── assessments.html          ← Quiz & Assessment Builder
│   ├── monitoring.html           ← Student Progress Monitoring
│   ├── reports.html              ← Analytics & Report Generation
│   ├── announcements.html        ← Announcement Management
│   └── media.html                ← 3D Model & Media Library
│
├── student/
│   ├── dashboard.html            ← Student Dashboard
│   ├── anatomy.html              ← 3D Interactive Anatomy Explorer
│   ├── lessons.html              ← Learning Modules (9 Body Systems)
│   ├── quiz.html                 ← Quiz & Activities (with timer)
│   ├── progress.html             ← Progress Tracking
│   ├── scores.html               ← Score History & Grades
│   └── notifications.html        ← Notifications & Announcements
│
└── database/
    ├── schema.php                ← MySQL Schema (17 tables)
    └── connection.php            ← PHP PDO Connection & Auth Class
```

---

## 🎨 Design System

### Color Palette
| Token | Hex | Usage |
|-------|-----|-------|
| `--primary-500` | `#3b82f6` | Primary Blue – Buttons, links, progress |
| `--accent-500`  | `#8b5cf6` | Purple – Secondary accent, achievements |
| `--teal-500`    | `#14b8a6` | Teal – Success, completion indicators |
| `--gold-400`    | `#fbbf24` | Gold – Rankings, warnings, highlights |
| `--red-500`     | `#ef4444` | Red – Errors, danger, urgent alerts |
| `--bg-dark`     | `#0a0f1e` | Dark background base |
| `--bg-card`     | `#0f1729` | Card/panel background |
| `--bg-elevated` | `#1a2540` | Elevated surface |

### Typography
- **Display/Headings:** Outfit (Google Fonts) – 700–900 weight
- **Body/UI:** Inter (Google Fonts) – 300–800 weight
- **Code/Monospace:** Courier New

### Design Principles
- Dark mode with glassmorphism effects
- Gradient accents and glowing card borders
- Smooth CSS transitions (0.15–0.4s ease)
- Animated SVG/Canvas components
- Responsive grid-based layout

---

## 🗄️ Database Schema (17 Tables)

```
users                     ← All system users (teacher + student)
student_profiles          ← Student-specific info (section, grade, ID)
teacher_profiles          ← Teacher-specific info (subject, department)
body_systems              ← The 9 anatomical systems
modules                   ← Learning modules per system
lessons                   ← Individual lessons (reading/video/3D/interactive)
media_files               ← Uploaded 3D models, videos, images
assessments               ← Quiz/diagram/exploration activities
questions                 ← Individual assessment questions
answer_options            ← Multiple choice answer options
student_lesson_progress   ← Per-student lesson completion tracking
assessment_submissions    ← Student quiz submissions
answer_responses          ← Individual question answers per submission
announcements             ← Teacher announcements & notifications
announcement_reads        ← Read receipts per student
system_exploration_log    ← 3D viewer session analytics
student_progress_summary  ← Cached overall progress per student
```

### Key Relationships (ERD Concept)
```
users ──< student_profiles
users ──< teacher_profiles
body_systems ──< modules ──< lessons
modules ──< assessments ──< questions ──< answer_options
users (student) ──< student_lesson_progress >── lessons
users (student) ──< assessment_submissions ──< answer_responses
users (teacher) ──< announcements ──< announcement_reads >── users
body_systems ──< system_exploration_log >── users
```

---

## 🔑 Demo Credentials

| Role | Username | Password | Portal |
|------|----------|----------|--------|
| Teacher/Admin | `admin` | `admin123` | `/teacher/dashboard.html` |
| Student | `student001` | `student123` | `/student/dashboard.html` |

---

## 📱 Pages Overview

### Login (`/index.html`)
- Role selector (Student / Teacher)
- Animated particle background with floating orbs
- Demo credentials quick-fill
- Session-based authentication (sessionStorage in prototype)
- Forgot password modal

### Teacher Module
| Page | Features |
|------|---------|
| `dashboard.html` | Stats cards, bar chart, line chart, leaderboard, activity feed, quick actions |
| `modules.html` | 9 system module cards, create/edit modals, lesson type selector |
| `students.html` | Filterable table, student profile modal, progress bars |
| `assessments.html` | Quiz/Diagram/Identification/Exploration cards, creation modal |
| `monitoring.html` | Class monitoring table, CSV export, at-risk flagging |
| `reports.html` | Score trends, distribution bar, section comparison, full class table |
| `announcements.html` | Pinned announcements, category filters, creation modal |

### Student Module
| Page | Features |
|------|---------|
| `dashboard.html` | Progress stats, continue learning cards, upcoming activities, achievements |
| `anatomy.html` | Canvas-based interactive 3D viewer, layer toggles, 9 system selector, structure info panel |
| `lessons.html` | All 9 body system module cards with filter, lesson detail modal |
| `quiz.html` | Timer, question navigation map, answer selection, result screen |
| `progress.html` | Donut chart, streak calendar, system-by-system progress bars |
| `scores.html` | Full score history, grades, teacher feedback modal |
| `notifications.html` | Categorized notifications with unread tracking |

---

## 🧬 Body Systems Covered

1. 🦴 **Skeletal System** – 206 bones, structural framework
2. 💪 **Muscular System** – 600+ muscles, movement & posture
3. 🫀 **Circulatory System** – Heart, blood, vessels
4. 🫁 **Respiratory System** – Lungs, airways, gas exchange
5. 🥗 **Digestive System** – 9m GI tract, nutrient absorption
6. 🫘 **Urinary System** – Kidneys, filtration, urine production
7. 🧠 **Nervous System** – Brain, spinal cord, 86B neurons
8. 🌸 **Reproductive System** – Sex organs, hormones, reproduction
9. ⚗️ **Endocrine System** – 9 glands, hormone regulation

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────┐
│              CLIENT BROWSER                  │
│  HTML5 + CSS3 + JavaScript (ES6+)           │
│  Canvas API (3D Anatomy Viewer)             │
│  sessionStorage (Auth)                      │
└─────────────────────┬───────────────────────┘
                      │ HTTP/HTTPS
┌─────────────────────▼───────────────────────┐
│           WEB SERVER (Apache/Nginx)          │
│           PHP 8.x (Server-Side Logic)       │
│           PDO (Database Abstraction)         │
└─────────────────────┬───────────────────────┘
                      │ PDO/MySQL
┌─────────────────────▼───────────────────────┐
│           DATABASE (MySQL / MariaDB)         │
│           17 Relational Tables              │
│           phpMyAdmin (Admin Interface)      │
└─────────────────────────────────────────────┘
```

### XAMPP/LocalServer Setup
1. Copy project folder to `htdocs/Prototype2/`
2. Import `database/schema.php` SQL to phpMyAdmin
3. Configure `database/connection.php` with your credentials
4. Access via `http://localhost/Prototype2/`

---

## ✅ ISO/IEC 25010 Quality Compliance

| Quality Characteristic | Implementation |
|------------------------|---------------|
| **Functional Suitability** | All teacher and student use cases are implemented: login, modules, quizzes, monitoring, reports, 3D exploration, notifications |
| **Performance Efficiency** | Canvas-based 3D rendering (no heavy WebGL dependencies), CSS-optimized animations, minimal DOM re-renders |
| **Compatibility** | Responsive CSS Grid, tested on desktop and mobile viewports; modern browser support (Chrome, Firefox, Edge, Safari) |
| **Usability** | Sidebar navigation, breadcrumbs, tooltips, loading states, toast notifications, role-based UI separation |
| **Reliability** | Session-based auth, form validation, error messages, fallback loading states |
| **Security** | Password hashing (bcrypt via PHP), session cookies (httponly, samesite), prepared PDO statements (SQL injection prevention), CSRF-safe forms |
| **Maintainability** | Modular CSS design system, separated JS files, shared utility class, normalized DB schema, PHPDoc comments |
| **Portability** | Standard HTML5/CSS3/JS stack, PHP + MySQL (XAMPP compatible), no proprietary dependencies |

---

## 🧊 3D Visualization

The prototype uses a **custom Canvas API-based 3D viewer** that simulates anatomy exploration:

- **Rotation:** Click-drag for Y/X axis rotation
- **Zoom:** Mouse scroll wheel
- **Auto-rotate:** Slow continuous Y-axis rotation when idle
- **System-specific overlays:** Each of the 9 systems renders unique anatomical highlights (e.g., pulsing heart for Circulatory, glowing brain for Nervous)
- **Layer toggles:** Skin, muscle, skeleton, organs, vessels, nerves

### For Production – Recommended 3D Libraries:
| Library | Use Case |
|---------|---------|
| **Three.js** | Load GLTF/OBJ 3D anatomy models with orbit controls |
| **Babylon.js** | Full 3D engine with mesh highlighting and annotations |
| **Zygote Body** | Commercial-grade human body visualization API |
| **BioDigital Human** | Pre-built interactive anatomy platform (embed API) |

```javascript
// Example Three.js integration:
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

const loader = new GLTFLoader();
loader.load('models/skeletal_system.glb', (gltf) => {
  scene.add(gltf.scene);
  // Enable part highlighting on click
});
```

---

## 📊 Assessment Types

| Type | Description | Implementation |
|------|-------------|---------------|
| **Multiple Choice Quiz** | 4 options, timer, question navigation map, instant grading | ✅ Fully implemented |
| **Identification** | Type-in the name of anatomical parts | 🔧 Scaffold ready |
| **Labeled Diagram** | Drag-and-drop labels onto anatomy images | 🔧 Scaffold ready |
| **Guided Exploration** | Checkpoint-based 3D model navigation activity | 🔧 Scaffold ready |

---

## 🚀 Setup Instructions

### Prerequisites
- XAMPP (or LAMP/WAMP) with PHP 8.0+ and MySQL 5.7+
- VS Code (recommended)
- Modern web browser

### Steps
1. Clone/copy project to `C:/xampp/htdocs/Prototype2/`
2. Start Apache and MySQL in XAMPP Control Panel
3. Open `phpMyAdmin` → Create database `anatomiq_db`
4. Import the SQL from `database/schema.php`
5. Edit `database/connection.php`:
   ```php
   define('DB_USER', 'root');    // your MySQL username
   define('DB_PASS', '');        // your MySQL password
   ```
6. Open browser → `http://localhost/Prototype2/index.html`
7. Login with demo credentials (see table above)

> **Note:** The current prototype uses `sessionStorage` for authentication simulation. For production deployment with PHP backend, replace `assets/js/login.js` with PHP session-based auth using `database/connection.php`.

---

*AnatomIQ – Empowering Science Education through Interactive Anatomy*  
*Science Department | Lubang National High School | Occidental Mindoro | 2025*
