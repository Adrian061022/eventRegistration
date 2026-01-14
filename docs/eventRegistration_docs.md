# Event Registration System - Projekt Dokumentáció

## Projekt Áttekintése

Az **Event Registration System** egy Laravel-alapú REST API alkalmazás, amely lehetővé teszi a felhasználók számára, hogy eseményekre regisztráljanak. Az alkalmazás felhasználó-autentifikáción alapul (Laravel Sanctum API tokenek) és szerep-alapú hozzáférés-vezérlést (RBAC) alkalmaz az adminisztrációs funkciók megvédésére.

**Base URL:** `http://127.0.0.1:8000/api` (local development)
vagy `http://localhost/eventRegistration/public/api` (XAMPP)

## Technológia Stack

- **Backend Framework**: Laravel 11
- **Autentifikáció**: Laravel Sanctum (API tokenek)
- **Adatbázis**: MySQL
- **Testing**: PHPUnit (Feature tesztek)
- **Package Manager**: Composer, npm
- **PHP verzió**: 8.2+

## Adatbázis Terv

```
+---------------------+     +------------------+       +------------------+        +-----------------+
|personal_access_tokens|    |      users       |       |  registrations   |        |      events     |
+---------------------+     +------------------+       +------------------+        +-----------------+
| id (PK)             |   _1| id (PK)          |1__    | id (PK)          |     __1| id (PK)         |
| tokenable_id (FK)   |K_/  | name             |   \__N| user_id (FK)     |    /   | name            |
| tokenable_type      |     | email (unique)   |       | event_id (FK)    |M__/    | description     |
| name                |     | password         |       | created_at       |        | date            |
| token (unique)      |     | is_admin(boolean)|       | updated_at       |        | location        |
| abilities           |     | created_at       |       | deleted_at       |        | max_participants|
| last_used_at        |     | updated_at       |       +------------------+        | created_at      |
| created_at          |     | deleted_at       |                                   | updated_at      |
+---------------------+     +------------------+                                   | deleted_at      |
                                                                                  +-----------------+
```

## Projekt Szerkezete

```
eventRegistration/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           ├── AuthController.php          # Regisztráció, bejelentkezés, kijelentkezés
│   │           ├── UserController.php          # Felhasználó CRUD és profilkezelés
│   │           ├── EventController.php         # Esemény CRUD és szűrési operációk
│   │           └── RegistrationController.php  # Regisztrációk kezelése
│   ├── Models/
│   │   ├── User.php                        # Felhasználó modell (Sanctum, SoftDeletes)
│   │   ├── Event.php                       # Esemény modell (SoftDeletes)
│   │   └── Registration.php                # Regisztráció modell (SoftDeletes)
│   └── Providers/
│       └── AppServiceProvider.php
├── database/
│   ├── factories/                          # Factory-k tesztadatok generálásához
│   │   ├── UserFactory.php
│   │   ├── EventFactory.php
│   │   └── RegistrationFactory.php
│   ├── migrations/                         # Adatbázis sémamigráció
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 2026_01_08_085132_create_events_table.php
│   │   └── 2026_01_08_090038_create_registrations_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── UserSeeder.php
│       ├── EventSeeder.php
│       └── RegistrationSeeder.php
├── routes/
│   └── api.php                             # REST API végpontok definíciója
├── tests/
│   ├── Feature/
│   │   ├── AuthTest.php                    # Autentifikáció tesztek
│   │   ├── UserTest.php                    # Felhasználó kezelés tesztek
│   │   ├── EventTest.php                   # Esemény kezelés tesztek
│   │   ├── RegistrationTest.php            # Regisztráció kezelés tesztek
│   │   └── ExampleTest.php
│   └── Unit/
├── config/
│   ├── auth.php                            # Autentifikáció konfiguráció
│   └── sanctum.php                         # Sanctum (API) konfiguráció
├── .env                                    # Környezeti változók
├── composer.json                           # PHP függőségek
├── phpunit.xml                             # PHPUnit konfiguráció
└── README.md
```

## Adatmodell

### User (Felhasználó)
```php
{
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "password": "hashed_password",
    "is_admin": true,
    "created_at": "2026-01-14T10:00:00Z",
    "updated_at": "2026-01-14T10:00:00Z",
    "deleted_at": null
}
```

