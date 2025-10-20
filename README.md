Test suite for TFECode

These are simple PHP CLI scripts that exercise core features of the application using the existing models and controllers.

How to run (PowerShell):

# Use your PHP CLI from WAMP. Example path (adjust if different):
C:\wamp64\bin\php\php7.4.33\php.exe "c:\wamp64\www\TFECode\tools\tests\run_all_tests.php"

Each test prints PASS/FAIL and details. The scripts try to clean up created test data.

Files:
- run_all_tests.php: orchestrator that runs all tests and summarizes results
- test_evenement.php: CRUD + deadline behavior
- test_reservation.php: create/get/delete reservation and existsForDate
- test_panier.php: add/update/reserver/delete panier (reserver is attempted if available)
- test_planche.php: add/update/delete planche
- test_user_auth.php: creates a user row then tests authenticate (uses password_hash)

Note: these scripts connect to the database via config/database.php. Ensure your DB credentials in that file are correct and that running tests won't damage important production data (they create and delete rows). Prefer running on a dev DB or a DB snapshot.

Trello : https://trello.com/invite/b/68f6846a9dbc82d7722fc49f/ATTIde0727a0c32994c7fe112dc7cdb178ca2DDA35CB/mon-tableau-trello
Figma : https://www.figma.com/design/WOW6sbioZbqZbtjuRRo5GY/ThomasCooking?node-id=0-1&t=1XEGHOoVppqm9Yp0-1