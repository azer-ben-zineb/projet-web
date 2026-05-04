CREATE DATABASE IF NOT EXISTS ao_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ao_shop;

CREATE TABLE categories (
    id_categorie INT AUTO_INCREMENT PRIMARY KEY,
    nom_categorie VARCHAR(100) UNIQUE NOT NULL,
    nom_categorie_en VARCHAR(100)
) ENGINE=InnoDB;


CREATE TABLE produits (
    reference VARCHAR(50) PRIMARY KEY,
    designation VARCHAR(150) NOT NULL,
    designation_en VARCHAR(150),
    description TEXT,
    description_en TEXT,
    marque VARCHAR(100) NOT NULL,
    prix DECIMAL(10,2) NOT NULL CHECK (prix > 0),
    quantite INT NOT NULL DEFAULT 0 CHECK (quantite >= 0),
    photo VARCHAR(255),
    id_categorie INT NOT NULL,
    nombre_ventes INT DEFAULT 0,
    CONSTRAINT fk_prod_cat FOREIGN KEY (id_categorie) REFERENCES categories(id_categorie) ON DELETE RESTRICT,
    INDEX idx_marque (marque),
    INDEX idx_prix (prix),
    INDEX idx_ventes (nombre_ventes)
) ENGINE=InnoDB;


CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('client', 'admin') DEFAULT 'client',
    solde DECIMAL(10,2) DEFAULT 100000.00,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
) ENGINE=InnoDB;


CREATE TABLE panier (
    id_panier INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    reference_produit VARCHAR(50) NOT NULL,
    quantite INT DEFAULT 1,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    expiration DATETIME,
    CONSTRAINT fk_panier_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    CONSTRAINT fk_panier_prod FOREIGN KEY (reference_produit) REFERENCES produits(reference) ON DELETE CASCADE,
    INDEX idx_expiration (expiration),
    UNIQUE KEY unique_panier_item (id_user, reference_produit)
) ENGINE=InnoDB;


CREATE TABLE commandes (
    id_commande INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    reference_produit VARCHAR(50) NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cmd_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    CONSTRAINT fk_cmd_prod FOREIGN KEY (reference_produit) REFERENCES produits(reference) ON DELETE RESTRICT,
    INDEX idx_date_cmd (date_commande)
) ENGINE=InnoDB;


CREATE TABLE abonnements (
    id_abonnement INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    type_abonnement ENUM('mensuel', 'annuel'),
    prix_abonnement DECIMAL(10,2),
    date_debut DATETIME,
    date_fin DATETIME,
    actif TINYINT(1) DEFAULT 1,
    CONSTRAINT fk_abo_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    INDEX idx_abo_actif (actif)
) ENGINE=InnoDB;


CREATE TABLE coupons (
    id_coupon INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    code_coupon VARCHAR(20) UNIQUE,
    reduction INT CHECK (reduction IN (5, 10, 15, 20, 25)),
    date_obtention DATETIME DEFAULT CURRENT_TIMESTAMP,
    expiration DATETIME,
    utilise TINYINT(1) DEFAULT 0,
    CONSTRAINT fk_cpn_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    INDEX idx_exp_coupon (expiration),
    INDEX idx_utilise (utilise)
) ENGINE=InnoDB;


CREATE TABLE roulette_log (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    derniere_spin DATETIME,
    gratuit_utilise TINYINT(1) DEFAULT 0,
    CONSTRAINT fk_rl_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    UNIQUE KEY unique_user_spin (id_user)
) ENGINE=InnoDB;


INSERT INTO categories (nom_categorie, nom_categorie_en) VALUES
('Ordinateurs', 'Laptops & Computers'),
('Smartphones', 'Smartphones'),
('Tablettes', 'Tablets'),
('Écrans PC', 'PC Monitors'),
('Écouteurs', 'Headphones'),
('Imprimantes', 'Printers'),
('Scanners', 'Scanners'),
('Cartouches d\'encre', 'Ink Cartridges'),
('Disques durs', 'Hard Drives'),
('Chargeurs', 'Chargers'),
('Montres connectées', 'Smartwatches');



