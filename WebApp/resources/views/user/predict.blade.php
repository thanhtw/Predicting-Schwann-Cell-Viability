@extends('layouts.app')

@section('title', __('predict.title'))
@section('page-title', __('predict.title'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">{{ __('predict.breadcrumb_dashboard') }}</a></li>
    <li class="breadcrumb-item active">{{ __('predict.breadcrumb_predict') }}</li>
@endsection

@section('sidebar')
    <x-navigation.user-sidebar />
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/prediction-form.css') }}">
@endsection

@section('content')
<div class="prediction-form-container">
<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
</div>

<div class="row">
    <!-- Form Section (Left Side - Wider) -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-calculator me-2"></i>
                    {{ __('predict.page_title') }}
                </h3>
            </div>
            <div class="card-body">
                <form id="predictionForm">
                    @csrf
                    
                    <!-- AI Model Selection -->
                    <div class="form-group">
                        <label for="ml_model_id" class="form-label">
                            <i class="bi bi-cpu me-1"></i>
                            {{ __('predict.ai_model_selection') }}
                        </label>
                        <select class="form-control" id="ml_model_id" name="ml_model_id" required>
                            <option value="">{{ __('predict.select_model') }}</option>
                            @foreach($models as $model)
                                <option value="{{ $model->id }}" 
                                        data-lib-type="{{ $model->LibType }}"
                                        data-file-size="{{ $model->file_size }}"
                                        {{ $loop->first ? 'selected' : '' }}>
                                    {{ $model->MLMName }} ({{ ucfirst($model->LibType) }})
                                    @if($model->file_size > 0)
                                        - {{ $model->file_size }}MB
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted" id="model-info">
                            @if($models->count() > 0)
                                {{ __('predict.select_model_info') }} {{ $models->first()->MLMName }}
                            @else
                                {{ __('predict.no_models_available') }}
                            @endif
                        </small>
                        
                        <!-- Model Info Card -->
                        <div class="model-info-card" id="selectedModelInfo">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-1">
                                        <i class="bi bi-robot me-1"></i>
                                        <span id="selectedModelName">-</span>
                                    </h6>
                                    <small class="text-muted">
                                        {{ __('predict.library') }} <span class="model-badge" id="selectedModelBadge">-</span>
                                        | {{ __('predict.size') }} <strong id="selectedModelSize">-</strong>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <i class="bi bi-check-circle-fill text-success icon-24"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($models->count() === 0)
                        <!-- No Models Available Alert -->
                        <div class="no-models-alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>{{ __('predict.no_models_alert_title') }}</strong>
                            <p class="mb-0 mt-2">{{ __('predict.no_models_alert_desc') }}</p>
                        </div>
                    @endif

                    <!-- pc-MXene loading -->
                    <div class="form-group">
                        <label for="pc_mxene_loading" class="form-label">
                            <i class="bi bi-droplet me-1"></i>
                            {{ __('predict.pc_mxene_loading') }}
                        </label>
                        <input type="number" step="0.001" class="form-control" id="pc_mxene_loading" 
                               name="pc_mxene_loading" placeholder="{{ __('predict.pc_mxene_placeholder') }}" required>
                        <small class="form-text text-muted">{{ __('predict.pc_mxene_unit') }}</small>
                    </div>

                    <!-- Laminin peptide loading -->
                    <div class="form-group">
                        <label for="laminin_peptide_loading" class="form-label">
                            <i class="bi bi-capsule me-1"></i>
                            {{ __('predict.laminin_peptide_loading') }}
                        </label>
                        <input type="number" step="0.1" class="form-control" id="laminin_peptide_loading" 
                               name="laminin_peptide_loading" placeholder="{{ __('predict.laminin_placeholder') }}" required>
                        <small class="form-text text-muted">{{ __('predict.laminin_unit') }}</small>
                    </div>

                    <!-- Stimulation frequency -->
                    <div class="form-group">
                        <label for="stimulation_frequency" class="form-label">
                            <i class="bi bi-broadcast me-1"></i>
                            {{ __('predict.stimulation_frequency') }}
                        </label>
                        <input type="number" step="0.1" class="form-control" id="stimulation_frequency" 
                               name="stimulation_frequency" placeholder="{{ __('predict.stimulation_placeholder') }}" required>
                        <small class="form-text text-muted">{{ __('predict.stimulation_unit') }}</small>
                    </div>

                    <!-- Applied voltage -->
                    <div class="form-group">
                        <label for="applied_voltage" class="form-label">
                            <i class="bi bi-lightning me-1"></i>
                            {{ __('predict.applied_voltage') }}
                        </label>
                        <input type="number" step="0.1" class="form-control" id="applied_voltage" 
                               name="applied_voltage" placeholder="{{ __('predict.voltage_placeholder') }}" required>
                        <small class="form-text text-muted">{{ __('predict.voltage_unit') }}</small>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary btn-predict w-100" 
                                id="predictButton" {{ $models->count() === 0 ? 'disabled' : '' }}>
                            <i class="bi bi-calculator me-2"></i>
                            @if($models->count() > 0)
                                {{ __('predict.predict_button') }}
                            @else
                                {{ __('predict.no_models_button') }}
                            @endif
                        </button>
                        @if($models->count() === 0)
                            <small class="form-text text-danger mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                {{ __('predict.prediction_disabled') }}
                            </small>
                        @endif
                    </div>
                </form>
                
                <!-- Result Display -->
                <div id="predictionResult" class="mt-4 prediction-result-hidden">
                    <!-- Results will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Guidelines Section (Right Side - Narrower) -->
    <div class="col-lg-4">
        <div class="card guidelines-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-info-circle me-2"></i>
                    {{ __('predict.parameter_guidelines') }}
                </h3>
            </div>
            <div class="card-body">
                @if($models->count() > 0)
                    <div class="parameter-item mb-3">
                        <h6 class="mb-2">
                            <i class="bi bi-cpu text-info me-1"></i>
                            {{ __('predict.ai_model_selection') }}
                        </h6>
                        <p class="mb-2 small">
                            <strong>{{ __('predict.available_models') }}</strong> {{ $models->count() }}<br>
                            <strong>{{ __('predict.current_selection') }}</strong> {{ __('predict.dynamic_selection') }}
                        </p>
                        <div class="small">
                            @foreach($models as $model)
                                <span class="model-badge {{ strtolower($model->LibType) }} me-1 mb-1">
                                    {{ $model->MLMName }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="parameter-item">
                    <h6 class="mb-2">
                        <i class="bi bi-droplet text-primary me-1"></i>
                        {{ __('predict.pc_mxene_loading') }}
                    </h6>
                    <p class="mb-0 small">
                        <strong>{{ __('predict.description') }}</strong> {{ __('predict.pc_mxene_desc') }}
                    </p>
                </div>

                <div class="parameter-item">
                    <h6 class="mb-2">
                        <i class="bi bi-capsule text-success me-1"></i>
                        {{ __('predict.laminin_peptide_loading') }}
                    </h6>
                    <p class="mb-0 small">
                        <strong>{{ __('predict.description') }}</strong> {{ __('predict.laminin_desc') }}
                    </p>
                </div>

                <div class="parameter-item">
                    <h6 class="mb-2">
                        <i class="bi bi-broadcast text-warning me-1"></i>
                        {{ __('predict.stimulation_frequency') }}
                    </h6>
                    <p class="mb-0 small">
                        <strong>{{ __('predict.description') }}</strong> {{ __('predict.stimulation_desc') }}
                    </p>
                </div>

                <div class="parameter-item">
                    <h6 class="mb-2">
                        <i class="bi bi-lightning text-danger me-1"></i>
                        {{ __('predict.applied_voltage') }}
                    </h6>
                    <p class="mb-0 small">
                        <strong>{{ __('predict.description') }}</strong> {{ __('predict.voltage_desc') }}
                    </p>
                </div>

                <div class="mt-3 p-3 bg-light rounded">
                    <h6 class="text-primary">
                        <i class="bi bi-lightbulb me-1"></i>
                        {{ __('predict.tips') }}
                    </h6>
                    <ul class="small mb-0">
                        @if($models->count() > 0)
                            <li>{{ __('predict.tip_models') }}</li>
                            <li>{{ __('predict.tip_select') }}</li>
                        @endif
                        <li>{{ __('predict.tip_concentration') }}</li>
                        <li>{{ __('predict.tip_stimulation') }}</li>
                        <li>{{ __('predict.tip_optimal') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/prediction-form-config.js') }}"></script>
<script src="{{ asset('js/prediction-form.js') }}?v={{ filemtime(public_path('js/prediction-form.js')) }}"></script>
<script src="{{ asset('js/user-prediction.js') }}"></script>
<script>
// Register user prediction form configuration
window.PredictionFormConfig.register('user-predict', {
    submitUrl: '{{ route('user.predict.make') }}',
    csrfToken: '{{ csrf_token() }}',
    userType: 'user'
});
</script>
@endsection
