"""
Model Training Helper Functions
Contains training logic for different model types: RF, XGBoost, ANN
"""

import pandas as pd
import numpy as np
import os
import joblib
from datetime import datetime
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import mean_absolute_error
import mlflow
import mlflow.sklearn
import mlflow.keras
import mlflow.xgboost
import shutil

from steps.ingest_data import IngestData
from src.data_cleaning import DataCleaning, DataPreprocessStrategy, DataDivideStrategy
from src.model_dev import RandomForestModel
from src.evaluation import R2Score, RMSE
from src.ann_model import ANNModel
from src.xgboost_model import XGBoostModel
from app.utils.progress_tracker import TrainingProgressTracker


def train_random_forest(data, dataset_path, model_name, trained_by, dataset_id, session_id=None):
    """Train Random Forest model"""
    tracker = TrainingProgressTracker()
    
    # Initialize progress tracking
    if session_id:
        tracker.start_training(session_id)
        tracker.update_progress(session_id, progress=5, message="Initializing Random Forest training...")
    
    # Get parameters
    n_estimators = int(data.get('n_estimators', 100))
    max_depth_value = data.get('max_depth', None)
    max_depth = int(max_depth_value) if max_depth_value not in [None, ''] else None
    random_state = int(data.get('random_state', 42))
    
    print("🌲 Training Random Forest Model...")
    
    # Ingest and preprocess data
    if session_id:
        tracker.update_progress(session_id, progress=15, message="Loading and preprocessing dataset...")
    X_train, X_test, y_train, y_test, scaler = prepare_data(dataset_path, data, session_id)
    
    # Train model
    if session_id:
        tracker.update_progress(session_id, progress=50, message="Training Random Forest model...")
    model_instance = RandomForestModel()
    hyperparams = {
        'n_estimators': n_estimators,
        'max_depth': max_depth,
        'random_state': random_state,
        'n_jobs': -1
    }
    model = model_instance.train(X_train, y_train, **hyperparams)
    
    # Log parameters (don't log model_type again - already logged in main function)
    mlflow.log_param("n_estimators", n_estimators)
    mlflow.log_param("max_depth", max_depth if max_depth is not None else "None")
    
    # Evaluate
    if session_id:
        tracker.update_progress(session_id, progress=75, message="Evaluating model performance...")
    metrics = evaluate_model(model, X_test, y_test, "RandomForest")
    
    # Log model to MLflow
    if session_id:
        tracker.update_progress(session_id, progress=85, message="Logging model to MLflow...")
    mlflow.sklearn.log_model(model, "model", registered_model_name=f"RandomForest_{model_name}")
    
    # Save model
    if session_id:
        tracker.update_progress(session_id, progress=95, message="Saving model and scaler files...")
    save_paths = save_model_files(model, scaler, model_name, 'sklearn', '.pkl')
    
    return model, scaler, metrics, save_paths


def train_xgboost(data, dataset_path, model_name, trained_by, dataset_id, session_id=None):
    """Train XGBoost model"""
    tracker = TrainingProgressTracker()
    
    # Initialize progress tracking
    if session_id:
        tracker.start_training(session_id)
        tracker.update_progress(session_id, progress=5, message="Initializing XGBoost training...")
    
    # Get parameters
    n_estimators = int(data.get('n_estimators', 100))
    max_depth_value = data.get('max_depth', None)
    max_depth = int(max_depth_value) if max_depth_value not in [None, ''] else None
    learning_rate = float(data.get('learning_rate', 0.1))
    random_state = int(data.get('random_state', 42))
    
    print("🚀 Training XGBoost Model...")
    
    # Ingest and preprocess data
    if session_id:
        tracker.update_progress(session_id, progress=15, message="Loading and preprocessing dataset...")
    X_train, X_test, y_train, y_test, scaler = prepare_data(dataset_path, data, session_id)
    
    # Train model
    if session_id:
        tracker.update_progress(session_id, progress=45, message="Training XGBoost model...")
    model_instance = XGBoostModel(
        n_estimators=n_estimators,
        max_depth=max_depth,
        learning_rate=learning_rate,
        random_state=random_state
    )
    
    # Use sklearn API for easier integration
    test_size = float(data.get('test_size', 0.2))
    validation_split = min(test_size, 0.2)  # Use portion for validation
    
    # Split training data for early stopping
    from sklearn.model_selection import train_test_split
    X_tr, X_val, y_tr, y_val = train_test_split(
        X_train, y_train,
        test_size=validation_split,
        random_state=random_state
    )
    
    model = model_instance.train_sklearn_api(
        X_tr, y_tr,
        eval_set=[(X_val, y_val)],
        early_stopping_rounds=10
    )
    
    # Log parameters (don't log model_type again)
    mlflow.log_param("n_estimators", n_estimators)
    mlflow.log_param("max_depth", max_depth if max_depth is not None else 6)
    mlflow.log_param("learning_rate", learning_rate)
    
    # Evaluate
    if session_id:
        tracker.update_progress(session_id, progress=75, message="Evaluating model performance...")
    metrics = evaluate_model(model, X_test, y_test, "XGBoost")
    
    # Log model to MLflow with sklearn flavor (since we use XGBRegressor)
    if session_id:
        tracker.update_progress(session_id, progress=85, message="Logging model to MLflow...")
    mlflow.sklearn.log_model(model, "model", registered_model_name=f"XGBoost_{model_name}")
    
    # Save model
    if session_id:
        tracker.update_progress(session_id, progress=95, message="Saving model and scaler files...")
    save_paths = save_model_files(model, scaler, model_name, 'xgboost', '.json')
    
    return model, scaler, metrics, save_paths


