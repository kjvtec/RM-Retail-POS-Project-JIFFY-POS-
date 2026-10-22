<?php
/**
 * Admin Control Panel Dashboard (Version 2.0)
 * Nigiwai Project Management
 * Supports Multiple Projects, Assignees, Drag & Drop sorting, and Color Presets
 */

require_once '../config.php';

// บังคับให้ผู้ใช้งานล็อกอินเข้าสู่ระบบแอดมินก่อน
require_admin_login();

// จัดการการออกจากระบบ
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>แผงควบคุมระบบ - Nigiwai Project Management</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --ink: #1e293b;
      --muted: #64748b;
      --line: #e2e8f0;
      --bg: #f8fafc;
      --panel: #ffffff;
      --primary: #5146b6;
      --primary-hover: #3e32a0;
      --danger: #ef4444;
      --danger-hover: #dc2626;
      --success: #22c55e;
      --shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.08);
      --border-radius: 12px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: "Noto Sans Thai", sans-serif;
      background-color: var(--bg);
      color: var(--ink);
      line-height: 1.5;
    }

    /* Layout structure */
    .header {
      background-color: var(--panel);
      border-bottom: 1px solid var(--line);
      padding: 16px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .header-title h1 {
      font-size: 20px;
      font-weight: 800;
      color: var(--primary);
    }

    .header-title p {
      font-size: 12px;
      color: var(--muted);
    }

    .header-user {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .user-info {
      text-align: right;
    }

    .user-name {
      font-size: 14px;
      font-weight: 700;
    }

    .user-role {
      font-size: 11px;
      color: var(--muted);
      font-weight: 600;
      text-transform: uppercase;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 13.5px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      border: none;
    }

    .btn-primary {
      background-color: var(--primary);
      color: white;
    }

    .btn-primary:hover {
      background-color: var(--primary-hover);
      transform: translateY(-1px);
    }

    .btn-danger {
      background-color: #fee2e2;
      color: var(--danger);
    }

    .btn-danger:hover {
      background-color: var(--danger);
      color: white;
    }

    .btn-secondary {
      background-color: #e2e8f0;
      color: #334155;
    }

    .btn-secondary:hover {
      background-color: #cbd5e1;
    }

    .btn-outline {
      border: 1px solid var(--line);
      background-color: transparent;
      color: var(--ink);
    }

    .btn-outline:hover {
      background-color: #f1f5f9;
      border-color: #cbd5e1;
    }

    .container {
      max-width: 1200px;
      margin: 30px auto;
      padding: 0 20px;
    }

    /* Alert and toast notification */
    .toast-container {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 1000;
      display: grid;
      gap: 10px;
    }

    .toast {
      background: white;
      border-left: 4px solid var(--primary);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      padding: 12px 20px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 700;
      color: var(--ink);
      display: flex;
      align-items: center;
      gap: 12px;
      transform: translateY(20px);
      opacity: 0;
      animation: toastIn 0.3s forwards;
    }

    .toast.success { border-left-color: var(--success); }
    .toast.error { border-left-color: var(--danger); }

    @keyframes toastIn {
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    /* Control Toolbar */
    .toolbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      background: var(--panel);
      padding: 18px 24px;
      border: 1px solid var(--line);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      flex-wrap: wrap;
      gap: 16px;
    }

    .project-control-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .project-control-wrap select {
      padding: 8px 14px;
      font-size: 14px;
      font-weight: 700;
      width: 260px;
    }

    .toolbar h2 {
      font-size: 22px;
      font-weight: 800;
    }

    /* Stage card list styling */
    .stage-card {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      margin-bottom: 20px;
      overflow: hidden;
    }

    .stage-header {
      padding: 16px 20px;
      background: #f8fafc;
      border-bottom: 1px solid var(--line);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }

    .stage-meta {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 6px;
      min-width: 0;
      flex-grow: 1;
    }

    .color-swatch {
      width: 14px;
      height: 14px;
      border-radius: 4px;
      flex-shrink: 0;
      border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .stage-title {
      font-size: 16px;
      font-weight: 800;
      color: var(--ink);
    }

    .stage-dates {
      font-size: 12px;
      font-weight: 700;
      color: #475569;
      background: #f1f5f9;
      border: 1px solid var(--line);
      padding: 3px 8px;
      border-radius: 6px;
      white-space: nowrap;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .stage-actions {
      display: flex;
      gap: 8px;
    }

    .task-list {
      padding: 10px 20px;
      min-height: 40px;
    }

    .task-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 14px;
      border-bottom: 1px solid #f1f5f9;
      gap: 16px;
      background-color: var(--panel);
      transition: background-color 0.15s;
    }

    .task-item:last-child {
      border-bottom: none;
    }

    .task-item.dragging {
      opacity: 0.4;
      background-color: #f1f5f9;
      border: 1px dashed var(--line);
    }

    .task-meta {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 6px;
      min-width: 0;
      flex-grow: 1;
    }

    .task-title {
      font-size: 14px;
      font-weight: 600;
      color: #334155;
    }

    .lane-badge {
      font-size: 11px;
      font-weight: 800;
      background: #e0f2fe;
      color: #0369a1;
      border: 1px solid #bae6fd;
      padding: 2px 6px;
      border-radius: 6px;
      text-transform: uppercase;
      white-space: nowrap;
      display: inline-flex;
      align-items: center;
    }

    .drag-handle {
      cursor: grab;
      color: var(--muted);
      font-size: 18px;
      margin-right: 4px;
      user-select: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .drag-handle:active {
      cursor: grabbing;
    }

    /* Modal Form styling */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(15, 23, 42, 0.5);
      backdrop-filter: blur(4px);
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.25s;
    }

    .modal-overlay.active {
      opacity: 1;
      pointer-events: auto;
    }

    .modal {
      background: var(--panel);
      width: 100%;
      max-width: 500px;
      border-radius: 16px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
      overflow: hidden;
      transform: translateY(20px);
      transition: transform 0.25s;
    }

    .modal-overlay.active .modal {
      transform: translateY(0);
    }

    .modal-header {
      padding: 18px 24px;
      border-bottom: 1px solid var(--line);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-header h3 {
      font-size: 17px;
      font-weight: 800;
    }

    .modal-close {
      cursor: pointer;
      background: transparent;
      border: none;
      font-size: 20px;
      color: var(--muted);
      line-height: 1;
    }

    .modal-body {
      padding: 24px;
      max-height: 70vh;
      overflow-y: auto;
    }

    .form-group {
      margin-bottom: 18px;
    }

    .form-group label {
      display: block;
      margin-bottom: 6px;
      font-size: 13px;
      font-weight: 700;
      color: #475569;
    }

    .form-control {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--line);
      border-radius: 8px;
      font-size: 14px;
      font-family: inherit;
      color: var(--ink);
      transition: border-color 0.2s;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary);
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .modal-footer {
      padding: 16px 24px;
      border-top: 1px solid var(--line);
      background: #f8fafc;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .empty-state {
      padding: 40px;
      text-align: center;
      color: var(--muted);
      background: white;
      border: 1px dashed var(--line);
      border-radius: var(--border-radius);
      font-weight: 600;
    }

    /* Project Manager Table styling */
    .project-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
      margin-bottom: 20px;
    }

    .project-table th, .project-table td {
      padding: 8px 12px;
      border-bottom: 1px solid var(--line);
      text-align: left;
    }

    .project-table th {
      background: #f8fafc;
      font-weight: 700;
    }
    .assignee-checkbox-container {
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: 8px 12px;
      max-height: 150px;
      overflow-y: auto;
      background: var(--panel);
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .assignee-checkbox-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 6px 0;
      font-size: 13.5px;
      cursor: pointer;
      font-weight: 500 !important;
      color: #334155;
    }
    .assignee-checkbox-item input[type="checkbox"] {
      cursor: pointer;
      width: 16px;
      height: 16px;
    }
    .assignee-checkbox-item:not(:last-child) {
      border-bottom: 1px solid #f1f5f9;
    }

    /* Project Selector Modal Cards */
    .project-select-card {
      background: #ffffff;
      border: 2px solid var(--line);
      border-radius: var(--border-radius);
      padding: 16px;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
      display: flex;
      flex-direction: column;
      gap: 10px;
      position: relative;
    }

    .project-select-card:hover {
      border-color: var(--primary);
      box-shadow: 0 4px 12px rgba(81, 70, 182, 0.08);
      transform: translateY(-2px);
    }

    .project-select-card.active-project {
      border-color: var(--primary);
      background-color: #f5f3ff;
    }

    .project-select-card .card-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
    }

    .project-select-card .project-title {
      font-size: 15px;
      font-weight: 800;
      color: #1e1b4b;
    }

    .project-select-card .project-assignees {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
    }

    .project-select-card .project-meta-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 4px;
    }

    .project-select-card .progress-container {
      width: 100%;
      background-color: #e2e8f0;
      border-radius: 99px;
      height: 8px;
      overflow: hidden;
      margin-top: 4px;
    }

    .project-select-card .progress-bar {
      height: 100%;
      background-color: var(--success);
      border-radius: 99px;
      transition: width 0.3s ease;
    }

    .project-select-card .percentage-text {
      font-size: 12px;
      font-weight: 800;
      color: var(--muted);
    }

    .project-dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 16px;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
      .header {
        flex-direction: column;
        padding: 16px 20px;
        gap: 12px;
        text-align: center;
      }
      .header-user {
        flex-direction: column;
        width: 100%;
        gap: 8px;
      }
      .user-info {
        text-align: center;
        display: none; /* Hide admin name on very narrow views to save vertical space if needed, or stack */
      }
      .toolbar {
        flex-direction: column;
        align-items: stretch;
        padding: 16px;
      }
      .project-control-wrap {
        flex-direction: column;
        align-items: stretch;
      }
      .project-control-wrap select {
        width: 100% !important;
      }
      .project-control-wrap button {
        width: 100%;
      }
      .stage-control-wrap {
        width: 100%;
      }
      .stage-control-wrap button {
        width: 100%;
      }
      .stage-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
        padding: 16px;
      }
      .stage-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
      }
      .stage-actions {
        width: 100%;
        justify-content: flex-end;
      }
      .task-item {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
        padding: 16px;
      }
      .task-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
      }
      .task-item .stage-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        width: 100%;
      }
      .project-table {
        display: block;
        overflow-x: auto;
      }
      .modal {
        width: 90%;
        max-height: 90vh;
      }
    }

    /* Stuck/Delay Alert styling */
    .task-item.stuck-alert {
      border-left: 4px solid var(--danger) !important;
      background-color: #fff5f5 !important;
      animation: stuckPulse 2s infinite alternate;
    }
    
    .stage-card.stuck-alert {
      border: 2px solid var(--danger) !important;
      box-shadow: 0 0 15px rgba(239, 68, 68, 0.15) !important;
      animation: stuckCardPulse 2s infinite alternate;
    }
    
    .stage-card.stuck-alert .stage-header {
      background: #fff5f5 !important;
      border-bottom-color: #fee2e2 !important;
    }

    @keyframes stuckPulse {
      0% {
        box-shadow: inset 0 0 0 transparent;
      }
      100% {
        box-shadow: inset 0 0 8px rgba(239, 68, 68, 0.08);
      }
    }
    
    @keyframes stuckCardPulse {
      0% {
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.05);
      }
      100% {
        box-shadow: 0 10px 25px rgba(239, 68, 68, 0.18);
      }
    }

    .stuck-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background-color: #fee2e2;
      color: var(--danger);
      border: 1px solid #fca5a5;
      font-size: 11px;
      font-weight: 800;
      padding: 2px 8px;
      border-radius: 99px;
      animation: blinkText 1.5s infinite;
      white-space: nowrap;
      cursor: help;
    }

    @keyframes blinkText {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.6; }
    }
  </style>
