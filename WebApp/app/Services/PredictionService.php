<?php

namespace App\Services;

use App\Models\Prediction;
use App\Models\User;
use App\Models\MLModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PredictionService
{
    /**
     * Generate unique PredictionCode
     * 
     * @return string
     */
    public function generatePredictionCode(): string
    {
        do {
            // Generate random prediction code: PRED + timestamp + random
            $code = 'PRED' . date('Ymd') . '_' . mt_rand(100000, 999999);
            $exists = Prediction::where('PredictionCode', $code)->exists();
        } while ($exists);
        
        return $code;
    }

    /**
     * Make prediction using ML model
     * 
     * @param array $inputData
     * @param MLModel $model
     * @param User $user
     * @return array
     */
    public function makePrediction(array $inputData, MLModel $model, User $user): array
    {
        try {
            // Prepare prediction request
            $requestData = [
                'pc_mxene_loading' => $inputData['pc_mxene_loading'],
                'laminin_peptide_loading' => $inputData['laminin_peptide_loading'],
                'stimulation_frequency' => $inputData['stimulation_frequency'],
                'applied_voltage' => $inputData['applied_voltage'],
                'model_path' => $model->ModelPath
            ];

            // Make API call to prediction service
            $response = Http::timeout(30)->post(config('app.prediction_api_url', 'http://localhost:5000') . '/predict/model', $requestData);

            if (!$response->successful()) {
                throw new \Exception('Prediction API returned error: ' . $response->body());
            }

            $predictionResult = $response->json();

            // Validate response structure (Flask returns 'prediction' key)
            if (!isset($predictionResult['prediction'])) {
                throw new \Exception('Invalid prediction response format');
            }

            // Save prediction to database
            $prediction = $this->savePredictionResult(
                $inputData,
                $predictionResult,
                $model,
                $user
            );

            return [
                'success' => true,
                'data' => [
                    'prediction' => $prediction,
                    'viability_score' => $predictionResult['prediction'], // Flask returns 'prediction' key
                    'confidence' => $predictionResult['confidence'] ?? null,
                    'model_info' => [
                        'name' => $model->ModelName,
                        'version' => $model->Version,
                        'description' => $model->Description
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Prediction failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'model_id' => $model->id,
                'input_data' => $inputData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Save prediction result to database
     * 
     * @param array $inputData
     * @param array $predictionResult
     * @param MLModel $model
     * @param User $user
     * @return Prediction
     */
    private function savePredictionResult(array $inputData, array $predictionResult, MLModel $model, User $user): Prediction
    {
        return Prediction::create([
            'PredictionCode' => $this->generatePredictionCode(),
            'user_id' => $user->id,
            'model_id' => $model->id,
            'pc_MXene_loading' => $inputData['pc_mxene_loading'],
            'Laminin_peptide_loading' => $inputData['laminin_peptide_loading'],
            'Stimulation_frequency' => $inputData['stimulation_frequency'],
            'Applied_voltage' => $inputData['applied_voltage'],
            'ViabilityScore' => $predictionResult['prediction'], // Flask returns 'prediction' key
            'Confidence' => $predictionResult['confidence'] ?? null,
            'PredictionDate' => now(),
        ]);
    }

    /**
     * Get user prediction history with pagination
     * 
     * @param User $user
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUserPredictions(User $user, int $perPage = 10)
    {
        return Prediction::with(['user', 'model'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get prediction statistics for user
     * 
     * @param User $user
     * @return array
     */
    public function getUserPredictionStats(User $user): array
    {
        $predictions = $user->predictions();

        return [
            'total_predictions' => $predictions->count(),
            'recent_predictions' => $predictions->where('created_at', '>=', now()->subDays(30))->count(),
            'avg_viability' => round($predictions->avg('ViabilityScore') ?? 0, 2),
            'max_viability' => round($predictions->max('ViabilityScore') ?? 0, 2),
            'min_viability' => round($predictions->min('ViabilityScore') ?? 0, 2),
            'last_prediction' => $predictions->latest()->first()?->created_at,
        ];
    }

    /**
     * Delete prediction
     * 
     * @param Prediction $prediction
     * @param User $user
     * @return bool
     */
    public function deletePrediction(Prediction $prediction, User $user): bool
    {
        // Check if user owns this prediction or is admin
        if ($prediction->user_id !== $user->id && $user->role_id !== 1) {
            return false;
        }

        return $prediction->delete();
    }

    /**
     * Get popular input parameter ranges
     * 
     * @return array
     */
    public function getPopularParameterRanges(): array
    {
        return [
            'pc_mxene_loading' => [
                'min' => Prediction::min('pc_MXene_loading') ?? 0,
                'max' => Prediction::max('pc_MXene_loading') ?? 100,
                'avg' => round(Prediction::avg('pc_MXene_loading') ?? 50, 2),
            ],
            'laminin_peptide_loading' => [
                'min' => Prediction::min('Laminin_peptide_loading') ?? 0,
                'max' => Prediction::max('Laminin_peptide_loading') ?? 100,
                'avg' => round(Prediction::avg('Laminin_peptide_loading') ?? 50, 2),
            ],
            'stimulation_frequency' => [
                'min' => Prediction::min('Stimulation_frequency') ?? 0,
                'max' => Prediction::max('Stimulation_frequency') ?? 1000,
                'avg' => round(Prediction::avg('Stimulation_frequency') ?? 100, 2),
            ],
            'applied_voltage' => [
                'min' => Prediction::min('Applied_voltage') ?? 0,
                'max' => Prediction::max('Applied_voltage') ?? 10,
                'avg' => round(Prediction::avg('Applied_voltage') ?? 3, 2),
            ],
        ];
    }
}
