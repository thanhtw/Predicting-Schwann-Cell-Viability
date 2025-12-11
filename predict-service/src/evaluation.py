import logging
from abc import ABC, abstractmethod

import numpy as np
# SỬA: Import các metrics hồi quy (r2_score, mean_squared_error)
from sklearn.metrics import r2_score, mean_squared_error

class Evaluation(ABC):
    """
    Abstract Class defining the strategy for evaluation our models
    """
    @abstractmethod
    def calculate_score(self, y_true: np.ndarray, y_pred: np.ndarray):
        """
        Calculates the scores for the model
        Args:
            y_true: True target values (Giá trị mục tiêu thực tế)
            y_pred: Predicted target values (Giá trị mục tiêu dự đoán)
        Returns:
            Score: float
        """
        pass

class R2Score(Evaluation):
    """
    Evaluation strategy that uses R2 Score (Hệ số xác định)
    """
    def calculate_score(self, y_true: np.ndarray, y_pred: np.ndarray):
        try:
            logging.info("Entered the calculate_score method of the R2Score class")
            # Tính toán R2 Score
            r2 = r2_score(y_true, y_pred)
            logging.info(f"The R2 score value is: {r2}")
            return r2
        except Exception as e:
            logging.error(
                f"Exception occurred in calculate_score method of the R2Score class. Exception message: {e}")
            raise e

class RMSE(Evaluation):
    """
    Evaluation strategy that uses RMSE (Root Mean Squared Error)
    """
    def calculate_score(self, y_true: np.ndarray, y_pred: np.ndarray):
        try:
            logging.info("Entered the calculate_score method of the RMSE class")
            # 1. Tính Mean Squared Error (MSE)
            mse = mean_squared_error(y_true, y_pred)
            # 2. Tính Root Mean Squared Error (RMSE)
            rmse = np.sqrt(mse)
            
            logging.info(f"The RMSE value is: {rmse}")
            return rmse
        except Exception as e:
            logging.error(
                f"Exception occurred in calculate_score method of the RMSE class. Exception message: {e}")
            raise e