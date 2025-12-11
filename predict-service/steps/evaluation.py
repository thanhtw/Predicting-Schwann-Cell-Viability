import logging
from typing import Tuple

import mlflow
import pandas as pd
# SỬA: Import các class đánh giá cho Hồi quy
from src.evaluation import R2Score, RMSE # Giả sử các class này tồn tại trong src.evaluation
from sklearn.base import RegressorMixin # SỬA: Đổi sang RegressorMixin
from zenml import step
from typing_extensions import Annotated
from zenml.client import Client

experiment_tracker = Client().active_stack.experiment_tracker


@step(experiment_tracker=experiment_tracker.name)
def evaluate_model(
    model: RegressorMixin, # SỬA: Input model là RegressorMixin
    X_test: pd.DataFrame, 
    y_test: pd.Series, 
) -> Tuple[
    Annotated[float, "r2_score"], # SỬA: Đổi Accuracy thành R2 Score
    Annotated[float, "rmse"],    # SỬA: Đổi F1 Score thành RMSE
]:

    """
    Evaluates the regression model using R2 Score and RMSE.
    Args:
        model: RegressorMixin (Mô hình hồi quy)
        X_test: pd.DataFrame
        y_test: pd.Series
    Returns:
        r2_score: float (Hệ số xác định)
        rmse: float (Căn bậc hai lỗi bình phương trung bình)
    """ 
    try:
        # 1. Dự đoán
        prediction = model.predict(X_test)
        
        # 2. Đánh giá R2 Score (Hệ số xác định)
        r2_class = R2Score()
        r2_score_value = r2_class.calculate_score(y_test, prediction)
        mlflow.log_metric("r2_score", r2_score_value)
        
        # 3. Đánh giá RMSE (Căn bậc hai lỗi bình phương trung bình)
        rmse_class = RMSE()
        rmse_value = rmse_class.calculate_score(y_test, prediction)
        mlflow.log_metric("rmse", rmse_value)
        
        # 4. (Tùy chọn) Bạn có thể thêm các chỉ số hồi quy khác như MSE, MAE nếu cần
        
        logging.info("Model evaluation completed.")
        return r2_score_value, rmse_value # Trả về R2 Score và RMSE
        
    except Exception as e:
        # Lưu ý: Cần đảm bảo các class R2Score và RMSE tồn tại trong src.evaluation
        # và có phương thức calculate_score(y_true, y_pred)
        logging.error(f"Error in evaluating regression model: {e}")
        raise e