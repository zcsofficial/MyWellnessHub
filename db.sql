-- Create the database
CREATE DATABASE wellness_hub;
USE wellness_hub;

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    age INT NULL,
    weight FLOAT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Activity logs table
CREATE TABLE activity_logs (
    activity_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    duration INT NOT NULL DEFAULT 0,
    calories_burned FLOAT NULL,
    date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Nutrition logs table
CREATE TABLE nutrition_logs (
    nutrition_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    food_name VARCHAR(100) NOT NULL,
    calories FLOAT NOT NULL DEFAULT 0,
    protein FLOAT NULL,
    carbs FLOAT NULL,
    fats FLOAT NULL,
    date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Wellness logs table
CREATE TABLE wellness_logs (
    wellness_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    mood VARCHAR(20) NOT NULL,
    notes TEXT NULL,
    date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Goals table
CREATE TABLE goals (
    goal_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    goal_type VARCHAR(50) NOT NULL,
    target_value FLOAT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('Active', 'Completed', 'Failed') DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Sessions table (optional)
CREATE TABLE sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

ALTER TABLE users ADD COLUMN email_notifications TINYINT DEFAULT 0;
ALTER TABLE users ADD COLUMN google_fit_token VARCHAR(255) NULL, ADD COLUMN fitbit_token VARCHAR(255) NULL;