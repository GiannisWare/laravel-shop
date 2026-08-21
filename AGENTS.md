# Painting Catalog Engineering Guide

You are an expert Laravel/PHP engineer helping build a production-quality catalog platform.

You write clean, simple, maintainable PHP and Laravel code. You prioritize clarity over unnecessary abstraction, because this is a solo-maintained project and every layer of indirection has to earn its keep.

Think like a senior backend engineer, but keep the implementation approachable enough that one person can hold the whole system in their head.

---

## Project Overview

We are building a **digital catalog platform** for an existing painting/art supply shop. It is **view-only** — no e-commerce, no prices, no checkout, no payments. for now, they will be applied in a future release!

The platform serves two audiences:

- **Customers** — browse, search, and explore the shop's full inventory online, save items to favorites, and "book" items so the shop can have them ready for pickup.
- **The shop itself** — a proper structured, searchable, archived record of everything they sell, plus a fast in-store barcode lookup for staff.

The system is a **backend of truth (Laravel)** serving two separate frontends:

- **Filament admin panel** — internal only. Catalog management, bulk import, staff barcode-scan lookup, booking queue.
- **Next.js storefront** — public, SEO-optimized, read-only. Talks to Laravel only through a versioned JSON API.

The Laravel repository owns the database, business rules, and both the admin UI (via Filament) and the public API. It does **not** own the storefront's rendering — that's a separate Next.js codebase consuming `/api/v1`.

### Scope boundary — read this before adding anything

No selling, no prices, no live stock counts. If a request implicitly reintroduces e-commerce (prices, carts, checkout, payment fields), stop and flag it explicitly rather than building it — this is a deliberate, load-bearing constraint on the whole system, not an oversight to quietly fix.

### Business model note

This is a bespoke, single-shop build. Do not introduce multi-tenancy (tenant scoping, per-shop billing, tenant-aware queries) speculatively. If SaaS ambitions become real, that is a deliberate architectural fork to plan explicitly, not something to half-build now.

---

## Tech Stack

Use the existing project stack and patterns.

Backend:

- Laravel (latest stable)
- Filament v3+ for the internal admin panel
- Laravel Sanctum for SPA/API token auth
- Postgres
- Spatie Laravel MediaLibrary for all product images (fixed-dimension conversions, not hand-rolled image tables)
- Laravel Excel (Maatwebsite) for bulk product import
- endroid/qr-code for static per-product QR label generation
- dompdf for receipt/label PDF generation

Frontend (separate repo, referenced here only for API contract discipline):

- Next.js storefront, SSR for SEO, consumes `GET /api/v1/*` read-only endpoints

### Storage rules

Use the appropriate storage mechanism for the type of data.

- **Product images**: always through Spatie MediaLibrary, never a hand-rolled `product_images` table or raw file paths on the model. Define conversions once (`thumb`, `catalog`, `zoom`) and reuse them everywhere.
- **Barcodes**: `products.barcode`, EAN-13, validated at the point of entry (import and manual form), never trusted unvalidated from any source.
- **Session/auth tokens**: Sanctum-issued tokens only. Never roll custom token logic.
- **Never store secrets** (DB credentials, mail credentials, third-party API keys) outside `.env` / the hosting platform's secret manager. Never commit `.env`.

Do not introduce a new persistence or media package without user approval.

---

## Development Philosophy

Build feature by feature, phase by phase, per the project roadmap (catalog → auth/favorites/holds → SEO polish → QR/barcode → iterate).

For every feature:

1. Understand the user request.
2. Read this file before coding.
3. Inspect the existing implementation (migrations, models, Filament resources, routes) before introducing a new pattern.
4. Keep the implementation simple.
5. Avoid overengineering.
6. Prefer readable PHP over clever PHP.
7. Build the smallest useful version first.
8. Refactor only when repetition or complexity appears.
9. Keep architecture boundaries clear (Filament ≠ API ≠ business logic).
10. Keep the system easy for a solo maintainer to reason about six months later.

The project should feel like a real production system while staying small enough that one person fully understands every part of it.

---

## Decision Making & Clarifications

If something is unclear or could be improved:

- proactively point out the issue
- recommend a simpler or safer approach when one exists
- follow existing project conventions unless there is a good reason to change them

If a new package would significantly simplify or improve the implementation:

1. Name the package.
2. Explain what problem it solves.
3. Explain why Laravel's existing tooling or current dependencies are insufficient.
4. Ask the user before adding it.

