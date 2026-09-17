# Test Audit — tests/

## Baseline
- `php artisan test` => 28 passed, 176 assertions, green.
- Files audited: tests/Feature/Api/{LoginTest,UserIndexTest,UserStoreTest}.php, tests/Feature/Support/AuthTestHelper.php, tests/TestCase.php

## I. Inconsistency (format / convention drift)

1.1. setUp parent call casing
  - LoginTest: `parent::setup();`   (lowercase 's') — works in PHP but wrong casing
  - UserIndexTest / UserStoreTest: `parent::setUp();`
  - AuthTestHelper: mixed usage
  -> Standar Laravel = `parent::setUp();`

1.2. Assertion style mix
  - LoginTest pake `assertJson([...])` + `assertJsonStructure([...])` secara ganda (redundant — assertJson sudah memverifikasi struktur sekaligus).
  - StoreTest pake `assertJsonValidationErrors([...])` (lebih ringkas, idiomatik).
  -> Tidak konsisten. Pilih satu gaya. `assertJsonValidationErrors` lebih ringkas untuk error validasi.

1.3. Request helper gaya campur
  - LoginTest pake `postJson($this->endpoint, [...])` langsung.
  - StoreTest pake `withHeaders(...)->postJson(...)`.
  -> `withHeaders` harus selalu dipanggil sebelum request; konsistenkan urutan.

1.4. Namespace / import tidak dipakai
  - LoginTest: `use Illuminate\Support\Facades\Hash;` terimport tapi tidak dipakai (pengecekan hashing ada di StoreTest pake `\Hash::check` global).
  - UserIndexTest: `use App\Models\User;` dipakai, tapi `use App\Models\Order;` dipakai sebagian.
  -> Hapus import yang tidak dipakai.

1.5. Nama test tidak konsisten
  - Login: `test_login_with_*`
  - Index: `test_return_*`, `test_search_*`, `test_sort_*`, `test_orders_count`, `test_pagination`, `test_empty_parameters`, `test_index_with_*`
  - Store: `test_admin_create_*`, `test_manager_create_*`, `test_user_create_*`, `test_store_user_with_*`
  -> Pola penamaan tak beraturan (ada `test_index_with_*`, `test_store_user_with_*`, tapi `test_orders_count` / `test_pagination` tidak punya prefix).

1.6. `private` property visibility
  - LoginTest: `private string $endpoint`, `private array $attributes` (indexed array).
  - UserIndexTest: `private string $token`, `private string $endpoint`.
  - UserStoreTest: `private string $adminToken, $managerToken, $userToken`, `private string $endpoint`, `private string $email`, `private string $password`.
  -> Konsisten pakai `protected` untuk properti test (subclass-friendly) atau tetap `private` — pilih satu.

## II. Duplicate variable (root cause)

II.A. Credentials hardcoded — 5 sumber yang berpotensi drift
  1. AuthTestHelper::createSeededUsers()     => '#Password123' (hardcoded)
  2. AuthTestHelper::createTestUser()        => '#Password123' (hardcoded di fallback)
  3. AuthTestHelper::login()                 => butuh 'email'+'password' dari caller
  4. LoginTest::$attributes                  => ['admin@example.com', '#Password123']
  5. UserIndexTest::setUp()                  => hardcoded 'admin@example.com' + '#Password123' (2x duplikat, sekaligus login)
  6. UserStoreTest::setUp()                  => 'admin@example.com' '#Password123' (di login() 3x; email 'admin/manager/user@example.com')
  7. UserStoreTest::$email / $password       => 'john@example.com' / '#Password123'

  -> Password '#Password123' direpresentasikan di 6 lokasi; email 'admin@example.com' di 4 lokasi.

II.B. Email 'john@example.com' duplikat
  - UserIndexTest (John Doe, john@example.com) — dua factory identik, sengaja untuk search test.
  - UserStoreTest::$email = 'john@example.com' (payload default).
  -> Beda konteks, tapi nama variabel dan nilai sama.

II.C. Payload 'John Doe' + 'password123' duplikat
  - StoreTest::validPayload() => name 'John Doe', password 'password123'.
  - StoreTest::test_store_user_with_name_* => hardcode 'John Doe' + $this->password lagi.
  -> `validPayload()` dibuat tapi sebagian test nggak pakai (hardcode ulang).

II.D. authHeaders() duplikat pemanggilan
  - UserIndexTest & StoreTest: `$this->withHeaders($this->authHeaders($this->token))` dipanggil 8x di UserIndexTest, 7x di StoreTest. Setiap call bikin variabel $response baru.
  -> Bisa di-enkapsulasi: helper `authenticatedJson(string $token)` atau `actingAs`-style.

II.E. endpoint string duplikat
  - LoginTest::$endpoint = '/api/login' (hanya dipakai di sini, tapi LoginTest juga tidak pakai helper login()).
  - IndexTest & StoreTest: $endpoint = '/api/users'.
  -> Tapi LoginTest tidak pakai AuthTestHelper::login() — sebalihatnya setupnya di duplikat (createTestUser + manual post).

II.F. Assertion blok validasi error duplikat (index/store)
  - 'page' validation: test_index_with_page_zero / negative_page / non_integer_page — rantai `assertUnprocessable -> assertJson([...message...]) -> assertJsonStructure([...])` diulang 3x hampir sama.
  - StoreTest: name min/max, email max(255), password max(255) — blok assertJson + assertJsonStructure diulang 5x.

