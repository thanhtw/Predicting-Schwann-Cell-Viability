from pydantic import BaseModel
from typing import Dict, Any, Optional, List


class ModelNameConfig(BaseModel):
    """Cấu hình cho các mô hình (Model Configurations)"""

    # Đặt mặc định là mô hình Random Forest Regressor
    model_name: str = "RandomForest" 
    hyperparameters: Optional[Dict[str, Any]] = {}

    @classmethod
    def get_random_forest_config(cls, n_estimators: int = 100, max_depth: Optional[int] = None):
        """
        Trả về cấu hình cho Random Forest Regressor, bao gồm các siêu tham số.
        """
        return cls(
            model_name="RandomForest",
            hyperparameters={
                "n_estimators": n_estimators,
                "max_depth": max_depth,
                "random_state": 42
            }
        )

    @classmethod
    def get_linear_regression_config(cls):
        """Trả về cấu hình cho Linear Regression Model (không có siêu tham số đặc biệt)"""
        return cls(
            model_name="LinearRegression",
            hyperparameters={}
        )
    
#--------------------------------------------------------------------------------------
    
class ExperimentConfig(BaseModel):
    """Cấu hình cho việc chạy experiment với nhiều models và tiêu chí đánh giá"""
    
    # Danh sách các mô hình hồi quy để so sánh
    models_to_compare: List[str] = [
        "LinearRegression",
        "RandomForest",
        # Bạn có thể thêm các mô hình hồi quy khác (ví dụ: "SVR", "XGBoost")
    ]
    
    cross_validation_folds: int = 5
    test_size: float = 0.2
    random_state: int = 42
    
    # Tiêu chí triển khai (Deployment criteria)
    # SỬA: Thay đổi sang R2 Score (R2 Score càng gần 1 càng tốt)
    # R2 Score là chỉ số cho bài toán Hồi quy.
    min_r2_for_deployment: float = 0.85 # Ví dụ: Yêu cầu R2 Score tối thiểu 85%
    
    # Tiêu chí cải thiện mô hình
    min_improvement_threshold: float = 0.05 # Mức cải thiện tối thiểu 5%