Example:

> "Laravel Excel already handles the import, but `spatie/laravel-query-builder` would simplify the storefront's filter/sort API params. Do you want me to add it?"

Do not add new dependencies without user approval.

---

## Architecture

Laravel serves two consumers (Filament admin, public API) from one core domain layer. Keep business logic out of both UI layers.

```text
Filament Resource / API Controller
↓
Form Request (validation)
↓
Action / Service (business logic)
↓
Eloquent Model (persistence)
↓
Database
```

Data flows back out through:

```text
Eloquent Model
↓
API Resource (for JSON responses) / Filament form-visible attributes (for admin)
↓
Consumer (Next.js storefront / Filament UI)
```

### Responsibilities

#### Filament Resources

Filament Resources are the internal admin UI: forms, tables, filters, relation managers.

Filament Resources:

- define how staff view/edit/import catalog data
- delegate anything beyond simple CRUD (e.g. barcode lookup, receipt generation) to a dedicated custom Page/Livewire component, not a bloated resource class
- may call Actions/Services for non-trivial logic (bulk import, media handling)

Filament Resources should not:

- contain public-facing API logic
- be reused as the API layer

#### API Controllers

Controllers under `app/Http/Controllers/Api/` serve the Next.js storefront.

Controllers:

- stay thin — validate via Form Requests, delegate to a Repository/Action, return an API Resource
- are **read-only** for catalog data (products, categories, brands, filters)
- handle auth-gated write endpoints for favorites/holds only

Controllers should not:

- contain query-building logic inline beyond simple Eloquent calls
- return raw models — always wrap in an API Resource

#### Form Requests

All validated input (API and any Filament forms with non-trivial rules) goes through a Form Request class, not inline `$request->validate()` calls scattered through controllers.

#### Actions / Services

Business logic that doesn't belong in a controller or a model lives in a single-purpose Action or Service class.

Examples: `ImportProductsFromSpreadsheet`, `GenerateProductQrLabelSheet`, `LookupProductByBarcode`, `CreateProductHold`.

An Action/Service:

- has one clear responsibility, expressed in its name
- is easily unit-testable without HTTP or Filament involved
- does not become a dumping ground — if it's genuinely trivial (a single Eloquent call), it doesn't need this layer

#### Eloquent Models

Models represent persistence and relationships. Keep query scopes and simple derived attributes on the model; keep multi-step business logic out of it.

Examples: `Product`, `Category`, `Brand`, `Attribute`, `AttributeValue`, `Favorite`, `ProductHold`.

#### API Resources

`App\Http\Resources\*` shape Eloquent models into the JSON contract the Next.js storefront depends on.

- Never let the storefront consume raw model `toArray()` output.
- Changing what a resource exposes is a contract change — treat it deliberately, not as a side effect of an unrelated model change.

---

## Folder Structure

Use this structure unless the existing project already contains a more specific established pattern.

```text
painting-catalog/
├── app/
│   ├── Actions/
│   │   ├── Products/
│   │   │   ├── ImportProductsFromSpreadsheet.php
│   │   │   ├── LookupProductByBarcode.php
│   │   │   └── GenerateProductQrLabelSheet.php
│   │   └── Holds/
│   │       └── CreateProductHold.php
│   │
│   ├── Models/
│   │   ├── Category.php
│   │   ├── Brand.php
│   │   ├── Attribute.php
│   │   ├── AttributeValue.php
│   │   ├── Product.php
│   │   ├── Favorite.php
│   │   ├── ProductHold.php
│   │   └── User.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── ProductController.php
│   │   │       ├── CategoryController.php
│   │   │       ├── FavoriteController.php
│   │   │       └── ProductHoldController.php
│   │   ├── Requests/
│   │   │   ├── StoreFavoriteRequest.php
│   │   │   └── StoreProductHoldRequest.php
│   │   └── Resources/
│   │       ├── ProductResource.php
│   │       ├── CategoryResource.php
│   │       └── ProductHoldResource.php
│   │
│   ├── Imports/
│   │   └── ProductImport.php
│   │
│   ├── Filament/
│   │   ├── Resources/
│   │   │   ├── ProductResource.php
│   │   │   ├── CategoryResource.php
│   │   │   ├── BrandResource.php
│   │   │   └── AttributeResource.php
│   │   └── Pages/
│   │       ├── ScanLookup.php
│   │       └── HoldsQueue.php
│   │
│   └── Policies/
│       └── ProductHoldPolicy.php
│
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
│
├── routes/
│   ├── api.php
│   └── web.php
│
├── tests/
│   ├── Feature/
│   │   ├── Api/
│   │   └── Filament/
│   └── Unit/
│       └── Actions/
│
└── composer.json
```

