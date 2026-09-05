<?php
/**
 * AI Package Inspector - Centralized Landing Page & System Portal
 * File: index.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$isLoggedIn = is_logged_in();
$user = get_logged_user();
$dashboardUrl = ($user && strtolower($user['role'] ?? '') === 'officer') ? 'pages/officer-dashboard.php' : 'pages/inspector-dashboard.php';

$db = getDB();

// Fetch Live Database Stats from MySQL for Hero Summary
$totalCompanies = 0;
$totalInspections = 0;
$compliantCount = 0;
$nonCompliantCount = 0;
$openInquiries = 0;

try {
    $cStmt = $db->query("SELECT COUNT(*) FROM companies");
    $totalCompanies = intval($cStmt->fetchColumn());

    $iStmt = $db->query("SELECT COUNT(*) FROM inspections");
    $totalInspections = intval($iStmt->fetchColumn());

    $statStmt = $db->query("
        SELECT 
            SUM(CASE WHEN overall_status = 'COMPLIANT' THEN 1 ELSE 0 END) AS compliant,
            SUM(CASE WHEN overall_status = 'NON_COMPLIANT' THEN 1 ELSE 0 END) AS non_compliant
        FROM inspections
    ");
    $stats = $statStmt->fetch();
    $compliantCount = intval($stats['compliant'] ?? 0);
    $nonCompliantCount = intval($stats['non_compliant'] ?? 0);

    $inqStmt = $db->query("SELECT COUNT(*) FROM inquiries");
    $openInquiries = intval($inqStmt->fetchColumn());
} catch (Exception $e) {}

$complianceRate = ($totalInspections > 0) ? round(($compliantCount / $totalInspections) * 100, 1) : 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI Package Inspector - Legal Metrology Platform</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/responsive.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary-dark: #0f172a;
      --primary: #1e3a8a;
      --primary-light: #eff6ff;
      --primary-border: #bfdbfe;
      --accent: #0284c7;
      --success: #16a34a;
      --danger: #dc2626;
      --warning: #d97706;
      --surface: #ffffff;
      --background: #f8fafc;
      --border: #e2e8f0;
      --text: #1e293b;
      --text-muted: #64748b;
      --radius-sm: 6px;
      --radius: 10px;
      --radius-lg: 16px;
      --radius-full: 9999px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
      --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.08);
      --transition: all 0.25s ease-in-out;
    }

    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      color: var(--text);
      background-color: var(--background);
      margin: 0;
      padding: 0;
    }

    .landing-header {
      position: sticky;
      top: 0;
      z-index: 100;
      background-color: var(--primary-dark);
      border-bottom: 1px solid rgba(255,255,255,.1);
      padding: 16px 40px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    .landing-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      color: #fff;
      font-weight: 800;
      font-size: 1.25rem;
      text-decoration: none;
    }
    .landing-logo img { height: 42px; }
    .landing-nav {
      display: flex;
      align-items: center;
      gap: 28px;
    }
    .landing-nav a {
      color: #cbd5e1;
      font-weight: 600;
      font-size: .92rem;
      text-decoration: none;
      transition: var(--transition);
    }
    .landing-nav a:hover { color: #ffffff; }
    .landing-cta-group {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .hero-section {
      background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #172554 100%);
      color: #fff;
      padding: 80px 40px;
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 50px;
      align-items: center;
      min-height: 78vh;
      position: relative;
      overflow: hidden;
    }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255,255,255,.12);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,.2);
      padding: 6px 16px;
      border-radius: var(--radius-full);
      font-size: .85rem;
      font-weight: 700;
      color: #60a5fa;
      margin-bottom: 20px;
    }
    .hero-headline {
      font-size: 2.9rem;
      font-weight: 800;
      line-height: 1.15;
      margin-bottom: 20px;
      letter-spacing: -1px;
    }
    .hero-description {
      font-size: 1.1rem;
      color: #94a3b8;
      line-height: 1.6;
      margin-bottom: 32px;
      max-width: 580px;
    }
    .hero-btns {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }

    .hero-mockup-card {
      background: rgba(255,255,255,.08);
      backdrop-filter: blur(14px);
      border: 1px solid rgba(255,255,255,.2);
      border-radius: var(--radius-lg);
      padding: 24px;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,.5);
      position: relative;
    }
    .mockup-scanner-window {
      position: relative;
      height: 260px;
      background: #000;
      border-radius: var(--radius);
      overflow: hidden;
      border: 2px solid #38bdf8;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .mockup-scanner-window img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: .88;
    }
    .mockup-laser {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: #10b981;
      box-shadow: 0 0 15px #10b981, 0 0 25px #10b981;
      animation: heroLaser 2.2s ease-in-out infinite alternate;
    }
    @keyframes heroLaser {
      0% { top: 5%; }
      100% { top: 92%; }
    }

    /* Stats Banner Bar */
    .stats-summary-bar {
      max-width: 1140px;
      margin: -50px auto 0 auto;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      position: relative;
      z-index: 10;
      padding: 0 20px;
    }
    .stat-summary-card {
      background: #ffffff;
      border-radius: var(--radius);
      padding: 24px;
      box-shadow: var(--shadow-md);
      border: 1px solid var(--border);
      text-align: center;
    }
    .stat-summary-val {
      font-size: 2.2rem;
      font-weight: 800;
      color: var(--primary);
      line-height: 1;
      margin-bottom: 4px;
    }
    .stat-summary-lbl {
      font-size: 0.8rem;
      color: var(--text-muted);
      font-weight: 700;
      text-transform: uppercase;
    }

    .section-padding { padding: 90px 40px; max-width: 1200px; margin: 0 auto; }
    .section-header-center {
      text-align: center;
      max-width: 720px;
      margin: 0 auto 50px;
    }
    .section-tag {
      font-size: .82rem;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: var(--primary);
      font-weight: 800;
      margin-bottom: 8px;
    }
    .section-title {
      font-size: 2.3rem;
      font-weight: 800;
      color: var(--primary-dark);
      letter-spacing: -.5px;
    }
    .section-sub {
      font-size: 1.02rem;
      color: var(--text-muted);
      margin-top: 10px;
      line-height: 1.6;
    }

    .problem-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
    }
    .problem-card {
      background: var(--surface);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      padding: 32px;
      box-shadow: var(--shadow-sm);
    }
    .problem-list {
      list-style: none;
      margin-top: 18px;
      display: flex;
      flex-direction: column;
      gap: 14px;
      padding: 0;
    }
    .problem-list li {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: .96rem;
      color: var(--text);
    }

    .workflow-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 32px 24px;
      box-shadow: var(--shadow-sm);
      flex-wrap: wrap;
    }
    .workflow-step-box {
      text-align: center;
      flex: 1;
      min-width: 120px;
    }
    .workflow-step-num {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: var(--primary-light);
      color: var(--primary);
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 12px;
      font-size: 1.05rem;
    }
    .workflow-arrow {
      color: var(--text-muted);
      font-size: 1.2rem;
      font-weight: 800;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 24px;
    }
    .feature-card {
      background: var(--surface);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      padding: 26px 24px;
      transition: var(--transition);
    }
    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-md);
      border-color: var(--primary-border);
    }
    .feature-icon-box {
      width: 52px;
      height: 52px;
      border-radius: var(--radius-sm);
      background: var(--primary-light);
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 18px;
    }

    .landing-footer {
      background: var(--primary-dark);
      color: #94a3b8;
      padding: 60px 40px 30px;
      border-top: 1px solid rgba(255,255,255,.1);
    }

    @media (max-width: 992px) {
      .hero-section {
        grid-template-columns: 1fr;
        text-align: center;
      }
      .hero-description {
        margin-left: auto;
        margin-right: auto;
      }
      .hero-btns { justify-content: center; }
      .problem-grid,
      .features-grid { grid-template-columns: repeat(2,1fr); }
      .workflow-bar {
        flex-direction: column;
        text-align: center;
      }
      .stats-summary-bar { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 640px) {
      .problem-grid,
      .features-grid,
      .stats-summary-bar { grid-template-columns: 1fr; }
      .landing-nav { display: none; }
      .landing-header { padding: 14px 20px; }
      .hero-section,
      .section-padding { padding-left: 20px; padding-right: 20px; }
      .hero-headline { font-size: 2.1rem; }
    }
  </style>
</head>

<body>
  <!-- Header Navbar -->
  <header class="landing-header">
    <a href="index.php" class="landing-logo">
      <img src="assets/logo.svg" alt="AI Package Inspector Logo">
      <span>AI Package Inspector</span>
    </a>

    <nav class="landing-nav">
      <a href="#home">Home</a>
      <a href="#problem">The Problem</a>
      <a href="#solution">Solution</a>
      <a href="#features">Features</a>
      <a href="#how-it-works">How It Works</a>
    </nav>

    <div class="landing-cta-group">
      <?php if ($isLoggedIn): ?>
        <a href="<?php echo $dashboardUrl; ?>" class="btn btn-primary">
          <i class="fas fa-tachometer-alt"></i> Control Dashboard (<?php echo htmlspecialchars($user['name']); ?>)
        </a>
      <?php else: ?>
        <a href="auth/login.php" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.3)"><i class="fas fa-sign-in-alt"></i> Sign In</a>
        <a href="auth/register-officer.php" class="btn btn-primary"><i class="fas fa-user-shield"></i> Officer Register</a>
        <a href="auth/register-inspector.php" class="btn btn-secondary" style="background:#0d9488;color:#fff;border:none;padding:10px 16px;border-radius:6px;font-weight:600;"><i class="fas fa-search"></i> Inspector Register</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- Hero Section -->
  <section id="home" class="hero-section">
    <div>
      <div class="hero-badge">
        <span>🏛️</span>
        <span>Legal Metrology Enforcement System (LM Act 2009)</span>
      </div>

      <h1 class="hero-headline">
        AI-Powered Package Inspection for Smarter Legal Metrology Enforcement
      </h1>

      <p class="hero-description">
        A centralized full-stack digital inspection platform connecting Chief Officers and Field Inspectors. Verify packaged commodities, run WebRTC camera OCR scans, drill down company inspection dates, and generate compliance reports.
      </p>

      <div class="hero-btns">
        <?php if ($isLoggedIn): ?>
          <a href="<?php echo $dashboardUrl; ?>" class="btn btn-primary btn-lg"><i class="fas fa-tachometer-alt"></i> Access Dashboard</a>
          <a href="pages/new-inspection.php" class="btn btn-secondary btn-lg" style="background:#0d9488;border:none;"><i class="fas fa-camera"></i> Launch Camera OCR Scan</a>
        <?php else: ?>
          <a href="auth/register-officer.php" class="btn btn-primary btn-lg"><i class="fas fa-user-shield"></i> Register as Officer</a>
          <a href="auth/register-inspector.php" class="btn btn-secondary btn-lg" style="background:#0d9488;border:none;"><i class="fas fa-search"></i> Register as Inspector</a>
          <a href="auth/login.php" class="btn btn-outline btn-lg" style="color:#fff;border-color:rgba(255,255,255,.4)"><i class="fas fa-sign-in-alt"></i> Sign In</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="hero-mockup-card">
      <div class="mockup-scanner-window">
        <img src="food.png" alt="Packaged Commodity Inspection Preview">
        <div class="mockup-laser"></div>
      </div>

      <div style="display:flex;align-items:center;justify-content:space-between;color:#fff;gap:16px">
        <div>
          <div style="font-weight:700;font-size:.95rem"><i class="fas fa-camera"></i> Live WebRTC AI Inspection Scanner</div>
          <div style="font-size:.8rem;color:#94a3b8">Scanning Rule 6 Declarations in Real-Time...</div>
        </div>
        <span class="badge badge-success" style="background:#16a34a;color:#fff;padding:6px 12px;border-radius:20px;font-size:0.82rem;font-weight:700;">✓ 100% Scorecard</span>
      </div>
    </div>
  </section>

  <!-- Live MySQL Stats Summary Bar -->
  <div class="stats-summary-bar">
    <div class="stat-summary-card">
      <div class="stat-summary-val"><?php echo number_format($totalCompanies); ?></div>
      <div class="stat-summary-lbl">Companies Tracked</div>
    </div>
    <div class="stat-summary-card">
      <div class="stat-summary-val"><?php echo number_format($totalInspections); ?></div>
      <div class="stat-summary-lbl">Total Package Scans</div>
    </div>
    <div class="stat-summary-card">
      <div class="stat-summary-val" style="color: #16a34a;"><?php echo $complianceRate; ?>%</div>
      <div class="stat-summary-lbl">Overall Compliance Index</div>
    </div>
    <div class="stat-summary-card">
      <div class="stat-summary-val" style="color: #dc2626;"><?php echo number_format($nonCompliantCount); ?></div>
      <div class="stat-summary-lbl">Violations Detected</div>
    </div>
  </div>



  <!-- Problem Section -->
  <section id="problem" class="section-padding" style="background-color:var(--background)">
    <div class="section-header-center">
      <div class="section-tag">Challenges in Enforcement</div>
      <h2 class="section-title">The Problem</h2>
      <p class="section-sub">
        Under the Legal Metrology (Packaged Commodities) Rules 2011,
        every retail packaged commodity must carry 6 mandatory declarations.
      </p>
    </div>

    <div class="problem-grid">
      <div class="problem-card">
        <h3 style="color:var(--primary-dark);font-size:1.2rem;font-weight:700;margin-bottom:12px">
          Mandatory Packaging Declarations
        </h3>
        <ul class="problem-list">
          <li><span style="color:var(--primary)">•</span> Manufacturer / Packer / Importer Name &amp; Postal Address</li>
          <li><span style="color:var(--primary)">•</span> Generic / Common Product Name</li>
          <li><span style="color:var(--primary)">•</span> Net Quantity in standard units of weight or measure</li>
          <li><span style="color:var(--primary)">•</span> Maximum Retail Price (MRP) incl. of all taxes</li>
          <li><span style="color:var(--primary)">•</span> Month &amp; Year of Manufacture / Packing / Import</li>
          <li><span style="color:var(--primary)">•</span> Consumer Care Cell Phone Number &amp; Email Address</li>
        </ul>
      </div>

      <div class="problem-card">
        <h3 style="color:var(--danger);font-size:1.2rem;font-weight:700;margin-bottom:12px">
          Manual Inspection Limitations
        </h3>
        <ul class="problem-list">
          <li><span style="color:var(--danger)">✕</span> Time-consuming manual checking of fine font sizes</li>
          <li><span style="color:var(--danger)">✕</span> Human error in identifying missing email or address lines</li>
          <li><span style="color:var(--danger)">✕</span> Inconsistent paper-based inspection records across regions</li>
          <li><span style="color:var(--danger)">✕</span> Difficulty tracking historical violations for repeat offenders</li>
          <li><span style="color:var(--danger)">✕</span> Delayed central report generation for ministry submission</li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Solution Section -->
  <section id="solution" class="section-padding" style="background-color:var(--surface)">
    <div class="section-header-center">
      <div class="section-tag">Modern Digital Solution</div>
      <h2 class="section-title">Our Solution</h2>
      <p class="section-sub">
        An end-to-end digital workflow designed for Legal Metrology officers
        to streamline package inspections.
      </p>
    </div>

    <div class="workflow-bar">
      <div class="workflow-step-box"><div class="workflow-step-num">1</div><div style="font-weight:700;font-size:.9rem">Capture Package</div></div>
      <div class="workflow-arrow">→</div>
      <div class="workflow-step-box"><div class="workflow-step-num">2</div><div style="font-weight:700;font-size:.9rem">Extract Information</div></div>
      <div class="workflow-arrow">→</div>
      <div class="workflow-step-box"><div class="workflow-step-num">3</div><div style="font-weight:700;font-size:.9rem">Verify Declarations</div></div>
      <div class="workflow-arrow">→</div>
      <div class="workflow-step-box"><div class="workflow-step-num">4</div><div style="font-weight:700;font-size:.9rem">Identify Violations</div></div>
      <div class="workflow-arrow">→</div>
      <div class="workflow-step-box"><div class="workflow-step-num">5</div><div style="font-weight:700;font-size:.9rem">Generate Result</div></div>
      <div class="workflow-arrow">→</div>
      <div class="workflow-step-box"><div class="workflow-step-num">6</div><div style="font-weight:700;font-size:.9rem">Store &amp; Report</div></div>
    </div>
  </section>

  <!-- Key Features Grid Section -->
  <section id="features" class="section-padding" style="background-color:var(--background)">
    <div class="section-header-center">
      <div class="section-tag">Platform Capabilities</div>
      <h2 class="section-title">Key Features</h2>
      <p class="section-sub">
        Equipping Legal Metrology Officers and Inspectors with tools for rapid compliance evaluation.
      </p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-camera"></i></div>
        <h4 style="font-size:1.05rem;font-weight:700;color:var(--primary-dark);margin-bottom:8px">Package Scanning</h4>
        <p class="text-muted" style="font-size:.88rem">WebRTC camera photo capture or file upload with live preview and quality pre-checks.</p>
        </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-check-circle"></i></div>
        <h4 style="font-size:1.05rem;font-weight:700;color:var(--primary-dark);margin-bottom:8px">Declaration Verification</h4>
        <p class="text-muted" style="font-size:.88rem">Automated checking of Manufacturer, Net Qty, MRP, Mfg Date, and Consumer Care details.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-exclamation-triangle"></i></div>
        <h4 style="font-size:1.05rem;font-weight:700;color:var(--primary-dark);margin-bottom:8px">Violation Detection</h4>
        <p class="text-muted" style="font-size:.88rem">Flags missing declarations with rule citations and severity levels (High, Medium, Low).</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-users-cog"></i></div>
        <h4 style="font-size:1.05rem;font-weight:700;color:var(--primary-dark);margin-bottom:8px">Inspector Management</h4>
        <p class="text-muted" style="font-size:.88rem">Officer dashboard for onboarding inspectors, tracking active status, and monitoring scans.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-building"></i></div>
        <h4 style="font-size:1.05rem;font-weight:700;color:var(--primary-dark);margin-bottom:8px">Company Inspection Dates</h4>
        <p class="text-muted" style="font-size:.88rem">Expandable company history drilldown (COMPANY ➔ DATES ➔ FULL DETAILS) loaded from MySQL.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-chart-pie"></i></div>
        <h4 style="font-size:1.05rem;font-weight:700;color:var(--primary-dark);margin-bottom:8px">Analytics &amp; Reports</h4>
        <p class="text-muted" style="font-size:.88rem">Top violator manufacturers, statutory rule violation charts, print view, and dataset export.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-boxes"></i></div>
        <h4 style="font-size:1.05rem;font-weight:700;color:var(--primary-dark);margin-bottom:8px">Product Database</h4>
        <p class="text-muted" style="font-size:.88rem">Catalog of inspected packaged commodities with historical violation timelines per brand.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-gavel"></i></div>
        <h4 style="font-size:1.05rem;font-weight:700;color:var(--primary-dark);margin-bottom:8px">Inquiry Management</h4>
        <p class="text-muted" style="font-size:.88rem">Issue statutory show-cause notices, track manufacturer replies, and manage dispute cases.</p>
      </div>
    </div>
  </section>

  <!-- How It Works Step-by-Step -->
  <section id="how-it-works" class="section-padding" style="background-color:var(--surface)">
    <div class="section-header-center">
      <div class="section-tag">Inspection Step-by-Step</div>
      <h2 class="section-title">How It Works</h2>
    </div>

    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;text-align:center">
      <div class="card"><div style="font-size:2rem;font-weight:800;color:var(--primary)">01</div><h4 style="font-weight:700;margin:8px 0">Capture</h4><p class="text-muted" style="font-size:.82rem">Upload packaging image at retail location or snap photo via WebRTC camera.</p></div>
      <div class="card"><div style="font-size:2rem;font-weight:800;color:var(--primary)">02</div><h4 style="font-weight:700;margin:8px 0">Extract</h4><p class="text-muted" style="font-size:.82rem">AI OCR engine parses raw text into structured JSON fields.</p></div>
      <div class="card"><div style="font-size:2rem;font-weight:800;color:var(--primary)">03</div><h4 style="font-weight:700;margin:8px 0">Verify</h4><p class="text-muted" style="font-size:.82rem">Cross-check Legal Metrology Packaged Commodities Rules 2011.</p></div>
      <div class="card"><div style="font-size:2rem;font-weight:800;color:var(--primary)">04</div><h4 style="font-weight:700;margin:8px 0">Review</h4><p class="text-muted" style="font-size:.82rem">Officer reviews extracted fields and corrects low-confidence values.</p></div>
      <div class="card"><div style="font-size:2rem;font-weight:800;color:var(--primary)">05</div><h4 style="font-weight:700;margin:8px 0">Report</h4><p class="text-muted" style="font-size:.82rem">Generate compliance report or issue show-cause legal notice in MySQL.</p></div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="landing-footer">
    <div style="max-width:1100px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px">
      <div>
        <a href="index.php" class="landing-logo">
          <img src="assets/logo.svg" alt="AI Package Inspector Logo">
          <span>AI Package Inspector</span>
        </a>
        <p style="font-size:.82rem;margin-top:8px;color:#94a3b8">
          Department of Legal Metrology Enforcement Platform
        </p>
      </div>
      <div style="font-size:.85rem;color:#94a3b8">
        &copy; <?php echo date('Y'); ?> AI Package Inspector. All rights reserved. (Legal Metrology Govt Platform)
      </div>
    </div>
  </footer>

</body>
</html>
