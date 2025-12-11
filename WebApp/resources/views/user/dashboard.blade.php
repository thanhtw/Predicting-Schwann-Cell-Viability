@extends('layouts.app')

@section('title', __('user_dashboard.title'))
@section('page-title', __('user_dashboard.dashboard'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('user_dashboard.dashboard') }}</li>
@endsection

@section('sidebar')
    <x-navigation.user-sidebar />
@endsection

@section('content')
<div class="row">
    <!-- Info Boxes using Small Box Component -->
    <x-ui.small-box 
        color="info" 
        :value="$totalPredictions" 
        :label="__('user_dashboard.total_predictions')" 
        icon="fas fa-calculator" 
        :link="route('user.history')" 
        :linkText="__('user_dashboard.view_history')" />
    
    <x-ui.small-box 
        color="success" 
        :value="__('user_dashboard.make_new')" 
        :label="__('user_dashboard.prediction')" 
        icon="fas fa-plus-circle" 
        :link="route('user.predict')" 
        :linkText="__('user_dashboard.start_predicting')" />
</div>

<div class="row">
    <div class="col-12">
        <x-ui.card :title="__('user_dashboard.recent_predictions')">
            @if($recentPredictions->count() > 0)
                <div class="table-responsive">
                    <table id="recent-predictions" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('user_dashboard.date') }}</th>
                                <th>{{ __('user_dashboard.model') }}</th>
                                <th>{{ __('user_dashboard.mxene') }}</th>
                                <th>{{ __('user_dashboard.peptide') }}</th>
                                <th>{{ __('user_dashboard.stimulation') }}</th>
                                <th>{{ __('user_dashboard.voltage') }}</th>
                                <th>{{ __('user_dashboard.result') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentPredictions as $prediction)
                            <tr>
                                <td>{{ $prediction->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $prediction->mlModel->MLMName }}</td>
                                <td>{{ $prediction->MXene }}</td>
                                <td>{{ $prediction->Peptide }}</td>
                                <td>{{ $prediction->Stimulation }}</td>
                                <td>{{ $prediction->Voltage }}</td>
                                <td><strong>{{ number_format($prediction->Result, 2) }}%</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-inbox text-muted icon-48"></i>
                    <h5 class="text-muted mt-2">{{ __('user_dashboard.no_predictions_yet') }}</h5>
                    <p class="text-muted">{{ __('user_dashboard.start_first_prediction') }}</p>
                    <a href="{{ route('user.predict') }}" class="btn btn-primary">
                        <i class="fas fa-calculator me-1"></i>
                        {{ __('user_dashboard.make_prediction') }}
                    </a>
                </div>
            @endif
        </x-ui.card>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <x-ui.card :title="__('user_dashboard.about_title')">
            <p>{{ __('user_dashboard.about_intro') }}</p>
            <ul>
                <li><strong>{{ __('user_dashboard.param_mxene') }}</strong> {{ __('user_dashboard.param_mxene_desc') }}</li>
                <li><strong>{{ __('user_dashboard.param_peptide') }}</strong> {{ __('user_dashboard.param_peptide_desc') }}</li>
                <li><strong>{{ __('user_dashboard.param_stimulation') }}</strong> {{ __('user_dashboard.param_stimulation_desc') }}</li>
                <li><strong>{{ __('user_dashboard.param_voltage') }}</strong> {{ __('user_dashboard.param_voltage_desc') }}</li>
            </ul>
            <p>{{ __('user_dashboard.about_result') }}</p>
        </x-ui.card>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin-tables.css') }}">
@endsection

@section('scripts')
<script src="{{ asset('js/user-dashboard-table.js') }}"></script>
@endsection