-- Catégorie 1: Ordinateurs (1500-5000 DT)
INSERT INTO produits (reference, designation, designation_en, description, description_en, marque, prix, quantite, photo, id_categorie, nombre_ventes) VALUES
('ORD-APL-MAC14', 'MacBook Pro 14" M3', 'MacBook Pro 14" M3', 'Ordinateur portable Apple avec puce M3, 18 Go RAM, SSD 512 Go. Performance exceptionnelle pour les professionnels.', 'Apple laptop with M3 chip, 18GB RAM, 512GB SSD. Exceptional performance for professionals.', 'Apple', 4850.00, 12, 'macbook_pro_14.jpg', 1, FLOOR(10 + RAND() * 490)),
('ORD-DEL-XPS15', 'Dell XPS 15', 'Dell XPS 15', 'Écran InfinityEdge 15.6" FHD+, Intel Core i7, 16 Go RAM, SSD 1 To. Design premium aluminium.', 'InfinityEdge 15.6" FHD+ display, Intel Core i7, 16GB RAM, 1TB SSD. Premium aluminium design.', 'Dell', 3890.00, 8, 'dell_xps15.jpg', 1, FLOOR(10 + RAND() * 490)),
('ORD-HP-PAV15', 'HP Pavilion 15', 'HP Pavilion 15', 'Ordinateur polyvalent 15.6" FHD, AMD Ryzen 7, 16 Go RAM, SSD 512 Go. Idéal pour la bureautique.', 'Versatile 15.6" FHD laptop, AMD Ryzen 7, 16GB RAM, 512GB SSD. Ideal for office work.', 'HP', 2190.00, 15, 'hp_pavilion15.jpg', 1, FLOOR(10 + RAND() * 490)),
('ORD-LEN-TX1', 'Lenovo ThinkPad X1', 'Lenovo ThinkPad X1', 'Ultrabook professionnel 14" WQXGA, Intel Core i7 vPro, 16 Go RAM, SSD 512 Go. Clavier légendaire.', 'Professional 14" WQXGA ultrabook, Intel Core i7 vPro, 16GB RAM, 512GB SSD. Legendary keyboard.', 'Lenovo', 4200.00, 6, 'thinkpad_x1.jpg', 1, FLOOR(10 + RAND() * 490)),
('ORD-ASU-ZEN14', 'ASUS ZenBook 14', 'ASUS ZenBook 14', 'Ultra léger 1.19 kg, écran OLED 14", Intel Core i5, 8 Go RAM, SSD 512 Go. Autonomie 18h.', 'Ultra light 1.19kg, 14" OLED display, Intel Core i5, 8GB RAM, 512GB SSD. 18h battery life.', 'ASUS', 2890.00, 9, 'zenbook_14.jpg', 1, FLOOR(10 + RAND() * 490)),
('ORD-ACE-NIT5', 'Acer Nitro 5', 'Acer Nitro 5', 'PC Gamer 15.6" 144Hz, Intel Core i5, RTX 3050, 16 Go RAM, SSD 512 Go. Refroidissement avancé.', 'Gaming laptop 15.6" 144Hz, Intel Core i5, RTX 3050, 16GB RAM, 512GB SSD. Advanced cooling.', 'Acer', 2490.00, 11, 'nitro5.jpg', 1, FLOOR(10 + RAND() * 490));

-- Catégorie 2: Smartphones (800-2500 DT)
INSERT INTO produits (reference, designation, designation_en, description, description_en, marque, prix, quantite, photo, id_categorie, nombre_ventes) VALUES
('SMR-SAM-S24U', 'Samsung Galaxy S24 Ultra', 'Samsung Galaxy S24 Ultra', 'Écran AMOLED 6.8" 120Hz, Snapdragon 8 Gen 3, 256 Go, S Pen intégré. Photo 200MP.', '6.8" AMOLED 120Hz display, Snapdragon 8 Gen 3, 256GB, integrated S Pen. 200MP camera.', 'Samsung', 2390.00, 20, 's24_ultra.jpg', 2, FLOOR(10 + RAND() * 490)),
('SMR-APL-IP15P', 'iPhone 15 Pro', 'iPhone 15 Pro', 'Titanium design, A17 Pro, 128 Go, USB-C, Dynamic Island. Photo pro 48MP.', 'Titanium design, A17 Pro, 128GB, USB-C, Dynamic Island. Pro 48MP camera.', 'Apple', 2290.00, 18, 'iphone15_pro.jpg', 2, FLOOR(10 + RAND() * 490)),
('SMR-XIA-13TP', 'Xiaomi 13T Pro', 'Xiaomi 13T Pro', 'Écran AMOLED 6.67" 144Hz, Dimensity 9200+, 256 Go, charge 120W. Photo Leica.', '6.67" AMOLED 144Hz display, Dimensity 9200+, 256GB, 120W charging. Leica camera.', 'Xiaomi', 1490.00, 25, 'xiaomi_13t_pro.jpg', 2, FLOOR(10 + RAND() * 490)),
('SMR-HUA-P60P', 'Huawei P60 Pro', 'Huawei P60 Pro', 'Écran OLED 6.67" 120Hz, Snapdragon 8+ Gen 1, 256 Go. Photo ultra éclairée.', '6.67" OLED 120Hz display, Snapdragon 8+ Gen 1, 256GB. Ultra lighting camera.', 'Huawei', 1890.00, 14, 'p60_pro.jpg', 2, FLOOR(10 + RAND() * 490)),
('SMR-OPR-R11', 'OPPO Reno 11', 'OPPO Reno 11', 'Écran AMOLED 6.7" 120Hz, Dimensity 7050, 256 Go, charge 67W. Design slim.', '6.7" AMOLED 120Hz display, Dimensity 7050, 256GB, 67W charging. Slim design.', 'OPPO', 890.00, 22, 'reno11.jpg', 2, FLOOR(10 + RAND() * 490)),
('SMR-REL-GT5', 'realme GT 5', 'realme GT 5', 'Écran AMOLED 6.74" 144Hz, Snapdragon 8 Gen 2, 256 Go, charge 150W.', '6.74" AMOLED 144Hz display, Snapdragon 8 Gen 2, 256GB, 150W charging.', 'realme', 1190.00, 17, 'gt5.jpg', 2, FLOOR(10 + RAND() * 490));

