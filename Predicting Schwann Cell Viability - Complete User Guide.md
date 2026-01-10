---
title: Predicting Schwann Cell Viability - Complete User Guide

---

# Predicting Schwann Cell Viability - Complete User Guide

## 📋 Table of Contents

1. [Introduction](#introduction)
2. [System Overview](#system-overview)
3. [Getting Started](#getting-started)
4. [Installation Guide](#installation-guide)
5. [User Roles and Permissions](#user-roles-and-permissions)
6. [Web Application Guide](#web-application-guide)
7. [Prediction Service API](#prediction-service-api)
8. [Admin Panel Guide](#admin-panel-guide)
9. [Model Management](#model-management)
10. [Model Training](#model-training)
11. [API Reference](#api-reference)

---

## 📖 Introduction

The **Predicting Schwann Cell Viability System** is a comprehensive machine learning platform designed to predict the viability of Schwann cells based on various experimental parameters. This system combines a user-friendly web interface built with Laravel and a powerful Python-based machine learning service to provide accurate predictions for research and analysis.

### Key Features

- ✅ **User-Friendly Interface**: Intuitive web dashboard for managing predictions
- ✅ **Role-Based Access Control**: Separate admin and user functionalities
- ✅ **Machine Learning Integration**: Advanced ANN (Artificial Neural Network), XGBoost and Random Forest models
- ✅ **Model Training Pipeline**: End-to-end training workflow with data preprocessing and validation
- ✅ **MLflow Integration**: Experiment tracking, model versioning, and performance monitoring
- ✅ **Model Management**: Upload, manage, and compare multiple ML models
- ✅ **Model Comparison**: Compare performance metrics across different models and versions
- ✅ **Training Progress Tracking**: Real-time monitoring of model training progress
- ✅ **Data Augmentation**: Built-in data preprocessing and feature engineering
- ✅ **Prediction History**: Track and analyze past predictions
- ✅ **RESTful API**: Programmatic access to prediction services
- ✅ **Docker Support**: Easy deployment with containerization
- ✅ **CI/CD Integration**: Automated testing and deployment

### System Requirements

#### For Docker Deployment (Recommended)
- **Docker**: Version 28.1 or higher
- **Docker Compose**: Version 2.0 or higher
- **Operating System**: Windows 10+, macOS 10.15+, or Linux (Ubuntu 20.04+)
- **Memory**: Minimum 4GB RAM (8GB recommended)
- **Disk Space**: At least 10GB free space

#### For Manual Installation
- **PHP**: 8.4 or higher
- **Python**: 3.11 or higher
- **Node.js**: 16.x or higher
- **Database**: MySQL 8.0, PostgreSQL 12+, or SQLite
- **Composer**: Latest version
- **npm**: Latest version

---

## 🏗️ System Overview

### Architecture

The system consists of four main components that work together:

```
┌─────────────────────────────────────────────────────────────┐
│                      CLIENT BROWSER                          │
│                    (Port: 52025)                             │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    NGINX (Reverse Proxy)                     │
│                      Load Balancer                           │
└────────────┬───────────────────────────────┬────────────────┘
             │                               │
             ▼                               ▼
┌────────────────────────────┐  ┌──────────────────────────────┐
│   Laravel WebApp           │  │   Python Predict Service     │
│   (PHP 8.4 + PHP-FPM)      │  │   (Flask API)                │
│   Port: 9000               │  │   Port: 5000                 │
│                            │  │                              │
│   • User Interface         │  │   • ML Model Loading         │
│   • Authentication         │  │   • Predictions              │
│   • Admin Panel            │  │   • API Endpoints            │
│   • Model Management       │  │   • Model Training             │
└────────────┬───────────────┘  └──────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────┐
│                    MySQL Database                            │
│                    (Port: 3306)                              │
│   • User Data                                                │
│   • Model Metadata                                           │
│   • Prediction History                                       │
└─────────────────────────────────────────────────────────────┘
```

### Component Descriptions

#### 1. **Laravel WebApp**
- Provides the main user interface
- Handles user authentication and authorization
- Manages ML model uploads and storage
- Displays prediction results and history
- Communicates with the Predict Service API

#### 2. **Predict Service**
- Python Flask-based REST API
- Loads and manages ML models
- Processes prediction requests
- Returns cell viability predictions
- Provides Swagger API documentation

#### 3. **MySQL Database**
- Stores user accounts and credentials
- Maintains model metadata
- Records prediction history
- Manages system configurations

#### 4. **Nginx Reverse Proxy**
- Routes traffic to appropriate services
- Load balancing
- Serves static files efficiently
- SSL/TLS termination support

---

## 🚀 Getting Started

### Quick Start with Docker (5 Minutes)

This is the **recommended** method for most users as it requires minimal setup and ensures consistency across different environments.

#### Step 1: Prerequisites Check

Before starting, ensure you have:
- Docker Desktop installed (version 28.1+)
- Git installed on your system
- At least 4GB of available RAM
- 10GB of free disk space

#### Step 2: Clone the Repository

```bash
# Clone the repository
git clone https://github.com/nguyenhuuluan1702/PCS_MLops.git

# Navigate to the project directory
cd PCS_MLops
```

#### Step 3: Deploy with One Command

**For Windows (PowerShell):**
```powershell
# Run the deployment script
.\deploy.ps1 -Fresh
```

**For Linux/macOS:**
```bash
# Make the script executable
chmod +x deploy.sh

# Run the deployment script
./deploy.sh --fresh
```

The `-Fresh` or `--fresh` flag will:
- Create a fresh database
- Run all migrations
- Seed default admin and user accounts

#### Step 4: Access the Application

Once deployment is complete, open your browser and navigate to:
- **Application URL**: http://localhost:52025

Default credentials:
- **Admin Account**: 
  - Username: `admin`
  - Password: `password`
- **User Account**: 
  - Username: `your_username`
  - Password: `password`

🔐 **Important**: Change these default passwords after your first login!

---

## 📦 Installation Guide

### Option 1: Docker Installation (Recommended)

#### Prerequisites Installation

**Windows:**
1. Download and install [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop)
2. Enable WSL 2 if prompted
3. Restart your computer if required
4. Verify installation:
```powershell
docker --version
docker-compose --version
```

**macOS:**
1. Download and install [Docker Desktop for Mac](https://www.docker.com/products/docker-desktop)
2. Start Docker Desktop from Applications
3. Verify installation:
```bash
docker --version
docker-compose --version
```

**Linux (Ubuntu/Debian):**
```bash
# Update package index
sudo apt-get update

# Install prerequisites
sudo apt-get install ca-certificates curl gnupg lsb-release

# Add Docker's official GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Set up the stable repository
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker Engine
sudo apt-get update
sudo apt-get install docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Verify installation
docker --version
docker compose version
```

#### Deployment Steps

1. **Clone the Repository**
```bash
git clone https://github.com/nguyenhuuluan1702/PCS_MLops.git
cd PCS_MLops
```

2. **Configure Environment Variables (Optional)**

If you want to customize database credentials or other settings:

```bash
# Copy the example environment file
cp .env.docker.example .env.docker

# Edit the file with your preferred editor
# Windows: notepad .env.docker
# Linux/Mac: nano .env.docker
```

Key configurations in `.env.docker`:
```env
# Database Configuration
DB_HOST=mysql
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=LaravelSecurePass2025!

# MySQL Root Password
MYSQL_ROOT_PASSWORD=MySecureRootPass2025!

# Application Settings
APP_NAME="Schwann Cell Viability Predictor"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:52025

# Prediction Service
PREDICT_SERVICE_URL=http://predict-service:5000
```

3. **Deploy the Application**

**Windows:**
```powershell
# Deploy with fresh database (recommended for first-time setup)
.\deploy.ps1 -Fresh

# Or deploy without resetting database
.\deploy.ps1
```

**Linux/macOS:**
```bash
# Make script executable (first time only)
chmod +x deploy.sh

# Deploy with fresh database
./deploy.sh --fresh

# Or deploy without resetting database
./deploy.sh
```

4. **Verify Deployment**

The deployment script will:
- ✅ Build Docker images
- ✅ Start all services
- ✅ Run database migrations
- ✅ Seed default data
- ✅ Create admin and user accounts

Check if all services are running:
```bash
docker-compose ps
```

You should see 4 services running:
- `laravel-webapp` (PHP application)
- `predict-service` (Python API)
- `mysql` (Database)
- `nginx` (Web server)

5. **Access the Application**

Open your browser and go to: http://localhost:52025

---

### Option 2: Manual Installation

For advanced users who prefer not to use Docker.

#### Step 1: Install PHP 8.4+

**Windows:**
```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.4'))
```

**Linux (Ubuntu/Debian):**
```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.4)"
```

**macOS:**
```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.4)"
```

#### Step 2: Install Python 3.11+

**Windows:**
Download from [python.org](https://www.python.org/downloads/) or use Chocolatey:
```powershell
choco install python311
```

**Linux:**
```bash
sudo apt-get update
sudo apt-get install python3.11 python3.11-venv python3-pip
```

**macOS:**
```bash
brew install python@3.11
```

#### Step 3: Install Node.js

**All Platforms:**
Download from [nodejs.org](https://nodejs.org/) or use a package manager:

```bash
# Windows (with Chocolatey)
choco install nodejs

# Linux
sudo apt-get install nodejs npm

# macOS
brew install node
```

#### Step 4: Install Composer

Download from [getcomposer.org](https://getcomposer.org/download/) or:

```bash
# Linux/macOS
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### Step 5: Setup Laravel WebApp

```bash
# Navigate to WebApp directory
cd WebApp

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env file
# Edit .env and set your database credentials

# Run migrations
php artisan migrate

# Seed database with default data
php artisan db:seed

# Build frontend assets
npm run build
```

#### Step 6: Setup Python Predict Service

```bash
# Navigate to predict-service directory
cd predict-service

# Create virtual environment
python -m venv venv

# Activate virtual environment
# Windows:
venv\Scripts\activate
# Linux/macOS:
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt
```

#### Step 7: Start the Services

Open three separate terminal windows:

**Terminal 1 - Laravel:**
```bash
cd WebApp
php artisan serve
```

**Terminal 2 - Predict Service:**
```bash
cd predict-service
# Activate venv first
python run.py
```

**Terminal 3 - Frontend Build (if developing):**
```bash
cd WebApp
npm run dev
```

Access the application at: http://localhost:8000

---

## 👥 User Roles and Permissions

The system has two distinct user roles with different capabilities:

### Administrator Role

Administrators have full system access including:

#### User Management
- ✅ View all registered users
- ✅ Create new user accounts
- ✅ Edit user information
- ✅ Delete user accounts
- ✅ Assign/change user roles
- ✅ Reset user passwords

#### Model Management
- ✅ Upload new ML models
- ✅ View all models
- ✅ Edit model metadata
- ✅ Delete models
- ✅ Activate/deactivate models

#### System Monitoring
- ✅ View system statistics
- ✅ Monitor prediction history
- ✅ Access admin dashboard
- ✅ Configure system settings

#### Prediction Features
- ✅ Make predictions using any model
- ✅ View all prediction history
- ✅ Export prediction data
- ✅ Analyze results

### Regular User Role

Regular users have limited access:

#### Prediction Features
- ✅ Make predictions using active models
- ✅ View own prediction history
- ✅ Export own prediction data

#### Profile Management
- ✅ Update own profile
- ✅ Change own password
- ✅ View own statistics

#### Restrictions
- ❌ Cannot access admin panel
- ❌ Cannot manage other users
- ❌ Cannot upload/manage models
- ❌ Cannot view other users' predictions

---

## 🖥️ Web Application Guide

### First Login

1. **Navigate to the Application**
   - Open your browser
   - Go to http://localhost:52025 (Docker) or http://localhost:8000 (Manual)

2. **Login Screen**
   - Enter your username
   - Enter your password
   - Click "Login"

3. **First-Time Setup**
   - Change your default password
   - Update your profile information
   - Review the welcome tutorial

### Dashboard Overview

After logging in, you'll see the main dashboard with different sections based on your role.

#### User Dashboard

The user dashboard displays:

1. **Quick Stats Card**
   - Total predictions made
   - Recent prediction count
   - Average prediction time

2. **Recent Predictions Table**
   - Date and time
   - Model used
   - Input parameters
   - Prediction result
   - Status indicator

3. **Quick Actions**
   - New Prediction button
   - View History button
   - Profile Settings

#### Admin Dashboard

The admin dashboard includes everything in the User Dashboard plus:

1. **System Statistics**
   - Total users
   - Total models
   - System uptime
   - Total predictions

2. **User Activity**
   - Recent logins
   - Active users
   - User registration trends

3. **Model Statistics**
   - Active models
   - Most used models
   - Model performance metrics

4. **Admin Tools**
   - User Management
   - Model Management
   - System Configuration

### Making a Prediction

#### Step-by-Step Guide

1. **Access Prediction Form**
   - Click "New Prediction" from dashboard
   - Or navigate to "Predictions > New Prediction"

2. **Select Model**
   - Choose from available ML models
   - Each model shows:
     - Model name
     - Description
     - Accuracy rate
     - Last updated date

3. **Enter Input Parameters**
   
   The prediction requires four input parameters:

   - **PC-MXene Loading** (mg/mL)
     - Range: 0.0 to 50.0
     - Description: Concentration of PC-MXene in the solution
     - Example: 0.01
   
   - **Laminin Peptide Loading** (mg/mL)
     - Range: 0.0 to 10.0
     - Description: Concentration of Laminin peptide
     - Example: 0.5
   
   - **Stimulation Frequency** (Hz)
     - Range: 0 to 1000
     - Description: Electrical stimulation frequency
     - Example: 100
   
   - **Applied Voltage** (V)
     - Range: 0 to 100
     - Description: Voltage applied to the system
     - Example: 5.0

4. **Submit Prediction**
   - Review your inputs
   - Click "Submit Prediction"
   - Wait for processing (typically 1-3 seconds)

5. **View Results**
   - Prediction result displayed
   - Confidence level shown
   - Option to save or export
   - Link to similar predictions

#### Input Validation

The form includes real-time validation:
- ✅ Range checking for all parameters
- ✅ Format validation (numbers only)
- ✅ Required field checking
- ✅ Helpful error messages

#### Example Prediction

Here's a sample prediction workflow:

```
Input Parameters:
├─ PC-MXene Loading: 0.01 mg/mL
├─ Laminin Peptide: 0.5 mg/mL
├─ Stimulation Frequency: 100 Hz
└─ Applied Voltage: 5.0 V

Model: ANN_Model_v2.0

Result:
├─ Predicted Viability: 87.5%
├─ Confidence: High (95%)
├─ Processing Time: 1.2 seconds
└─ Status: Success
```

### Viewing Prediction History

#### Access History

1. Navigate to "Predictions > History"
2. View table of all your past predictions

#### History Table Columns

- **Date/Time**: When prediction was made
- **Model Used**: Name of ML model
- **Input Parameters**: Summary of inputs
- **Result**: Prediction outcome
- **Status**: Success/Failed
- **Actions**: View details, Export, Delete

#### Filtering and Search

Use the filter controls to:
- **Date Range**: Filter by date period
- **Model**: Filter by specific model
- **Result Range**: Filter by prediction value
- **Status**: Show only successful or failed

#### Exporting Data

Export your prediction history:
1. Click "Export" button
2. Choose format:
   - CSV (for Excel/spreadsheets)
   - JSON (for programming)
   - PDF (for reports)
3. Select date range
4. Download file

### Profile Management

#### Update Profile

1. Click your name in top-right corner
2. Select "Profile Settings"
3. Update information:
   - Name
   - Email
   - Organization (optional)

#### Change Password

1. Go to Profile Settings
2. Click "Change Password"
3. Enter:
   - Current password
   - New password
   - Confirm new password
4. Save changes

Password requirements:
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character

---

## 🔧 Admin Panel Guide

This section is for administrators only.

### Accessing Admin Panel

1. Login with admin credentials
2. Click "Admin Panel" in navigation
3. Or navigate to "/admin" path

### User Management

#### View All Users

1. Go to "Admin > Users"
2. View user table with:
   - Name
   - Email
   - Role
   - Registration date
   - Last login
   - Status (Active/Inactive)

#### Create New User

1. Click "Add New User"
2. Fill in form:
   - Name
   - Email
   - Password
   - Role (Admin/User)
3. Click "Create User"
4. User receives email with credentials

#### Edit User

1. Click "Edit" next to user
2. Modify information
3. Save changes

#### Delete User

1. Click "Delete" next to user
2. Confirm deletion
3. User and their data are removed

⚠️ **Warning**: Deleting a user is permanent and cannot be undone!

#### Reset User Password

1. Click "Reset Password" next to user
2. Choose method:
   - Generate random password
   - Set custom password
3. User receives email with new password

### Model Management

#### View All Models

1. Go to "Admin > Models"
2. View model table:
   - Model name
   - Version
   - Upload date
   - File size
   - Status (Active/Inactive)
   - Usage count

#### Upload New Model

1. Click "Upload Model"
2. Fill in details:
   - **Model Name**: Descriptive name
   - **Version**: Version number
   - **Description**: What the model does
   - **Model File**: Upload .h5 or .pkl file
   - **Scaler File**: Upload scaler file (if required)
   - **Status**: Active/Inactive

3. Click "Upload"
4. System validates model file
5. Model becomes available for predictions

#### Model File Requirements

- **Format**: HDF5 (.h5) or Pickle (.pkl)
- **Size**: Maximum 500MB
- **Structure**: Must be compatible with prediction service
- **Scaler**: Required if model uses feature scaling

#### Edit Model

1. Click "Edit" next to model
2. Update metadata:
   - Name
   - Description
   - Status
3. Cannot change model file (must upload new version)

#### Delete Model

1. Click "Delete" next to model
2. System checks if model is in use
3. Confirm deletion
4. Model file and metadata removed

⚠️ **Warning**: Cannot delete models that have associated predictions!

#### Activate/Deactivate Model

- **Activate**: Makes model available for all users
- **Deactivate**: Hides model from prediction form

1. Toggle status switch
2. Changes take effect immediately

### System Configuration

#### Application Settings

1. Go to "Admin > Settings"
2. Configure:
   - Site name
   - Site logo
   - Contact email
   - Maintenance mode

#### Email Configuration

Configure SMTP settings:
- SMTP host
- SMTP port
- Username
- Password
- Encryption (TLS/SSL)

#### API Configuration

Manage API settings:
- Prediction service URL
- API timeout
- Rate limiting
- CORS settings

### System Monitoring

#### View Logs

1. Go to "Admin > Logs"
2. View different log types:
   - Application logs
   - Error logs
   - Access logs
   - Prediction logs

#### System Statistics

Dashboard shows:
- Server uptime
- Database size
- Storage usage
- Active sessions
- Request per minute
- Average response time

---

## 🤖 Model Management

### Understanding ML Models

The system uses Artificial Neural Network (ANN) models trained on Schwann cell viability data. Each model:
- Takes 4 input parameters
- Outputs viability prediction (0-100%)
- Has associated accuracy metrics
- Requires preprocessing (scaling)

### Model Lifecycle

```
Upload → Validate → Activate → Use → Monitor → Update/Retire
```

### Model Versioning

Best practices for model versions:
- Use semantic versioning (e.g., v1.0.0)
- Increment minor version for small improvements
- Increment major version for significant changes
- Keep multiple versions for comparison

### Model Performance

Track model performance:
- Prediction accuracy
- Average prediction time
- Usage frequency
- User feedback

### Model Storage

Models are stored in:
- **Docker**: `/var/www/html/storage/app/models`
- **Manual**: `WebApp/storage/app/models`

Storage limits:
- Maximum 10 models (configurable)
- Maximum 500MB per model
- Total storage: 5GB

---

## 🎓 Model Training

The system includes a comprehensive model training pipeline for creating and improving machine learning models.

### Training Pipeline Overview

The training pipeline consists of several automated steps:

```
Data Ingestion → Data Cleaning → Feature Engineering → 
Model Training → Evaluation → Model Registration → Deployment
```

### Supported Model Types

The system supports training multiple model types:

#### 1. **Artificial Neural Network (ANN)**
- Deep learning model with multiple hidden layers
- Best for complex non-linear relationships
- High accuracy for Schwann cell viability prediction

**Architecture**:
```
Input Layer (4 features)
    ↓
Hidden Layer 1 (64 neurons, ReLU)
    ↓
Dropout (0.2)
    ↓
Hidden Layer 2 (32 neurons, ReLU)
    ↓
Dropout (0.2)
    ↓
Hidden Layer 3 (16 neurons, ReLU)
    ↓
Output Layer (1 neuron, Sigmoid)
```

#### 2. **XGBoost Model**
- Gradient boosting algorithm
- Fast training and inference
- Good for structured/tabular data
- Excellent feature importance analysis

#### 3. **Random Forest Model**
- Ensemble learning method using multiple decision trees
- Robust against overfitting
- Works well with small to medium datasets
- Provides feature importance metrics
- No need for extensive feature scaling

**Key Characteristics**:
```
Multiple Decision Trees (100-500 trees)
    ↓
Bootstrap Sampling (Random sampling with replacement)
    ↓
Random Feature Selection (sqrt(n_features) per split)
    ↓
Voting/Averaging across all trees
    ↓
Final Prediction
```

### Running the Training Pipeline

#### Method 1: Using Python Script

**Prerequisites**:
```bash
cd predict-service
# Activate virtual environment
source venv/bin/activate  # Linux/Mac
venv\Scripts\activate     # Windows
```

**Run Training**:
```bash
# Run full training pipeline
python run_pipeline.py
```

**Custom Training Configuration**:
```python
# Edit pipelines/training_pipeline.py
from zenml import pipeline
from steps import ingest_data, clean_data, model_train, evaluation

@pipeline
def training_pipeline():
    """Complete ML training pipeline"""
    # Step 1: Load data
    df = ingest_data()
    
    # Step 2: Clean and preprocess
    X_train, X_test, y_train, y_test = clean_data(df)
    
    # Step 3: Train model
    model = model_train(X_train, y_train)
    
    # Step 4: Evaluate
    metrics = evaluation(model, X_test, y_test)
    
    return model, metrics

if __name__ == "__main__":
    pipeline = training_pipeline()
    pipeline.run()
```

#### Method 2: Using Docker

```bash
# Execute training inside Docker container
docker-compose exec predict-service python run_pipeline.py
```

### Training Configuration

#### Data Configuration

Edit `steps/config.py` to configure data sources:

```python
# Data Configuration
DATA_PATH = "data/Dataset.new3.csv"
TEST_SIZE = 0.2
RANDOM_STATE = 42
VALIDATION_SPLIT = 0.2

# Feature Columns
FEATURE_COLUMNS = [
    'pc_mxene_loading',
    'laminin_peptide_loading',
    'stimulation_frequency',
    'applied_voltage'
]

# Target Column
TARGET_COLUMN = 'cell_viability'
```

#### Model Hyperparameters

**ANN Model Configuration** (`src/ann_model.py`):
```python
MODEL_CONFIG = {
    'input_dim': 4,
    'hidden_layers': [64, 32, 16],
    'activation': 'relu',
    'dropout_rate': 0.2,
    'output_activation': 'sigmoid',
    'learning_rate': 0.001,
    'batch_size': 32,
    'epochs': 100,
    'early_stopping_patience': 10
}
```

**XGBoost Model Configuration** (`src/xgboost_model.py`):
```python
XGBOOST_CONFIG = {
    'max_depth': 6,
    'learning_rate': 0.1,
    'n_estimators': 100,
    'objective': 'reg:squarederror',
    'subsample': 0.8,
    'colsample_bytree': 0.8,
    'random_state': 42
}
```

**Random Forest Model Configuration** (`src/random_forest_model.py`):
```python
RANDOM_FOREST_CONFIG = {
    'n_estimators': 200,           # Number of trees
    'max_depth': 15,               # Maximum tree depth
    'min_samples_split': 5,        # Min samples to split node
    'min_samples_leaf': 2,         # Min samples in leaf node
    'max_features': 'sqrt',        # Features to consider for split
    'bootstrap': True,             # Use bootstrap sampling
    'random_state': 42,
    'n_jobs': -1                   # Use all CPU cores
}
```

### Data Preprocessing and Augmentation

#### Built-in Data Cleaning

The `clean_data` step performs:

1. **Missing Value Handling**
   - Remove rows with missing values
   - Or impute with mean/median

2. **Outlier Detection**
   - Z-score method (threshold: 3)
   - IQR method for robust detection

3. **Feature Scaling**
   - StandardScaler for ANN models
   - MinMaxScaler option available

4. **Data Splitting**
   - Training: 60%
   - Validation: 20%
   - Testing: 20%

#### Custom Data Augmentation

Add custom augmentation in `steps/clean_data.py`:

```python
def augment_data(X, y, augmentation_factor=1.5):
    """
    Augment training data with noise injection
    """
    # Add Gaussian noise to features
    noise_std = 0.01
    X_augmented = X + np.random.normal(0, noise_std, X.shape)
    
    # Combine original and augmented data
    X_combined = np.vstack([X, X_augmented])
    y_combined = np.hstack([y, y])
    
    return X_combined, y_combined
```

### Training Progress Tracking

#### Real-time Monitoring

Training progress is saved to `training_progress/` directory:

```json
{
  "session_id": "abc123-def456",
  "model_type": "ANN",
  "start_time": "2025-12-30T10:00:00Z",
  "status": "training",
  "current_epoch": 45,
  "total_epochs": 100,
  "current_loss": 0.0234,
  "current_accuracy": 0.945,
  "best_accuracy": 0.952,
  "estimated_time_remaining": "5 minutes"
}
```

#### View Training Progress

**Option 1: Check JSON File**
```bash
cat training_progress/<session_id>.json
```

**Option 2: Monitor Logs**
```bash
# Docker
docker-compose logs -f predict-service | grep "Training"

# Manual
tail -f predict-service/logs/training.log
```

### MLflow Integration

The system uses MLflow for experiment tracking and model versioning.

#### MLflow UI Access

**Start MLflow UI**:
```bash
# Navigate to predict-service
cd predict-service

# Start MLflow server
mlflow ui --backend-store-uri app/mlruns --port 5001
```

Access at: http://localhost:5001

#### What MLflow Tracks

For each training run:
- ✅ **Parameters**: All hyperparameters used
- ✅ **Metrics**: Accuracy, loss, MAE, RMSE, R²
- ✅ **Artifacts**: Model files, scalers, plots
- ✅ **Tags**: Model type, version, dataset
- ✅ **Duration**: Training time
- ✅ **Environment**: Python version, package versions

#### Logging Custom Metrics

```python
import mlflow

with mlflow.start_run():
    # Log parameters
    mlflow.log_param("learning_rate", 0.001)
    mlflow.log_param("batch_size", 32)
    
    # Log metrics
    mlflow.log_metric("train_accuracy", 0.95)
    mlflow.log_metric("val_accuracy", 0.92)
    
    # Log model
    mlflow.keras.log_model(model, "model")
    
    # Log artifacts
    mlflow.log_artifact("scaler.pkl")
```

### Model Evaluation

After training, models are automatically evaluated on test data.

#### Evaluation Metrics

**Regression Metrics**:
- **MAE** (Mean Absolute Error): Average absolute difference
- **RMSE** (Root Mean Squared Error): Penalizes large errors
- **R² Score**: Proportion of variance explained (0-1)
- **MAPE** (Mean Absolute Percentage Error): Percentage error

**Example Output**:
```
Model Evaluation Results:
========================
Model Type: ANN
Test Samples: 200

Metrics:
  MAE:  2.34%
  RMSE: 3.12%
  R²:   0.952
  MAPE: 2.8%

Prediction Distribution:
  Mean Prediction: 85.3%
  Std Deviation:   12.4%
  Min Prediction:  45.2%
  Max Prediction:  99.8%
```

### Model Registration

After successful training and evaluation:

1. **Automatic Registration**
   - Model saved to `app/models/`
   - Scaler saved alongside model
   - Metadata recorded in database

2. **Model Naming Convention**
   ```
   {model_type}_{version}_{timestamp}.h5
   
   Example:
   ann_v2.0_20251230_103045.h5
   xgboost_v1.5_20251230_104512.pkl
   ```

3. **Admin Approval**
   - New models marked as "pending"
   - Admin reviews performance metrics
   - Activates for production use

### Best Practices for Training

#### 1. Data Quality
- ✅ Ensure sufficient training data (minimum 500 samples)
- ✅ Balance dataset if needed
- ✅ Remove duplicates
- ✅ Validate data ranges

#### 2. Hyperparameter Tuning
- Start with default parameters
- Use grid search or random search
- Monitor validation metrics
- Avoid overfitting

#### 3. Training Monitoring
- Watch for overfitting (train vs. val accuracy)
- Use early stopping
- Save best model based on validation performance
- Log all experiments in MLflow

#### 4. Model Validation
- Always test on held-out test set
- Compare with baseline models
- Check predictions on edge cases
- Validate with domain experts


## 📚 API Reference

### Complete API Endpoints

#### Authentication Endpoints

##### POST /api/login
Authenticate user and obtain JWT token.

**Request**:
```json
{
  username: "your_username",
  "password": "password"
}
```

**Response**:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

##### POST /api/logout
Invalidate current token.

**Headers**:
```
Authorization: Bearer YOUR_TOKEN
```

**Response**:
```json
{
  "message": "Successfully logged out"
}
```

#### User Endpoints

##### GET /api/user
Get authenticated user information.

##### PUT /api/user
Update user profile.

##### POST /api/user/change-password
Change user password.

#### Model Endpoints

##### GET /api/models
List all available models.

**Response**:
```json
{
  "models": [
    {
      "id": 1,
      "name": "ANN Model v2.0",
      "version": "2.0.0",
      "description": "Enhanced neural network model",
      "accuracy": 0.95,
      "file_path": "models/ann_model_v2.h5",
      "status": "active",
      "created_at": "2025-01-15T10:00:00Z"
    }
  ]
}
```

##### GET /api/models/{id}
Get specific model details.

##### POST /api/models (Admin only)
Upload new model.

##### PUT /api/models/{id} (Admin only)
Update model metadata.

##### DELETE /api/models/{id} (Admin only)
Delete model.

#### Prediction Endpoints

##### POST /api/predictions
Create new prediction.

##### GET /api/predictions
List user's predictions.

##### GET /api/predictions/{id}
Get specific prediction details.

##### DELETE /api/predictions/{id}
Delete prediction record.

### Input Parameter Specifications

| Parameter | Type | Unit | Min | Max | Description |
|-----------|------|------|-----|-----|-------------|
| pc_mxene_loading | float | mg/mL | 0.0 | 50.0 | PC-MXene concentration |
| laminin_peptide_loading | float | mg/mL | 0.0 | 10.0 | Laminin peptide concentration |
| stimulation_frequency | integer | Hz | 0 | 1000 | Electrical stimulation frequency |
| applied_voltage | float | V | 0.0 | 100.0 | Applied voltage |

### Output Format

All prediction responses include:

```json
{
  "prediction": 87.5,           // Predicted viability (%)
  "confidence": 0.95,           // Model confidence (0-1)
  "unit": "%",                  // Result unit
  "model_used": "ann_v2.h5",   // Model identifier
  "processing_time": 1.2,       // Time in seconds
  "timestamp": "ISO8601",       // Prediction timestamp
  "user": "user@example.com"    // User who made prediction
}
```



---

## 📝 Additional Resources

### Documentation Links

- [Laravel Documentation](https://laravel.com/docs)
- [Flask Documentation](https://flask.palletsprojects.com/)
- [Docker Documentation](https://docs.docker.com/)
- [TensorFlow/Keras](https://www.tensorflow.org/guide/keras)

### Project Resources

- **GitHub Repository**: https://github.com/nguyenhuuluan1702/PCS_MLops
- **Issue Tracker**: https://github.com/nguyenhuuluan1702/PCS_MLops/issues
- **CI/CD Pipeline**: GitHub Actions

### Support

For additional support:
- 📧 **Email**: nguyenhuuluantvtc@gmail.com
- 💬 **Discussions**: GitHub Discussions

---


**Document Version**: 1.0  
**Last Updated**: December 30, 2025  

For the latest updates to this documentation, please visit the GitHub repository.
