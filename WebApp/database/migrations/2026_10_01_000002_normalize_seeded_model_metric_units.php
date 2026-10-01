<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert the bundled demo metrics from a 0-1 target scale to the
     * percentage scale used by the current Cell viability (%) pipeline.
     */
    public function up(): void
    {
        $this->convert([
            'Default ANN Model' => [0.0281, 0.1144, 281.0, 11.44],
            'Customize ANN Model' => [0.0200, 0.0896, 200.0, 8.96],
            'Preview Linear Regression Model' => [0.0265, 0.1310, 265.0, 13.10],
            'Preview Random Forest Model' => [0.0093, 0.0709, 93.0, 7.09],
            'Preview XGBoost Model' => [0.0095, 0.0725, 95.0, 7.25],
        ]);
    }

    public function down(): void
    {
        $this->convert([
            'Default ANN Model' => [281.0, 11.44, 0.0281, 0.1144],
            'Customize ANN Model' => [200.0, 8.96, 0.0200, 0.0896],
            'Preview Linear Regression Model' => [265.0, 13.10, 0.0265, 0.1310],
            'Preview Random Forest Model' => [93.0, 7.09, 0.0093, 0.0709],
            'Preview XGBoost Model' => [95.0, 7.25, 0.0095, 0.0725],
        ]);
    }

    /**
     * Only update untouched seeded rows; user-edited metrics are preserved.
     *
     * @param array<string, array{float, float, float, float}> $models
     */
    private function convert(array $models): void
    {
        foreach ($models as $name => [$oldMse, $oldMae, $newMse, $newMae]) {
            DB::table('ml_models')
                ->where('MLMName', $name)
                ->where('MSEValue', $oldMse)
                ->where('MAEValue', $oldMae)
                ->update([
                    'MSEValue' => $newMse,
                    'MAEValue' => $newMae,
                    'RMSEValue' => sqrt($newMse),
                ]);
        }
    }
};