-- Catégorie 3: Tablettes (600-2000 DT)
INSERT INTO produits (reference, designation, designation_en, description, description_en, marque, prix, quantite, photo, id_categorie, nombre_ventes) VALUES
('TAB-APL-IPADP', 'iPad Pro 11" M2', 'iPad Pro 11" M2', 'Écran Liquid Retina 11", puce M2, 128 Go, WiFi 6E, USB-C. Puissance professionnelle.', '11" Liquid Retina display, M2 chip, 128GB, WiFi 6E, USB-C. Professional power.', 'Apple', 1980.00, 10, 'ipad_pro_11.jpg', 3, FLOOR(10 + RAND() * 490)),
('TAB-SAM-TABS9', 'Samsung Galaxy Tab S9', 'Samsung Galaxy Tab S9', 'Écran AMOLED 11" 120Hz, Snapdragon 8 Gen 2, 128 Go, S Pen inclus. IP68.', '11" AMOLED 120Hz display, Snapdragon 8 Gen 2, 128GB, S Pen included. IP68.', 'Samsung', 1690.00, 12, 'tab_s9.jpg', 3, FLOOR(10 + RAND() * 490)),
('TAB-LEN-P12', 'Lenovo Tab P12', 'Lenovo Tab P12', 'Écran 12.7" 3K, MediaTek Dimensity 7050, 128 Go, 4 haut-parleurs JBL.', '12.7" 3K display, MediaTek Dimensity 7050, 128GB, 4 JBL speakers.', 'Lenovo', 790.00, 18, 'tab_p12.jpg', 3, FLOOR(10 + RAND() * 490)),
('TAB-XIA-PAD6', 'Xiaomi Pad 6', 'Xiaomi Pad 6', 'Écran 11" 144Hz 2.8K, Snapdragon 870, 128 Go, 8840 mAh. Charge 33W.', '11" 144Hz 2.8K display, Snapdragon 870, 128GB, 8840 mAh. 33W charging.', 'Xiaomi', 690.00, 20, 'pad6.jpg', 3, FLOOR(10 + RAND() * 490)),
('TAB-HUA-MP11', 'Huawei MatePad 11', 'Huawei MatePad 11', 'Écran 11" 120Hz 2.5K, Snapdragon 865, 128 Go, M-Pencil support.', '11" 120Hz 2.5K display, Snapdragon 865, 128GB, M-Pencil support.', 'Huawei', 850.00, 15, 'matepad11.jpg', 3, FLOOR(10 + RAND() * 490)),
('TAB-HON-PAD9', 'HONOR Pad 9', 'HONOR Pad 9', 'Écran 12.1" 2.5K 120Hz, Snapdragon 6 Gen 1, 256 Go, 8 haut-parleurs.', '12.1" 2.5K 120Hz display, Snapdragon 6 Gen 1, 256GB, 8 speakers.', 'HONOR', 720.00, 14, 'pad9.jpg', 3, FLOOR(10 + RAND() * 490));

