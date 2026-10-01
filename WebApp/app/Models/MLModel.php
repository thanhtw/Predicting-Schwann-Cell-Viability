<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MLModel extends Model
{
    use HasFactory;

    protected $table = 'ml_models';

    protected $fillable = [
        'MLMName',
        'FilePath',
        'LibType',
        'IsActive',
        'MSEValue',
        'MAEValue',
        'R2Value',
        'RMSEValue',
        'ZenmlPipelineId',
        'TrainedBy',
        'DatasetId',
        'CreatedDate',
        'UpdatedDate',
        'mlflow_run_id',
        'mlflow_experiment_id',
    ];

    protected $casts = [
        'IsActive' => 'boolean',
        'CreatedDate' => 'datetime',
        'UpdatedDate' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function predictions()
    {
        return $this->hasMany(Prediction::class, 'ml_model_id');
    }

    public function dataset()
    {
        return $this->belongsTo(Dataset::class, 'DatasetId');
    }

    public function trainedByUser()
    {
        return $this->belongsTo(User::class, 'TrainedBy');
    }

    // Alias for relationship
    public function trainer()
    {
        return $this->trainedByUser();
    }

    /**
     * Accessors & Utility Functions
     */
    // Alias accessors for consistent naming
    public function getModelNameAttribute()
    {
        return $this->MLMName;
    }

    public function getLibraryTypeAttribute()
    {
        return $this->LibType;
    }

    public function getMSEAttribute()
    {
        // Stored metrics use Cell viability (%) units. Present error metrics
        // on the normalized 0-1 viability scale for consistent UI reporting.
        return $this->MSEValue === null ? null : $this->MSEValue / 10000;
    }

    public function getMAEAttribute()
    {
        return $this->MAEValue === null ? null : $this->MAEValue / 100;
    }

    public function getRMSEAttribute()
    {
        $rmse = $this->RMSEValue;
        if ($rmse === null && $this->MSEValue !== null) {
            $rmse = sqrt($this->MSEValue);
        }

        return $rmse === null ? null : $rmse / 100;
    }

    public function getR2Attribute()
    {
        // Return stored R2 value, or default to 0 if not stored
        return $this->R2Value ?? 0;
    }

    public function getTrainedDateAttribute()
    {
        return $this->CreatedDate;
    }

    public function getAbsolutePathAttribute()
    {
        return public_path($this->FilePath);
    }

    public function fileExists()
    {
        return file_exists($this->getAbsolutePathAttribute());
    }

    public function getFileSizeAttribute()
    {
        if ($this->fileExists()) {
            $sizeInBytes = filesize($this->getAbsolutePathAttribute());
            return round($sizeInBytes / 1024 / 1024, 2); // MB
        }
        return 0;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('IsActive', true);
    }

    /**
     * Static helper: get default active model
     */
    public static function getDefaultModel()
    {
        return static::active()->first();
    }
}
