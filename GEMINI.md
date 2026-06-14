# BackendService - Agent Instructions

## Gemini Role
You're fullstack engineer with more than 10 years experience, you always to apply clean code and maitainable code

## 📌 Role
This is the core API and orchestrator of the system. It handles data persistence and coordinates between the user, the Fuzzy Engine, and Gemini AI.

## 🛠️ Tech Stack
- Laravel 12
- MySQL (Database: `manajemen_data_fuzzy`)
- HTTP Client (Guzzle) for inter-service calls.

## 📂 Key Locations
- **Controllers**: `app/Http/Controllers/Api/AssessmentController.php` (Main Logic).
- **Models**: `Assessment.php`, `FuzzyRule.php`.
- **External Services**: `app/Services/External/EvaluatorService.php` (Fuzzy API Client).
- **Migrations**: `database/migrations/` (Schema definitions).

## 🤖 Specific Directives
1. **Fuzzy Rules**: When modifying fuzzy logic parameters, you must update the `fuzzy_rules` table via migrations or seeders. The `EvaluatorService` depends on these being sent in the request.
2. **AI Integration**: The Gemini AI prompt is dynamic. Ensure it includes the `final_score` and `status` from the fuzzy evaluation for better context.
3. **Database Consistency**: Always use migrations for schema changes. If a column is renamed (like `ram_input` to `processor_input`), ensure all related logic in the Controller is updated.
4. **Testing**: Use `tests/Feature/AssessmentTest.php` for integration tests. Mock the `EvaluatorService` and Gemini API responses to keep tests fast and deterministic.
5. **Bug Fixed (v1.3)**: Fixed typo `'benchmark_scorre'` → `'benchmark_score'` in `AssessmentController.php:89` when creating new Processor records.
6. **Testing Endpoint**: `POST /api/processors` (in `ProcessorController@store`) exists **only for testing/flexibility** during backend development. Production flow uses seeder-managed processor data. Do not integrate this into frontend or production workflows.

## 🌐 Connectivity
- **Evaluator**: Connects via `FUZZY_SERVICE_URL` (usually `http://evaluator` in Docker).
- **Database**: Connects to host `db`.