-- Catégorie 4: Écrans PC (400-1800 DT)
INSERT INTO produits (reference, designation, designation_en, description, description_en, marque, prix, quantite, photo, id_categorie, nombre_ventes) VALUES
('ECR-DEL-U2724', 'Dell UltraSharp 27" U2724D', 'Dell UltraSharp 27" U2724D', 'Écran IPS 27" QHD 120Hz, USB-C 90W, colorimétrie professionnelle. Design sans bords.', '27" IPS QHD 120Hz monitor, USB-C 90W, professional color accuracy. Borderless design.', 'Dell', 1290.00, 7, 'u2724d.jpg', 4, FLOOR(10 + RAND() * 490)),
('ECR-SAM-OG9', 'Samsung Odyssey G9', 'Samsung Odyssey G9', 'Écran incurvé 49" DQHD 240Hz, 1ms, HDR1000. Immersion totale gaming.', '49" curved DQHD 240Hz monitor, 1ms, HDR1000. Total gaming immersion.', 'Samsung', 1790.00, 4, 'odyssey_g9.jpg', 4, FLOOR(10 + RAND() * 490)),
('ECR-LG-27GP', 'LG UltraGear 27" QHD', 'LG UltraGear 27" QHD', 'Écran IPS 27" QHD 165Hz, 1ms GtG, HDR10, sRGB 99%. Pour gamers.', '27" IPS QHD 165Hz monitor, 1ms GtG, HDR10, sRGB 99%. For gamers.', 'LG', 890.00, 13, 'ultragear27.jpg', 4, FLOOR(10 + RAND() * 490)),
('ECR-ASU-VB27', 'ASUS VA27EHE', 'ASUS VA27EHE', 'Écran 27" Full HD 75Hz, IPS, sans cadre, Eye Care. Idéal bureautique.', '27" Full HD 75Hz IPS monitor, frameless, Eye Care. Ideal for office.', 'ASUS', 490.00, 16, 'va27ehe.jpg', 4, FLOOR(10 + RAND() * 490)),
('ECR-AOC-C24G', 'AOC C24G2AE', 'AOC C24G2AE', 'Écran incurvé 24" FHD 165Hz, VA, 1ms, FreeSync Premium. Budget gaming.', '24" curved FHD 165Hz VA monitor, 1ms, FreeSync Premium. Budget gaming.', 'AOC', 420.00, 19, 'c24g2ae.jpg', 4, FLOOR(10 + RAND() * 490)),
('ECR-BEN-PD270', 'BenQ PD2705U', 'BenQ PD2705U', 'Écran 27" 4K IPS, USB-C, colorimétrie AQCOLOR, mode Darkroom. Pour créatifs.', '27" 4K IPS monitor, USB-C, AQCOLOR, Darkroom mode. For creatives.', 'BenQ', 1580.00, 5, 'pd2705u.jpg', 4, FLOOR(10 + RAND() * 490));

-- Catégorie 5: Écouteurs (50-400 DT)
INSERT INTO produits (reference, designation, designation_en, description, description_en, marque, prix, quantite, photo, id_categorie, nombre_ventes) VALUES
('ECO-APL-AP2', 'AirPods Pro 2', 'AirPods Pro 2', 'Réduction active du bruit, audio spatial personnalisé, MagSafe, autonomie 6h.', 'Active noise cancellation, personalized spatial audio, MagSafe, 6h battery.', 'Apple', 890.00, 30, 'airpods_pro2.jpg', 5, FLOOR(10 + RAND() * 490)),
('ECO-SON-WF10', 'Sony WF-1000XM5', 'Sony WF-1000XM5', 'ANC leader, Hi-Res Audio, 8h d\'autonomie, design compact. Meilleur du marché.', 'Industry-leading ANC, Hi-Res Audio, 8h battery, compact design. Best on market.', 'Sony', 790.00, 22, 'wf1000xm5.jpg', 5, FLOOR(10 + RAND() * 490)),
('ECO-SAM-BUD2P', 'Samsung Galaxy Buds2 Pro', 'Samsung Galaxy Buds2 Pro', 'ANC intelligent, audio 360°, étanchéité IPX7. Confort amélioré.', 'Intelligent ANC, 360 audio, IPX7 water resistance. Improved comfort.', 'Samsung', 490.00, 25, 'buds2_pro.jpg', 5, FLOOR(10 + RAND() * 490)),
('ECO-JBL-T77', 'JBL Tune 770NC', 'JBL Tune 770NC', 'ANC adaptative, JBL Pure Bass, 70h d\'autonomie, multipoint. Excellent rapport qualité-prix.', 'Adaptive ANC, JBL Pure Bass, 70h battery, multipoint. Great value.', 'JBL', 290.00, 35, 'tune770nc.jpg', 5, FLOOR(10 + RAND() * 490)),
('ECO-XIA-BUD4', 'Xiaomi Redmi Buds 4 Pro', 'Xiaomi Redmi Buds 4 Pro', 'ANC 43dB, mode transparence, autonomie 36h avec boîtier. Son HiFi.', '43dB ANC, transparency mode, 36h total battery. HiFi sound.', 'Xiaomi', 190.00, 40, 'buds4_pro.jpg', 5, FLOOR(10 + RAND() * 490)),
('ECO-ANK-S25', 'Anker Soundcore Life P3', 'Anker Soundcore Life P3', 'ANC personnalisable, basses puissantes, mode jeu. 10h d\'autonomie.', 'Customizable ANC, powerful bass, gaming mode. 10h battery.', 'Anker', 150.00, 28, 'life_p3.jpg', 5, FLOOR(10 + RAND() * 490));

