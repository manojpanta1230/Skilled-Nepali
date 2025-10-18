CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  role ENUM('admin','employer','jobseeker','training_center'),
  status ENUM('active','pending') DEFAULT 'pending'
);

CREATE TABLE jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employer_id INT,
  title VARCHAR(255),
  description TEXT,
  salary VARCHAR(100),
  status ENUM('pending','approved') DEFAULT 'pending',
  FOREIGN KEY (employer_id) REFERENCES users(id)
);

CREATE TABLE courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  training_center_id INT,
  title VARCHAR(255),
  structure TEXT,
  cost VARCHAR(100),
  status ENUM('pending','approved') DEFAULT 'pending',
  FOREIGN KEY (training_center_id) REFERENCES users(id)
);

INSERT INTO users (name,email,password,role,status)
VALUES ('Admin','admin@example.com','<?= password_hash(\"admin123\", PASSWORD_DEFAULT) ?>','admin','active');

CREATE TABLE otp_verification (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  otp_code VARCHAR(10) NOT NULL,
  expires_at DATETIME NOT NULL,
  is_verified TINYINT(1) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
