# Changelog

All notable changes to `translation-editor` will be documented in this file.

## 1.1.0 - 2026-09-14

- Filament 5 (Livewire 4) support; Filament 4 stays supported by the same release
- Laravel 13 added to the test matrix
- Locale tabs can show a label from the locale model (`models.locale.label_column`) instead of the upper-cased code
- Fixed: the disabled and guest states now render an empty root element. Livewire requires one, so a user who turned the editor off during a session used to hit a "root tag missing" error on the next update
- Removed the HTML debug comments the wrapper printed into every page (they exposed the toggle column name and value in the page source)
- Development: Pest 4, and Livewire component tests for the modal

## 1.0.0 - 2026-01-26

- initial release