II.G. AuthTestHelper.login() vs LoginTest setUp
  - AuthTestHelper sudah punya `login(['email'=>...,'password'=>...])` tapi LoginTest tidak pakai — ia pake createTestUser() + manual postJson. Logika login tes duplikat dengan helper yang ada.

## III. Kualitas / smell (bukan duplikat variabel tapi terkait konsistensi)

III.A. Indeks `array $attributes` (LoginTest)
  - `private array $attributes = ['admin@example.com', '#Password123'];` pakai numeric index.
  - Akses: `$this->attributes[0]`, `$this->attributes[1]` — tidak self-explanatory.
  -> Lebih jelas pakai associative: `$credentials = ['email'=>..., 'password'=>...]`.

III.B. `str_repeat` email 244
  - test_store_user_with_email_greater_than_255: `str_repeat('a',244).'@example.com'` — benar (244+12=256). Tapi angka 244 bmagic number; tambah komentar atau helper `longEmail()`.

III.C. `assertJsonMissing(['password' => 'password123'])`
  - Memverifikasi field password tidak ada di response. Benar, tapi `assertJsonMissing` hanya cek key tidak ada — cukup. Tetap.

III.D. StoreTest password inconsistency
  - $this->password = '#Password123' (property), tapi validPayload() pake 'password123' (tanpa '#').
  - test_store_user_with_name_* pake $this->password ('#Password123'), validPayload() pake 'password123'.
  - test_password_hashed_in_database chek 'password123' (dari validPayload).
  -> Dua nilai password beda: '#Password123' vs 'password123'. Berisiko bikin test bingung karena validPayload override.

III.E. LoginTest tidak pakai Mail::fake()
  - StoreTest pakai Mail::fake() karena UserService kirim email.
  - LoginTest tidak butuh (login gak kirim email). OK, tapi konsistenkan: semua test yang melewati UserService->create() harus Mail::fake().

III.F. UserStoreTest.test_user_create_new_user (role user) — seharusnya forbidden
  - Policy: user role => create() false. Test expect assertForbidden + 'This action is unauthorized.' — betul. Tapi StoreUserRequest.authorize() membaca `$this->input('role', 'user')`; payload validPayload() tidak set 'role', default 'user'. Manager test juga tidak set 'role'. Jadi semua create sebagai 'user'. Test 'manager_create' dan 'admin_create' hanya beda token, bukan beda role payload.
  -> Ini bug potensial: test manager/admin create "user" (role user) — mungkin memang begitu memang, tapi tidak uji manager bisa create manager.

## IV. Rekomendasi desain (agenda konsistensi — untuk direview sebelum di-coding)

R1. Centralkan credentials di AuthTestHelper sebagai konstanta/property:
    - ADMIN_EMAIL, MANAGER_EMAIL, USER_EMAIL, TEST_PASSWORD
    - Buat `loginAs(string $role): string` (login admin/manager/user, kembalikan token).
    - Buat `authHeaders(string $token): array` (sudah ada) + `json(string $method, string $uri, array $headers=[], mixed $data=[])`.

R2. LoginTest pake AuthTestHelper::login() — jangan duplicate setup user sendiri.
    LoginTest hanya butuh: createTestUser() + postJson manual karena sedang UJI login. Tapi createTestUser() sudah ada. Setidaknya pakai nama properti konsisten (associative, bukan numeric index).

R3. StoreTest: hapus duplikasi property credential.
    - Hapus $email/$password property; pake validPayload() konsisten.
    - validPayload() jadi satu-satunya sumber payload default. Test length-validation pakai validPayload(['field'=>...]).
    - Perbaiki inkonsistensi '#Password123' vs 'password123': pakai satu konstanta TEST_PASSWORD di helper.

R4. Parameterize validasi-error test.
    - IndexTest 'page' errors (zero/negative/non-int): pake data provider `pageValidationErrorProvider()`.
    - StoreTest length errors (name<3, name>50, email>255, password>255): data provider + satu method assertValidationError.

R5. Konsistenkan gaya assertion:
    - Untuk error validasi: selalu `assertUnprocessable() -> assertJsonValidationErrors([...])`.
    - Jangan dobel `assertJson` + `assertJsonStructure` (assertJson sudah cek struktur).

R6. StoreTest: uji role yang dibedakan di payload, bukan hanya di token.
    - Tambah payload `validPayload(['role'=>'manager'])` untuk uji admin bisa create manager.

R7. Rename properti: `$adminToken,$managerToken,$userToken` -> konsisten, atau pake method loginAs().

## III. Summary
- Duplikat terdalam: credentials ('#Password123', 'admin@example.com') tersebar 6 lokasi.
- Inkonstensi: casing setUp, gaya assertion, penamaan test, visibility property, numeric-index attributes.
- 2 bug/isku: (a) StoreTest password property vs validPayload beda nilai; (b) Manager/Admin create test hanya beda token bukan role payload.
- Semua test lolos, jadi ini refactoring konsistensi (buggable), bukan perbaikan kegagalan.

## IV. Next step
Audit selesai. Tanpa commit/coding. Siapkan `AuthTestHelper` v2 (R1-R2) + data provider (R4-R5) sebagai desain — approval dulu sebelum kode.
