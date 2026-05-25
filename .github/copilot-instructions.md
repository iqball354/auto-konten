# Copilot Instructions

- Gunakan TypeScript strict mode
- Hindari any
- Gunakan async/await
- Gunakan clean architecture
- Semua logic bisnis di service
- Jangan query database di controller
- Tambahkan error handling
- Jangan membuat kode dummy

# Laravel Project Architecture Rules

## General
- Gunakan PHP 8+ syntax modern
- Ikuti PSR-12 coding standard
- Jangan membuat kode dummy atau placeholder
- Jangan menghapus existing business logic tanpa alasan jelas
- Selalu analisis file terkait sebelum membuat kode baru

## Architecture
- Gunakan clean architecture ringan
- Controller harus tipis
- Semua business logic berada di Service
- Query database tidak boleh berada di Controller
- Akses database hanya melalui Model atau Repository
- Jangan gunakan DB::table langsung kecuali benar-benar diperlukan
- Gunakan Eloquent relationship jika memungkinkan

## Controller Rules
- Controller hanya boleh:
  - menerima request
  - validasi request
  - memanggil service
  - mengembalikan response
- Jangan letakkan query kompleks di controller
- Jangan letakkan logic publish/posting di controller

## Service Rules
- Semua logic bisnis berada di folder Service
- Service harus reusable
- Gunakan dependency injection
- Pisahkan logic Facebook, Instagram, Scheduler, dan Queue

## Model Rules
- Semua akses data melalui Model
- Gunakan fillable/guarded
- Gunakan relationship Eloquent
- Hindari query berulang (N+1)
- Gunakan eager loading jika diperlukan

## Validation
- Gunakan Form Request validation
- Jangan validasi manual di controller
- Semua input wajib divalidasi

## Error Handling
- Gunakan try/catch pada external API
- Gunakan logging untuk error penting
- Jangan gunakan dd() atau dump() pada production code

## Logging
- Gunakan Log::info untuk observability
- Gunakan Log::warning untuk warning
- Gunakan Log::error untuk error
- Tambahkan context array pada log

## Queue Rules
- Semua proses berat wajib menggunakan Queue
- Job harus idempotent
- Gunakan retry dan backoff
- Pisahkan queue heavy dan default jika diperlukan

## Database
- Gunakan migration Laravel
- Jangan hardcode SQL jika Eloquent cukup
- Gunakan transaction untuk operasi multi-query

## API Response
- Gunakan format response konsisten:

{
  "success": true,
  "message": "Success message",
  "data": {}
}

## Security
- Jangan hardcode token atau credential
- Gunakan env()
- Sanitasi input user
- Gunakan authorization jika diperlukan

## File Structure
- Controller hanya di Http/Controllers
- Business logic di Services
- Queue logic di Jobs
- Scheduled task di Console/Commands
- External API integration di Services

## Performance
- Hindari query dalam loop
- Gunakan eager loading
- Cache jika diperlukan
- Hindari duplicate API call

## Code Quality
- Buat function kecil dan fokus
- Gunakan naming yang jelas
- Hindari function terlalu panjang
- Tambahkan typing yang jelas

## Laravel Best Practice
- Gunakan artisan command jika sesuai
- Gunakan dependency injection
- Gunakan config file dibanding hardcode
- Ikuti convention Laravel

## Forbidden
- Jangan query database di Blade
- Jangan query database di Controller
- Jangan letakkan business logic di Route
- Jangan gunakan any style coding PHP procedural

## Exception Handling
- Gunakan exception handling yang proper
- Gunakan try/catch pada:
  - database operation
  - external API call
  - file upload
  - queue process
- Gunakan Throwable atau Exception sesuai kebutuhan
- Jangan swallow exception tanpa logging
- Semua error penting wajib di-log menggunakan Log::error
- Tambahkan context array pada logging

Contoh:
Log::error('Publish failed', [
    'post_id' => $post->id,
    'error' => $e->getMessage()
]);

- Gunakan custom exception jika logic bisnis kompleks
- Jangan gunakan dd(), dump(), var_dump() pada production code
- Gunakan abort() hanya untuk HTTP response sederhana
- Gunakan response JSON konsisten untuk API error

## Database Transaction
- Gunakan DB::transaction() untuk operasi multi-query
- Gunakan transaction pada:
  - create/update data terkait
  - publish workflow
  - scheduler process
  - token refresh process
  - queue critical process

Contoh:
DB::transaction(function () use ($data) {
    // database operations
});

- Jangan melakukan sebagian update tanpa transaction
- Rollback wajib terjadi jika exception muncul
- Hindari transaction terlalu panjang
- Jangan lakukan external API call di dalam transaction jika tidak diperlukan
- Gunakan locking jika race condition memungkinkan

## Queue Safety
- Job harus aman untuk retry
- Hindari duplicate insert saat retry
- Gunakan idempotent process
- Tangani failed job dengan logging yang jelas

## Database Query Rules
- Hindari query dalam loop
- Gunakan eager loading
- Gunakan select field seperlunya
- Gunakan pagination untuk data besar
- Gunakan index-friendly query

## Production Safety
- Jangan expose stack trace ke user
- Gunakan message error yang aman
- Simpan detail error hanya di log