-- Catégorie 6: Imprimantes (200-900 DT)
INSERT INTO produits (reference, designation, designation_en, description, description_en, marque, prix, quantite, photo, id_categorie, nombre_ventes) VALUES
('IMP-HP-LAS17', 'HP LaserJet Pro M17w', 'HP LaserJet Pro M17w', 'Imprimante laser monochrome WiFi, 20 ppm, compacte. Idétudiant.', 'Monochrome laser WiFi printer, 20 ppm, compact. Ideal for students.', 'HP', 290.00, 14, 'laserjet_m17w.jpg', 6, FLOOR(10 + RAND() * 490)),
('IMP-CAN-G31', 'Canon PIXMA G3411', 'Canon PIXMA G3411', 'Imprimante réservoir intégré 3en1, WiFi, haut rendement. Économique long terme.', 'Integrated tank 3in1 printer, WiFi, high yield. Economical long term.', 'Canon', 380.00, 11, 'g3411.jpg', 6, FLOOR(10 + RAND() * 490)),
('IMP-EPS-L42', 'Epson EcoTank L4260', 'Epson EcoTank L4260', 'Imprimante réservoir 3en1, WiFi Direct, impression sans marges. Très économique.', 'Tank 3in1 printer, WiFi Direct, borderless printing. Very economical.', 'Epson', 450.00, 9, 'l4260.jpg', 6, FLOOR(10 + RAND() * 490)),
('IMP-HP-ENVY', 'HP ENVY 6020e', 'HP ENVY 6020e', 'Imprimante 3en1 Jet d\'encre, WiFi, HP Instant Ink ready. Polyvalente.', '3in1 inkjet printer, WiFi, HP Instant Ink ready. Versatile.', 'HP', 240.00, 16, 'envy6020e.jpg', 6, FLOOR(10 + RAND() * 490)),
('IMP-BRO-HL', 'Brother HL-L2350DW', 'Brother HL-L2350DW', 'Imprimante laser monochrome WiFi, 30 ppm, recto-verso automatique.', 'Monochrome laser WiFi printer, 30 ppm, auto duplex.', 'Brother', 350.00, 12, 'hl_l2350dw.jpg', 6, FLOOR(10 + RAND() * 490)),
('IMP-CAN-MF', 'Canon i-SENSYS MF443dw', 'Canon i-SENSYS MF443dw', 'Imprimante laser multifonction 3en1, 38 ppm, recto-verso, WiFi.', '3in1 laser multifunction printer, 38 ppm, duplex, WiFi.', 'Canon', 890.00, 6, 'mf443dw.jpg', 6, FLOOR(10 + RAND() * 490));

