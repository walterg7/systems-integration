DROP TABLE IF EXISTS bundles;
CREATE TABLE bundles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    version VARCHAR(255) NOT NULL,
    status ENUM('new', 'passed', 'failed'),
    comment VARCHAR(255) NOT NULL,
    bundle LONGBLOB NOT NULL
)