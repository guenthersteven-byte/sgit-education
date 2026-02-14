/**
 * ============================================================================
 * sgiT Education - Hausaufgaben Client JS
 * ============================================================================
 * Kamera, Upload, Galerie, Filter, SATs-Animation
 *
 * @version 1.0
 * @date 12.02.2026
 * ============================================================================
 */

const HausaufgabenApp = {

    // State
    selectedFile: null,
    csrfToken: '',
    currentFilters: { subject: '', school_year: '', grade_level: '' },
    baseUrl: '',

    // ========================================================================
    // INIT
    // ========================================================================

    init(config) {
        this.csrfToken = config.csrfToken || '';
        this.baseUrl = config.baseUrl || '';

        this.bindPhotoButtons();
        this.bindUploadForm();
        this.bindFilters();
        this.bindModal();
        this.bindSchoolInfo();

        this.loadGallery();
        this.loadStats();
    },

    // ========================================================================
    // PHOTO SELECTION
    // ========================================================================

    bindPhotoButtons() {
        const cameraInput = document.getElementById('hw-camera-input');
        const galleryInput = document.getElementById('hw-gallery-input');

        if (cameraInput) {
            cameraInput.addEventListener('change', (e) => this.handleFileSelect(e));
        }
        if (galleryInput) {
            galleryInput.addEventListener('change', (e) => this.handleFileSelect(e));
        }

        const removeBtn = document.getElementById('hw-preview-remove');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => this.clearPreview());
        }
    },

    handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Client-Validierung
        if (!file.type.startsWith('image/')) {
            this.showError('Bitte waehle ein Bild aus');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            this.showError('Datei zu gross (max 10MB)');
            return;
        }

        this.selectedFile = file;
        this.showPreview(file);
    },

    showPreview(file) {
        const container = document.getElementById('hw-preview-container');
        const img = document.getElementById('hw-preview-img');

        const reader = new FileReader();
        reader.onload = (e) => {
            img.src = e.target.result;
            container.classList.add('active');
        };
        reader.readAsDataURL(file);
    },

    clearPreview() {
        const container = document.getElementById('hw-preview-container');
        const img = document.getElementById('hw-preview-img');
        container.classList.remove('active');
        img.src = '';
        this.selectedFile = null;

        // File inputs zuruecksetzen
        const cameraInput = document.getElementById('hw-camera-input');
        const galleryInput = document.getElementById('hw-gallery-input');
        if (cameraInput) cameraInput.value = '';
        if (galleryInput) galleryInput.value = '';
    },

    // ========================================================================
    // UPLOAD
    // ========================================================================

    bindUploadForm() {
        const btn = document.getElementById('hw-upload-btn');
        if (btn) {
            btn.addEventListener('click', () => this.doUpload());
        }
    },

    async doUpload() {
        if (!this.selectedFile) {
            this.showError('Bitte waehle zuerst ein Foto aus');
            return;
        }

        const subject = document.getElementById('hw-subject').value;
        const gradeLevel = document.getElementById('hw-school-grade').value;
        const schoolYear = document.getElementById('hw-school-year-input').value;
        const description = document.getElementById('hw-description').value;

        if (!subject) { this.showError('Bitte waehle ein Fach'); return; }
        if (!gradeLevel || !schoolYear) { this.showError('Bitte zuerst Schulinfo oben speichern'); return; }

        const formData = new FormData();
        formData.append('photo', this.selectedFile);
        formData.append('subject', subject);
        formData.append('grade_level', gradeLevel);
        formData.append('school_year', schoolYear);
        formData.append('description', description);
        formData.append('csrf_token', this.csrfToken);

        const btn = document.getElementById('hw-upload-btn');
        const progress = document.getElementById('hw-upload-progress');

        btn.disabled = true;
        btn.textContent = 'Wird hochgeladen...';
        if (progress) progress.classList.add('active');

        try {
            const xhr = new XMLHttpRequest();
            const url = this.baseUrl + 'hausaufgaben/upload.php';

            const result = await new Promise((resolve, reject) => {
                xhr.open('POST', url);

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        const pct = Math.round((e.loaded / e.total) * 100);
                        const fill = document.getElementById('hw-progress-fill');
                        const text = document.getElementById('hw-progress-text');
                        if (fill) fill.style.width = pct + '%';
                        if (text) text.textContent = pct + '%';
                    }
                };

                xhr.onload = () => {
                    try {
                        resolve(JSON.parse(xhr.responseText));
                    } catch (e) {
                        reject(new Error('Ungueltige Server-Antwort'));
                    }
                };
                xhr.onerror = () => reject(new Error('Netzwerkfehler'));
                xhr.send(formData);
            });

            if (result.success) {
                this.showSatsAnimation(result.sats_earned || 0, result.message || 'Hochgeladen!');
                this.clearPreview();
                document.getElementById('hw-description').value = '';
                this.loadGallery();
                this.loadStats();

                // Balance im Header updaten
                const balanceEl = document.getElementById('hw-balance');
                if (balanceEl && result.new_balance !== null) {
                    balanceEl.textContent = result.new_balance;
                }
            } else {
                this.showError(result.error || 'Upload fehlgeschlagen');
            }

        } catch (err) {
            this.showError(err.message || 'Fehler beim Upload');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span>Hochladen</span><span class="sats-badge">+15 Sats</span>';
            if (progress) {
                progress.classList.remove('active');
                const fill = document.getElementById('hw-progress-fill');
                if (fill) fill.style.width = '0%';
            }
        }
    },

    // ========================================================================
    // GALLERY
    // ========================================================================

    async loadGallery() {
        const gallery = document.getElementById('hw-gallery');
        if (!gallery) return;

        const params = new URLSearchParams({action: 'list', limit: '50'});
        if (this.currentFilters.subject) params.append('subject', this.currentFilters.subject);
        if (this.currentFilters.school_year) params.append('school_year', this.currentFilters.school_year);
        if (this.currentFilters.grade_level) params.append('grade_level', this.currentFilters.grade_level);

        try {
            const resp = await fetch(this.baseUrl + 'hausaufgaben/api.php?' + params);
            const data = await resp.json();

            if (!data.success || !data.uploads.length) {
                gallery.innerHTML = `
                    <div class="hw-empty" style="grid-column: 1/-1;">
                        <div class="icon">📝</div>
                        <p>Noch keine Hausaufgaben hochgeladen</p>
                    </div>`;
                return;
            }

            gallery.innerHTML = data.uploads.map(u => this.renderGalleryItem(u)).join('');

        } catch (err) {
            console.error('Gallery load error:', err);
        }
    },

    renderGalleryItem(upload) {
        const subjectName = this.getSubjectName(upload.subject);
        const date = new Date(upload.created_at).toLocaleDateString('de-DE', {
            day: '2-digit', month: '2-digit', year: '2-digit'
        });

        return `
            <div class="hw-gallery-item" onclick="HausaufgabenApp.showDetail(${upload.id})">
                <img class="hw-gallery-thumb"
                     src="${this.baseUrl}${upload.file_path}"
                     alt="${subjectName}"
                     loading="lazy">
                <div class="hw-gallery-info">
                    <span class="hw-gallery-subject">${this.escHtml(subjectName)}</span>
                    <div class="hw-gallery-date">${date}</div>
                </div>
            </div>`;
    },

    // ========================================================================
    // DETAIL MODAL
    // ========================================================================

    bindModal() {
        const overlay = document.getElementById('hw-modal-overlay');
        const closeBtn = document.getElementById('hw-modal-close');

        if (overlay) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) this.closeModal();
            });
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeModal());
        }
    },

    async showDetail(uploadId) {
        try {
            const resp = await fetch(this.baseUrl + `hausaufgaben/api.php?action=detail&id=${uploadId}`);
            const data = await resp.json();

            if (!data.success || !data.upload) return;

            const u = data.upload;
            const subjectName = this.getSubjectName(u.subject);
            const date = new Date(u.created_at).toLocaleDateString('de-DE', {
                day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });

            document.getElementById('hw-modal-subject').textContent = subjectName;
            document.getElementById('hw-modal-image').src = this.baseUrl + u.file_path;

            const metaHtml = `
                <div class="hw-modal-meta-row"><span class="label">Fach</span><span class="value">${this.escHtml(subjectName)}</span></div>
                <div class="hw-modal-meta-row"><span class="label">Klasse</span><span class="value">${u.grade_level}. Klasse</span></div>
                <div class="hw-modal-meta-row"><span class="label">Schuljahr</span><span class="value">${this.escHtml(u.school_year)}</span></div>
                <div class="hw-modal-meta-row"><span class="label">Datum</span><span class="value">${date}</span></div>
                ${u.description ? `<div class="hw-modal-meta-row"><span class="label">Notiz</span><span class="value">${this.escHtml(u.description)}</span></div>` : ''}
                <div class="hw-modal-meta-row"><span class="label">SATs</span><span class="value" style="color:#43D240;">+${u.sats_earned}</span></div>
            `;
            document.getElementById('hw-modal-meta').innerHTML = metaHtml;

            const ocrEl = document.getElementById('hw-modal-ocr');
            if (u.ocr_text) {
                ocrEl.style.display = 'block';
                ocrEl.querySelector('pre').textContent = u.ocr_text;
                const confEl = ocrEl.querySelector('.confidence');
                if (confEl) confEl.textContent = u.ocr_confidence ? `(${Math.round(u.ocr_confidence)}% Konfidenz)` : '';
            } else {
                ocrEl.style.display = 'none';
            }

            const deleteBtn = document.getElementById('hw-modal-delete-btn');
            if (deleteBtn) {
                deleteBtn.onclick = () => this.deleteUpload(u.id);
            }

            document.getElementById('hw-modal-overlay').classList.add('active');
            document.body.style.overflow = 'hidden';

        } catch (err) {
            console.error('Detail load error:', err);
        }
    },

    closeModal() {
        document.getElementById('hw-modal-overlay').classList.remove('active');
        document.body.style.overflow = '';
    },

    async deleteUpload(uploadId) {
        if (!confirm('Hausaufgabe wirklich loeschen?')) return;

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', uploadId);
            formData.append('csrf_token', this.csrfToken);

            const resp = await fetch(this.baseUrl + 'hausaufgaben/api.php?action=delete', {
                method: 'POST',
                body: formData,
            });
            const data = await resp.json();

            if (data.success) {
                this.closeModal();
                this.loadGallery();
                this.loadStats();
            } else {
                this.showError(data.error || 'Loeschen fehlgeschlagen');
            }
        } catch (err) {
            this.showError('Fehler beim Loeschen');
        }
    },

    // ========================================================================
    // FILTERS
    // ========================================================================

    bindFilters() {
        document.querySelectorAll('.hw-filter-pill[data-subject]').forEach(pill => {
            pill.addEventListener('click', () => {
                const subject = pill.dataset.subject;
                this.currentFilters.subject = (this.currentFilters.subject === subject) ? '' : subject;

                document.querySelectorAll('.hw-filter-pill[data-subject]').forEach(p => p.classList.remove('active'));
                if (this.currentFilters.subject) pill.classList.add('active');

                this.loadGallery();
            });
        });

        const yearSelect = document.getElementById('hw-filter-year');
        if (yearSelect) {
            yearSelect.addEventListener('change', () => {
                this.currentFilters.school_year = yearSelect.value;
                this.loadGallery();
            });
        }
    },

    // ========================================================================
    // STATS
    // ========================================================================

    async loadStats() {
        try {
            const resp = await fetch(this.baseUrl + 'hausaufgaben/api.php?action=stats');
            const data = await resp.json();

            if (!data.success) return;

            const s = data.stats;
            this.updateStat('hw-stat-total', s.total);
            this.updateStat('hw-stat-month', s.this_month);
            this.updateStat('hw-stat-subjects', s.subjects);
            this.updateStat('hw-stat-size', s.total_size_formatted);
        } catch (err) {
            console.error('Stats load error:', err);
        }
    },

    updateStat(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    },

    // ========================================================================
    // SCHOOL INFO
    // ========================================================================

    bindSchoolInfo() {
        const saveBtn = document.getElementById('hw-school-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveSchoolInfo());
        }

        const editBtn = document.getElementById('hw-school-edit');
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                document.getElementById('hw-school-form').style.display = 'flex';
                document.getElementById('hw-school-display').style.display = 'none';
            });
        }
    },

    async saveSchoolInfo() {
        const grade = document.getElementById('hw-school-grade').value;
        const year = document.getElementById('hw-school-year-input').value;

        if (!grade || !year) {
            this.showError('Bitte Klassenstufe und Schuljahr angeben');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'update_school_info');
            formData.append('current_grade', grade);
            formData.append('current_school_year', year);
            formData.append('csrf_token', this.csrfToken);

            const resp = await fetch(this.baseUrl + 'hausaufgaben/api.php?action=update_school_info', {
                method: 'POST',
                body: formData,
            });
            const data = await resp.json();

            if (data.success) {
                // UI updaten
                const display = document.getElementById('hw-school-display');
                const form = document.getElementById('hw-school-form');
                if (display && form) {
                    display.style.display = 'flex';
                    form.style.display = 'none';
                    display.querySelector('.grade-chip').textContent = grade + '. Klasse';
                    display.querySelector('.year-chip').textContent = year;
                }
            } else {
                this.showError(data.error || 'Speichern fehlgeschlagen');
            }
        } catch (err) {
            this.showError('Fehler beim Speichern');
        }
    },

    // ========================================================================
    // SATS ANIMATION
    // ========================================================================

    showSatsAnimation(amount, message) {
        const popup = document.getElementById('hw-sats-popup');
        if (!popup) return;

        popup.querySelector('.amount').textContent = '+' + amount;
        popup.querySelector('.message').textContent = message;
        popup.classList.add('show');

        setTimeout(() => {
            popup.classList.remove('show');
        }, 2500);
    },

    // ========================================================================
    // HELPERS
    // ========================================================================

    showError(msg) {
        alert(msg);
    },

    getSubjectName(key) {
        const subjects = {
            mathematik: 'Mathematik', deutsch: 'Deutsch', englisch: 'Englisch',
            sachkunde: 'Sachkunde', biologie: 'Biologie', physik: 'Physik',
            chemie: 'Chemie', geschichte: 'Geschichte', erdkunde: 'Erdkunde',
            kunst: 'Kunst', musik: 'Musik', sport: 'Sport',
            religion_ethik: 'Religion/Ethik', informatik: 'Informatik', sonstige: 'Sonstige'
        };
        return subjects[key] || key;
    },

    escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
};