### Event (Esemény)
```php
{
    "id": 1,
    "name": "Laravel Konferencia",
    "description": "Egy nap a Laravel világáról.",
    "date": "2026-10-20 09:00:00",
    "location": "Budapest Kongresszusi Központ",
    "max_participants": 150,
    "created_at": "2026-01-14T10:00:00Z",
    "updated_at": "2026-01-14T10:00:00Z",
    "deleted_at": null
}
```

### Registration (Regisztráció)
```php
{
    "id": 1,
    "user_id": 2,
    "event_id": 1,
    "created_at": "2026-01-15T12:00:00Z",
    "updated_at": "2026-01-15T12:00:00Z",
    "deleted_at": null
}
```

## API Végpontok

### Nem védett végpontok:
- **GET** `/hello` - API teszteléshez
- **POST** `/register` - Regisztrációhoz
- **POST** `/login` - Bejelentkezéshez

### Hibák kezelése:
- **400 Bad Request**: A kérés hibás formátumú vagy hiányoznak a szükséges mezők
- **401 Unauthorized**: Érvénytelen vagy hiányzó token
- **403 Forbidden**: A felhasználó nem rendelkezik megfelelő jogosultságokkal
- **404 Not Found**: A kért erőforrás nem található
- **409 Conflict**: Az erőforrás már létezik vagy egy már bekövetkezett állapotot próbálnak ismét végrehajtani
- **422 Unprocessable Entity**: Validációs hibák a kérésben

---

## Felhasználókezelés

### **POST** `/register` - Regisztráció

Új felhasználó regisztrálása. Az új felhasználók alapértelmezetten normál felhasználók (`is_admin = false`).

**Kérés Törzse:**
```json
{
    "name": "Teszt Elek",
    "email": "teszt@example.com",
    "password": "Jelszo_2026",
    "password_confirmation": "Jelszo_2026"
}
```

**Válasz (sikeres regisztráció):** `201 Created`
```json
{
    "message": "User registered successfully",
    "user": {
        "id": 10,
        "name": "Teszt Elek",
        "email": "teszt@example.com",
        "created_at": "2026-01-14T12:00:00Z",
        "updated_at": "2026-01-14T12:00:00Z"
    }
}
```

**Válasz (e-mail már foglalt):** `422 Unprocessable Entity`
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

### **POST** `/login` - Bejelentkezés

Bejelentkezés e-mail címmel és jelszóval.

