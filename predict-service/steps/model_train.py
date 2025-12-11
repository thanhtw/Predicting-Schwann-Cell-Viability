import logging

import mlflow
import pandas as pd
from src.model_dev import (
    LinearRegressionModel, 
    RandomForestModel, 
)
# SỬA: Thay đổi từ ClassifierMixin sang RegressorMixin
from sklearn.base import RegressorMixin 
from zenml import step
from zenml.client import Client
from typing_extensions import Annotated 

from .config import ModelNameConfig


experiment_tracker = Client().active_stack.experiment_tracker


@step(experiment_tracker=experiment_tracker.name)
def train_model(
    X_train: pd.DataFrame,
    X_test: pd.DataFrame,
    y_train: pd.Series, 
    y_test: pd.Series, 
    config: ModelNameConfig,
) -> Annotated[RegressorMixin, "model"]: # SỬA: Đổi kiểu dữ liệu trả về thành RegressorMixin
    """
    Trains the model on the ingested data.
    Args:
        X_train: pd.DataFrame
        X_test: pd.DataFrame
        y_train: pd.Series
        y_test: pd.Series
    Returns:
        model: RegressorMixin (Mô hình hồi quy đã huấn luyện)
    """
    try:
        mlflow.sklearn.autolog()
        
        # Dictionary mapping model names to classes
        model_classes = {
            "LinearRegression": LinearRegressionModel,
            "RandomForest": RandomForestModel, # Đây là RandomForestRegressor sau khi sửa
        }
        
        # Get model class
        if config.model_name not in model_classes:
            available_models = list(model_classes.keys())
            raise ValueError(f"Model {config.model_name} not supported. "
                             f"Available models: {available_models}")
        
        # Initialize and train model
        model_class = model_classes[config.model_name]
        model_instance = model_class()
        
        # Pass hyperparameters if available
        hyperparams = getattr(config, 'hyperparameters', {})
        trained_model = model_instance.train(X_train, y_train, **hyperparams)
        
        logging.info(f"Successfully trained {config.model_name}")
        return trained_model
        
    except Exception as e:
        logging.error(f"Error in training model: {e}")
        raise e