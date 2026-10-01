from pathlib import Path
from unittest.mock import MagicMock, patch

import pytest

from app.routes import train_helpers


@pytest.mark.parametrize(
    ("trainer", "options", "expected_class"),
    [
        (train_helpers.train_linear_regression, {}, "LinearRegression"),
        (
            train_helpers.train_svr,
            {"svr_c": 2.0, "svr_epsilon": 0.2, "svr_kernel": "rbf"},
            "SVR",
        ),
        (
            train_helpers.train_gradient_boosting,
            {
                "n_estimators": 20,
                "learning_rate": 0.05,
                "max_depth": 2,
                "random_state": 42,
            },
            "GradientBoostingRegressor",
        ),
    ],
)
def test_additional_model_trainers(trainer, options, expected_class):
    dataset_path = Path(__file__).parents[1] / "data" / "Dataset.new3.csv"
    saved_paths = {
        "model_path": "/tmp/model.pkl",
        "scaler_path": "/tmp/scaler.pkl",
        "model_filename": "model.pkl",
        "lib_type": "sklearn",
    }

    with patch.object(train_helpers, "mlflow", MagicMock()), patch.object(
        train_helpers, "save_model_files", return_value=saved_paths
    ):
        model, scaler, metrics, paths = trainer(
            options, str(dataset_path), "test_model", 1, 1
        )

    assert type(model).__name__ == expected_class
    assert scaler.n_features_in_ == 4
    assert set(metrics) == {"r2_score", "rmse", "mae", "mse"}
    assert paths["lib_type"] == "sklearn"
