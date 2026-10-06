# Task B — Generate & Print Barcode (PHP Native)

REST API inventaris belanja kantor + generate barcode + data label cetak. PHP Native tanpa framework, MySQL via PDO.

## Struktur

```
public/index.php   router + dispatcher
public/index.html  tabel barang, filter, CRUD, checkbox, generate, print
public/app.js      web client logic
public/print.html  halaman cetak (JsBarcode Code128 + @media print)
src/Database.php   koneksi PDO
src/helpers.php    jsonResponse, parseJsonBody, isValidBarcode, generateBarcode
src/ItemRepository.php  semua SQL
src/BarcodeService.php  sequence MAX+1 per periode + labels
src/Validator.php  validasi create/patch/ids/copies
schema.sql  seed.sql
```

## Cara Jalan

```bash
mysql -u root < schema.sql
mysql -u root < seed.sql
DB_HOST=127.0.0.1 DB_NAME=inventory_db DB_USER=root DB_PASS= php -S localhost:8000 -t public
# buka http://localhost:8000
```

Env opsional: `DB_HOST DB_NAME DB_USER DB_PASS` (default 127.0.0.1/inventory_db/root/kosong).

## Koleksi Request (curl)

```bash
# Items
curl -X POST localhost:8000/api/items -H 'Content-Type: application/json' \
  -d '{"period_id":2,"name":"Amplop","category":"ATK","unit":"pack","quantity":5,"price":12000}'
curl 'localhost:8000/api/items?period_id=2&has_barcode=false&page=1'
curl localhost:8000/api/items/1
curl -X PATCH localhost:8000/api/items/1 -H 'Content-Type: application/json' \
  -d '{"name":"Kertas A4 Premium","price":60000}'
curl -X DELETE localhost:8000/api/items/3

# Barcode
curl -X POST localhost:8000/api/periods/2/barcodes
curl localhost:8000/api/barcodes/BLJ-202609-0001
curl 'localhost:8000/api/labels?ids=1,2&copies=2'

# Kasus error
curl -X POST localhost:8000/api/periods/1/barcodes        # 422 CLOSED
curl localhost:8000/api/barcodes/SALAH                    # 422 format
curl localhost:8000/api/barcodes/BLJ-202609-9999          # 404
curl 'localhost:8000/api/labels?ids=1,999&copies=2'       # 422 invalid_ids
curl 'localhost:8000/api/labels?ids=1&copies=99'          # 422 copies
```

## Aturan Bisnis

- POST: `stock = quantity`, `barcode = NULL`.
- PATCH hanya `name, category, unit, price`.
- DELETE ditolak bila `barcode NOT NULL`; semua mutasi + generate ditolak bila periode CLOSED (422).
- Barcode `BLJ-{YYYYMM}-{NNNN}`, sequence per periode dari MAX (bukan COUNT), hanya untuk `barcode IS NULL`, permanen.
- Labels: `copies` 1-50 default 1, duplikasi per barang; ID tak dikenal / tanpa barcode → 422 + `invalid_ids`.

## GITHUB REPOSITORY

https://github.com/qhiyn/generate_and_print_barcode.git