</head>
<body>

  <!-- Header -->
  <header class="header">
    <div class="header-title">
      <h1>Nigiwai Project Management</h1>
      <p>แผงควบคุมสำหรับจัดการโครงการ ขั้นตอนงาน และงานย่อย (Admin Panel)</p>
    </div>
    <div class="header-user">
      <a href="../" class="btn btn-outline" target="_blank">← ดูหน้าแดชบอร์ดหลัก</a>
      <div class="user-info">
        <div class="user-name"><?= sanitize_output($_SESSION['admin_display_name']) ?></div>
        <div class="user-role">Administrator</div>
      </div>
      <a href="?logout=1" class="btn btn-danger">ออกจากระบบ</a>
    </div>
  </header>

  <!-- Container -->
  <main class="container">
    <div class="toolbar">
      <div class="project-control-wrap">
        <button class="btn btn-outline" onclick="openProjectSelectorModal()" style="font-weight: 800; border-color: var(--primary); color: var(--primary); display: inline-flex; align-items: center; gap: 8px;">
          📂 โครงการปัจจุบัน: <span id="currentProjectDisplayName" style="font-weight: 800; color: #1e1b4b;">กำลังโหลด...</span>
        </button>
        <button class="btn btn-outline" onclick="openProjectManagerModal()">⚙️ จัดการโครงการ</button>
        <button class="btn btn-outline" onclick="openAssigneeManagerModal()">👥 จัดการผู้รับผิดชอบ</button>
        <button class="btn btn-outline" onclick="openColorManagerModal()">🎨 จัดการโทนสี</button>
      </div>
      <div class="stage-control-wrap">
        <button class="btn btn-primary" onclick="openStageModal()">+ เพิ่มขั้นตอนหลัก (Stage)</button>
      </div>
    </div>

    <!-- ลิสต์ข้อมูล Stage & Task ย่อย -->
    <div id="stagesContainer">
      <div class="empty-state">กรุณาเลือกหรือสร้างโครงการเพื่อจัดการข้อมูล...</div>
    </div>
  </main>

  <!-- Toast Notification Container -->
  <div id="toastContainer" class="toast-container"></div>

  <!-- Project Selector Modal -->
  <div id="projectSelectorModal" class="modal-overlay">
    <div class="modal" style="max-width: 650px;">
      <div class="modal-header">
        <h3 style="font-weight: 800; color: var(--primary); font-size: 18px; display: flex; align-items: center; gap: 8px;">📂 เลือกโครงการที่จะเข้าจัดการ</h3>
        <button class="modal-close" onclick="closeProjectSelectorModal()">×</button>
      </div>
      <div class="modal-body" style="background-color: #f8fafc; padding: 20px;">
        <p style="font-size: 13.5px; color: var(--muted); margin-bottom: 16px; font-weight: 600;">
          กรุณาเลือกโครงการที่ต้องการอัปเดตหรือแก้ไขด้านล่าง เพื่อความถูกต้องและป้องกันความผิดพลาด
        </p>
        <div id="projectSelectorList" style="display: grid; gap: 12px; max-height: 50vh; overflow-y: auto; padding-right: 4px;">
          <!-- Dynamic Project Cards will be inserted here -->
        </div>
      </div>
      <div class="modal-footer" style="background-color: #fff;">
        <button type="button" class="btn btn-secondary" onclick="closeProjectSelectorModal()">ปิด</button>
      </div>
    </div>
  </div>

  <!-- 1. Modal จัดการขั้นตอนหลัก (Stage) -->
  <div id="stageModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3 id="stageModalTitle">เพิ่มขั้นตอนหลัก</h3>
        <button class="modal-close" onclick="closeStageModal()">×</button>
      </div>
      <form id="stageForm" onsubmit="saveStage(event)">
        <input type="hidden" id="stageId" name="id">
        <input type="hidden" id="stageProjectId" name="project_id">
        <div class="modal-body">
          <div class="form-group">
            <label>โครงการต้นสังกัด (Project)</label>
            <input type="text" id="stageProjectName" class="form-control" readonly style="background-color: #f1f5f9; font-weight:700;">
          </div>
          <div class="form-group">
            <label for="stageTitle">ชื่อขั้นตอนหลัก (Stage Title)</label>
            <input type="text" id="stageTitle" name="title" class="form-control" placeholder="เช่น 2. Business Requirement..." required>
          </div>
          <div class="form-group">
            <label>ผู้รับผิดชอบหลักขั้นตอน (Stage Assignees)</label>
            <div id="stageAssigneesWrap" class="assignee-checkbox-container">
              <!-- Dynamic checkboxes will be inserted here -->
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="stageStart">วันที่เริ่มต้น (Start Date)</label>
              <input type="date" id="stageStart" name="start_date" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="stageEnd">วันที่สิ้นสุด (End Date)</label>
              <input type="date" id="stageEnd" name="end_date" class="form-control" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="stageColor">สีขั้นตอนหลัก (Color Preset)</label>
              <select id="stageColor" name="color" class="form-control" required onchange="this.style.backgroundColor = this.value; this.style.color = getContrastColor(this.value); handleColorChange('stage', this.value);">
                <option value="">-- เลือกสี --</option>
              </select>
            </div>
            <div class="form-group">
              <label for="stageOrder">ลำดับการจัดเรียง (Sort Order)</label>
              <input type="number" id="stageOrder" name="sort_order" class="form-control" value="0" min="0" required>
            </div>
          </div>
          <div class="form-group" style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
            <input type="checkbox" id="stageCompleted" name="is_completed" value="1" style="width: 18px; height: 18px; cursor: pointer;">
            <label for="stageCompleted" style="margin-bottom: 0; cursor: pointer; font-weight: 700; color: var(--success);">ทำเสร็จแล้ว 100% (Mark as Completed)</label>
          </div>
          <div class="form-group" id="stageStuckReasonGroup" style="display: none; border: 1px dashed var(--danger); padding: 10px; border-radius: 8px; margin-top: 10px;">
            <label for="stageStuckReason" style="color: var(--danger); font-weight: 800; display: flex; align-items: center; gap: 4px;">⚠️ ระบุสาเหตุที่ Stuck / Delay *</label>
            <textarea id="stageStuckReason" name="stuck_reason" class="form-control" placeholder="กรุณาระบุรายละเอียด เช่น ติดตามของ/รอการอนุมัติแบบ" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeStageModal()">ยกเลิก</button>
          <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 2. Modal จัดการงานย่อย (Task) -->
  <div id="taskModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3 id="taskModalTitle">เพิ่มงานย่อย</h3>
        <button class="modal-close" onclick="closeTaskModal()">×</button>
      </div>
      <form id="taskForm" onsubmit="saveTask(event)">
        <input type="hidden" id="taskId" name="id">
        <input type="hidden" id="taskStageId" name="stage_id">
        <div class="modal-body">
          <div class="form-group">
            <label>ขั้นตอนหลักต้นสังกัด (Stage)</label>
            <input type="text" id="taskStageName" class="form-control" readonly style="background-color: #f1f5f9; font-weight:700;">
          </div>
          <div class="form-group">
            <label for="taskTitle">ชื่องานย่อย (Task Title)</label>
            <input type="text" id="taskTitle" name="title" class="form-control" placeholder="เช่น S01- Memo & Masterdata" required>
          </div>
          <div class="form-group">
            <label>ผู้รับผิดชอบงานย่อย (Assignees)</label>
            <div id="taskAssigneesWrap" class="assignee-checkbox-container">
              <!-- Dynamic checkboxes will be inserted here -->
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="taskStart">วันที่เริ่ม (Start Date)</label>
              <input type="date" id="taskStart" name="start_date" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="taskEnd">วันที่สิ้นสุด (End Date)</label>
              <input type="date" id="taskEnd" name="end_date" class="form-control" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="taskColor">สีประเภทงานย่อย (Color Preset)</label>
              <select id="taskColor" name="color" class="form-control" required onchange="this.style.backgroundColor = this.value; this.style.color = getContrastColor(this.value); handleColorChange('task', this.value);">
                <option value="">-- เลือกสี --</option>
              </select>
            </div>
            <div class="form-group">
              <label for="taskLane">เลนแสดงผล Gantt (Lane)</label>
              <input type="number" id="taskLane" name="lane" class="form-control" value="1" min="1" max="10" required>
            </div>
          </div>
          <div class="form-group" style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
            <input type="checkbox" id="taskCompleted" name="is_completed" value="1" style="width: 18px; height: 18px; cursor: pointer;">
            <label for="taskCompleted" style="margin-bottom: 0; cursor: pointer; font-weight: 700; color: var(--success);">ทำเสร็จแล้ว 100% (Mark as Completed)</label>
          </div>
          <div class="form-group" id="taskStuckReasonGroup" style="display: none; border: 1px dashed var(--danger); padding: 10px; border-radius: 8px; margin-top: 10px;">
            <label for="taskStuckReason" style="color: var(--danger); font-weight: 800; display: flex; align-items: center; gap: 4px;">⚠️ ระบุสาเหตุที่ Stuck / Delay *</label>
            <textarea id="taskStuckReason" name="stuck_reason" class="form-control" placeholder="กรุณาระบุรายละเอียด เช่น ติดตามของ/รอการอนุมัติแบบ" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeTaskModal()">ยกเลิก</button>
          <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 3. Modal จัดการโครงการหลักทั้งหมด (Project Manager) -->
  <div id="projectManagerModal" class="modal-overlay">
    <div class="modal" style="max-width: 680px;">
      <div class="modal-header">
        <h3>จัดการโครงการทั้งหมด</h3>
        <button class="modal-close" onclick="closeProjectManagerModal()">×</button>
      </div>
      <div class="modal-body">
        <!-- ฟอร์มเพิ่ม/แก้ไขโครงการ (ซ่อนไว้เริ่มต้น) -->
        <div id="projectFormSection" style="display: none; background: #f8fafc; padding: 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--line);">
          <h4 id="projectFormTitle" style="margin-bottom: 12px; font-weight:800;">เพิ่มโครงการใหม่</h4>
          <form id="projectForm" onsubmit="saveProject(event)">
            <input type="hidden" id="projectId" name="id">
            <div class="form-group">
              <label for="projectName">ชื่อโครงการ (Project Name)</label>
              <input type="text" id="projectName" name="name" class="form-control" placeholder="ระบุชื่อโครงการ" required>
            </div>
            <div class="form-group">
              <label for="projectDescription">คำอธิบายรายละเอียด (Description)</label>
              <textarea id="projectDescription" name="description" class="form-control" rows="2" placeholder="ระบุรายละเอียดโครงการอย่างย่อ"></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>ผู้รับผิดชอบหลักรวม (Owners)</label>
                <div id="projectAssigneesWrap" class="assignee-checkbox-container">
                  <!-- Dynamic checkboxes will be inserted here -->
                </div>
              </div>
              <div class="form-group">
                <label for="projectStatus">สถานะโครงการ</label>
                <select id="projectStatus" name="status" class="form-control" required>
                  <option value="Active">Active (กำลังดำเนินการ)</option>
                  <option value="Completed">Completed (เสร็จสิ้น)</option>
                  <option value="At Risk">At Risk (มีความเสี่ยง)</option>
                  <option value="Inactive">Inactive (ปิดใช้งาน)</option>
                </select>
              </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:10px;">
              <button type="button" class="btn btn-secondary" onclick="hideProjectForm()">ยกเลิก</button>
              <button type="submit" class="btn btn-primary">บันทึกโครงการ</button>
            </div>
          </form>
        </div>

        <div style="display: flex; justify-content: space-between; align-items:center; margin-bottom:12px;">
          <h4 style="font-weight: 800;">รายชื่อโครงการในระบบ</h4>
          <button class="btn btn-primary" style="padding: 6px 12px; font-size:12px;" onclick="showAddProjectForm()">+ เพิ่มโครงการใหม่</button>
        </div>

        <!-- รายชื่อโครงการในคลัง -->
        <table class="project-table">
          <thead>
            <tr>
              <th>ชื่อโครงการ</th>
              <th>ผู้รับผิดชอบหลัก</th>
              <th>สถานะ</th>
              <th style="width: 120px;">จัดการ</th>
            </tr>
          </thead>
          <tbody id="projectListBody">
            <tr>
              <td colspan="4" style="text-align: center;">กำลังดึงรายชื่อโครงการ...</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeProjectManagerModal()">ปิดหน้าต่าง</button>
      </div>
    </div>
  </div>

  <!-- 4. Modal จัดการผู้รับผิดชอบ (Assignee Manager) -->
  <div id="assigneeManagerModal" class="modal-overlay">
    <div class="modal" style="max-width: 550px;">
      <div class="modal-header">
        <h3>จัดการรายชื่อผู้รับผิดชอบ</h3>
        <button class="modal-close" onclick="closeAssigneeManagerModal()">×</button>
      </div>
      <div class="modal-body">
        <!-- ฟอร์มเพิ่ม/แก้ไขผู้รับผิดชอบ -->
        <div id="assigneeFormSection" style="display: none; background: #f8fafc; padding: 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--line);">
          <h4 id="assigneeFormTitle" style="margin-bottom: 12px; font-weight:800;">เพิ่มผู้รับผิดชอบใหม่</h4>
          <form id="assigneeForm" onsubmit="saveAssignee(event)">
            <input type="hidden" id="assigneeId" name="id">
            <div class="form-group">
              <label for="assigneeName">ชื่อผู้รับผิดชอบ</label>
              <input type="text" id="assigneeName" name="name" class="form-control" placeholder="ระบุชื่อผู้รับผิดชอบ" required>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:10px;">
              <button type="button" class="btn btn-secondary" onclick="hideAssigneeForm()">ยกเลิก</button>
              <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
            </div>
          </form>
        </div>

        <div style="display: flex; justify-content: space-between; align-items:center; margin-bottom:12px;">
          <h4 style="font-weight: 800;">รายชื่อผู้รับผิดชอบในระบบ</h4>
          <button class="btn btn-primary" style="padding: 6px 12px; font-size:12px;" onclick="showAddAssigneeForm()">+ เพิ่มใหม่</button>
        </div>

        <!-- รายชื่อผู้รับผิดชอบ -->
        <table class="project-table">
          <thead>
            <tr>
              <th>ชื่อผู้รับผิดชอบ</th>
              <th style="width: 120px; text-align: center;">จัดการ</th>
            </tr>
          </thead>
          <tbody id="assigneeListBody">
            <tr>
              <td colspan="2" style="text-align: center;">กำลังโหลดข้อมูล...</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeAssigneeManagerModal()">ปิดหน้าต่าง</button>
      </div>
    </div>
  </div>

  <!-- 5. Modal จัดการชุดสี (Color Manager) -->
  <div id="colorManagerModal" class="modal-overlay">
    <div class="modal" style="max-width: 600px;">
      <div class="modal-header">
        <h3>จัดการรหัสสีและชื่อเรียกสี</h3>
        <button class="modal-close" onclick="closeColorManagerModal()">×</button>
      </div>
      <div class="modal-body">
        <!-- ฟอร์มเพิ่ม/แก้ไขสี -->
        <div id="colorFormSection" style="display: none; background: #f8fafc; padding: 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--line);">
          <h4 id="colorFormTitle" style="margin-bottom: 12px; font-weight:800;">เพิ่มรหัสสีใหม่</h4>
          <form id="colorForm" onsubmit="saveColor(event)">
            <input type="hidden" id="colorId" name="id">
            <div class="form-row">
              <div class="form-group">
                <label for="colorName">ชื่อป้ายอธิบายสี</label>
                <input type="text" id="colorName" name="name" class="form-control" placeholder="เช่น Infrastructure / Green" required>
              </div>
              <div class="form-group">
                <label for="colorCode">เลือกสีประจำประเภท</label>
                <div style="display: flex; gap: 8px;">
                  <input type="color" id="colorPicker" class="form-control" style="width: 60px; padding: 2px; height: 42px;" oninput="document.getElementById('colorCode').value = this.value">
                  <input type="text" id="colorCode" name="color_code" class="form-control" placeholder="#77c56b" pattern="^#[0-9A-Fa-f]{6}$" required oninput="document.getElementById('colorPicker').value = this.value">
                </div>
              </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:10px;">
              <button type="button" class="btn btn-secondary" onclick="hideColorForm()">ยกเลิก</button>
              <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
            </div>
          </form>
        </div>

        <div style="display: flex; justify-content: space-between; align-items:center; margin-bottom:12px;">
          <h4 style="font-weight: 800;">โทนสีป้ายกำกับทั้งหมด</h4>
          <button class="btn btn-primary" style="padding: 6px 12px; font-size:12px;" onclick="showAddColorForm()">+ เพิ่มใหม่</button>
        </div>

        <!-- รายชื่อป้ายสี -->
        <table class="project-table">
          <thead>
            <tr>
              <th style="width: 80px;">ตัวอย่างสี</th>
              <th>ชื่อป้ายสี</th>
              <th>รหัสสี HEX</th>
              <th style="width: 120px; text-align: center;">จัดการ</th>
            </tr>
          </thead>
          <tbody id="colorListBody">
            <tr>
              <td colspan="4" style="text-align: center;">กำลังโหลดข้อมูล...</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeColorManagerModal()">ปิดหน้าต่าง</button>
      </div>
    </div>
  </div>

  <script>
    let rawTimelineData = [];
    let projectsList = [];
    let currentProjectId = null;
    let assigneesList = [];
    let colorsList = [];

    function getContrastColor(hex) {
      if (!hex) return '#1e293b';
      hex = hex.replace('#', '');
      if (hex.length === 3) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
      }
      if (hex.length !== 6) return '#1e293b';
      const r = parseInt(hex.substr(0, 2), 16);
      const g = parseInt(hex.substr(2, 2), 16);
      const b = parseInt(hex.substr(4, 2), 16);
      const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
      return (yiq >= 128) ? '#182033' : '#ffffff';
    }

    // Helper to toggle Stuck/Delay Reason fields dynamically
    function handleColorChange(type, colorCode) {
      const isStuck = colorCode && colorCode.toLowerCase() === '#dc3d78';
      const reasonGroup = document.getElementById(`${type}StuckReasonGroup`);
      const reasonInput = document.getElementById(`${type}StuckReason`);
      
      if (reasonGroup && reasonInput) {
        if (isStuck) {
          reasonGroup.style.display = "block";
          reasonInput.setAttribute("required", "required");
        } else {
          reasonGroup.style.display = "none";
          reasonInput.removeAttribute("required");
          reasonInput.value = "";
        }
      }
    }

    // Helper to sync the display name on the toolbar button
    function updateProjectDisplay() {
      const displayEl = document.getElementById("currentProjectDisplayName");
      if (!displayEl) return;
      if (!currentProjectId) {
        displayEl.textContent = "ยังไม่ได้เลือกโครงการ";
        return;
      }
      const project = projectsList.find(p => p.id == currentProjectId);
      if (project) {
        displayEl.textContent = project.name;
      } else {
        displayEl.textContent = "ยังไม่ได้เลือกโครงการ";
      }
    }

    // Project Selector Modal Helpers
    const projectSelectorModal = document.getElementById("projectSelectorModal");

    function openProjectSelectorModal() {
      renderProjectSelectorList();
      projectSelectorModal.classList.add("active");
    }

    function closeProjectSelectorModal() {
      projectSelectorModal.classList.remove("active");
    }

    function renderProjectSelectorList() {
      const container = document.getElementById("projectSelectorList");
      if (!container) return;

      if (projectsList.length === 0) {
        container.innerHTML = `
          <div class="empty-state" style="background:#fff;">
            <p style="margin-bottom:12px;">ยังไม่มีโครงการหลักในระบบ</p>
            <button class="btn btn-primary" onclick="closeProjectSelectorModal(); openProjectManagerModal(); showAddProjectForm();">สร้างโครงการแรกของคุณ</button>
          </div>
        `;
        return;
      }

      container.innerHTML = projectsList.map(p => {
        const isActive = p.id == currentProjectId;
        const statusStyle = `background-color: ${p.status === 'Active' ? '#e0f2fe' : (p.status === 'Completed' ? '#dcfce7' : (p.status === 'At Risk' ? '#fee2e2' : '#f1f5f9'))}; color: ${p.status === 'Active' ? '#0369a1' : (p.status === 'Completed' ? '#15803d' : (p.status === 'At Risk' ? '#ef4444' : '#475569'))};`;
        const assigneesHTML = renderAssignees(p.assignee, 'project');

        return `
          <div class="project-select-card ${isActive ? 'active-project' : ''}" onclick="selectProjectForEditing(${p.id}, '${escapeQuote(p.name)}')">
            <div class="card-top">
              <span class="project-title">📂 ${escapeHTML(p.name)}</span>
              <span class="lane-badge" style="${statusStyle}">${escapeHTML(p.status)}</span>
            </div>
            <div style="margin-top: 4px;">
              <div style="font-size: 12.5px; color: var(--muted); font-weight: 700; margin-bottom: 4px;">ผู้รับผิดชอบโครงการ:</div>
              <div class="project-assignees">${assigneesHTML || '<span style="color:var(--muted); font-size:12px; font-style:italic;">ไม่ระบุ</span>'}</div>
            </div>
            <div class="project-meta-row">
              <div style="flex-grow: 1; max-width: 80%;">
                <div class="progress-container">
                  <div class="progress-bar" style="width: ${p.percent_complete}%"></div>
                </div>
              </div>
              <span class="percentage-text">${p.percent_complete}%</span>
            </div>
            ${isActive ? '<span style="position: absolute; right: 12px; bottom: 8px; font-size: 11px; font-weight: 800; color: var(--primary);">กำลังจัดการโครงการนี้อยู่</span>' : ''}
          </div>
        `;
      }).join('');
    }

    function selectProjectForEditing(id, name) {
      if (id == currentProjectId) {
        closeProjectSelectorModal();
        return;
      }
      if (confirm(`คุณแน่ใจหรือไม่ว่าต้องการเปลี่ยนไปเข้าจัดการโครงการ "${name}"?\n(โปรดตรวจสอบเพื่อป้องกันการแก้ไขผิดโครงการ)`)) {
        switchProject(id);
        closeProjectSelectorModal();
      }
    }

    // Helper to render selection cards directly in the main container when no project is selected
    function renderProjectSelectionDashboard() {
      const container = document.getElementById("stagesContainer");
      if (!container) return;

      if (projectsList.length === 0) {
        container.innerHTML = `
          <div class="empty-state">
            <p style="margin-bottom:12px;">ยังไม่มีโครงการหลักในระบบ</p>
            <button class="btn btn-primary" onclick="openProjectManagerModal(); showAddProjectForm();">สร้างโครงการแรกของคุณ</button>
          </div>
        `;
        return;
      }

      const cardsHTML = projectsList.map(p => {
        const statusStyle = `background-color: ${p.status === 'Active' ? '#e0f2fe' : (p.status === 'Completed' ? '#dcfce7' : (p.status === 'At Risk' ? '#fee2e2' : '#f1f5f9'))}; color: ${p.status === 'Active' ? '#0369a1' : (p.status === 'Completed' ? '#15803d' : (p.status === 'At Risk' ? '#ef4444' : '#475569'))};`;
        const assigneesHTML = renderAssignees(p.assignee, 'project');

        return `
          <div class="project-select-card" onclick="switchProject(${p.id})" style="border-width: 2px;">
            <div class="card-top">
              <span class="project-title" style="font-size: 16px;">📂 ${escapeHTML(p.name)}</span>
              <span class="lane-badge" style="${statusStyle}">${escapeHTML(p.status)}</span>
            </div>
            <div style="margin-top: 4px;">
              <div style="font-size: 12.5px; color: var(--muted); font-weight: 700; margin-bottom: 4px;">ผู้รับผิดชอบโครงการ:</div>
              <div class="project-assignees">${assigneesHTML || '<span style="color:var(--muted); font-size:12px; font-style:italic;">ไม่ระบุ</span>'}</div>
            </div>
            <div class="project-meta-row" style="margin-top: 10px;">
              <div style="flex-grow: 1; max-width: 80%;">
                <div class="progress-container" style="background-color: #cbd5e1; height: 10px;">
                  <div class="progress-bar" style="width: ${p.percent_complete}%"></div>
                </div>
              </div>
              <span class="percentage-text" style="font-size: 13.5px; font-weight: 800;">${p.percent_complete}%</span>
            </div>
            <div style="margin-top: 8px; text-align: right;">
              <span style="font-size: 12px; font-weight: 800; color: var(--primary);">คลิกเข้าจัดการโครงการ →</span>
            </div>
          </div>
        `;
      }).join('');

      container.innerHTML = `
        <div style="background: var(--panel); border: 1px solid var(--line); border-radius: var(--border-radius); box-shadow: var(--shadow); padding: 24px; margin-bottom: 20px;">
          <h2 style="font-weight: 800; font-size: 20px; color: var(--primary); margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">📂 เลือกโครงการที่ต้องการจัดการและแก้ไข</h2>
          <p style="font-size: 13.5px; color: var(--muted); margin-bottom: 20px; font-weight: 600;">กรุณาเลือกโครงการด้านล่างเพื่อเข้าสู่หน้าจอจัดการขั้นตอนงานหลักและงานย่อย</p>
          <div class="project-dashboard-grid">
            ${cardsHTML}
          </div>
        </div>
      `;
    }

    // -------------------------------------------------------------
    // ฟังก์ชันดึงและสลับโปรเจกต์
    // -------------------------------------------------------------
    async function loadProjects() {
      try {
        const response = await fetch('../api.php?action=get_projects');
        if (!response.ok) throw new Error("เกิดข้อผิดพลาดดึงข้อมูลโครงการ");
        projectsList = await response.json();
        
        if (projectsList.length === 0) {
          updateProjectDisplay();
          document.getElementById("stagesContainer").innerHTML = `
            <div class="empty-state">
              <p style="margin-bottom:12px;">ยังไม่มีโครงการหลักในระบบ</p>
              <button class="btn btn-primary" onclick="openProjectManagerModal(); showAddProjectForm();">สร้างโครงการแรกของคุณ</button>
            </div>
          `;
          return;
        }

        // เช็กว่า currentProjectId มีอยู่ในโครงการที่มีอยู่จริงไหม
        const projectExists = projectsList.some(p => p.id == currentProjectId);
        if (!currentProjectId || !projectExists) {
          currentProjectId = null;
          localStorage.removeItem('nigiwai_admin_project_id');
          updateProjectDisplay();
          renderProjectSelectionDashboard();
          return;
        }

        updateProjectDisplay();
        loadData();
      } catch (error) {
        showToast("ไม่สามารถเรียกรายการโปรเจกต์ได้: " + error.message, "error");
      }
    }

    function switchProject(id) {
      if (!id) return;
      currentProjectId = parseInt(id);
      localStorage.setItem('nigiwai_admin_project_id', currentProjectId);
      updateProjectDisplay();
      
      // Update URL query parameter without full reload
      const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?project_id=' + currentProjectId;
      window.history.pushState({ path: newUrl }, '', newUrl);
      
      loadData();
    }

    // Helper to render assignee badges
    function renderAssignees(assigneeStr, type = 'task') {
      if (!assigneeStr) return '';
      const list = assigneeStr.split(',').map(s => s.trim()).filter(Boolean);
      if (list.length === 0) return '';
      let bg = '#f0fdf4';
      let color = '#166534';
      if (type === 'stage') {
        bg = '#e0f2fe';
        color = '#0369a1';
      } else if (type === 'project') {
        bg = '#f1f5f9';
        color = '#334155';
      }
      return list.map(name => `<span class="lane-badge" style="background:${bg}; color:${color}; font-weight:700; display:inline-flex; align-items:center; margin-right:4px; margin-top:2px;">👤 ${escapeHTML(name)}</span>`).join('');
    }

    async function loadAssignees() {
      try {
        const response = await fetch('../api.php?action=get_assignees');
        if (!response.ok) throw new Error("ดึงข้อมูลผู้รับผิดชอบล้มเหลว");
        assigneesList = await response.json();
        
        // Populate all assignee checkbox wrappers
        const wrappers = [
          { id: "projectAssigneesWrap", name: "projectAssignee" },
          { id: "stageAssigneesWrap", name: "stageAssignee" },
          { id: "taskAssigneesWrap", name: "taskAssignee" }
        ];
        
        wrappers.forEach(w => {
          const container = document.getElementById(w.id);
          if (container) {
            if (assigneesList.length === 0) {
              container.innerHTML = '<div style="color: var(--muted); font-size: 13px; font-style: italic;">ไม่มีรายชื่อผู้รับผิดชอบในระบบ</div>';
            } else {
              container.innerHTML = assigneesList.map(a => `
                <label class="assignee-checkbox-item">
                  <input type="checkbox" value="${escapeHTML(a.name)}">
                  <span>${escapeHTML(a.name)}</span>
                </label>
              `).join('');
            }
          }
        });
      } catch (error) {
        showToast("ไม่สามารถโหลดผู้รับผิดชอบได้: " + error.message, "error");
      }
    }

    async function loadColors() {
      try {
        const response = await fetch('../api.php?action=get_colors');
        if (!response.ok) throw new Error("ดึงข้อมูลสีล้มเหลว");
        colorsList = await response.json();
        
        // Populate all color dropdowns
        const selectors = ["stageColor", "taskColor"];
        selectors.forEach(id => {
          const el = document.getElementById(id);
          if (el) {
            const val = el.getAttribute('data-temp-value') || el.value; // preserve value
            el.innerHTML = '<option value="">-- เลือกสี --</option>' + 
              colorsList.map(c => `<option value="${escapeHTML(c.color_code)}">${escapeHTML(c.name)}</option>`).join('');
            el.value = val;
            el.removeAttribute('data-temp-value');
            
            // Trigger color style update
            if (val) {
              el.style.backgroundColor = val;
              el.style.color = getContrastColor(val);
            } else {
              el.style.backgroundColor = '';
              el.style.color = '';
            }
          }
        });
      } catch (error) {
        showToast("ไม่สามารถโหลดชุดสีได้: " + error.message, "error");
      }
    }




    // ดึงข้อมูล Stages และ Tasks ของโปรเจกต์ปัจจุบัน
    async function loadData() {
      if (!currentProjectId) return;
      try {
        const response = await fetch(`../api.php?action=get_timeline&project_id=${currentProjectId}`);
        if (!response.ok) {
          let errorMsg = "เกิดข้อผิดพลาดในการโหลด API";
          try {
            const errData = await response.json();
            if (errData && errData.error) errorMsg = errData.error;
          } catch(e) {}
          throw new Error(errorMsg);
        }
        
        const data = await response.json();
        rawTimelineData = data.stages || [];
        renderStages();
      } catch (error) {
        showToast("เกิดข้อผิดพลาดในการเชื่อมต่อ: " + error.message, "error");
      }
    }

    // -------------------------------------------------------------
    // เรนเดอร์หน้าจอหลัก (Stages และ Tasks)
    // -------------------------------------------------------------
    function renderStages() {
      const container = document.getElementById("stagesContainer");
      const currentProject = projectsList.find(p => p.id == currentProjectId);
      const projectName = currentProject ? currentProject.name : "โครงการ";

      if (rawTimelineData.length === 0) {
        container.innerHTML = `
          <div class="empty-state">
            <p style="margin-bottom:12px;">ยังไม่มีขั้นตอนงานหลักในโครงการ "${escapeHTML(projectName)}"</p>
            <button class="btn btn-primary btn-sm" onclick="openStageModal()">เริ่มสร้างขั้นตอนแรก</button>
          </div>
        `;
        return;
      }

      container.innerHTML = rawTimelineData.map(stage => {
        const tasks = stage.tasks || [];
        const taskRows = tasks.map(task => {
          const isTaskStuck = task.color && task.color.toLowerCase() === '#dc3d78';
          const stuckBadge = isTaskStuck 
            ? `<span class="stuck-badge" title="สาเหตุที่ Stuck/Delay: ${escapeHTML(task.stuck_reason || 'ไม่ได้ระบุสาเหตุ')}">⚠️ Stuck/Delay</span>` 
            : '';
          const isTaskCompleted = parseInt(task.is_completed) === 1;
          const completedBadge = isTaskCompleted 
            ? `<span class="lane-badge" style="background:#dcfce7; color:#15803d; font-weight:800; border: 1px solid #bbf7d0;">✓ เสร็จแล้ว</span>` 
            : '';
          return `
            <div class="task-item ${isTaskStuck ? 'stuck-alert' : ''}" draggable="true" data-id="${task.id}" data-stage-id="${stage.id}">
              <div class="task-meta">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 12px;">
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="drag-handle" title="คลิกลากสลับเรียงลำดับย่อย">☰</span>
                    <span class="color-swatch" style="background-color: ${task.color}"></span>
                    <span class="task-title" style="font-size: 14.5px; font-weight: 700; color: #334155;">${escapeHTML(task.title)}</span>
                  </div>
                  <div style="margin-left: auto; margin-right: 16px;">
                    <span class="stage-dates" style="font-size: 13px; font-weight: 800; color: #1e1b4b; background: #e0e7ff; border: 1px solid #c7d2fe; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                      📅 ${formatDate(task.start)} - ${formatDate(task.end)}
                    </span>
                  </div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 6px;">
                  ${task.assignee ? `<span style="display:inline-flex; align-items:center; flex-wrap:wrap; gap:4px; font-size:12px; font-weight:700;">ผู้รับผิดชอบ: ${renderAssignees(task.assignee, 'task')}</span>` : ''}
                  <span class="lane-badge">Lane ${task.lane}</span>
                  ${stuckBadge}
                  ${completedBadge}
                </div>
              </div>
              <div class="stage-actions">
                <button class="btn btn-outline" style="padding: 4px 10px; font-size:12px;" onclick="editTask(${stage.id}, ${task.id})">แก้ไข</button>
                <button class="btn btn-danger" style="padding: 4px 10px; font-size:12px;" onclick="deleteTask(${task.id})">ลบ</button>
              </div>
            </div>
          `;
        }).join('');

        const isStageStuck = stage.color && stage.color.toLowerCase() === '#dc3d78';
        const stageStuckBadge = isStageStuck 
          ? `<span class="stuck-badge" title="สาเหตุที่ Stuck/Delay: ${escapeHTML(stage.stuck_reason || 'ไม่ได้ระบุสาเหตุ')}">⚠️ Stuck/Delay</span>` 
          : '';
        const isStageCompleted = parseInt(stage.is_completed) === 1;
        const stageCompletedBadge = isStageCompleted 
          ? `<span class="lane-badge" style="background:#dcfce7; color:#15803d; font-weight:800; font-size:12px; border: 1px solid #bbf7d0;">✓ เสร็จแล้ว 100%</span>` 
          : '';

        return `
          <article class="stage-card ${isStageStuck ? 'stuck-alert' : ''}">
            <div class="stage-header">
              <div class="stage-meta">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 12px;">
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="color-swatch" style="background-color: ${stage.color}"></span>
                    <h3 class="stage-title">${escapeHTML(stage.title)}</h3>
                  </div>
                  <div style="margin-left: auto; margin-right: 16px;">
                    <span class="stage-dates" style="font-size: 13px; font-weight: 800; color: #1e1b4b; background: #e0e7ff; border: 1px solid #c7d2fe; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                      📅 ${formatDate(stage.start)} - ${formatDate(stage.end)}
                    </span>
                  </div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 6px;">
                  ${stage.assignee ? `<span style="display:inline-flex; align-items:center; flex-wrap:wrap; gap:4px; font-size:12px; font-weight:700;">ผู้รับผิดชอบหลัก: ${renderAssignees(stage.assignee, 'stage')}</span>` : ''}
                  <span class="lane-badge" style="background:#f3e8ff; color:#6b21a8">Sort: ${stage.sort_order}</span>
                  ${stageStuckBadge}
                  ${stageCompletedBadge}
                </div>
              </div>
              <div class="stage-actions">
                <button class="btn btn-primary" style="padding: 6px 12px; font-size:12px;" onclick="openTaskModalForAdd(${stage.id}, '${escapeQuote(stage.title)}')">+ เพิ่มงานย่อย</button>
                <button class="btn btn-outline" style="padding: 6px 12px; font-size:12px;" onclick="editStage(${stage.id})">แก้ไข</button>
                <button class="btn btn-danger" style="padding: 6px 12px; font-size:12px;" onclick="deleteStage(${stage.id})">ลบ</button>
              </div>
            </div>
            <div class="task-list" data-stage-id="${stage.id}">
              ${taskRows || '<div style="color: var(--muted); padding:16px 20px; font-size:13.5px; font-style:italic;">ยังไม่มีงานย่อยภายใต้ขั้นตอนนี้ (ลากวางเรียงลำดับไม่ได้)</div>'}
            </div>
          </article>
        `;
      }).join('');

      attachDragAndDropListeners();
    }

    // -------------------------------------------------------------
    // ฟังก์ชันจัดการ Drag & Drop สำหรับงานย่อย
    // -------------------------------------------------------------
    function attachDragAndDropListeners() {
      const taskLists = document.querySelectorAll('.task-list');
      taskLists.forEach(list => {
        let draggedItem = null;

        list.querySelectorAll('.task-item').forEach(item => {
          item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
          });

          item.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            draggedItem = null;
          });

          item.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            const draggingEl = list.querySelector('.dragging');
            const targetEl = this;
            
            if (draggingEl && draggingEl.dataset.stageId === targetEl.dataset.stageId && draggingEl !== targetEl) {
              const rect = targetEl.getBoundingClientRect();
              const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
              list.insertBefore(draggingEl, next ? targetEl.nextSibling : targetEl);
            }
          });

          item.addEventListener('drop', function(e) {
            e.preventDefault();
            if (draggedItem) {
              reorderTasksInStage(list);
            }
          });
        });
      });
    }

    async function reorderTasksInStage(listElement) {
      const items = [...listElement.querySelectorAll('.task-item')];
      const orders = items.map((item, index) => ({
        id: parseInt(item.dataset.id),
        sort_order: index + 1
      }));

      try {
        const response = await fetch('../api.php?action=reorder_tasks', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ orders })
        });
        const result = await response.json();
        if (result.success) {
          showToast(result.message, "success");
          loadData();
        } else {
          showToast(result.error || "เกิดข้อผิดพลาดในการบันทึกจัดลำดับใหม่", "error");
          loadData();
        }
      } catch (error) {
        showToast("ล้มเหลวในการเชื่อมต่อเซิร์ฟเวอร์", "error");
        loadData();
      }
    }

    // -------------------------------------------------------------
    // ฟังก์ชันจัดการ Modal โครงการ (Project Manager)
    // -------------------------------------------------------------
    const projectManagerModal = document.getElementById("projectManagerModal");
    const projectFormSection = document.getElementById("projectFormSection");
    const projectListBody = document.getElementById("projectListBody");

    function openProjectManagerModal() {
      hideProjectForm();
      renderProjectManagerList();
      projectManagerModal.classList.add("active");
    }

    function closeProjectManagerModal() {
      projectManagerModal.classList.remove("active");
      loadProjects(); // รีโหลดตัวเลือกโครงการหลังจากปิด
    }

    function renderProjectManagerList() {
      if (projectsList.length === 0) {
        projectListBody.innerHTML = '<tr><td colspan="4" style="text-align:center;">ยังไม่มีโครงการหลักในระบบ</td></tr>';
        return;
      }

      projectListBody.innerHTML = projectsList.map(p => `
        <tr>
          <td style="font-weight: 700;">${escapeHTML(p.name)}</td>
          <td>${renderAssignees(p.assignee, 'project') || 'ไม่ระบุ'}</td>
          <td><span class="lane-badge ${p.status === 'Active' ? '' : (p.status === 'Completed' ? 'success' : 'danger')}" style="background-color: ${p.status === 'Active' ? '#e0f2fe' : (p.status === 'Completed' ? '#dcfce7' : (p.status === 'At Risk' ? '#fee2e2' : '#f1f5f9'))}; color: ${p.status === 'Active' ? '#0369a1' : (p.status === 'Completed' ? '#15803d' : (p.status === 'At Risk' ? '#ef4444' : '#475569'))}">${escapeHTML(p.status)}</span></td>
          <td>
            <div style="display:flex; gap:6px;">
              <button class="btn btn-outline" style="padding:2px 6px; font-size:11px;" onclick="editProject(${p.id})">แก้ไข</button>
              <button class="btn btn-outline" style="padding:2px 6px; font-size:11px; color:var(--primary); border-color:var(--line);" onclick="duplicateProject(${p.id})">คัดลอก</button>
              <button class="btn btn-danger" style="padding:2px 6px; font-size:11px;" onclick="deleteProject(${p.id})">ลบ</button>
            </div>
          </td>
        </tr>
      `).join('');
    }

    function showAddProjectForm() {
      document.getElementById("projectFormTitle").textContent = "เพิ่มโครงการใหม่";
      document.getElementById("projectForm").reset();
      document.getElementById("projectId").value = '';
      document.querySelectorAll('#projectAssigneesWrap input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
      });
      projectFormSection.style.display = "block";
    }

    function showEditProjectForm(project) {
      document.getElementById("projectFormTitle").textContent = "แก้ไขโครงการหลัก";
      document.getElementById("projectId").value = project.id;
      document.getElementById("projectName").value = project.name;
      document.getElementById("projectDescription").value = project.description || '';
      
      const projectAssignees = project.assignee ? project.assignee.split(',').map(s => s.trim()) : [];
      document.querySelectorAll('#projectAssigneesWrap input[type="checkbox"]').forEach(cb => {
        cb.checked = projectAssignees.includes(cb.value);
      });
      
      document.getElementById("projectStatus").value = project.status;
      projectFormSection.style.display = "block";
    }

    function hideProjectForm() {
      projectFormSection.style.display = "none";
    }

    async function saveProject(event) {
      event.preventDefault();
      const form = document.getElementById("projectForm");
      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());

      const checkedAssignees = Array.from(document.querySelectorAll('#projectAssigneesWrap input[type="checkbox"]:checked')).map(cb => cb.value);
      data.assignee = checkedAssignees.join(', ');

      try {
        const response = await fetch('../api.php?action=save_project', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        const result = await response.json();
        
        if (result.success) {
          showToast(result.message, "success");
          hideProjectForm();
          
          // โหลดข้อมูลโครงการล่าสุด
          const res = await fetch('../api.php?action=get_projects');
          projectsList = await res.json();
          renderProjectManagerList();
        } else {
          showToast(result.error || "เกิดข้อผิดพลาดในการบันทึกโครงการ", "error");
        }
      } catch (e) {
        showToast("การเชื่อมต่อเซิร์ฟเวอร์ผิดพลาด: " + e.message, "error");
      }
    }

    async function deleteProject(id) {
      if (!confirm("⚠️ คำเตือน: คุณต้องการลบโครงการนี้ใช่หรือไม่? การกระทำนี้จะลบทุกขั้นตอนงานและงานย่อยทั้งหมดภายใต้โครงการนี้แบบถาวรและไม่สามารถกู้คืนได้!")) {
        return;
      }

      try {
        const response = await fetch('../api.php?action=delete_project', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        const result = await response.json();
        if (result.success) {
          showToast(result.message, "success");
          
          if (currentProjectId === id) {
            currentProjectId = null;
            localStorage.removeItem('nigiwai_admin_project_id');
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({ path: newUrl }, '', newUrl);
          }

          const res = await fetch('../api.php?action=get_projects');
          projectsList = await res.json();
          renderProjectManagerList();
        } else {
          showToast(result.error, "error");
        }
      } catch (e) {
        showToast("ล้มเหลวในการส่งคำขอลบโครงการ", "error");
      }
    }

    async function duplicateProject(id) {
      if (!confirm("คุณต้องการคัดลอกโครงการนี้พร้อมขั้นตอนและงานย่อยทั้งหมดใช่หรือไม่?")) {
        return;
      }

      try {
        const response = await fetch('../api.php?action=duplicate_project', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        const result = await response.json();
        if (result.success) {
          showToast(result.message, "success");
          
          // รีโหลดข้อมูลโครงการทั้งหมด
          const res = await fetch('../api.php?action=get_projects');
          projectsList = await res.json();
          renderProjectManagerList();
        } else {
          showToast(result.error || "เกิดข้อผิดพลาดในการคัดลอกโครงการ", "error");
        }
      } catch (e) {
        showToast("ล้มเหลวในการส่งคำขอคัดลอกโครงการ: " + e.message, "error");
      }
    }

    // -------------------------------------------------------------
    // ฟังก์ชันจัดการ Modal ของ Stage
    // -------------------------------------------------------------
    const stageModal = document.getElementById("stageModal");

    function openStageModal() {
      if (!currentProjectId) {
        showToast("กรุณาเลือกหรือสร้างโครงการหลักก่อนเพิ่มขั้นตอนงาน", "error");
        return;
      }
      const currentProject = projectsList.find(p => p.id == currentProjectId);
      const projectName = currentProject ? currentProject.name : "โครงการที่เลือก";

      document.getElementById("stageModalTitle").textContent = "เพิ่มขั้นตอนหลัก";
      document.getElementById("stageForm").reset();
      document.getElementById("stageId").value = '';
      document.getElementById("stageProjectId").value = currentProjectId;
      document.getElementById("stageProjectName").value = projectName;
      document.getElementById("stageCompleted").checked = false;
      
      const sc = document.getElementById("stageColor");
      sc.value = '';
      sc.style.backgroundColor = '';
      sc.style.color = '';
      handleColorChange('stage', '');
      
      document.querySelectorAll('#stageAssigneesWrap input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
      });

      stageModal.classList.add("active");
    }

    function openStageModalForEdit(stage) {
      const currentProject = projectsList.find(p => p.id == currentProjectId);
      const projectName = currentProject ? currentProject.name : "โครงการที่เลือก";

      document.getElementById("stageModalTitle").textContent = "แก้ไขขั้นตอนหลัก";
      document.getElementById("stageId").value = stage.id;
      document.getElementById("stageProjectId").value = currentProjectId;
      document.getElementById("stageProjectName").value = projectName;
      document.getElementById("stageTitle").value = stage.title;
      
      const stageAssignees = stage.assignee ? stage.assignee.split(',').map(s => s.trim()) : [];
      document.querySelectorAll('#stageAssigneesWrap input[type="checkbox"]').forEach(cb => {
        cb.checked = stageAssignees.includes(cb.value);
      });
      
      document.getElementById("stageStart").value = stage.start;
      document.getElementById("stageEnd").value = stage.end;
      
      const sc = document.getElementById("stageColor");
      if (sc) {
        sc.setAttribute('data-temp-value', stage.color);
        sc.value = stage.color;
        sc.style.backgroundColor = stage.color;
        sc.style.color = getContrastColor(stage.color);
      }
      
      handleColorChange('stage', stage.color);
      document.getElementById("stageStuckReason").value = stage.stuck_reason || '';
      
      document.getElementById("stageOrder").value = stage.sort_order;
      document.getElementById("stageCompleted").checked = parseInt(stage.is_completed) === 1;
      stageModal.classList.add("active");
    }

    function closeStageModal() {
      stageModal.classList.remove("active");
    }

    async function saveStage(event) {
      event.preventDefault();
      const form = document.getElementById("stageForm");
      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());

      const checkedAssignees = Array.from(document.querySelectorAll('#stageAssigneesWrap input[type="checkbox"]:checked')).map(cb => cb.value);
      data.assignee = checkedAssignees.join(', ');
      data.is_completed = document.getElementById("stageCompleted").checked ? 1 : 0;

      // Validation
      if (data.start_date > data.end_date) {
        showToast("วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด", "error");
        return;
      }

      if (data.color && data.color.toLowerCase() === '#dc3d78' && (!data.stuck_reason || !data.stuck_reason.trim())) {
        showToast("กรุณาระบุสาเหตุที่ Stuck/Delay", "error");
        return;
      }

      try {
        const response = await fetch('../api.php?action=save_stage', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        const result = await response.json();
        
        if (result.success) {
          showToast(result.message, "success");
          closeStageModal();
          loadData();
        } else {
          showToast(result.error || "เกิดข้อผิดพลาดในการบันทึก", "error");
        }
      } catch (e) {
        showToast("ไม่สามารถประมวลผลคำขอได้: " + e.message, "error");
      }
    }

    async function deleteStage(id) {
      if (!confirm("คุณต้องการลบขั้นตอนหลักนี้ใช่หรือไม่? การกระทำนี้จะส่งผลให้งานย่อยทั้งหมดที่สังกัดอยู่ในขั้นตอนนี้ถูกลบออกถาวร!")) {
        return;
      }

      try {
        const response = await fetch('../api.php?action=delete_stage', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        const result = await response.json();
        if (result.success) {
          showToast(result.message, "success");
          loadData();
        } else {
          showToast(result.error, "error");
        }
      } catch (e) {
        showToast("ไม่สามารถส่งคำขอได้", "error");
      }
    }

    // -------------------------------------------------------------
    // ฟังก์ชันจัดการ Modal ของ Task
    // -------------------------------------------------------------
    const taskModal = document.getElementById("taskModal");

    function openTaskModalForAdd(stageId, stageTitle) {
      document.getElementById("taskModalTitle").textContent = "เพิ่มงานย่อย";
      document.getElementById("taskForm").reset();
      document.getElementById("taskId").value = '';
      document.getElementById("taskStageId").value = stageId;
      document.getElementById("taskStageName").value = stageTitle;
      document.getElementById("taskCompleted").checked = false;
      
      const tc = document.getElementById("taskColor");
      tc.value = '';
      tc.style.backgroundColor = '';
      tc.style.color = '';
      handleColorChange('task', '');
      
      document.querySelectorAll('#taskAssigneesWrap input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
      });
      
      // ตั้งค่าตั้งต้นของวันที่ตามระยะของ Stage
      const stage = rawTimelineData.find(s => s.id == stageId);
      if (stage) {
        document.getElementById("taskStart").value = stage.start;
        document.getElementById("taskEnd").value = stage.end;
      }

      taskModal.classList.add("active");
    }

    function openTaskModalForEdit(stageId, stageTitle, task) {
      document.getElementById("taskModalTitle").textContent = "แก้ไขงานย่อย";
      document.getElementById("taskId").value = task.id;
      document.getElementById("taskStageId").value = stageId;
      document.getElementById("taskStageName").value = stageTitle;
      document.getElementById("taskTitle").value = task.title;
      
      const taskAssignees = task.assignee ? task.assignee.split(',').map(s => s.trim()) : [];
      document.querySelectorAll('#taskAssigneesWrap input[type="checkbox"]').forEach(cb => {
        cb.checked = taskAssignees.includes(cb.value);
      });
      
      document.getElementById("taskStart").value = task.start;
      document.getElementById("taskEnd").value = task.end;
      
      const tc = document.getElementById("taskColor");
      if (tc) {
        tc.setAttribute('data-temp-value', task.color);
        tc.value = task.color;
        tc.style.backgroundColor = task.color;
        tc.style.color = getContrastColor(task.color);
      }
      
      handleColorChange('task', task.color);
      document.getElementById("taskStuckReason").value = task.stuck_reason || '';
      
      document.getElementById("taskLane").value = task.lane;
      document.getElementById("taskCompleted").checked = parseInt(task.is_completed) === 1;
      taskModal.classList.add("active");
    }

    function closeTaskModal() {
      taskModal.classList.remove("active");
    }

    async function saveTask(event) {
      event.preventDefault();
      const form = document.getElementById("taskForm");
      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());

      const checkedAssignees = Array.from(document.querySelectorAll('#taskAssigneesWrap input[type="checkbox"]:checked')).map(cb => cb.value);
      data.assignee = checkedAssignees.join(', ');
      data.is_completed = document.getElementById("taskCompleted").checked ? 1 : 0;

      // Validation
      if (data.start_date > data.end_date) {
        showToast("วันที่เริ่มต้นงานย่อยต้องไม่เกินวันที่สิ้นสุด", "error");
        return;
      }

      if (data.color && data.color.toLowerCase() === '#dc3d78' && (!data.stuck_reason || !data.stuck_reason.trim())) {
        showToast("กรุณาระบุสาเหตุที่ Stuck/Delay", "error");
        return;
      }

      // ตรวจสอบกับ Stage แม่ฝั่ง Client
      const stage = rawTimelineData.find(s => s.id == data.stage_id);
      if (stage) {
        if (data.start_date < stage.start || data.end_date > stage.end) {
          showToast(`ช่วงเวลาของงานย่อยต้องอยู่ระหว่าง ${stage.start} ถึง ${stage.end} เท่านั้น`, "error");
          return;
        }
      }

      try {
        const response = await fetch('../api.php?action=save_task', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        const result = await response.json();
        
        if (result.success) {
          showToast(result.message, "success");
          closeTaskModal();
          loadData();
        } else {
          showToast(result.error || "เกิดข้อผิดพลาดในการบันทึก", "error");
        }
      } catch (e) {
        showToast("เกิดข้อผิดพลาดในการส่งข้อมูล: " + e.message, "error");
      }
    }

    async function deleteTask(id) {
      if (!confirm("คุณต้องการลบงานย่อยนี้ใช่หรือไม่?")) {
        return;
      }

      try {
        const response = await fetch('../api.php?action=delete_task', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        const result = await response.json();
        if (result.success) {
          showToast(result.message, "success");
          loadData();
        } else {
          showToast(result.error, "error");
        }
      } catch (e) {
        showToast("เกิดข้อผิดพลาดในการสั่งงานลบ", "error");
      }
    }

    // -------------------------------------------------------------
    // ฟังก์ชันย่อยและแจ้งเตือน (Utility functions)
    // -------------------------------------------------------------
    function formatDate(dateStr) {
      const parts = dateStr.split('-');
      if (parts.length !== 3) return dateStr;
      const date = new Date(parts[0], parts[1] - 1, parts[2]);
      return new Intl.DateTimeFormat('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }).format(date);
    }

    function escapeHTML(str) {
      if (!str) return '';
      return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function escapeQuote(str) {
      if (!str) return '';
      return str.replace(/'/g, "\\'");
    }

    function showToast(message, type = "success") {
      const container = document.getElementById("toastContainer");
      const toast = document.createElement("div");
      toast.className = `toast ${type}`;
      toast.innerHTML = `
        <span>${type === 'success' ? '✅' : '❌'}</span>
        <span>${escapeHTML(message)}</span>
      `;
      container.appendChild(toast);
      
      setTimeout(() => {
        toast.style.animation = "fadeOut 0.3s forwards";
        toast.addEventListener("animationend", () => toast.remove());
      }, 3500);
    }

    // -------------------------------------------------------------
    // ฟังก์ชันจัดการ Modal ผู้รับผิดชอบ (Assignee Manager)
    // -------------------------------------------------------------
    const assigneeManagerModal = document.getElementById("assigneeManagerModal");
    const assigneeFormSection = document.getElementById("assigneeFormSection");
    const assigneeListBody = document.getElementById("assigneeListBody");

    function openAssigneeManagerModal() {
      hideAssigneeForm();
      renderAssigneeManagerList();
      assigneeManagerModal.classList.add("active");
    }

    function closeAssigneeManagerModal() {
      assigneeManagerModal.classList.remove("active");
      loadAssignees(); // refresh dropdown values
      loadData(); // reload timeline to display updated assignee strings
    }

    function renderAssigneeManagerList() {
      if (assigneesList.length === 0) {
        assigneeListBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">ยังไม่มีผู้รับผิดชอบในระบบ</td></tr>';
        return;
      }

      assigneeListBody.innerHTML = assigneesList.map(a => `
        <tr>
          <td style="font-weight: 700;">👤 ${escapeHTML(a.name)}</td>
          <td style="text-align: center;">
            <div style="display:flex; gap:6px; justify-content: center;">
              <button class="btn btn-outline" style="padding:2px 6px; font-size:11px;" onclick="editAssignee(${a.id})">แก้ไข</button>
              <button class="btn btn-danger" style="padding:2px 6px; font-size:11px;" onclick="deleteAssignee(${a.id})">ลบ</button>
            </div>
          </td>
        </tr>
      `).join('');
    }

    function showAddAssigneeForm() {
      document.getElementById("assigneeFormTitle").textContent = "เพิ่มผู้รับผิดชอบใหม่";
      document.getElementById("assigneeForm").reset();
      document.getElementById("assigneeId").value = '';
      assigneeFormSection.style.display = "block";
    }

    function showEditAssigneeForm(assignee) {
      document.getElementById("assigneeFormTitle").textContent = "แก้ไขชื่อผู้รับผิดชอบ";
      document.getElementById("assigneeId").value = assignee.id;
      document.getElementById("assigneeName").value = assignee.name;
      assigneeFormSection.style.display = "block";
    }

    function hideAssigneeForm() {
      assigneeFormSection.style.display = "none";
    }

    async function saveAssignee(event) {
      event.preventDefault();
      const form = document.getElementById("assigneeForm");
      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());

      try {
        const response = await fetch('../api.php?action=save_assignee', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        const result = await response.json();
        
        if (result.success) {
          showToast(result.message, "success");
          hideAssigneeForm();
          await loadAssignees();
          renderAssigneeManagerList();
        } else {
          showToast(result.error || "เกิดข้อผิดพลาดในการบันทึก", "error");
        }
      } catch (e) {
        showToast("การเชื่อมต่อเซิร์ฟเวอร์ผิดพลาด: " + e.message, "error");
      }
    }

    async function deleteAssignee(id) {
      if (!confirm("⚠️ คำเตือน: คุณต้องการลบผู้รับผิดชอบนี้ใช่หรือไม่? กิจกรรมที่เคยระบุชื่อนี้ไว้จะถูกปลดเป็น 'ไม่ระบุ'")) {
        return;
      }

      try {
        const response = await fetch('../api.php?action=delete_assignee', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        const result = await response.json();
        if (result.success) {
          showToast(result.message, "success");
          await loadAssignees();
          renderAssigneeManagerList();
        } else {
          showToast(result.error || "เกิดข้อผิดพลาดในการลบ", "error");
        }
      } catch (e) {
        showToast("ล้มเหลวในการส่งคำขอลบ", "error");
      }
    }

    // -------------------------------------------------------------
    // ฟังก์ชันจัดการ Modal ป้ายสี (Color Manager)
    // -------------------------------------------------------------
    const colorManagerModal = document.getElementById("colorManagerModal");
    const colorFormSection = document.getElementById("colorFormSection");
    const colorListBody = document.getElementById("colorListBody");

    function openColorManagerModal() {
      hideColorForm();
      renderColorManagerList();
      colorManagerModal.classList.add("active");
    }

    function closeColorManagerModal() {
      colorManagerModal.classList.remove("active");
      loadColors(); // refresh dropdown values
      loadData(); // reload timeline to display updated color codes
    }

    function renderColorManagerList() {
      if (colorsList.length === 0) {
        colorListBody.innerHTML = '<tr><td colspan="4" style="text-align:center;">ยังไม่มีโทนสีในระบบ</td></tr>';
        return;
      }

      colorListBody.innerHTML = colorsList.map(c => `
        <tr>
          <td style="text-align: center;"><span class="color-swatch" style="background-color: ${c.color_code}; display: inline-block; width: 24px; height: 24px;"></span></td>
          <td style="font-weight: 700;">${escapeHTML(c.name)}</td>
          <td><code>${escapeHTML(c.color_code)}</code></td>
          <td style="text-align: center;">
            <div style="display:flex; gap:6px; justify-content: center;">
              <button class="btn btn-outline" style="padding:2px 6px; font-size:11px;" onclick="editColor(${c.id})">แก้ไข</button>
              <button class="btn btn-danger" style="padding:2px 6px; font-size:11px;" onclick="deleteColor(${c.id})">ลบ</button>
            </div>
          </td>
        </tr>
      `).join('');
    }

    function showAddColorForm() {
      document.getElementById("colorFormTitle").textContent = "เพิ่มสีใหม่";
      document.getElementById("colorForm").reset();
      document.getElementById("colorId").value = '';
      document.getElementById("colorPicker").value = '#77c56b';
      document.getElementById("colorCode").value = '#77c56b';
      colorFormSection.style.display = "block";
    }

    function showEditColorForm(color) {
      document.getElementById("colorFormTitle").textContent = "แก้ไขป้ายสี";
      document.getElementById("colorId").value = color.id;
      document.getElementById("colorName").value = color.name;
      document.getElementById("colorPicker").value = color.color_code;
      document.getElementById("colorCode").value = color.color_code;
      colorFormSection.style.display = "block";
    }

    function hideColorForm() {
      colorFormSection.style.display = "none";
    }

    async function saveColor(event) {
      event.preventDefault();
      const form = document.getElementById("colorForm");
      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());

      try {
        const response = await fetch('../api.php?action=save_color', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        const result = await response.json();
        
        if (result.success) {
          showToast(result.message, "success");
          hideColorForm();
          await loadColors();
          renderColorManagerList();
        } else {
          showToast(result.error || "เกิดข้อผิดพลาดในการบันทึก", "error");
        }
      } catch (e) {
        showToast("การเชื่อมต่อเซิร์ฟเวอร์ผิดพลาด: " + e.message, "error");
      }
    }

    async function deleteColor(id) {
      if (!confirm("⚠️ คำเตือน: คุณต้องการลบสีนี้ใช่หรือไม่? ขั้นตอนและงานย่อยที่ระบุสีนี้จะยังคงแสดงสีเดิม แต่อาจจะไม่ปรากฏในแถบตัวเลือกอีก")) {
        return;
      }

      try {
        const response = await fetch('../api.php?action=delete_color', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        const result = await response.json();
        if (result.success) {
          showToast(result.message, "success");
          await loadColors();
          renderColorManagerList();
        } else {
          showToast(result.error || "เกิดข้อผิดพลาดในการลบ", "error");
        }
      } catch (e) {
        showToast("ล้มเหลวในการส่งคำขอลบ", "error");
      }
    }

    // Helper functions for secure inline event callbacks to avoid JSON quoting bugs
    function editStage(stageId) {
      const stage = rawTimelineData.find(s => s.id == stageId);
      if (stage) {
        openStageModalForEdit(stage);
      }
    }

    function editTask(stageId, taskId) {
      const stage = rawTimelineData.find(s => s.id == stageId);
      if (stage) {
        const task = (stage.tasks || []).find(t => t.id == taskId);
        if (task) {
          openTaskModalForEdit(stageId, stage.title, task);
        }
      }
    }

    function editProject(projectId) {
      const project = projectsList.find(p => p.id == projectId);
      if (project) {
        showEditProjectForm(project);
      }
    }

    function editAssignee(assigneeId) {
      const assignee = assigneesList.find(a => a.id == assigneeId);
      if (assignee) {
        showEditAssigneeForm(assignee);
      }
    }

    function editColor(colorId) {
      const color = colorsList.find(c => c.id == colorId);
      if (color) {
        showEditColorForm(color);
      }
    }

    // Support browser back/forward buttons
    window.addEventListener('popstate', () => {
      const urlParams = new URLSearchParams(window.location.search);
      const urlProjectId = parseInt(urlParams.get('project_id'));
      if (urlProjectId && urlProjectId !== currentProjectId) {
        currentProjectId = urlProjectId;
        localStorage.setItem('nigiwai_admin_project_id', currentProjectId);
        updateProjectDisplay();
        loadData();
      } else if (!urlProjectId && currentProjectId) {
        currentProjectId = null;
        localStorage.removeItem('nigiwai_admin_project_id');
        updateProjectDisplay();
        renderProjectSelectionDashboard();
      }
    });

    // โหลดข้อมูลขึ้นจอเมื่อเข้าใช้งานหน้าเว็บ
    (async () => {
      const urlParams = new URLSearchParams(window.location.search);
      const urlProjectId = parseInt(urlParams.get('project_id'));
      
      if (urlProjectId) {
        currentProjectId = urlProjectId;
        localStorage.setItem('nigiwai_admin_project_id', currentProjectId);
      } else {
        currentProjectId = null;
        localStorage.removeItem('nigiwai_admin_project_id');
      }

      await loadAssignees();
      await loadColors();
      await loadProjects();
    })();
  </script>
</body>
</html>
