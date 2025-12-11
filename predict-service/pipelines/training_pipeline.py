from zenml import pipeline

# Giả định tất cả các imports này đều đúng đường dẫn
from steps.ingest_data import ingest_df
from steps.clean_data import clean_df
from steps.model_train import train_model
from steps.evaluation import evaluate_model 
# Import ModelNameConfig từ đường dẫn cấu hình
from steps.config import ModelNameConfig 
from typing import Optional # Thêm import Optional cho model_config

@pipeline(
    enable_cache=False,
    settings={
        "experiment_tracker": {
            "name": "mlflow_tracker_customer"
        }
    }
)
def train_pipeline(data_path: str, model_config: Optional[ModelNameConfig] = None):
    # 1. Ingest Data
    df = ingest_df(data_path)
    
    # 2. Clean and Divide Data (Hồi quy)
    X_train, X_test, y_train, y_test = clean_df(df)
    
    # 3. Handle Config
    # Use provided config or default
    if model_config is None:
        config = ModelNameConfig()
    else:
        config = model_config
        
    # 4. Train Model (Hồi quy)
    model = train_model(X_train, X_test, y_train, y_test, config)
    
    # 5. Evaluate Model (Hồi quy)
    # SỬA: Thay thế chỉ số Phân loại (accuracy, f1_score) bằng chỉ số Hồi quy (r2_score, rmse)
    r2_score, rmse = evaluate_model(model, X_test, y_test)
    
    # In ra kết quả cuối cùng để theo dõi
    print(f"Evaluation Results for Regression Model:")
    print(f"R2 Score = {r2_score}")
    print(f"RMSE = {rmse}")