**Kérés Törzse:**
```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

**Válasz (sikeres bejelentkezés):** `200 OK`
```json
{
    "access_token": "2|exampletoken",
    "token_type": "Bearer"
}
```

**Válasz (hibás bejelentkezés):** `401 Unauthorized`
```json
{
  "message": "Invalid credentials"
}
```

---

> Innen kezdve minden végpont **autentifikált**, tehát a kérés `Authorization` headerében meg kell adni a tokent:
> 
> `Authorization: Bearer 2|exampletoken`

---

### **POST** `/logout` - Kijelentkezés

**Válasz (sikeres kijelentkezés):** `200 OK`
```json
{
  "message": "Logged out successfully"
}
```

---

### **GET** `/users/me` - Aktuális Profil

**Válasz:** `200 OK`
```json
{
    "id": 5,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+36201234567"
}
```

---

### **PUT** `/users/me` - Profil Frissítése

**Kérés Törzse:**
```json
{
  "name": "New Name",
  "email": "newemail@example.com",
  "password": "NewPassword_2026",
  "phone": "+36209876543"
}
```

**Válasz (sikeres frissítés):** `200 OK`
```json
{
  "id": 5,
  "name": "New Name",
  "email": "newemail@example.com",
  "phone": "+36209876543"
}
```

---

### **GET** `/users` - Összes Felhasználó Listázása (Admin Csak)

**Válasz:** `200 OK`
```json
[
    {
        "id": 1,
        "name": "admin",
        "email": "admin@example.com"
    },
    {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
    }
]
```

**Válasz (nem admin):** `403 Forbidden`
```json
{
  "message": "Forbidden"
}
```

---

### **GET** `/users/{id}` - Konkrét Felhasználó Megtekintése (Admin Csak)

**Válasz:** `200 OK`
```json
{
    "id": 5,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+36201234567"
}
```

---

### **DELETE** `/users/{id}` - Felhasználó Törlése (Admin Csak)

**Válasz (sikeres törlés):** `200 OK`
```json
{
  "message": "User successfully deleted."
}
```

---

## Események Kezelése

### **GET** `/events` - Összes Esemény Listázása

**Válasz:** `200 OK`
```json
[
    {
        "id": 1,
        "name": "Laravel Konferencia 2026",
        "description": "Minden, ami Laravel. Előadások, workshopok.",
        "date": "2026-10-20 09:00:00",
        "location": "Budapest",
        "max_participants": 100,
        "created_at": "2026-01-14T10:00:00Z",
        "updated_at": "2026-01-14T10:00:00Z",
        "deleted_at": null
    }
]
```

---

### **GET** `/events/{event}` - Konkrét Esemény Megtekintése

**Válasz:** `200 OK`
```json
{
    "id": 1,
    "name": "Laravel Konferencia 2026",
    "description": "Minden, ami Laravel. Előadások, workshopok.",
    "date": "2026-10-20 09:00:00",
    "location": "Budapest",
    "max_participants": 100,
    "created_at": "2026-01-14T10:00:00Z",
    "updated_at": "2026-01-14T10:00:00Z"
}
```

---

### **POST** `/events` - Esemény Létrehozása (Admin Csak)

**Kérés Törzse:**
```json
{
    "name": "PHP Meetup",
    "description": "Havi rendszerességű PHP találkozó.",
    "date": "2026-02-15 18:00:00",
    "location": "Debrecen",
    "max_participants": 50
}
```

**Válasz:** `201 Created`
```json
{
    "id": 2,
    "name": "PHP Meetup",
    "description": "Havi rendszerességű PHP találkozó.",
    "date": "2026-02-15 18:00:00",
    "location": "Debrecen",
    "max_participants": 50,
    "created_at": "2026-01-14T13:00:00Z",
    "updated_at": "2026-01-14T13:00:00Z"
}
```

---

### **PUT** `/events/{event}` - Esemény Módosítása (Admin Csak)

**Kérés Törzse:**
```json
{
    "name": "Updated Event Name",
    "location": "Szeged"
}
```

**Válasz:** `200 OK`
```json
{
    "id": 2,
    "name": "Updated Event Name",
    "description": "Havi rendszerességű PHP találkozó.",
    "date": "2026-02-15 18:00:00",
    "location": "Szeged",
    "max_participants": 50,
    "created_at": "2026-01-14T13:00:00Z",
    "updated_at": "2026-01-14T14:00:00Z"
}
```

---

### **DELETE** `/events/{event}` - Esemény Törlése (Admin Csak)

**Válasz:** `200 OK`
```json
{
  "message": "Event successfully deleted."
}
```

---

### **POST** `/events/filter` - Események Szűrése

**Kérés Törzse:**
```json
{
  "name": "Laravel",
  "location": "Budapest"
}
```

**Válasz:** `200 OK`
```json
[
  {
    "id": 1,
    "name": "Laravel Konferencia",
    "location": "Budapest",
    "date": "2026-10-20 09:00:00"
  }
]
```

---

## Regisztrációk Kezelése

### **POST** `/events/{event}/register` - Eseményre Regisztráció

**Válasz (sikeres):** `201 Created`
```json
{
  "message": "Successfully registered for the event."
}
```

**Válasz (már regisztrált):** `409 Conflict`
```json
{
  "message": "User is already registered for this event."
}
```

**Válasz (esemény betelt):** `409 Conflict`
```json
{
  "message": "Event is full."
}
```

---

### **DELETE** `/events/{event}/unregister` - Leiratkozás Eseményről

**Válasz (sikeres):** `200 OK`
```json
{
  "message": "Successfully unregistered from the event."
}
```

---

### **DELETE** `/events/{event}/users/{user}` - Admin töröl felhasználót az eseményről

**Válasz (sikeres):** `200 OK`
```json
{
  "message": "User has been removed from the event."
}
```

---

## Összefoglalás - Végpontok Táblázata

| HTTP | Útvonal | Jogosultság | Státusz | Leírás |
|------|---------|-------------|--------|--------|
| GET | `/hello` | Nyilvános | 200 OK | API teszteléshez |
| POST | `/register` | Nyilvános | 201 Created, 422 | Regisztráció |
| POST | `/login` | Nyilvános | 200 OK, 401 | Bejelentkezés |
| POST | `/logout` | Auth | 200 OK, 401 | Kijelentkezés |
| GET | `/users/me` | Auth | 200 OK, 401 | Saját profil |
| PUT | `/users/me` | Auth | 200 OK, 401, 422 | Profil frissítés |
| GET | `/users` | Admin | 200 OK, 403, 401 | Összes felhasználó |
| GET | `/users/{id}` | Admin | 200 OK, 403, 404, 401 | Konkrét felhasználó |
| DELETE | `/users/{id}` | Admin | 200 OK, 403, 404, 401 | Felhasználó törlés (soft delete) |
| GET | `/events` | Auth | 200 OK, 401 | Események |
| GET | `/events/upcoming` | Auth | 200 OK, 401 | Közelgő események |
| GET | `/events/past` | Auth | 200 OK, 401 | Múltbeli események |
| POST | `/events/filter` | Auth | 200 OK, 401 | Események szűrése |
| GET | `/events/{event}` | Auth | 200 OK, 401, 404 | Konkrét esemény |
| POST | `/events` | Admin | 201 Created, 403, 401 | Esemény létrehozás |
| PUT | `/events/{event}` | Admin | 200 OK, 403, 401 | Esemény módosítás |
| DELETE | `/events/{event}` | Admin | 200 OK, 403, 401 | Esemény törlés |
| POST | `/events/{event}/register` | Auth | 201 Created, 401, 409 | Regisztráció eseményre |
| DELETE | `/events/{event}/unregister` | Auth | 200 OK, 401, 404 | Leiratkozás eseményről |
| DELETE | `/events/{event}/users/{user}` | Admin | 200 OK, 403, 401 | Admin töröl felhasználót |

## Autentifikáció és Jogosultságok

### Token-alapú Autentifikáció
- Minden autentifikált endpoint `Authorization: Bearer {token}` header-t igényel
- A token bejelentkezéskor jön vissza
- A tokeneket a `personal_access_tokens` táblában tároljuk
- Érvénytelen token esetén: `401 Unauthorized`

### Szerepek (Roles)

1. **Normál felhasználó** (`is_admin = false`)
   - ✓ Saját profil megtekintése és módosítása
   - ✓ Események megtekintése, szűrése
   - ✓ Regisztráció eseményekre és leiratkozás
   - ✗ Eseményeket nem hozhat létre, nem módosíthat, nem törölhet
   - ✗ Más felhasználók adataihoz nincs hozzáférése

2. **Administrator** (`is_admin = true`)
   - ✓ Összes felhasználó kezelése
   - ✓ Események teljes kezelése (létrehozás, módosítás, törlés)
   - ✓ Bármely felhasználó regisztrációjának törlése

## Modellek

- User — Felhasználó entitás, tartalmaz kapcsolatot a regisztrációkkal.
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'phone'
];

public function registrations()
{
    return $this->hasMany(Registration::class);
}
```

