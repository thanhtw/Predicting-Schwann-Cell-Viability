import argparse
from zenml.client import Client
from pipelines.training_pipeline import train_pipeline
from steps.config import ModelNameConfig
import os
import sys

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument('--data', type=str, required=True)
    args = parser.parse_args()

    data_path = args.data
    
    # Kiểm tra tính tồn tại của file (nếu cần)
    if not os.path.exists(data_path):
        print(f"LỖI: Không tìm thấy file dữ liệu tại đường dẫn: {data_path}")
        sys.exit(1)

    rf_config = ModelNameConfig.get_random_forest_config(
        n_estimators=150,
        max_depth=5
    )

    # 3. In URI của MLflow tracker
    print("ZenML Experiment Tracker URI:", Client().active_stack.experiment_tracker.get_tracking_uri())
    print(" Bắt đầu chạy training_pipeline với Random Forest Regressor...")

    # Gọi pipeline trực tiếp — KHÔNG dùng .run()
    run = train_pipeline(
        data_path=data_path,
        model_config=rf_config
    )

    print(f" Pipeline đã hoàn tất! Run name: {run.name}")