-- Catégorie 7: Scanners (150-700 DT)
INSERT INTO produits (reference, designation, designation_en, description, description_en, marque, prix, quantite, photo, id_categorie, nombre_ventes) VALUES
('SCA-EPS-V19', 'Epson Perfection V19', 'Epson Perfection V19', 'Scanner plat A4, 4800 dpi, alimentation USB. Compact et design.', 'Flatbed A4 scanner, 4800 dpi, USB powered. Compact and stylish.', 'Epson', 190.00, 13, 'v19.jpg', 7, FLOOR(10 + RAND() * 490)),
('SCA-CAN-LID', 'Canon CanoScan LiDE 400', 'Canon CanoScan LiDE 400', 'Scanner plat A4, 4800 dpi, USB-C, 5 boutons EZ. Design ultra fin.', 'Flatbed A4 scanner, 4800 dpi, USB-C, 5 EZ buttons. Ultra slim.', 'Canon', 210.00, 11, 'lide400.jpg', 7, FLOOR(10 + RAND() * 490)),
('SCA-HP-SJ4', 'HP ScanJet Pro 4500 fn1', 'HP ScanJet Pro 4500 fn1', 'Scanner réseau avec chargeur 50 pages, 30 ppm, recto-verso. Professionnel.', 'Network scanner with 50-page ADF, 30 ppm, duplex. Professional.', 'HP', 680.00, 5, 'scanjet4500.jpg', 7, FLOOR(10 + RAND() * 490)),
('SCA-EPS-DS5', 'Epson WorkForce DS-530', 'Epson WorkForce DS-530', 'Scanner de documents 35 ppm, chargeur 50 pages, recto-verso.', 'Document scanner 35 ppm, 50-page ADF, duplex.', 'Epson', 590.00, 7, 'ds530.jpg', 7, FLOOR(10 + RAND() * 490)),
('SCA-FUJ-FI', 'Fujitsu fi-65F', 'Fujitsu fi-65F', 'Scanner plat compact A4, 600 dpi, chauffage instantané. Pour banques.', 'Compact flatbed A4 scanner, 600 dpi, instant warm-up. For banks.', 'Fujitsu', 450.00, 8, 'fi65f.jpg', 7, FLOOR(10 + RAND() * 490));

-- Catégorie 8: Cartouches d'encre (15-80 DT)
INSERT INTO produits (reference, designation, designation_en, description, description_en, marque, prix, quantite, photo, id_categorie, nombre_ventes) VALUES
('CRT-HP-305', 'HP 305 Noir', 'HP 305 Black', 'Cartouche d\'encre noire originale HP. Rendement ~120 pages.', 'Original HP black ink cartridge. Yield ~120 pages.', 'HP', 35.00, 50, 'hp305_black.jpg', 8, FLOOR(10 + RAND() * 490)),
('CRT-HP-305C', 'HP 305 Trois Couleurs', 'HP 305 Tri-color', 'Cartouche 3 couleurs originale HP. Rendement ~100 pages.', 'Original HP tri-color ink cartridge. Yield ~100 pages.', 'HP', 38.00, 45, 'hp305_color.jpg', 8, FLOOR(10 + RAND() * 490)),
('CRT-CAN-545', 'Canon PG-545 Noir', 'Canon PG-545 Black', 'Cartouche d\'encre noire originale Canon. Rendement ~180 pages.', 'Original Canon black ink cartridge. Yield ~180 pages.', 'Canon', 32.00, 40, 'pg545.jpg', 8, FLOOR(10 + RAND() * 490)),
('CRT-EPS-104', 'Epson 104 Noir EcoTank', 'Epson 104 Black EcoTank', 'Bouteille d\'encre noire EcoTank. Rendement ~4500 pages.', 'EcoTank black ink bottle. Yield ~4500 pages.', 'Epson', 28.00, 35, '104_black.jpg', 8, FLOOR(10 + RAND() * 490)),
('CRT-HP-207X', 'HP 207X Noir Laser', 'HP 207X Black Laser', 'Toner noir haute capacité pour LaserJet. Rendement ~3150 pages.', 'High-capacity black toner for LaserJet. Yield ~3150 pages.', 'HP', 75.00, 22, '207x.jpg', 8, FLOOR(10 + RAND() * 490)),
('CRT-BRO-TN', 'Brother TN-2420 Noir', 'Brother TN-2420 Black', 'Toner noir haute capacité. Rendement ~3000 pages.', 'High-capacity black toner. Yield ~3000 pages.', 'Brother', 68.00, 18, 'tn2420.jpg', 8, FLOOR(10 + RAND() * 490));

