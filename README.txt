Kanban MVP – Aplikacja do zarządzania zadaniami

Prosta aplikacja webowa w stylu Kanban napisana w czystym, strukturalnym PHP (PDO) bez użycia frameworków backendowych i Composera, z wykorzystaniem zapytań asynchronicznych w JavaScript (fetch) oraz Bootstrap 5.

Funkcjonalności:
- Autoryzacja użytkowników: Logowanie oparte na sesjach PHP oraz bezpiecznym hashowaniu haseł (password_hash / password_verify).
- Zarządzanie projektami: Tworzenie projektów i przypisywanie do nich członków zespołu.
- Tablica Kanban (3 kolumny): Podział zadań na Do zrobienia, W trakcie oraz Gotowe z kolorowymi etykietami, terminami wykonania i przypisanymi osobami.
- Zmiana statusu bez przeładowania strony (AJAX): Przenoszenie zadań między kolumnami za pomocą fetch() komunikującego się z backendem w formacie JSON.
- Lista wszystkich zadań: Widok tabelaryczny ze stronicowaniem (paginacją) oraz sortowaniem po terminie wykonania.
- Historia aktywności (zadanie dodatkowe): Rejestrowanie utworzenia zadań oraz zmian ich statusów w obrębie projektu.

Endpointy API (JSON):
- GET /api/tasks.php?project_id={id} – zwraca listę zadań dla danego projektu.
- POST /api/update_status.php – aktualizuje status zadania (todo, in_progress, done) na podstawie danych JSON (task_id, status).

Oba endpointy zwracają nagłówek Content-Type: application/json oraz odpowiednie kody statusu HTTP (200, 400, 403, 404).

Struktura projektu:
- api/tasks.php – Endpoint GET zwracający zadania w JSON
- api/update_status.php – Endpoint POST aktualizujący status zadania
- includes/header.php – Nagłówek HTML, nawigacja, Bootstrap 5 CSS
- includes/footer.php – Stopka, Bootstrap 5 JS, podpięcie app.js
- app.js – Obsługa asynchronicznej zmiany statusów (fetch)
- config.example.php – Przykładowy plik konfiguracyjny bazy danych
- database.sql – Schemat bazy danych MySQL wraz z kontami testowymi
- functions.php – Połączenie PDO, autoryzacja i funkcje pomocnicze
- index.php – Lista projektów i formularz tworzenia nowego projektu
- login.php – Logowanie użytkownika
- logout.php – Wylogowanie użytkownika
- project.php – Widok tablicy Kanban i historii aktywności
- tasks_list.php – Lista wszystkich zadań z paginacją i sortowaniem

Instalacja lokalna:
1. Sklonuj repozytorium do katalogu serwera lokalnego (np. htdocs w XAMPP).
2. Utwórz bazę danych w MySQL (np. kanban_mvp) i zaimportuj plik database.sql.
3. Skopiuj plik config.example.php jako config.php i uzupełnij dane dostępowe do bazy danych (DB_HOST, DB_NAME, DB_USER, DB_PASS).
4. Uruchom serwer Apache oraz MySQL i otwórz aplikację w przeglądarce (http://localhost/kanban-app/login.php).

Konta testowe:
- Login: anna | Hasło: haslo123
- Login: marek | Hasło: haslo123