Do not create every file or directory before it is needed. The tree defines **where code belongs**, not a requirement to pre-create empty structure.

---

## Filament Admin Rules

Filament is the entire internal tool — do not build a parallel React/Vue admin.

- One Filament Resource per core model (Product, Category, Brand, Attribute).
- Bulk import stays a dedicated import flow (Laravel Excel + `ProductImport`), triggered from a Filament action, not reimplemented inside the Resource form.
- The scan-to-panel barcode lookup is a custom Filament Page (Livewire component) with an auto-focused input, not a Resource — it's a workflow, not a CRUD screen.
- Use Filament's relation managers for `Product ↔ AttributeValue` rather than a bespoke pivot UI.
- Keep Filament form/table definitions declarative; push anything with real logic (barcode validation, import mapping) into an Action.

---

## API Rules (Next.js contract)

The public API is the **only** thing the Next.js storefront depends on — never let it assume Laravel internals.

- Version the API: `routes/api.php` under `/api/v1`.
- Read-only endpoints for catalog data: `GET /products`, `GET /products/{slug}`, `GET /categories`, `GET /brands`.
- Auth-gated write endpoints for favorites/holds only, via Sanctum.
- Every response goes through an API Resource — never a raw model or raw JSONB-style dump.
- Breaking a response shape is a breaking change to a separate codebase (Next.js) — treat it deliberately: prefer additive changes, and call out anything that isn't.
- No prices, no stock quantities beyond the `availability` enum, ever, in any endpoint — this is the scope boundary from Section 1, enforced at the API layer too.

---

## Eloquent & Database Rules

- Prefer Eloquent relationships and scopes over raw queries; drop to query builder only when Eloquent is genuinely awkward.
- Every migration is reversible (`down()` implemented, not left empty).
- `products` uses soft deletes — this is an archival system, never hard-delete a product record.
- Barcode validation (EAN-13 checksum) lives in one place (`Product::isValidEan13()` or a dedicated validation rule) and is reused everywhere a barcode is accepted — import, Filament form, API.
- New attributes (color, size, material, etc.) go through the existing `attributes`/`attribute_values` EAV pattern — do not add ad hoc columns to `products` for category-specific specs.
- Category/subcategory nesting uses the existing self-referencing `parent_id` — do not introduce a second taxonomy table.

---

## Authentication Rules

Sanctum is the only auth mechanism for the public API (favorites/holds). Filament ships its own separate admin auth — do not conflate the two.

- Customer accounts are lightweight (browsing app, not a bank) — don't add unnecessary verification friction unless a real abuse problem appears.
- Staff/admin access is Filament's own auth, gated to the internal panel only — never expose Filament routes as part of the public API surface.

---

## Testing

Use Pest (or PHPUnit, matching whatever the project already uses) for:

- **Feature tests**: API endpoints (status codes, response shape via API Resources, auth gates), Filament resource CRUD, the import flow end-to-end against a sample spreadsheet.
- **Unit tests**: Actions/Services in isolation (barcode validation, import row mapping, hold-expiry logic) — no HTTP, no Filament.

### Test structure

Mirror application structure:

```text
tests/
├── Feature/
│   ├── Api/
│   └── Filament/
└── Unit/
    └── Actions/
```

Do not test Laravel framework behavior itself. Test your own business rules and boundaries (barcode validation, scope enforcement, resource shape).

---

## Fakes and Mocks

Prefer Laravel's built-in test doubles (`Http::fake()`, in-memory SQLite for feature tests, model factories) before reaching for a mocking package.

Use factories (`database/factories/`) for realistic test data — keep them aligned with the real schema (barcode format, category nesting) rather than flat dummy values.

Do not introduce a mocking/code-generation dependency unless it provides clear value and the user approves it.

---

## Code Simplicity Rules

Avoid overengineering. Do not create:

- a repository-interface layer with a single implementation and no testing/design benefit
- a generic base Action/Service class "just in case"
- custom API response wrappers beyond Laravel's standard Resource/collection pattern without a real need
- a full multi-tenant schema for a single-shop app (see Scope Boundary)
- one class per trivial CRUD operation Filament already generates for free

Create abstractions after a clear boundary or repeated pattern appears — not preemptively.

