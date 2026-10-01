<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MLModel;

class MLModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only create default model if it doesn't exist
        if (MLModel::count() == 0) {
            MLModel::create([
                'MLMName' => 'Default ANN Model',
                'FilePath' => 'models/default_ann_model.keras',
                'LibType' => 'keras',
                'IsActive' => true,
                // Metrics use the same percentage scale as Cell viability (%).
                'MSEValue' => 281.0,
                'MAEValue' => 11.44,
                'RMSEValue' => sqrt(281.0),
            ]);

            MLModel::create([
                'MLMName' => 'Customize ANN Model',
                'FilePath' => 'models/custom_ann_model.keras',
                'LibType' => 'keras',
                'IsActive' => true,
                'MSEValue' => 200.0,
                'MAEValue' => 8.96,
                'RMSEValue' => sqrt(200.0),
            ]);

            MLModel::create([
                'MLMName' => 'Preview Linear Regression Model',
                'FilePath' => 'models/lr_augmented_model.pkl',
                'LibType' => 'sklearn',
                'IsActive' => true,
                'MSEValue' => 265.0,
                'MAEValue' => 13.10,
                'RMSEValue' => sqrt(265.0),
            ]);
            
            MLModel::create([
                'MLMName' => 'Preview Random Forest Model',
                'FilePath' => 'models/rf_augmented_model.pkl',
                'LibType' => 'sklearn',
                'IsActive' => true,
                'MSEValue' => 93.0,
                'MAEValue' => 7.09,
                'RMSEValue' => sqrt(93.0),
            ]);

            MLModel::create([
                'MLMName' => 'Preview XGBoost Model',
                'FilePath' => 'models/xgb_augmented_model.json',
                'LibType' => 'xgboost',
                'IsActive' => true,
                'MSEValue' => 95.0,
                'MAEValue' => 7.25,
                'RMSEValue' => sqrt(95.0),
            ]);
        }

        
    }
}
