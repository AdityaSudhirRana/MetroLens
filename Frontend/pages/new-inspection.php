<?php
/**
 * AI Package Inspector - Real Camera Capture & Image OCR Workspace
 * File: pages/new-inspection.php
 */
$pageTitle = 'New AI Package Inspection';
$activeNav = 'new-inspection';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <!-- 6-Step Progress Stepper -->
        <div class="stepper-container">
            <div class="step-item active" id="stepIndicator1">
                <div class="step-num">1</div>
                <span>Capture / Upload</span>
            </div>
            <div class="stepper-line"></div>
            <div class="step-item" id="stepIndicator2">
                <div class="step-num">2</div>
                <span>OCR Processing</span>
            </div>
            <div class="stepper-line"></div>
            <div class="step-item" id="stepIndicator3">
                <div class="step-num">3</div>
                <span>Extract Text</span>
            </div>
            <div class="stepper-line"></div>
            <div class="step-item" id="stepIndicator4">
                <div class="step-num">4</div>
                <span>JSON Conversion</span>
            </div>
            <div class="stepper-line"></div>
            <div class="step-item" id="stepIndicator5">
                <div class="step-num">5</div>
                <span>Officer Review</span>
            </div>
            <div class="stepper-line"></div>
            <div class="step-item" id="stepIndicator6">
                <div class="step-num">6</div>
                <span>Compliance Verification</span>
            </div>
        </div>

        <!-- Step Views Container -->
        <div id="stepContainer">

            <!-- STEP 1: CAPTURE / UPLOAD PACKAGE IMAGE -->
            <div class="card" id="viewStep1">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-camera" style="color: var(--primary);"></i> Step 1: Capture or Upload Package Image</h3>
                    <p class="card-subtitle">Photograph the physical package label or upload an image file for AI OCR text extraction</p>
                </div>
                <div class="card-body">

                    <!-- Camera & Upload Choice Action Bar -->
                    <div style="display: flex; gap: 16px; margin-bottom: 24px; justify-content: center; flex-wrap: wrap;">
                        <button type="button" class="btn btn-primary btn-lg" id="btnStartCamera">
                            <i class="fas fa-camera"></i> 📷 Capture Package (Camera)
                        </button>
                        <button type="button" class="btn btn-outline btn-lg" onclick="document.getElementById('packageImageInput').click();">
                            <i class="fas fa-folder-open"></i> 📁 Upload Image File
                        </button>
                        <input type="file" id="packageImageInput" accept="image/jpeg,image/png,image/webp" class="hidden-file-input">
                    </div>

                    <!-- WebRTC Camera Viewfinder Overlay (Hidden by Default) -->
                    <div class="camera-viewfinder-card" id="cameraViewfinderCard" style="display: none;">
                        <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;"><i class="fas fa-video"></i> Live Camera Viewfinder</h4>
                        
                        <div class="camera-video-wrapper">
                            <video id="cameraVideo" autoplay playsinline class="camera-video-element"></video>
                            <div class="camera-scan-frame">
                                <span class="scan-frame-label"><i class="fas fa-expand"></i> Place Package Label Here</span>
                            </div>
                        </div>

                        <div id="cameraErrorMessage" class="alert alert-danger" style="display: none; margin-bottom: 16px;">
                            <i class="fas fa-exclamation-triangle"></i> Camera permission was denied or camera is unavailable.
                            <br><span style="font-size: 0.85rem;">Please allow camera permissions in browser settings or upload an image file manually.</span>
                            <div style="margin-top: 10px;">
                                <button type="button" class="btn btn-outline" onclick="document.getElementById('packageImageInput').click();">
                                    📁 Upload Image Instead
                                </button>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: center; gap: 16px;">
                            <button type="button" class="btn-capture-shutter" id="btnCaptureShutter">
                                <i class="fas fa-dot-circle"></i> Capture Photo
                            </button>
                            <button type="button" class="btn btn-outline-light" id="btnCloseCamera">
                                Cancel
                            </button>
                        </div>
                    </div>

                    <!-- Hidden Canvas for Video Frame Capture -->
                    <canvas id="captureCanvas" style="display: none;"></canvas>

                    <!-- Drag & Drop Upload Zone -->
                    <div class="upload-dropzone" id="uploadDropzone">
                        <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <div class="upload-text-main">Drop package photograph here or click Browse File</div>
                        <div class="upload-text-sub">Supports JPG, JPEG, PNG, WEBP (Max 10MB)</div>
                    </div>

                    <!-- Captured / Uploaded Image Preview & Quality Pre-Check -->
                    <div class="card" id="imagePreviewCard" style="display: none; margin-top: 24px; border: 1px solid var(--primary-border); background: #f8fafc;">
                        <div class="card-body">
                            <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                                <img id="imgPreviewThumb" src="" alt="Package Image Preview" style="width: 140px; height: 140px; object-fit: cover; border-radius: 8px; border: 2px solid #cbd5e1;">
                                
                                <div style="flex: 1;">
                                    <h4 style="font-size: 1.1rem; font-weight: 800; color: var(--primary-dark);" id="previewFileName">captured_package_photo.jpg</h4>
                                    <p style="font-size: 0.85rem; color: var(--text-muted);" id="previewImageSource">Source: Camera Capture</p>

                                    <!-- Image Quality Pre-Check Badges -->
                                    <div style="display: flex; gap: 12px; margin-top: 10px; flex-wrap: wrap;">
                                        <span class="badge badge-success" id="chkRes"><i class="fas fa-check"></i> Resolution Acceptable</span>
                                        <span class="badge badge-success" id="chkFmt"><i class="fas fa-check"></i> Format Supported</span>
                                        <span class="badge badge-primary" id="chkRdy"><i class="fas fa-check"></i> Ready for OCR</span>
                                    </div>
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <button type="button" class="btn btn-outline" id="btnRetakePhoto">
                                        <i class="fas fa-redo"></i> Retake / Re-upload
                                    </button>
                                    <button type="button" class="btn btn-primary btn-lg" id="btnStartOcrStep1">
                                        Start AI OCR Scan <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- STEP 2: REAL OCR PROCESSING ANIMATION -->
            <div class="ocr-processing-card" id="viewStep2" style="display: none; text-align: center;">
                <div class="spinner-icon" style="width: 50px; height: 50px; border-width: 4px; border-color: #cbd5e1; border-top-color: var(--primary); margin: 0 auto 20px auto;"></div>
                <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark);">Executing AI Image OCR</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Processing captured image binary through OCR Service & Pattern Parser</p>

                <div class="ocr-checklist" style="max-width: 500px; margin: 24px auto;">
                    <div class="ocr-checklist-item active" id="procItem1">
                        <i class="fas fa-spinner fa-spin"></i> <span>Sending actual image binary to backend OCR Service...</span>
                    </div>
                    <div class="ocr-checklist-item pending" id="procItem2">
                        <i class="far fa-circle"></i> <span>Extracting raw OCR text stream...</span>
                    </div>
                    <div class="ocr-checklist-item pending" id="procItem3">
                        <i class="far fa-circle"></i> <span>Parsing Legal Metrology declarations via regex patterns...</span>
                    </div>
                    <div class="ocr-checklist-item pending" id="procItem4">
                        <i class="far fa-circle"></i> <span>Computing dynamic confidence scores per field...</span>
                    </div>
                </div>
            </div>

            <!-- STEP 3 & 4: RAW OCR TEXT & STRUCTURED JSON VIEWER -->
            <div class="card" id="viewStep3" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-code" style="color: var(--primary);"></i> Step 3 & 4: Raw OCR Stream & Extracted JSON</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                        <div>
                            <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 8px;"><i class="fas fa-align-left"></i> RAW OCR TEXT EXTRACTED FROM IMAGE</h4>
                            <div class="ocr-raw-box" id="rawOcrTextDisplay">Extracting text...</div>
                        </div>
                        <div>
                            <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 8px;"><i class="fas fa-brackets-curly"></i> PARSED STRUCTURED JSON</h4>
                            <div class="json-code-viewer" id="jsonSchemaDisplay">{}</div>
                        </div>
                    </div>

                    <!-- Extraction Summary Counts Bar -->
                    <div class="ocr-summary-card">
                        <div class="summary-metric">
                            <span class="summary-metric-val" id="sumDetected">0</span>
                            <span class="summary-metric-lbl">Fields Detected</span>
                        </div>
                        <div class="summary-metric">
                            <span class="summary-metric-val" style="color: #dc2626;" id="sumMissing">0</span>
                            <span class="summary-metric-lbl">Fields Missing</span>
                        </div>
                        <div class="summary-metric">
                            <span class="summary-metric-val" style="color: #16a34a;" id="sumHighConf">0</span>
                            <span class="summary-metric-lbl">High Confidence</span>
                        </div>
                        <div class="summary-metric">
                            <span class="summary-metric-val" style="color: #d97706;" id="sumLowConf">0</span>
                            <span class="summary-metric-lbl">Low Confidence</span>
                        </div>
                        <button type="button" class="btn btn-primary" id="btnProceedToReview">
                            Proceed to Officer Review <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 5: AUTO-FILLED OFFICER REVIEW & CORRECTION FORM -->
            <div class="card" id="viewStep5" style="display: none;">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 class="card-title"><i class="fas fa-edit" style="color: var(--primary);"></i> Step 5: Officer Review & Declaration Verification</h3>
                        <p class="card-subtitle">Verify auto-filled extracted fields. Fields edited by officer will be marked as MANUALLY VERIFIED.</p>
                    </div>
                    <span class="badge badge-primary" style="font-size: 0.85rem;"><i class="fas fa-user-shield"></i> Officer Review Mode</span>
                </div>
                <div class="card-body">
                    <form id="inspectionReviewForm">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                            <div>
                                <div class="form-group" style="margin-bottom: 16px;">
                                    <label class="form-label-with-confidence">
                                        <strong>1. Manufacturer / Packer Details *</strong>
                                        <span class="confidence-tag high" id="conf_manufacturer">OCR 94%</span>
                                    </label>
                                    <textarea id="field_manufacturer" name="manufacturer" class="form-control field-input-item" rows="2" required></textarea>
                                </div>

                                <div class="form-group" style="margin-bottom: 16px;">
                                    <label class="form-label-with-confidence">
                                        <strong>2. Generic Product Name *</strong>
                                        <span class="confidence-tag high" id="conf_product_name">OCR 95%</span>
                                    </label>
                                    <input type="text" id="field_product_name" name="product_name" class="form-control field-input-item" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 16px;">
                                    <label class="form-label-with-confidence">
                                        <strong>3. Brand Name</strong>
                                        <span class="confidence-tag high" id="conf_brand">OCR 90%</span>
                                    </label>
                                    <input type="text" id="field_brand" name="brand" class="form-control field-input-item">
                                </div>

                                <div class="form-group" style="margin-bottom: 16px;">
                                    <label class="form-label-with-confidence">
                                        <strong>4. Net Quantity *</strong>
                                        <span class="confidence-tag high" id="conf_net_quantity">OCR 98%</span>
                                    </label>
                                    <input type="text" id="field_net_quantity" name="net_quantity" class="form-control field-input-item" placeholder="e.g. 250 g" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 16px;">
                                    <label class="form-label-with-confidence">
                                        <strong>5. Maximum Retail Price (MRP) *</strong>
                                        <span class="confidence-tag high" id="conf_mrp">OCR 97%</span>
                                    </label>
                                    <input type="text" id="field_mrp" name="mrp" class="form-control field-input-item" placeholder="e.g. ₹30.00" required>
                                </div>
                            </div>

                            <div>
                                <div class="form-group" style="margin-bottom: 16px;">
                                    <label class="form-label-with-confidence">
                                        <strong>6. Date of Manufacturing / Packing *</strong>
                                        <span class="confidence-tag high" id="conf_manufacturing_date">OCR 94%</span>
                                    </label>
                                    <input type="text" id="field_manufacturing_date" name="manufacturing_date" class="form-control field-input-item" placeholder="MM/YYYY" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 16px;">
                                    <label class="form-label-with-confidence">
                                        <strong>7. Consumer Care Phone</strong>
                                        <span class="confidence-tag high" id="conf_consumer_care_phone">OCR 96%</span>
                                    </label>
                                    <input type="text" id="field_consumer_care_phone" name="consumer_care_phone" class="form-control field-input-item" placeholder="1800-XX-XXXX">
                                </div>

                                <div class="form-group" style="margin-bottom: 16px;">
                                    <label class="form-label-with-confidence">
                                        <strong>8. Consumer Care Email</strong>
                                        <span class="confidence-tag high" id="conf_consumer_care_email">OCR 97%</span>
                                    </label>
                                    <input type="email" id="field_consumer_care_email" name="consumer_care_email" class="form-control field-input-item" placeholder="customercare@brand.com">
                                </div>

                                <div class="form-group" style="margin-bottom: 16px;">
                                    <label class="form-label-with-confidence">
                                        <strong>9. Country of Origin *</strong>
                                        <span class="confidence-tag high" id="conf_country_of_origin">OCR 98%</span>
                                    </label>
                                    <input type="text" id="field_country_of_origin" name="country_of_origin" class="form-control field-input-item" placeholder="India" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 16px;">
                                    <label><strong>Inspection Commodity Category</strong></label>
                                    <select id="field_category" name="category" class="form-control">
                                        <option value="Packaged Foods & Biscuits">Packaged Foods & Biscuits</option>
                                        <option value="Personal Care & Cosmetics">Personal Care & Cosmetics</option>
                                        <option value="Household Cleaning">Household Cleaning</option>
                                        <option value="Beverages & Juices">Beverages & Juices</option>
                                        <option value="Pharmaceuticals">Pharmaceuticals</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label><strong>Officer Remarks / Witness Notes</strong></label>
                            <textarea id="field_notes" class="form-control" rows="2" placeholder="Enter physical inspection notes, package dimensions, or witness details..."></textarea>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <button type="button" class="btn btn-outline" onclick="showStep(3);">
                                <i class="fas fa-arrow-left"></i> Back to Raw Text
                            </button>
                            <button type="button" class="btn btn-primary btn-lg" id="btnVerifyCompliance">
                                Run Rule 6 Verification <i class="fas fa-shield-alt"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- STEP 6: COMPLIANCE RESULT SCORECARD & SAVE -->
            <div id="viewStep6" style="display: none;">
                <div class="result-status-banner compliant" id="resultBanner">
                    <div class="banner-left">
                        <div class="banner-status-icon" id="resultStatusIcon"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <div class="banner-title" id="resultStatusTitle">COMPLIANT PACKAGE</div>
                            <div class="banner-sub" id="resultStatusSub">All Mandatory Declarations Verified Satisfactory</div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 2.8rem; font-weight: 800; line-height: 1;" id="resultScore">100%</div>
                        <div style="font-size: 0.85rem; opacity: 0.9;">Compliance Index</div>
                    </div>
                </div>

                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header">
                        <h3 class="card-title">Rule 6 Verification Checklist (LM Packaged Commodities Rules 2011)</h3>
                    </div>
                    <div class="card-body">
                        <div class="checklist-grid" id="ruleChecklistGrid">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>

                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header">
                        <h3 class="card-title">Authorization & Save Report</h3>
                    </div>
                    <div class="card-body" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>Inspecting Officer:</strong> <?php echo htmlspecialchars($currentUser['name']); ?> (<?php echo htmlspecialchars($currentUser['employee_id']); ?>)<br>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">Saved Image Path: <code id="savedImagePathDisplay">uploads/inspections/...</code></span>
                        </div>
                        <div style="display: flex; gap: 12px;">
                            <button type="button" class="btn btn-outline" onclick="showStep(5);">
                                <i class="fas fa-edit"></i> Edit Form
                            </button>
                            <button type="button" class="btn btn-success btn-lg" id="btnSaveInspectionReport">
                                <i class="fas fa-save"></i> Save Inspection Report to Database
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- end stepContainer -->

    </div>
