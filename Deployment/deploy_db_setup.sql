CREATE TABLE bundles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    category ENUM('TheLogger', 'Dashboard', 'Trader', 'Portfolio', 'News', 'Css', 'Database'),
    major INT NOT NULL,
    minor INT NOT NULL,
    patch INT NOT NULL,
    status ENUM('new', 'passed', 'failed'),
    comment VARCHAR(255),
    creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);