# Readora project plan (reconstructed)

This is a retrospective plan assembled from the source and Git history, not a plan written before development. The project owner says the original Readora project was developed last year and reused for this assignment. The available Git history begins with foundation commits dated May 2026; that metadata alone cannot establish when the original work was first written.

## Goal

Extend the existing bookstore to meet the assignment's catalogue, reservation, review, service-separation, testing, and documentation requirements while keeping the design understandable.

## Reused foundation

The earlier Readora app supplies the main Laravel website and backend: Blade pages, book and category browsing, authentication, profiles, contact/FAQ pages, and admin routes. The repository history records these features before the assignment extensions.

## Reconstructed phases

| Phase | Evidence in the repository |
| --- | --- |
| Existing site | Book/category browsing, authentication, profile/contact features, and admin tools. |
| Catalogue and workflows | ISBN and price; reservations, cancellation, purchase status, and reviews. |
| Separate service | Standalone Inventory API, separate SQLite database, token and signed-identity authentication, inventory/reservation operations, and legacy importer. |
| Integration | Readora's server-side HTTP client/services read and manage API stock and reservation lifecycle. |
| Handover | Feature tests in both apps, setup guides, and this reconstructed plan. |

These groups describe the finished project; Git history does not prove they were planned or completed in this exact order.

## Design decisions

- Keep the Blade interface, user accounts, book details, and reviews in Readora.
- Make the Inventory API the source of stock and reservation status.
- Communicate from Readora's backend over HTTP/JSON; keep API credentials out of the browser.
- Keep the SQLite databases separate.
- Use API transactions and idempotency for stock changes and retries. The apps do not share a transaction, so API errors must not appear as successful operations.

## Submission checklist

- [ ] Confirm the teacher can access the repository.
- [ ] Follow the installation guide from a fresh clone.
- [ ] Run both test suites and record actual results.
- [ ] Verify the local API URL and credentials without publishing their values.
- [ ] Test the end-to-end workflow with a local admin and API stock.
- [ ] Do not describe deployment as complete unless it has been tested.
- [ ] Confirm the timeline and source/AI disclosure before submission.
