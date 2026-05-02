CREATE TABLE mesaje (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100),
    suprafata INT,
    detalii TEXT,
    data_trimiterii TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE setari_preturi (
    id INT PRIMARY KEY,
    pret_m2 DECIMAL(10, 2)
);

INSERT INTO setari_preturi (id, pret_m2) VALUES (1, 15.50);