- Event — Esemény entitás, tartalmaz kapcsolatot a regisztrációkkal.
```php
protected $fillable = [
    'name',
    'description',
    'date',
    'location',
    'max_participants'
];

protected $casts = [
    'date' => 'datetime',
];

public function registrations()
{
    return $this->hasMany(Registration::class);
}
```

- Registration — Regisztráció entitás, kapcsolat a User és Event modellekhez.
```php
protected $fillable = [
    'user_id',
    'event_id'
];

public function user()
{
    return $this->belongsTo(User::class);
}

public function event()
{
    return $this->belongsTo(Event::class);
}
```

## Factory-k

- UserFactory — Generál name, email, bcrypt-elt alap jelszó.
```php
'password' => bcrypt('password'),
```

- EventFactory — Generál név, leírás, dátum, helyszín, max_participants értékeket.
```php
'name' => fake()->sentence(3),
'description' => fake()->paragraph(),
'date' => fake()->dateTimeBetween('+1 week', '+1 year'),
'location' => fake()->city(),
'max_participants' => fake()->numberBetween(20, 200),
```

- RegistrationFactory — Generál user_id, event_id kapcsolatokat.
```php
'user_id' => User::factory(),
'event_id' => Event::factory(),

```

## Seeder-ek

A seederek célja, hogy fejlesztés közben gyorsan legyenek tesztadatok (felhasználók, események, regisztrációk). A `DatabaseSeeder` futtatja a többi seedert ebben a sorrendben: **UserSeeder → EventSeeder → RegistrationSeeder**.