def train_ann(data, dataset_path, model_name, trained_by, dataset_id, session_id=None):
    """Train ANN model"""
    tracker = TrainingProgressTracker()
    
    # Initialize progress tracking
    if session_id:
        tracker.start_training(session_id)
        tracker.update_progress(session_id, progress=5, message="Initializing ANN training...")
    
    # Get parameters
    hidden_layers = data.get('hidden_layers', '64,32,16')
    activation = data.get('activation', 'relu')
    dropout_rate = float(data.get('dropout_rate', 0.2))
    learning_rate = float(data.get('learning_rate', 0.001))
    epochs = int(data.get('epochs', 100))
    batch_size = int(data.get('batch_size', 32))
    random_state = int(data.get('random_state', 42))
    
    print("🧠 Training ANN Model...")
    
    # Ingest and preprocess data
    if session_id:
        tracker.update_progress(session_id, progress=15, message="Loading and preprocessing dataset...")
    X_train, X_test, y_train, y_test, scaler = prepare_data(dataset_path, data, session_id)
    
    # Train model
    if session_id:
        tracker.update_progress(session_id, progress=40, message="Building ANN architecture...")
    model_instance = ANNModel(
        hidden_layers=hidden_layers,
        activation=activation,
        dropout_rate=dropout_rate,
        learning_rate=learning_rate,
        random_state=random_state
    )
    
    if session_id:
        tracker.update_progress(session_id, progress=50, message=f"Training ANN for {epochs} epochs...")
    history = model_instance.train(
        X_train, y_train,
        epochs=epochs,
        batch_size=batch_size,
        validation_split=0.2,
        verbose=1
    )
    
    model = model_instance.model
    
    # Log parameters (don't log model_type again)
    mlflow.log_param("hidden_layers", hidden_layers)
    mlflow.log_param("activation", activation)
    mlflow.log_param("dropout_rate", dropout_rate)
    mlflow.log_param("learning_rate", learning_rate)
    mlflow.log_param("epochs", epochs)
    mlflow.log_param("batch_size", batch_size)
    
    # Log training history
    if history:
        for epoch, loss in enumerate(history.history.get('loss', [])):
            mlflow.log_metric("train_loss", loss, step=epoch)
        for epoch, val_loss in enumerate(history.history.get('val_loss', [])):
            mlflow.log_metric("val_loss", val_loss, step=epoch)
    
    # Evaluate
    if session_id:
        tracker.update_progress(session_id, progress=75, message="Evaluating model performance...")
    metrics = evaluate_model(model_instance, X_test, y_test, "ANN")
    
    # Log model to MLflow
    if session_id:
        tracker.update_progress(session_id, progress=85, message="Logging model to MLflow...")
    mlflow.keras.log_model(model, "model", registered_model_name=f"ANN_{model_name}")
    
    # Save model (Keras format)
    if session_id:
        tracker.update_progress(session_id, progress=95, message="Saving model and scaler files...")
    save_paths = save_model_files(model, scaler, model_name, 'keras', '.keras')
    
    return model, scaler, metrics, save_paths