-- Catégorie 9: Disques durs (120-600 DT)
INSERT INTO produits (reference, designation, designation_en, description, description_en, marque, prix, quantite, photo, id_categorie, nombre_ventes) VALUES
('DIS-SEA-2TO', 'Seagate Barracuda 2 To', 'Seagate Barracuda 2 TB', 'Disque dur interne 3.5" 2 To, 7200 RPM, 256 Mo cache. Fiable.', 'Internal 3.5" 2TB HDD, 7200 RPM, 256MB cache. Reliable.', 'Seagate', 220.00, 20, 'barracuda_2tb.jpg', 9, FLOOR(10 + RAND() * 490)),
('DIS-WD-4TO', 'Western Digital Blue 4 To', 'Western Digital Blue 4 TB', 'Disque dur interne 3.5" 4 To, 5400 RPM, 256 Mo cache. Stockage massif.', 'Internal 3.5" 4TB HDD, 5400 RPM, 256MB cache. Massive storage.', 'WD', 340.00, 15, 'wd_blue_4tb.jpg', 9, FLOOR(10 + RAND() * 490)),
('DIS-SEA-1TO', 'Seagate FireCuda 1 To SSD', 'Seagate FireCuda 1 TB SSD', 'SSD NVMe M.2 1 To, lecture 7300 Mo/s. Pour gaming.', 'NVMe M.2 1TB SSD, read 7300 MB/s. For gaming.', 'Seagate', 290.00, 12, 'firecuda_1tb.jpg', 9, FLOOR(10 + RAND() * 490)),
('DIS-SAM-87', 'Samsung 870 EVO 500 Go', 'Samsung 870 EVO 500 GB', 'SSD SATA 2.5" 500 Go, lecture 560 Mo/s. Fiable et rapide.', 'SATA 2.5" 500GB SSD, read 560 MB/s. Reliable and fast.', 'Samsung', 170.00, 25, '870evo_500.jpg', 9, FLOOR(10 + RAND() * 490)),
('DIS-WD-P50', 'WD My Passport 2 To', 'WD My Passport 2 TB', 'Disque dur externe portable 2 To, USB 3.2, chiffrement hardware.', 'Portable external HDD 2TB, USB 3.2, hardware encryption.', 'WD', 240.00, 18, 'mypassport_2tb.jpg', 9, FLOOR(10 + RAND() * 490)),
('DIS-SAN-1TO', 'SanDisk Extreme Portable 1 To', 'SanDisk Extreme Portable 1 TB', 'SSD externe portable 1 To, USB-C, résistant aux chocs, IP55.', 'Portable external SSD 1TB, USB-C, shock resistant, IP55.', 'SanDisk', 380.00, 14, 'extreme_1tb.jpg', 9, FLOOR(10 + RAND() * 490));

-- Catégorie 10: Chargeurs (20-150 DT)
INSERT INTO produits (reference, designation, designation_en, description, description_en, marque, prix, quantite, photo, id_categorie, nombre_ventes) VALUES
('CHG-APL-20W', 'Apple Chargeur 20W USB-C', 'Apple 20W USB-C Charger', 'Chargeur mural USB-C 20W, Power Delivery. Pour iPhone/iPad.', 'USB-C wall charger 20W, Power Delivery. For iPhone/iPad.', 'Apple', 85.00, 40, 'apple_20w.jpg', 10, FLOOR(10 + RAND() * 490)),
('CHG-ANK-65', 'Anker Chargeur 65W GaN', 'Anker 65W GaN Charger', 'Chargeur GaN 65W 3 ports, Power Delivery, compact.', 'GaN 65W 3-port charger, Power Delivery, compact.', 'Anker', 120.00, 25, 'anker_65w.jpg', 10, FLOOR(10 + RAND() * 490)),
('CHG-SAM-25W', 'Samsung Chargeur 25W', 'Samsung 25W Charger', 'Chargeur rapide 25W USB-C, Adaptive Fast Charging. Pour Galaxy.', 'Fast charger 25W USB-C, Adaptive Fast Charging. For Galaxy.', 'Samsung', 55.00, 35, 'samsung_25w.jpg', 10, FLOOR(10 + RAND() * 490)),
('CHG-XIA-67', 'Xiaomi Chargeur 67W GaN', 'Xiaomi 67W GaN Charger', 'Chargeur GaN 67W, compatible QC/PD, câble inclus.', 'GaN 67W charger, QC/PD compatible, cable included.', 'Xiaomi', 95.00, 22, 'xiaomi_67w.jpg', 10, FLOOR(10 + RAND() * 490)),
('CHG-BEL-10', 'Belkin Chargeur sans fil 10W', 'Belkin 10W Wireless Charger', 'Chargeur à induction 10W, compatible Qi. Design élégant.', '10W wireless induction charger, Qi compatible. Elegant design.', 'Belkin', 75.00, 18, 'belkin_wireless.jpg', 10, FLOOR(10 + RAND() * 490)),
('CHG-UGR-10', 'UGREEN Chargeur 100W GaN', 'UGREEN 100W GaN Charger', 'Chargeur GaN 100W 4 ports, compatible tous appareils.', 'GaN 100W 4-port charger, compatible with all devices.', 'UGREEN', 145.00, 15, 'ugreen_100w.jpg', 10, FLOOR(10 + RAND() * 490));