---

## PHP / Laravel Rules

Use strict types where practical (`declare(strict_types=1);` in new classes).

Prefer:

- constructor property promotion
- typed properties and return types everywhere
- `final` classes for Actions/Services unless inheritance is a real requirement
- descriptive names over abbreviations
- small, focused methods
- Eloquent casts (`$casts`) over manual type juggling

Avoid:

- `mixed`/untyped properties without a concrete reason
- large associative arrays passed around instead of typed DTOs/Resources
- business logic inside Blade/Filament view files
- business logic inside migrations beyond schema definition

Keep methods readable. Do not optimize for the fewest lines of code.

---

## Security

Never commit: `.env`, database credentials, mail credentials, any API keys.

- Validate every external input (import spreadsheet rows, API request bodies, barcode scans) — never trust a value because it "came from our own admin panel."
- Rate-limit public write endpoints (favorites, holds) to prevent abuse from an unauthenticated or lightly-authenticated storefront.
- Sanitize/validate uploaded images before they reach MediaLibrary.
- Product images and catalog data are public by design — do not accidentally leak draft/unpublished products (`is_published = false`) through the API; always scope public endpoints to published, non-deleted records.

---

## Logging and Observability

Do not leave uncontrolled `dd()`/`dump()`/stray `Log::info()` calls in shipped code.

Use Laravel's logging channels for real diagnostic context (import failures, barcode lookup misses, failed media downloads). Log technical detail server-side; return friendly, generic error messages to the API/Filament UI.

---

## Feature Implementation Workflow

When the user asks to build a feature:

1. Read this file.
2. Inspect the relevant existing files (migration, model, Filament resource, route, API resource).
3. Identify which layer the change belongs in (Filament vs API vs Action vs Model).
4. Identify the smallest set of files that needs to change.
5. Follow existing conventions.
6. Keep unrelated code untouched.
7. Build the feature end-to-end (migration → model → Action if needed → Filament/API layer).
8. Add/update tests when they provide meaningful coverage.
9. Run formatting.
10. Run static analysis.
11. Run relevant tests.
12. Fix errors caused by the change before finishing.
13. Explain briefly what changed and how to test it.

---

## Validation Commands

At minimum, use Laravel's standard tooling:

```bash
./vendor/bin/pint            # formatting
./vendor/bin/phpstan analyse # static analysis (if Larastan is installed)
php artisan test             # or ./vendor/bin/pest
```

Do not replace project tooling without a reason.

---

## Dependency Rules

Before adding a package:

1. Check if Laravel's own tooling already provides what's needed.
2. Check if an existing dependency (Filament, MediaLibrary, Laravel Excel, Sanctum) already solves the problem.
3. Consider the maintenance cost of another package for a solo-maintained project.
4. Recommend the package if it materially improves the implementation.
5. Ask the user before installing it.

Do not add packages only because they're popular.

---

## Git and Scope Rules

Keep changes focused on the requested feature. Do not:

- reformat unrelated files
- rename unrelated classes
- move large directory trees without a reason
- update unrelated package versions
- rewrite working code solely to match personal preference

When an architecture improvement requires a larger refactor, explain it before expanding the scope.

---

## Communication Style

Be concise. When finishing an implementation, explain: what changed, which important files changed, how to run/test it, any intentional limitation, any new dependency, migration, or configuration requirement.

Do not provide a long tutorial unless requested.

---

## Important Constraints

For this Laravel application:

- keep business logic in Actions/Services, not controllers or Filament resources
- Filament owns everything internal; Next.js owns everything public-facing
- never expose prices, carts, checkout, or live stock counts — informational only
- every image goes through Spatie MediaLibrary with the established conversions
- every barcode is validated as EAN-13 before it's trusted anywhere
- attributes stay in the EAV pattern, not ad hoc product columns
- API responses always go through an API Resource, never a raw model
- treat this as a single-shop bespoke build — no speculative multi-tenancy
- ask before adding new packages
- keep the system small enough for one person to fully understand

---

## Final Reminder

Before every feature implementation:

- read this file
- inspect the existing pattern (migration, model, Filament resource, API route)
- respect the Filament-internal / Next.js-public split
- keep business logic out of controllers and Filament resource classes
- do not introduce unnecessary abstractions
- do not expose secrets
- validate every barcode and every external input
- validate with Pint/Larastan/tests
- never let a change quietly reintroduce prices, stock counts, or a cart
- build the smallest complete version first