def prepare_data(dataset_path, data, session_id=None):
    """Prepare data for training"""
    tracker = TrainingProgressTracker()
    test_size = float(data.get('test_size', 0.2))
    random_state = int(data.get('random_state', 42))
    
    # Step 1: Ingest Data
    if session_id:
        tracker.update_progress(session_id, progress=18, message="Loading dataset...")
    print(f"📂 Loading data from: {dataset_path}")
    ingest_data = IngestData(dataset_path)
    df = ingest_data.get_data()
    print(f"✅ Dataset loaded: {len(df)} rows, {len(df.columns)} columns")
    
    # Step 2a: Preprocess
    if session_id:
        tracker.update_progress(session_id, progress=25, message="Preprocessing data...")
    print("🧹 Preprocessing data...")
    preprocess_strategy = DataPreprocessStrategy()
    data_cleaning = DataCleaning(df, preprocess_strategy)
    preprocessed_data = data_cleaning.handle_data()
    
    # Step 2b: Split data
    if session_id:
        tracker.update_progress(session_id, progress=32, message="Splitting data into train/test sets...")
    print("✂️ Splitting data...")
    divide_strategy = DataDivideStrategy()
    data_cleaning = DataCleaning(preprocessed_data, divide_strategy)
    X_train, X_test, y_train, y_test = data_cleaning.handle_data()
    
    # Step 2c: Scale features
    if session_id:
        tracker.update_progress(session_id, progress=38, message="Scaling features...")
    print("📏 Scaling features...")
    scaler = StandardScaler()
    X_train = pd.DataFrame(
        scaler.fit_transform(X_train),
        columns=X_train.columns,
        index=X_train.index
    )
    X_test = pd.DataFrame(
        scaler.transform(X_test),
        columns=X_test.columns,
        index=X_test.index
    )
    
    # Log data info (don't log test_size and random_state again - already logged in main)
    mlflow.log_param("train_samples", len(X_train))
    mlflow.log_param("test_samples", len(X_test))
    mlflow.log_param("n_features", X_train.shape[1])
    
    return X_train, X_test, y_train, y_test, scaler
    
    return X_train, X_test, y_train, y_test, scaler


def evaluate_model(model, X_test, y_test, model_type):
    """Evaluate model and log metrics"""
    print("📊 Evaluating model...")
    
    # Predict
    if hasattr(model, 'predict'):
        y_pred = model.predict(X_test)
        if len(y_pred.shape) > 1:  # For ANN models
            y_pred = y_pred.flatten()
    else:
        raise ValueError("Model does not have predict method")
    
    # Calculate metrics
    r2_calc = R2Score()
    r2 = r2_calc.calculate_score(y_test, y_pred)
    
    rmse_calc = RMSE()
    rmse = rmse_calc.calculate_score(y_test, y_pred)
    
    mae = mean_absolute_error(y_test, y_pred)
    mse = rmse ** 2
    
    # Log to MLflow
    mlflow.log_metric("r2_score", r2)
    mlflow.log_metric("rmse", rmse)
    mlflow.log_metric("mae", mae)
    mlflow.log_metric("mse", mse)
    
    print(f"\n📈 {model_type} Performance:")
    print(f"   R² Score: {r2:.4f}")
    print(f"   RMSE: {rmse:.4f}")
    print(f"   MAE: {mae:.4f}")
    
    return {
        'r2_score': float(r2),
        'rmse': float(rmse),
        'mae': float(mae),
        'mse': float(mse)
    }


def save_model_files(model, scaler, model_name, lib_type, extension):
    """Save model and scaler files"""
    # Determine directories
    current_file = os.path.abspath(__file__)
    project_root = os.path.dirname(os.path.dirname(os.path.dirname(current_file)))
    model_dir = os.path.join(project_root, 'predict-service', 'app', 'ml_model')
    os.makedirs(model_dir, exist_ok=True)
    
    # Save model
    model_filename = f"{model_name}{extension}"
    model_path = os.path.join(model_dir, model_filename)
    
    if lib_type == 'keras':
        model.save(model_path)
    elif lib_type == 'xgboost':
        model.save_model(model_path)
    else:  # sklearn
        joblib.dump(model, model_path)
    
    print(f"💾 Model saved: {model_path}")
    
    # Save scaler
    scaler_filename = f"{model_name}_scaler.pkl"
    scaler_path = os.path.join(model_dir, scaler_filename)
    joblib.dump(scaler, scaler_path)
    print(f"💾 Scaler saved: {scaler_path}")
    
    # Save shared scaler
    shared_scaler_path = os.path.join(model_dir, 'scaler.pkl')
    joblib.dump(scaler, shared_scaler_path)
    
    # Log scaler to MLflow
    mlflow.log_artifact(scaler_path, "scaler")
    
    # Copy to Laravel public directory
    try:
        workspace_root = os.path.dirname(project_root)
        laravel_public_models = os.path.join(workspace_root, 'WebApp', 'public', 'models')
        os.makedirs(laravel_public_models, exist_ok=True)
        
        laravel_model_path = os.path.join(laravel_public_models, model_filename)
        laravel_scaler_path = os.path.join(laravel_public_models, scaler_filename)
        
        if lib_type == 'keras':
            # Copy keras model directory
            if os.path.exists(laravel_model_path):
                shutil.rmtree(laravel_model_path)
            shutil.copytree(model_path, laravel_model_path)
        else:
            shutil.copy2(model_path, laravel_model_path)
        
        shutil.copy2(scaler_path, laravel_scaler_path)
        print(f"📋 Files copied to Laravel: {laravel_public_models}")
        
    except Exception as e:
        print(f"⚠️ Warning: Could not copy to Laravel: {str(e)}")
    
    return {
        'model_path': model_path,
        'scaler_path': scaler_path,
        'model_filename': model_filename,
        'lib_type': lib_type
    }