-- Catégorie 11: Montres connectées (250-1200 DT)
INSERT INTO produits (reference, designation, designation_en, description, description_en, marque, prix, quantite, photo, id_categorie, nombre_ventes) VALUES
('MTR-APL-WG9', 'Apple Watch Series 9 GPS', 'Apple Watch Series 9 GPS', 'Boîtier aluminium 45mm, S9 SiP, capteur oxygène sanguin. Étanche 50m.', '45mm aluminium case, S9 SiP, blood oxygen sensor. 50m water resistant.', 'Apple', 1190.00, 16, 'watch_s9.jpg', 11, FLOOR(10 + RAND() * 490)),
('MTR-SAM-W6C', 'Samsung Galaxy Watch6 Classic', 'Samsung Galaxy Watch6 Classic', 'Boîtier 47mm, lunette rotative, Wear OS, body composition.', '47mm case, rotating bezel, Wear OS, body composition.', 'Samsung', 890.00, 14, 'watch6_classic.jpg', 11, FLOOR(10 + RAND() * 490)),
('MTR-HUA-GT4', 'Huawei Watch GT 4', 'Huawei Watch GT 4', 'Boîtier 46mm, autonomie 14 jours, GPS, SpO2. Design premium.', '46mm case, 14-day battery, GPS, SpO2. Premium design.', 'Huawei', 590.00, 20, 'gt4.jpg', 11, FLOOR(10 + RAND() * 490)),
('MTR-XIA-R4', 'Xiaomi Redmi Watch 4', 'Xiaomi Redmi Watch 4', 'Écran AMOLED 1.97", autonomie 20 jours, GPS, 150+ modes sport.', '1.97" AMOLED display, 20-day battery, GPS, 150+ sport modes.', 'Xiaomi', 280.00, 30, 'redmi_watch4.jpg', 11, FLOOR(10 + RAND() * 490)),
('MTR-GAR-55', 'Garmin Forerunner 55', 'Garmin Forerunner 55', 'Montre GPS running, autonomie 14 jours, programmes d\'entraînement.', 'GPS running watch, 14-day battery, training programs.', 'Garmin', 520.00, 12, 'forerunner55.jpg', 11, FLOOR(10 + RAND() * 490)),
('MTR-AMA-GTR', 'Amazfit GTR Mini', 'Amazfit GTR Mini', 'Montre fine 42mm, autonomie 14 jours, 120+ modes sport, SpO2.', 'Slim 42mm watch, 14-day battery, 120+ sport modes, SpO2.', 'Amazfit', 250.00, 18, 'gtr_mini.jpg', 11, FLOOR(10 + RAND() * 490));

-- Admin@1234 et Admin@5678
INSERT INTO users (nom, prenom, email, mot_de_passe, role, solde) VALUES
('Ben Ammar', 'Oussama', 'admin1@aoshop.tn', '$2y$10$57.ghHgggsnPnQSbFGsoberUldbEYDx9Euq7Em9z4.E4UwdKA/KHm', 'admin', 0.00),
('Dridi', 'Amira', 'admin2@aoshop.tn', '$2y$10$FKCqKcJIhec8YkFgwARYpuMA.tg.ZDSybyS.XtRVGWQm.HRtedoCe', 'admin', 0.00);


INSERT INTO users (nom, prenom, email, mot_de_passe, role, solde) VALUES
('Trabelsi', 'Mohamed', 'mohamed.trabelsi@email.tn', '$2y$10$DummyHashForTestingClientAccount1ABC123', 'client', 100000.00),
('Bouazizi', 'Fatima', 'fatima.bouazizi@email.tn', '$2y$10$DummyHashForTestingClientAccount2DEF456', 'client', 100000.00),
('Guesmi', 'Karim', 'karim.guesmi@email.tn', '$2y$10$DummyHashForTestingClientAccount3GHI789', 'client', 100000.00);


DELIMITER //
CREATE TRIGGER trg_panier_expiration
BEFORE INSERT ON panier
FOR EACH ROW
BEGIN
    IF NEW.expiration IS NULL THEN
        SET NEW.expiration = DATE_ADD(NEW.date_ajout, INTERVAL 7 DAY);
    END IF;
END//
DELIMITER ;
