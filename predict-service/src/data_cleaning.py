import logging
from abc import ABC, abstractmethod
from typing import Union, Tuple

import pandas as pd
from sklearn.model_selection import train_test_split
# Bỏ LabelEncoder vì Cell viability (%) là biến mục tiêu hồi quy (số liên tục)

# Định nghĩa các lớp DataStrategy, DataCleaning như cũ...

class DataStrategy(ABC):
    """Abstract Class defining strategy for handling data"""

    @abstractmethod
    def handle_data(self, data: pd.DataFrame) -> Union[pd.DataFrame, Tuple[pd.DataFrame, pd.DataFrame, pd.Series, pd.Series]]:
        pass


class DataPreprocessStrategy(DataStrategy):
    """
    Tiền xử lý cho tập dữ liệu Cell viability.
    1. Xử lý giá trị thiếu (điền giá trị trung vị cho cột số).
    2. Loại bỏ các dòng trùng lặp.
    3. Loại bỏ cột 'Cell viability Index' (không dùng làm target).
    """

    def handle_data(self, data: pd.DataFrame) -> pd.DataFrame:
        try:
            # 1. Xử lý giá trị thiếu
            # Kiểm tra và điền giá trị trung vị (median) cho các cột số.
            numerical_cols = data.select_dtypes(include=['float64', 'int64']).columns
            for col in numerical_cols:
                data[col].fillna(data[col].median(), inplace=True)
            
            # 2. Loại bỏ các dòng trùng lặp
            data = data.drop_duplicates()
            
            # 3. Loại bỏ cột 'Cell viability Index' vì chúng ta dùng 'Cell viability (%)' làm target
            if 'Cell viability Index' in data.columns:
                data = data.drop(columns=['Cell viability Index'])
            
            # Ghi log tên các cột sau khi tiền xử lý để kiểm tra
            # logging.info(f"Columns after preprocessing: {data.columns.tolist()}")

            logging.info("Data preprocessing completed: missing values handled, duplicates removed, and 'Cell viability Index' column dropped.")
            return data
        except Exception as e:
            logging.error(f"Error in DataPreprocessStrategy: {e}")
            raise e


class DataDivideStrategy(DataStrategy):
    """Chia dữ liệu thành tập huấn luyện (X_train, y_train) và kiểm tra (X_test, y_test)"""

    def handle_data(self, data: pd.DataFrame) -> Tuple[pd.DataFrame, pd.DataFrame, pd.Series, pd.Series]:
        try:
            # Biến mục tiêu (Target variable)
            target_col = 'Cell viability (%)'
            
            # Các đặc trưng (Features) là tất cả các cột trừ biến mục tiêu
            # data.columns.tolist() trả về list các tên cột
            feature_cols = [col for col in data.columns if col != target_col]
            
            X = data[feature_cols]
            y = data[target_col] 

            # Kiểm tra xem có cần Stratify không.
            # Vì đây là bài toán Hồi quy (Regression), không nên dùng stratify.
            # Hàm train_test_split được gọi không có tham số stratify.
            X_train, X_test, y_train, y_test = train_test_split(
                X, y, test_size=0.2, random_state=42
            )
            
            logging.info("Data division completed.")
            return X_train, X_test, y_train, y_test
        except Exception as e:
            logging.error(f"Error in DataDivideStrategy: {e}")
            raise e


class DataCleaning:
    """Data cleaning with chosen strategy"""

    def __init__(self, data: pd.DataFrame, strategy: DataStrategy):
        self.data = data
        self.strategy = strategy

    def handle_data(self) -> Union[pd.DataFrame, Tuple[pd.DataFrame, pd.DataFrame, pd.Series, pd.Series]]:
        try:
            return self.strategy.handle_data(self.data)
        except Exception as e:
            logging.error(f"Error in handling data: {e}")
            raise e