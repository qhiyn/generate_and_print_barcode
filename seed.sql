USE inventory_db;

INSERT INTO periods (id, name, month, status) VALUES
(1, 'AGUSTUS 2026', '2026-08', 'CLOSED'),
(2, 'SEPTEMBER 2026', '2026-09', 'OPEN');

INSERT INTO items (id, period_id, barcode, name, category, unit, quantity, stock, price) VALUES
  (1, 2, 'BLJ-202609-0001', 'Kertas A4 80gsm', 'ATK', 'pack', 10, 10, 55000),
  (2, 2, 'BLJ-202609-0002', 'Pulpen Biru', 'ATK', 'pcs', 24, 20, 3500),
  (3, 2, NULL, 'Tisu Multipurpose', 'Kebersihan', 'pack', 6, 6, 18000),
  (4, 2, NULL, 'Kopi Tubruk 250g', 'Pantry', 'pack', 4, 4, 32000),
  (5, 2, NULL, 'Gula Pasir 1kg', 'Pantry', 'pack', 5, 5, 17500),
  (6, 1, 'BLJ-202608-0001', 'Sabun Cuci Tangan', 'Kebersihan', 'botol', 8, 3, 22000),
  (7, 1, NULL, 'Teh Celup Sariwangi', 'Pantry', 'pack', 10, 10, 15000);