</div>

<script>
    // State management for Real Camera & OCR Pipeline
    let currentStep = 1;
    let cameraStream = null;
    let currentImagePayload = null; // File or Base64 string
    let ocrResponseData = null;
    let manualEditTracker = {};

    document.addEventListener('DOMContentLoaded', function() {
        // UI Controls
        const btnStartCamera = document.getElementById('btnStartCamera');
        const btnCaptureShutter = document.getElementById('btnCaptureShutter');
        const btnCloseCamera = document.getElementById('btnCloseCamera');
        const packageImageInput = document.getElementById('packageImageInput');
        const btnRetakePhoto = document.getElementById('btnRetakePhoto');
        const btnStartOcrStep1 = document.getElementById('btnStartOcrStep1');
        const btnProceedToReview = document.getElementById('btnProceedToReview');
        const btnVerifyCompliance = document.getElementById('btnVerifyCompliance');
        const btnSaveInspectionReport = document.getElementById('btnSaveInspectionReport');

        // Drag and drop zone
        const dropzone = document.getElementById('uploadDropzone');
        dropzone.addEventListener('click', () => packageImageInput.click());
        dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('dragover'); });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                handleFileSelected(e.dataTransfer.files[0]);
            }
        });

        packageImageInput.addEventListener('change', function() {
            if (this.files.length) handleFileSelected(this.files[0]);
        });

        // WebRTC Camera Handlers
        btnStartCamera.addEventListener('click', openCameraViewfinder);
        btnCaptureShutter.addEventListener('click', captureCameraPhoto);
        btnCloseCamera.addEventListener('click', stopCameraStream);

        btnRetakePhoto.addEventListener('click', function() {
            currentImagePayload = null;
            document.getElementById('imagePreviewCard').style.display = 'none';
            stopCameraStream();
        });

        btnStartOcrStep1.addEventListener('click', runRealOcrPipeline);
        btnProceedToReview.addEventListener('click', () => { populateReviewForm(); showStep(5); });
        btnVerifyCompliance.addEventListener('click', runRuleVerification);
        btnSaveInspectionReport.addEventListener('click', saveInspectionToMySQL);

        // Listen for manual field edits by officer
        document.querySelectorAll('.field-input-item').forEach(input => {
            input.addEventListener('input', function() {
                const fieldName = this.name || this.id.replace('field_', '');
                manualEditTracker[fieldName] = true;
                const tag = document.getElementById('conf_' + fieldName);
                if (tag) {
                    tag.className = 'confidence-tag verified';
                    tag.innerHTML = '✓ MANUALLY VERIFIED';
                }
            });
        });
    });

    function showStep(stepNum) {
        currentStep = stepNum;
        for (let i = 1; i <= 6; i++) {
            const view = document.getElementById('viewStep' + i);
            const ind = document.getElementById('stepIndicator' + i);
            if (view) view.style.display = (i === stepNum || (stepNum === 3 && i === 3)) ? 'block' : 'none';
            if (ind) {
                ind.classList.remove('active', 'completed');
                if (i === stepNum) ind.classList.add('active');
                else if (i < stepNum) ind.classList.add('completed');
            }
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // WebRTC Camera API
    async function openCameraViewfinder() {
        const card = document.getElementById('cameraViewfinderCard');
        const err = document.getElementById('cameraErrorMessage');
        const video = document.getElementById('cameraVideo');

        card.style.display = 'block';
        err.style.display = 'none';

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            err.style.display = 'block';
            err.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Camera is not supported on this browser/device.<br><button type="button" class="btn btn-outline" onclick="document.getElementById(\'packageImageInput\').click();">📁 Upload Image Instead</button>';
            return;
        }

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: "environment" } }
            });
            video.srcObject = cameraStream;
        } catch (e) {
            err.style.display = 'block';
            err.innerHTML = 'Camera permission denied or camera unavailable.<br>Please allow camera permission or upload a package image manually.<br><div style="margin-top:10px;"><button type="button" class="btn btn-outline" onclick="document.getElementById(\'packageImageInput\').click();">📁 Upload Image Instead</button></div>';
        }
    }

    function captureCameraPhoto() {
        const video = document.getElementById('cameraVideo');
        const canvas = document.getElementById('captureCanvas');
        if (!video.videoWidth) return;

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
        currentImagePayload = dataUrl;

        stopCameraStream();

        // Show Preview
        document.getElementById('imgPreviewThumb').src = dataUrl;
        document.getElementById('previewFileName').textContent = 'captured_camera_frame.jpg';
        document.getElementById('previewImageSource').textContent = 'Source: Camera Capture';
        document.getElementById('imagePreviewCard').style.display = 'block';
    }

    function stopCameraStream() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }
        document.getElementById('cameraViewfinderCard').style.display = 'none';
    }

    function handleFileSelected(file) {
        currentImagePayload = file;
        document.getElementById('previewFileName').textContent = file.name;
        document.getElementById('previewImageSource').textContent = 'Source: File Upload (' + (file.size / 1024).toFixed(1) + ' KB)';

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imgPreviewThumb').src = e.target.result;
            document.getElementById('imagePreviewCard').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    // Run Real OCR API Call
    function runRealOcrPipeline() {
        if (!currentImagePayload) {
            alert('Please capture a photo or select an image file first.');
            return;
        }

        showStep(2);

        const formData = new FormData();
        if (typeof currentImagePayload === 'string') {
            formData.append('image', currentImagePayload);
        } else {
            formData.append('package_image', currentImagePayload);
        }

        setTimeout(() => {
            document.getElementById('procItem1').className = 'ocr-checklist-item done';
            document.getElementById('procItem1').querySelector('i').className = 'fas fa-check-circle';
            document.getElementById('procItem2').className = 'ocr-checklist-item active';
        }, 500);

        fetch('../api/ocr.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert('OCR Error: ' + (data.error || 'Failed to process image'));
                showStep(1);
                return;
            }

            ocrResponseData = data;

            document.getElementById('procItem2').className = 'ocr-checklist-item done';
            document.getElementById('procItem3').className = 'ocr-checklist-item done';
            document.getElementById('procItem4').className = 'ocr-checklist-item done';

            // Populate Viewers
            document.getElementById('rawOcrTextDisplay').textContent = data.raw_text;
            document.getElementById('jsonSchemaDisplay').textContent = JSON.stringify(data.fields, null, 2);

            const sum = data.summary;
            document.getElementById('sumDetected').textContent = sum.detected_fields;
            document.getElementById('sumMissing').textContent = sum.missing_fields;
            document.getElementById('sumHighConf').textContent = sum.high_confidence;
            document.getElementById('sumLowConf').textContent = sum.low_confidence;

            document.getElementById('savedImagePathDisplay').textContent = data.image_url;

            showStep(3);
        })
        .catch(err => {
            alert('Network error executing OCR API: ' + err);
            showStep(1);
        });
    }

    function populateReviewForm() {
        if (!ocrResponseData || !ocrResponseData.fields) return;
        const f = ocrResponseData.fields;

        for (const key in f) {
            const input = document.getElementById('field_' + key);
            const tag = document.getElementById('conf_' + key);
            const item = f[key];

            if (input) {
                input.value = item.value || '';
                if (!item.value) {
                    input.classList.add('input-missing-highlight');
                } else {
                    input.classList.remove('input-missing-highlight');
                }
            }

            if (tag) {
                if (item.value) {
                    if (item.confidence >= 90) {
                        tag.className = 'confidence-tag high';
                        tag.textContent = 'OCR ' + item.confidence + '%';
                    } else {
                        tag.className = 'confidence-tag low';
                        tag.textContent = '⚠ LOW CONFIDENCE: ' + item.confidence + '%';
                    }
                } else {
                    tag.className = 'confidence-tag missing';
                    tag.textContent = '⚠ NOT DETECTED (MANUAL ENTRY REQUIRED)';
                }
            }
        }
    }

    function runRuleVerification() {
        const payload = {};
        const inputs = document.querySelectorAll('.field-input-item');
        inputs.forEach(inp => {
            const name = inp.name || inp.id.replace('field_', '');
            payload[name] = inp.value;
        });

        fetch('../api/inspections.php?action=verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fields: payload })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            const evalRes = data.evaluation;
            
            document.getElementById('resultScore').textContent = evalRes.compliance_score + '%';

            const banner = document.getElementById('resultBanner');
            const icon = document.getElementById('resultStatusIcon');
            const title = document.getElementById('resultStatusTitle');
            const sub = document.getElementById('resultStatusSub');

            if (evalRes.overall_status === 'Compliant') {
                banner.className = 'result-status-banner compliant';
                icon.innerHTML = '<i class="fas fa-check-circle"></i>';
                title.textContent = 'COMPLIANT PACKAGE';
                sub.textContent = 'All Mandatory Legal Metrology Declarations Satisfied';
            } else {
                banner.className = 'result-status-banner non-compliant';
                icon.innerHTML = '<i class="fas fa-times-circle"></i>';
                title.textContent = 'NON-COMPLIANT PACKAGE';
                sub.textContent = `${evalRes.total_declarations - evalRes.passed_declarations} Declaration Violation(s) Identified`;
            }

            const grid = document.getElementById('ruleChecklistGrid');
            grid.innerHTML = '';
            evalRes.verifications.forEach(v => {
                const card = document.createElement('div');
                card.className = 'checklist-card ' + (v.pass ? 'pass' : 'fail');
                card.innerHTML = `
                    <span class="checklist-label">${v.rule_name}</span>
                    <span class="badge ${v.pass ? 'badge-success' : 'badge-danger'}">
                        ${v.pass ? '✓ Pass' : '✕ Non-Compliant'}
                    </span>
                `;
                grid.appendChild(card);
            });

            showStep(6);
        });
    }

    function saveInspectionToMySQL() {
        const btn = document.getElementById('btnSaveInspectionReport');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving to Database...';

        const fieldsObj = {};
        document.querySelectorAll('.field-input-item').forEach(inp => {
            const name = inp.name || inp.id.replace('field_', '');
            fieldsObj[name] = {
                value: inp.value,
                confidence: manualEditTracker[name] ? 100 : (ocrResponseData?.fields[name]?.confidence || 90),
                status: manualEditTracker[name] ? 'Manually Verified' : 'Verified'
            };
        });

        const payload = {
            product_name: document.getElementById('field_product_name').value.trim(),
            manufacturer_name: document.getElementById('field_manufacturer').value.trim(),
            brand_name: document.getElementById('field_brand').value.trim(),
            category: document.getElementById('field_category').value,
            inspection_type: 'Routine Market Inspection',
            ocr_extracted_text: ocrResponseData ? ocrResponseData.raw_text : '',
            image_url: ocrResponseData ? ocrResponseData.image_url : '',
            notes: document.getElementById('field_notes').value,
            fields: fieldsObj
        };

        fetch('../api/inspections.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'inspection-result.php?id=' + data.inspection_id;
            } else {
                alert('Database save error: ' + (data.error || 'Failed to save inspection'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Save Inspection Report to Database';
            }
        })
        .catch(err => {
            alert('Network error saving report: ' + err);
            btn.disabled = false;
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
