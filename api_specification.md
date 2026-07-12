# Spesifikasi API Backend - MasjidKu (Frontend Requirements)

Dokumen ini berisi daftar lengkap *Endpoint API* dan struktur data (JSON) yang dibutuhkan oleh Frontend (React) agar bisa beroperasi penuh tanpa *LocalStorage* (Mock Data). 

Semua respons (balasan) dari server **DIWAJIBKAN** menggunakan format standar pembungkus berikut:
```json
{
  "success": true,
  "message": "Pesan sukses atau error",
  "data": { ... } // atau array [...]
}
```

---

## 1. Authentication (Otentikasi)
*Digunakan untuk Login, Register, dan sesi Pengguna.*

### A. Register Akun
- **Endpoint:** `POST /api/v1/auth/register`
- **Request Body:**
  ```json
  {
    "name": "Budi Santoso",
    "email": "budi@masjid.com",
    "password": "password123"
  }
  ```
- **Catatan Backend:** Saat register, backend harus otomatis men-generate `slug` unik berdasarkan nama (contoh: `budi-santoso-123`) dan membuatkan *record* profil masjid kosong yang terikat dengan pengguna tersebut.

### B. Login Akun
- **Endpoint:** `POST /api/v1/auth/login`
- **Request Body:** `{ "email": "...", "password": "..." }`
- **Response Data:**
  ```json
  "data": {
    "token": "eyJh...", // Bearer Token (JWT)
    "user": {
      "id": 1,
      "name": "Budi Santoso",
      "email": "budi@masjid.com",
      "slug": "masjid-al-ikhlas",
      "isSetupComplete": true
    }
  }
  ```

---

## 2. Setup & Profile Masjid (Protected - Butuh Token)
*Digunakan di Dashboard dan halaman Setup Wizard.*

### A. Simpan Profil & Konfigurasi Tema Masjid
- **Endpoint:** `PUT /api/v1/dashboard/profile`
- **Request Body (Bisa mengirim sebagian atau seluruhnya):**
  ```json
  {
    "name": "Masjid Al-Ikhlas",
    "domain": "masjid-al-ikhlas",
    "address": "Jl. Kebaikan No. 1",
    "province": "JAWA BARAT",
    "city": "KOTA BANDUNG",
    "template_code": "template-1",
    "primary_color": "#1A5C45"
  }
  ```

### B. Simpan Konfigurasi Jadwal Sholat
- **Endpoint:** `POST /api/v1/dashboard/prayer-config`
- **Request Body:**
  ```json
  {
    "province": "JAWA BARAT",
    "city": "KOTA BANDUNG",
    "imam": {
      "subuh": "Ust. Fulan",
      "dzuhur": "Ust. Ahmad"
    },
    "iqamah": {
      "subuh": 15,
      "dzuhur": 10
    },
    "manual_times": {
      "fajr": "04:30",
      "dhuhr": "12:00",
      "asr": "15:15",
      "maghrib": "18:00",
      "isha": "19:15"
    }
  }
  ```

---

## 3. Kelola Konten (Kajian, Program, Artikel) (Protected)
*CRUD untuk semua jenis postingan.*

### A. Ambil Semua Konten Masjid (Milik User yang Login)
- **Endpoint:** `GET /api/v1/dashboard/posts`
- **Response Data:**
  ```json
  "data": [
    {
      "id": 101,
      "type": "kajian", 
      "title": "Kajian Subuh Bersama",
      "slug": "kajian-subuh-bersama",
      "content": "Isi kajian...",
      "image": "https://url-gambar.com/img.jpg",
      "is_published": true,
      "created_at": "2026-06-10T10:00:00Z"
    }
  ]
  ```
  *(Catatan: Kolom `type` bisa berisi: `"kajian"`, `"program"`, `"artikel"`, `"berita"`, `"galeri"`).*

### B. Buat Konten Baru
- **Endpoint:** `POST /api/v1/dashboard/posts`
- **Request Body:** Mengirim `title`, `content`, `type`, `image`.
- **Catatan Backend:** Otomatis set `is_published: false` dan buatkan *slug* url-nya.

### C. Update, Delete, dan Publish Konten
- **Update:** `PUT /api/v1/dashboard/posts/:id`
- **Delete:** `DELETE /api/v1/dashboard/posts/:id`
- **Toggle Publish:** `PATCH /api/v1/dashboard/posts/:id/publish` (Mengubah `is_published` menjadi true/false).

---

## 4. Public API (Tanpa Token - Untuk Tampilan Template Pengunjung)
*Diakses oleh pengunjung web untuk melihat halaman masjid tertentu berdasarkan slug.*

### A. Ambil Data Master Masjid (Profil, Tema, Jadwal Sholat)
- **Endpoint:** `GET /api/v1/public/:slug/master`
- **Contoh Pemanggilan:** `/api/v1/public/masjid-al-ikhlas/master`
- **Response Data:**
  ```json
  "data": {
    "profile": {
      "name": "Masjid Al-Ikhlas",
      "address": "Jl. Kebaikan No. 1",
      "template_code": "template-1",
      "primary_color": "#1A5C45"
    },
    "prayer_config": {
      "imam": { "subuh": "Ust. Fulan" },
      "iqamah": { "subuh": 15 },
      "times": {
        "fajr": "04:30",
        "dhuhr": "12:00"
      }
    }
  }
  ```

### B. Ambil Daftar Postingan Publik Masjid
- **Endpoint:** `GET /api/v1/public/:slug/posts?type=kajian`
- **Catatan Backend:** **HANYA** kembalikan data yang berstatus `is_published: true`. Jangan bocorkan *draft* yang belum dipublish oleh takmir.
