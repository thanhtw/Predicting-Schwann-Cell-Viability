/**
 * Prediction Form JavaScript
 * Handles form submission, validation, and model selection
 */

class PredictionForm {
    constructor(formId, options = {}) {
        this.form = document.getElementById(formId);
        this.loadingOverlay = document.getElementById('loadingOverlay');
        this.resultDiv = document.getElementById('predictionResult');
        this.options = {
            submitUrl: options.submitUrl || '/predict',
            csrfToken: options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            userType: options.userType || 'user', // 'user' or 'admin'
            ...options
        };
        
        this.init();
    }
    
    init() {
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            this.setupInputValidation();
            this.setupModelSelection();
        }
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        this.showLoading();
        this.hideResult();
        
        try {
            const formData = new FormData(this.form);
            const data = this.prepareData(formData);
            
            // Validate data
            this.validateData(data);
            
            const response = await fetch(this.options.submitUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.options.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            this.displayResult(result);
            
        } catch (error) {
            this.displayError(error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    prepareData(formData) {
        return {
            pc_mxene_loading: parseFloat(formData.get('pc_mxene_loading')),
            laminin_peptide_loading: parseFloat(formData.get('laminin_peptide_loading')),
            stimulation_frequency: parseFloat(formData.get('stimulation_frequency')),
            applied_voltage: parseFloat(formData.get('applied_voltage')),
            ml_model_id: parseInt(formData.get('ml_model_id'))
        };
    }
    
    validateData(data) {
        const validations = [
            { field: 'ml_model_id', condition: !data.ml_model_id || isNaN(data.ml_model_id), message: 'Please select an AI model' },
            { field: 'pc_mxene_loading', condition: data.pc_mxene_loading < 0 || data.pc_mxene_loading > 0.3, message: 'pc-MXene loading must be between 0 and 0.3' },
            { field: 'laminin_peptide_loading', condition: data.laminin_peptide_loading < 0 || data.laminin_peptide_loading > 150, message: 'Laminin peptide must be between 0 and 150' },
            { field: 'stimulation_frequency', condition: data.stimulation_frequency < 0 || data.stimulation_frequency > 3, message: 'Stimulation frequency must be between 0 and 3' },
            { field: 'applied_voltage', condition: data.applied_voltage < 0 || data.applied_voltage > 3, message: 'Applied voltage must be between 0 and 3' }
        ];
        
        for (const validation of validations) {
            if (validation.condition) {
                throw new Error(validation.message);
            }
        }
    }
    
    displayResult(result) {
        if (result.success) {
            const accessType = this.options.userType === 'admin' ? '(Admin Access)' : '(User Access)';
            
            // 🆕 Check if using MLflow or file-based model
            const modelSource = result.model_source || 'file';
            const isMLflow = modelSource === 'mlflow';
            const cached = result.cached || false;
            
            // Build model info HTML
            let modelInfoHtml = `<i class="bi bi-robot me-1"></i>Prediction completed using <strong>${result.model_used || 'AI model'}</strong> ${accessType}`;
            
            if (isMLflow) {
                // MLflow model with additional info
                const cacheIcon = cached ? '<i class="bi bi-lightning-fill text-warning"></i>' : '<i class="bi bi-cloud-download text-info"></i>';
                const cacheText = cached ? '' : 'Loaded from MLflow';
                
                modelInfoHtml = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <i class="bi bi-robot me-1"></i>Prediction using <strong>${result.model_used || 'AI model'}</strong> ${accessType}
                        </div>
                    </div>
                    ${result.mlflow_run_id ? `<small class="text-muted d-block mt-1"><i class="bi bi-tag me-1"></i>MLflow Run: ${result.mlflow_run_id.substring(0, 8)}...</small>` : ''}
                `;
            }
            
            this.resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <h4><i class="bi bi-check-circle"></i> Prediction Result</h4>
                    <p class="mb-2"><strong>Schwann Cell Viability: </strong><span class="result-value">${result.prediction}%</span></p>
                    <small>${modelInfoHtml}</small>
                </div>
            `;
        } else {
            this.displayError(result.error || 'Unknown error occurred');
        }
        this.showResult();
    }
    
    displayError(message) {
        this.resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <h4><i class="bi bi-exclamation-triangle"></i> Error</h4>
                <p class="mb-0">${message}</p>
            </div>
        `;
        this.showResult();
    }
    
    showLoading() {
        if (this.loadingOverlay) {
            this.loadingOverlay.style.display = 'flex';
        }
        
        // Simple button state change
        const submitBtn = this.form.querySelector('.btn-predict');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
        }
    }
    
    hideLoading() {
        if (this.loadingOverlay) {
            this.loadingOverlay.style.display = 'none';
        }
        
        // Reset button
        const submitBtn = this.form.querySelector('.btn-predict');
        if (submitBtn) {
            submitBtn.disabled = false;
            const userType = this.options.userType === 'admin' ? '(ADMIN)' : '';
            submitBtn.innerHTML = `<i class="bi bi-calculator me-2"></i>Predict Cell Viability ${userType}`;
        }
    }
    
    showResult() {
        if (this.resultDiv) {
            this.resultDiv.style.display = 'block';
        }
    }
    
    hideResult() {
        if (this.resultDiv) {
            this.resultDiv.style.display = 'none';
        }
    }
    
    setupInputValidation() {
        // Simple validation without complex effects
        const inputs = this.form.querySelectorAll('input[type="number"]');
        
        inputs.forEach(input => {
            input.addEventListener('input', (e) => {
                const value = parseFloat(e.target.value);
                const min = parseFloat(e.target.min);
                const max = parseFloat(e.target.max);
                
                if (!isNaN(value) && !isNaN(min) && !isNaN(max)) {
                    if (value < min || value > max) {
                        e.target.classList.add('is-invalid');
                    } else {
                        e.target.classList.remove('is-invalid');
                    }
                }
            });
        });
    }
    
    setupModelSelection() {
        const modelSelect = document.getElementById('ml_model_id');
        const modelInfoCard = document.getElementById('selectedModelInfo');
        
        if (modelSelect && modelInfoCard) {
            // Auto-select first model on page load if no selection
            if (modelSelect.options.length > 1 && !modelSelect.value) {
                modelSelect.selectedIndex = 1; // Skip the "Select a model..." option
            }
            
            this.updateModelInfo();
            modelSelect.addEventListener('change', () => this.updateModelInfo());
        }
    }
    
    updateModelInfo() {
        const modelSelect = document.getElementById('ml_model_id');
        const modelInfoCard = document.getElementById('selectedModelInfo');
        const modelName = document.getElementById('selectedModelName');
        const modelBadge = document.getElementById('selectedModelBadge');
        const modelSize = document.getElementById('selectedModelSize');
        
        // 🆕 MLflow elements
        const mlflowBadge = document.getElementById('mlflowBadge');
        const mlflowRunInfo = document.getElementById('mlflowRunInfo');
        const mlflowRunId = document.getElementById('mlflowRunId');
        
        if (!modelSelect || !modelInfoCard) return;
        
        const selectedOption = modelSelect.options[modelSelect.selectedIndex];
        
        if (selectedOption && selectedOption.value) {
            const libType = selectedOption.dataset.libType || 'unknown';
            const fileSize = selectedOption.dataset.fileSize || 0;
            const modelNameText = selectedOption.text.split(' (')[0].replace('<span class="badge bg-info">MLflow</span>', '').trim();
            
            // 🆕 MLflow data
            const hasMlflow = selectedOption.dataset.hasMlflow === 'true';
            const mlflowRunIdValue = selectedOption.dataset.mlflowRunId || '';
            
            if (modelName) modelName.textContent = modelNameText;
            if (modelBadge) {
                modelBadge.textContent = libType.toUpperCase();
                modelBadge.className = `model-badge ${libType.toLowerCase()}`;
            }
            if (modelSize) {
                modelSize.textContent = fileSize > 0 ? `${fileSize}MB` : 'Unknown';
            }
            
            // 🆕 Show/hide MLflow badge and info
            if (mlflowBadge) {
                mlflowBadge.style.display = hasMlflow ? 'inline-block' : 'none';
            }
            if (mlflowRunInfo && mlflowRunId) {
                if (hasMlflow && mlflowRunIdValue) {
                    mlflowRunInfo.style.display = 'block';
                    mlflowRunId.textContent = mlflowRunIdValue.substring(0, 12) + '...';
                } else {
                    mlflowRunInfo.style.display = 'none';
                }
            }
            
            modelInfoCard.classList.add('show');
        } else {
            modelInfoCard.classList.remove('show');
        }
    }
    
    // Utility methods
    clearForm() {
        if (this.form) {
            this.form.reset();
            this.hideResult();
            this.updateModelInfo();
        }
    }
    
    resetValidation() {
        const inputs = this.form.querySelectorAll('.is-invalid');
        inputs.forEach(input => input.classList.remove('is-invalid'));
    }
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // This will be overridden by specific page implementations
    if (typeof window.initPredictionForm === 'function') {
        window.initPredictionForm();
    }
});