### DatabaseSeeder
```php
public function run(): void
{
    $this->call([
        UserSeeder::class,
        EventSeeder::class,
        RegistrationSeeder::class,
    ]);
}
```

### UserSeeder
Két fix felhasználót hoz létre (admin + test), majd 10 random felhasználót.
```php
User::factory()->create([
    'name' => 'Admin',
    'email' => 'admin@events.hu',
    'password' => Hash::make('admin123'),
    'is_admin' => true,
]);

User::factory()->create([
    'name' => 'Test',
    'email' => 'test@events.hu',
    'password' => Hash::make('test123'),
]);

User::factory()->count(10)->create();
```

### EventSeeder
Három fix eseményt hoz létre (köztük egy múltbelit), majd 10 random eseményt.
```php
$events = [
    [
        'name' => 'Tech Conference 2024',
        'description' => 'Éves technológiai konferencia innovatív témákkal.',
        'date' => now()->addDays(30),
        'location' => 'Budapest, BME Q épület',
        'max_participants' => 100,
    ],
    [
        'name' => 'Marketing Workshop',
        'description' => 'Gyakorlati marketing workshop digitális trendekkel.',
        'date' => now()->addDays(15),
        'location' => 'Online (Zoom)',
        'max_participants' => 50,
    ],
    [
        'name' => 'Webfejlesztés Alapjai',
        'description' => 'Kezdőknek szóló webfejlesztési tréning.',
        'date' => now()->subDays(10),
        'location' => 'Debrecen, Egyetem',
        'max_participants' => 40,
    ],
];

foreach ($events as $event) {
    Event::create($event);
}

Event::factory()->count(10)->create();
```

### RegistrationSeeder
Minta regisztrációkat hoz létre, majd minden felhasználónak 1–3 véletlen eseményt rendel, ha még nincs regisztrálva.
```php
$users = User::all();
$events = Event::all();

if ($users->count() < 4 || $events->count() < 3) {
    return;
}

$sampleRegistrations = [
    [
        'user_id' => $users[1]->id,
        'event_id' => $events[0]->id,
        'status' => 'Elfogadva',
        'registered_at' => now()->subDays(5),
    ],
    // ... további minták
];

foreach ($sampleRegistrations as $registration) {
    Registration::create($registration);
}

foreach ($users as $user) {
    $randomCount = min(rand(1, 3), $events->count());
    $randomEvents = $events->random($randomCount);

    foreach ($randomEvents as $event) {
        $exists = Registration::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->exists();

        if (!$exists) {
            Registration::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'status' => collect(['Függőben', 'Elfogadva', 'Elutasítva'])->random(),
                'registered_at' => now()->subDays(rand(0, 15)),
            ]);
        }
    }
}
```

## Controller

A Laravel controller-ek az MVC (Model-View-Controller) architektúra része. A controller-ek felelősek a HTTP kérések fogadásáért, az üzleti logika végrehajtásáért, és a válaszok visszaküldéséért. Az Event Registration System API-ja négy fő controller-t használ:

### 1. AuthController - Autentifikáció Kezelése

Az `AuthController` felelős a felhasználók regisztrációjáért, bejelentkezéséért és kijelentkezéséért. Laravel Sanctum tokent használ az API autentifikációhoz.

```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 200);
    }

    public function logout(Request $request){
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
```

**Főbb Funkciók:**
- **register()**: Hash-eli a jelszót (`Hash::make()`), validál minden input mezőt, és létrehoz egy új rekordot a `users` táblában
- **login()**: Ellenőrzi a jelszót (`Hash::check()`), generál egy Sanctum tokent a `createToken()` metódussal
- **logout()**: Törli a felhasználó összes aktív tokenét az adatbázisból

