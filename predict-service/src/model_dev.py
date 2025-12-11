import logging
from abc import ABC, abstractmethod
from sklearn.base import RegressorMixin # SỬA: Thay đổi từ ClassifierMixin sang RegressorMixin

import pandas as pd
from sklearn.ensemble import RandomForestRegressor # SỬA: Dùng RandomForestRegressor
from sklearn.linear_model import LinearRegression 

class Model(ABC):
    """
    Abstract base class for all models.
    """

    @abstractmethod
    def train(self, x_train, y_train):
        """
        Trains the model 
        Args:
            x_train: Training data
            y_train: Target data
        Returns:
            RegressorMixin
        """
        pass

# Giữ nguyên LinearRegressionModel cho bài toán hồi quy
class LinearRegressionModel(Model):
    """
    Linear Regression Model 
    """
    def train(self, X_train, y_train, **kwargs) -> RegressorMixin: # Thêm kiểu trả về
        try:
            reg = LinearRegression(**kwargs)
            reg.fit(X_train, y_train)
            logging.info("Linear Regression Model training completed.")
            return reg
        except Exception as e:
            logging.error(f"Error in training Linear Regression model: {e}")
            raise e
        

class RandomForestModel(Model):
    """
    Random Forest Regression Model cho bài toán Cell Viability (Hồi quy).
    """
    def train(self, X_train, y_train, **kwargs) -> RegressorMixin: # SỬA: Đổi kiểu trả về sang RegressorMixin
        try:
            # Default parameters cho Random Forest Regressor
            default_params = {
                'n_estimators': 100,
                'max_depth': None,
                'min_samples_split': 2,
                'min_samples_leaf': 1,
                'random_state': 42,
                # Bỏ 'class_weight' vì đây là bài toán Hồi quy
            }
            default_params.update(kwargs)
            
            # SỬA: Dùng RandomForestRegressor
            rf = RandomForestRegressor(**default_params) 
            rf.fit(X_train, y_train)
            logging.info("Random Forest Regressor training completed.")
            return rf
        except Exception as e:
            logging.error(f"Error in Random Forest Regression training: {e}")
            raise e