@extends('layouts.app')

@section('sidebar')
<x-navigation.admin-sidebar />
@endsection

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">
                <i class="bi bi-cpu"></i> {{ __('datasets.train_ml_model') }}
            </h2>

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('dashboard.title') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.datasets.index') }}">{{ __('nav.datasets') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('datasets.train_model') }}</li>
                </ol>
            </nav>

            <!-- Dataset Info Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-database"></i> {{ __('datasets.dataset_information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>{{ __('datasets.dataset_name') }}:</strong> {{ $dataset->DatasetName }}</p>
                            <p><strong>{{ __('datasets.description') }}:</strong> {{ $dataset->Description ?? __('models.na') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>{{ __('datasets.uploaded_by') }}:</strong> {{ $dataset->user->FullName ?? __('datasets.unknown_user') }}</p>
                            <p><strong>{{ __('datasets.upload_date') }}:</strong> {{ $dataset->UploadDate }}</p>
                            <p><strong>{{ __('datasets.file_path') }}:</strong> <code>{{ $dataset->FilePath }}</code></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Training Configuration Form -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> {{ __('datasets.training_configuration') }}</h5>
                </div>
                <div class="card-body">
                    <form id="trainingForm" action="{{ route('admin.datasets.train', $dataset->DatasetId) }}" method="POST">
                        @csrf

                        <!-- Model Type Selection -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">{{ __('datasets.model_type_required') }}</label>
                                <select name="model_type" id="model_type" class="form-select" required>
                                    <option value="random_forest" selected>Random Forest (RF)</option>
                                    <option value="xgboost">XGBoost</option>
                                    <option value="ann">Artificial Neural Network (ANN)</option>
                                </select>
                                <small class="text-muted">
                                    <strong>RF:</strong> {{ __('datasets.model_type_rf_desc') }} | 
                                    <strong>XGBoost:</strong> {{ __('datasets.model_type_xgb_desc') }} | 
                                    <strong>ANN:</strong> {{ __('datasets.model_type_ann_desc') }}
                                </small>
                            </div>
                        </div>

                        <!-- Training Method & Model Name -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('datasets.training_method') }}</label>
                                <select name="training_method" id="training_method" class="form-select">
                                    <option value="process">{{ __('datasets.training_method_process') }}</option>
                                    <option value="api" selected>{{ __('datasets.training_method_api') }}</option>
                                </select>
                                <small class="text-muted">{{ __('datasets.training_method_api_note') }}</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('datasets.model_name_optional') }}</label>
                                <input type="text" name="model_name" id="model_name" class="form-control" 
                                       placeholder="{{ __('datasets.auto_generated') }}"
                                       value="Model_{{ $dataset->DatasetName }}_{{ date('Ymd') }}">
                            </div>
                        </div>

                        <!-- Hyperparameters -->
                        <h6 class="border-bottom pb-2 mb-3">{{ __('datasets.hyperparameters') }}</h6>
                        
                        <!-- Random Forest / XGBoost Parameters -->
                        <div id="tree_params" class="hyperparameter-group">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="n_estimators" class="form-label">{{ __('datasets.n_estimators') }}</label>
                                    <input type="number" name="n_estimators" id="n_estimators" 
                                           class="form-control" value="100" min="10" max="1000">
                                    <small class="text-muted">{{ __('datasets.default') }}: 100</small>
                                </div>

                                <div class="col-md-4">
                                    <label for="max_depth" class="form-label">{{ __('datasets.max_depth') }}</label>
                                    <input type="number" name="max_depth" id="max_depth" 
                                           class="form-control" placeholder="None (unlimited)" min="1" max="50">
                                    <small class="text-muted">{{ __('datasets.max_depth_unlimited') }}</small>
                                </div>

                                <div class="col-md-4">
                                    <label for="learning_rate" class="form-label">{{ __('datasets.learning_rate') }} <span class="xgboost-only text-muted">({{ __('datasets.xgboost_only') }})</span></label>
                                    <input type="number" name="learning_rate" id="learning_rate" 
                                           class="form-control" value="0.1" min="0.001" max="1" step="0.01" disabled>
                                    <small class="text-muted">{{ __('datasets.default') }}: 0.1</small>
                                </div>
                            </div>
                        </div>

                        <!-- ANN Parameters -->
                        <div id="ann_params" class="hyperparameter-group" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="hidden_layers" class="form-label">{{ __('datasets.hidden_layers') }}</label>
                                    <input type="text" name="hidden_layers" id="hidden_layers" 
                                           class="form-control" value="64,32,16" placeholder="e.g., 64,32,16">
                                    <small class="text-muted">{{ __('datasets.hidden_layers_placeholder') }}</small>
                                </div>

                                <div class="col-md-4">
                                    <label for="epochs" class="form-label">{{ __('datasets.epochs') }}</label>
                                    <input type="number" name="epochs" id="epochs" 
                                           class="form-control" value="100" min="10" max="1000">
                                    <small class="text-muted">{{ __('datasets.default') }}: 100</small>
                                </div>

                                <div class="col-md-4">
                                    <label for="batch_size" class="form-label">{{ __('datasets.batch_size') }}</label>
                                    <input type="number" name="batch_size" id="batch_size" 
                                           class="form-control" value="32" min="8" max="256">
                                    <small class="text-muted">{{ __('datasets.default') }}: 32</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="learning_rate_ann" class="form-label">{{ __('datasets.learning_rate') }}</label>
                                    <input type="number" name="learning_rate_ann" id="learning_rate_ann" 
                                           class="form-control" value="0.001" min="0.0001" max="0.1" step="0.0001">
                                    <small class="text-muted">{{ __('datasets.default') }}: 0.001</small>
                                </div>

                                <div class="col-md-4">
                                    <label for="activation" class="form-label">{{ __('datasets.activation_function') }}</label>
                                    <select name="activation" id="activation" class="form-select">
                                        <option value="relu" selected>ReLU</option>
                                        <option value="tanh">Tanh</option>
                                        <option value="sigmoid">Sigmoid</option>
                                    </select>
                                    <small class="text-muted">{{ __('datasets.default') }}: ReLU</small>
                                </div>

                                <div class="col-md-4">
                                    <label for="dropout_rate" class="form-label">{{ __('datasets.dropout_rate') }}</label>
                                    <input type="number" name="dropout_rate" id="dropout_rate" 
                                           class="form-control" value="0.2" min="0" max="0.8" step="0.1">
                                    <small class="text-muted">{{ __('datasets.default') }}: 0.2</small>
                                </div>
                            </div>
                        </div>

                        <!-- Common Parameters -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="test_size" class="form-label">{{ __('datasets.test_size') }}</label>
                                <input type="number" name="test_size" id="test_size" 
                                       class="form-control" value="20" min="10" max="50" step="5">
                                <small class="text-muted">{{ __('datasets.default') }}: 20%</small>
                            </div>

                            <div class="col-md-6">
                                <label for="random_state" class="form-label">{{ __('datasets.random_state') }}</label>
                                <input type="number" name="random_state" id="random_state" 
                                       class="form-control" value="42" min="0">
                                <small class="text-muted">{{ __('datasets.random_state_desc') }}</small>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="{{ route('admin.datasets.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> {{ __('datasets.back_to_datasets') }}
                            </a>
                            
                            <div>
                                <!-- <button type="button" class="btn btn-outline-primary me-2" id="validateBtn">
                                    <i class="bi bi-check-circle"></i> {{ __('datasets.validate_settings') }}
                                </button> -->
                                <button type="submit" class="btn btn-success" id="trainBtn">
                                    <i class="bi bi-play-circle"></i> {{ __('datasets.start_training') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Training Progress Modal -->
            <div class="modal fade" id="progressModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="progressModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title" id="progressModalLabel">
                                <i class="bi bi-hourglass-split"></i> {{ __('datasets.training_progress') }}
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="progress mb-3" style="height: 35px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" 
                                     role="progressbar" style="width: 0%" id="progressBar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                    <strong style="font-size: 1.1em;">0%</strong>
                                </div>
                            </div>
                            <div id="progressMessage" class="text-center mb-3">
                                <h6 class="mb-0">{{ __('datasets.initializing_training') }}</h6>
                            </div>
                            <div class="card">
                                <div class="card-header bg-light">
                                    <strong><i class="bi bi-terminal"></i> {{ __('datasets.training_logs') }}</strong>
                                </div>
                                <div class="card-body p-2" id="trainingLog" style="max-height: 300px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 0.85em; background-color: #1e1e1e; color: #d4d4d4;">
                                    <div style="color: #858585;">⏳ {{ __('datasets.waiting_to_start') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <small class="text-muted me-auto">
                                <i class="bi bi-info-circle"></i> {{ __('datasets.do_not_close') }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎬 Training page JavaScript loaded!');
    
    const form = document.getElementById('trainingForm');
    const trainBtn = document.getElementById('trainBtn');
    const modelTypeSelect = document.getElementById('model_type');
    const modelNameInput = document.getElementById('model_name');
    const treeParams = document.getElementById('tree_params');
    const annParams = document.getElementById('ann_params');
    const learningRateInput = document.getElementById('learning_rate');
    const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
    const progressBar = document.getElementById('progressBar');
    const progressMessage = document.getElementById('progressMessage');
    const trainingLog = document.getElementById('trainingLog');

    console.log('Progress modal:', progressModal);
    console.log('Train button:', trainBtn);

    const PREDICT_SERVICE_URL = '{{ config("services.predict_service.url") }}';
    const DATASETS_INDEX_URL = '{{ route("admin.datasets.index") }}';
    const CSRF_TOKEN = '{{ csrf_token() }}';
    
    console.log('PREDICT_SERVICE_URL:', PREDICT_SERVICE_URL);
    console.log('DATASETS_INDEX_URL:', DATASETS_INDEX_URL);

    let sessionId = null;
    let progressInterval = null;

    // Handle model type change
    modelTypeSelect.addEventListener('change', function() {
        const modelType = this.value;
        
        // Toggle parameter groups
        if (modelType === 'ann') {
            treeParams.style.display = 'none';
            annParams.style.display = 'block';
        } else {
            treeParams.style.display = 'block';
            annParams.style.display = 'none';
            
            // Enable/disable learning rate for XGBoost
            if (modelType === 'xgboost') {
                learningRateInput.disabled = false;
            } else {
                learningRateInput.disabled = true;
            }
        }
        
        // Update model name prefix
        const datasetName = '{{ $dataset->DatasetName }}';
        const date = '{{ date("Ymd") }}';
        let prefix = 'Model';
        
        if (modelType === 'random_forest') {
            prefix = 'RF';
        } else if (modelType === 'xgboost') {
            prefix = 'XGB';
        } else if (modelType === 'ann') {
            prefix = 'ANN';
        }
        
        modelNameInput.value = `${prefix}_${datasetName}_${date}`;
    });

    // Function to start progress polling
    function startProgressPolling(sid) {
        progressInterval = setInterval(async function() {
            try {
                const response = await fetch(`${PREDICT_SERVICE_URL}/progress/${sid}`);
                const data = await response.json();
                
                if (data.success && data.progress) {
                    const progress = data.progress;
                    
                    // Update progress bar
                    const progressPercent = Math.round(progress.progress);
                    progressBar.style.width = progressPercent + '%';
                    progressBar.textContent = progressPercent + '%';
                    
                    // Update message
                    progressMessage.innerHTML = `<p class="mb-0">${progress.message}</p>`;
                    
                    // Add log entry with color coding
                    const logEntry = document.createElement('div');
                    logEntry.style.marginBottom = '4px';
                    logEntry.style.color = '#4EC9B0'; // Cyan color for logs
                    const timestamp = new Date().toLocaleTimeString();
                    logEntry.innerHTML = `<span style="color: #858585;">[${timestamp}]</span> <span style="color: #DCDCAA;">${progress.message}</span>`;
                    trainingLog.appendChild(logEntry);
                    trainingLog.scrollTop = trainingLog.scrollHeight;
                    
                    // Check if completed or failed
                    if (progress.status === 'completed') {
                        clearInterval(progressInterval);
                        progressBar.classList.remove('progress-bar-animated', 'bg-info');
                        progressBar.classList.add('bg-success');
                        progressMessage.innerHTML = `<h6 class="mb-0 text-success"><i class="bi bi-check-circle"></i> Training completed successfully!</h6>`;
                        
                        setTimeout(function() {
                            progressModal.hide();
                            window.location.href = DATASETS_INDEX_URL;
                        }, 3000);
                    } else if (progress.status === 'failed') {
                        clearInterval(progressInterval);
                        progressBar.classList.remove('progress-bar-animated', 'bg-info');
                        progressBar.classList.add('bg-danger');
                        progressMessage.innerHTML = `<h6 class="mb-0 text-danger"><i class="bi bi-x-circle"></i> ${progress.error || 'Training failed'}</h6>`;
                        
                        trainBtn.disabled = false;
                        trainBtn.innerHTML = '<i class="bi bi-play-circle"></i> Start Training';
                        
                        setTimeout(function() {
                            progressModal.hide();
                        }, 5000);
                    }
                }
            } catch (error) {
                console.error('Error fetching progress:', error);
            }
        }, 1000); // Poll every 1 second
    }

    // Form submission with real progress tracking
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const modelType = modelTypeSelect.value;
        let modelName = 'model';
        if (modelType === 'random_forest') modelName = 'Random Forest';
        else if (modelType === 'xgboost') modelName = 'XGBoost';
        else if (modelType === 'ann') modelName = 'ANN';
        
        if (!confirm(`Start training ${modelName} model? This may take several minutes.`)) {
            return;
        }

        try {
            console.log('🚀 Starting training process...');
            console.log('Predict Service URL:', PREDICT_SERVICE_URL);
            
            // Generate session ID
            console.log('📝 Generating session ID...');
            const sessionResponse = await fetch(`${PREDICT_SERVICE_URL}/progress/generate-session`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('Session response status:', sessionResponse.status);
            const sessionData = await sessionResponse.json();
            console.log('Session data:', sessionData);
            
            if (!sessionData.success) {
                throw new Error('Failed to generate session ID');
            }
            
            sessionId = sessionData.session_id;
            console.log('✅ Generated session ID:', sessionId);
            
            // Show progress modal
            progressModal.show();
            trainBtn.disabled = true;
            trainBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Training...';
            
            console.log('✅ Progress modal shown');
            
            // Clear previous logs and reset progress
            trainingLog.innerHTML = '<div style="color: #858585;">⏳ Initializing training session...</div>';
            progressBar.style.width = '0%';
            progressBar.innerHTML = '<strong style="font-size: 1.1em;">0%</strong>';
            progressBar.classList.remove('bg-success', 'bg-danger');
            progressBar.classList.add('bg-info', 'progress-bar-animated');
            
            // Start polling for progress
            console.log('🔄 Starting progress polling...');
            startProgressPolling(sessionId);
            
            // Get form data
            const formData = new FormData(form);
            const formDataObj = {};
            formData.forEach((value, key) => {
                formDataObj[key] = value;
            });
            
            // Add session_id to form data
            formDataObj.session_id = sessionId;
            
            // Submit form via AJAX
            console.log('📤 Submitting training request...');
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(formDataObj)
            });
            
            console.log('Training response status:', response.status);
            
            if (!response.ok) {
                const errorData = await response.json();
                console.error('❌ Training error:', errorData);
                throw new Error(errorData.error || 'Training request failed');
            }
            
            const responseData = await response.json();
            console.log('✅ Training submitted successfully:', responseData);
            
        } catch (error) {
            console.error('❌ Error starting training:', error);
            alert('Failed to start training: ' + error.message);
            trainBtn.disabled = false;
            trainBtn.innerHTML = '<i class="bi bi-play-circle"></i> Start Training';
            progressModal.hide();
            
            // Stop polling if started
            if (progressInterval) {
                clearInterval(progressInterval);
            }
            
            if (progressInterval) {
                clearInterval(progressInterval);
            }
        }
    });
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (progressInterval) {
            clearInterval(progressInterval);
        }
    });
});
</script>
@endsection