---

### 2. UserController - Felhasználó Kezelés

A `UserController` a felhasználói profilok kezelését végzi. Tartalmaz normál felhasználói és admin funkciókat egyaránt.

```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function me(Request $request)
    {
        return response()->json($request->user(), 200);
    }

    public function updateMe(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|nullable|min:6',
            'phone' => 'sometimes|nullable|string',
        ]);

        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->filled('password')) $user->password = Hash::make($request->password);
        if ($request->has('phone')) $user->phone = $request->phone;

        $user->save();

        return response()->json($user, 200);
    }

    public function index(Request $request)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(User::all(), 200);
    }

    public function show(Request $request, $id)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);

        return response()->json($user, 200);
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);

        $user->delete();
        return response()->json(['message' => 'User successfully deleted.']);
    }
}
```

**Főbb Funkciók:**
- **me()**: Visszaadja az autentifikált felhasználó adatait
- **updateMe()**: Részleges frissítés támogatása (`sometimes` validáció), jelszó hash-elés
- **index(), show(), destroy()**: Admin-only műveletek, jogosultság ellenőrzéssel (`is_admin` mező)
- **Soft Delete**: A `delete()` metódus nem törli teljesen a rekordot, csak a `deleted_at` mezőt állítja be

---

### 3. EventController - Esemény Kezelés

```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Event;

class EventController extends Controller
{
    public function index()
    {
        return Event::all();
    }

    public function show(Event $event)
    {
        return response()->json($event, 200);
    }

    public function store(Request $request)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date',
            'location' => 'required|string|max:255',
            'max_participants' => 'required|integer|min:1',
        ]);

        $event = Event::create($validated);

        return response()->json($event, 201);
    }

    public function update(Request $request, Event $event)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'date' => 'sometimes|required|date',
            'location' => 'sometimes|required|string|max:255',
            'max_participants' => 'sometimes|required|integer|min:1',
        ]);

        $event->update($validated);

        return response()->json($event->fresh(), 200);
    }

    public function destroy(Request $request, Event $event)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $event->delete();

        return response()->json(['message' => 'Event successfully deleted.']);
    }
}
```

---

### 4. RegistrationController - Regisztráció Kezelés

A regisztrációs controller kezeli az eseményre való jelentkezést, leiratkozást, illetve admin által végzett eltávolítást. Tartalmazza a kapacitás- és múltbeli esemény ellenőrzéseket, valamint a duplikált regisztrációk kezelését.

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RegistrationController extends Controller
{
    public function register(Event $event)
    {
        $user = Auth::user();

        if ($event->date->isPast()) {
            return response()->json(['message' => 'Cannot register for a past event.'], 422);
        }

        if ($event->registrations()->count() >= $event->max_participants) {
            return response()->json(['message' => 'Event is full.'], 422);
        }

        $existingRegistration = $user->registrations()->where('event_id', $event->id)->first();
        if ($existingRegistration) {
            return response()->json(['message' => 'You are already registered for this event.'], 409);
        }

        $registration = $user->registrations()->create(['event_id' => $event->id]);

        return response()->json([
            'message' => 'Successfully registered for the event.',
            'registration' => $registration
        ], 201);
    }

    public function unregister(Event $event)
    {
        $user = Auth::user();

        $registration = $user->registrations()->where('event_id', $event->id)->first();

        if (!$registration) {
            return response()->json(['message' => 'You are not registered for this event.'], 404);
        }

        $registration->delete();

        return response()->json(['message' => 'Successfully unregistered from the event.']);
    }

    public function adminRemoveUser(Event $event, User $user)
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $registration = $user->registrations()->where('event_id', $event->id)->first();

        if (!$registration) {
            return response()->json(['message' => 'User is not registered for this event.'], 404);
        }

        $registration->delete();

        return response()->json(['message' => 'User has been removed from the event.']);
    }
}
```

**Főbb Funkciók:**
- **register()**: Múltbeli esemény tiltása, kapacitás ellenőrzés, duplikáció kizárás, regisztráció létrehozás
- **unregister()**: Saját regisztráció törlése, 404 ha nincs regisztráció
- **adminRemoveUser()**: Admin jogosultság ellenőrzése, más felhasználó regisztrációjának törlése

