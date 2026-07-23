# Study Program V2 Import

This subsystem imports the cleaned Iranian university study-program workbooks from `Docs/v2`.

## Source Files

Only these files are authoritative:

- `tajrobi_study_programs_clean_v2.xlsx`
- `riazi_study_programs_clean_v2.xlsx`
- `ensani_study_programs_clean_v2.xlsx`
- `honar_study_programs_clean_v2.xlsx`
- `zaban_study_programs_clean_v2.xlsx`
- `universities_and_cities_reference_all_groups.xlsx`
- `data_quality_report_all_groups_v2.json`

Older files, duplicate `(1)` files, and Excel temp files starting with `~$` are ignored.

## Schema

The normalized tables are:

- `exam_years`
- `exam_groups`
- `provinces`
- `cities`
- `institutions`
- `institution_campuses`
- `academic_fields`
- `course_types`
- `admission_types`
- `study_programs`
- `study_program_review_records`
- `study_program_imports`
- `study_program_import_failures`

`code` is stored as a string and is not globally unique.

## Identity

Production rows use a deterministic SHA-256 `identity_hash`.

Identity input:

```text
exam year + exam group + code + institution + campus + academic field + course type
```

The unique constraint is on `study_programs.identity_hash`. The same code can exist in another exam group or year.

`source_hash` detects source-row content changes. Reimport behavior is:

- new identity: insert
- same identity and same source hash: unchanged
- same identity and changed source hash: update
- review-status rows: staging only

## Normalization

`PersianTextNormalizer` normalizes Arabic/Persian character variants, tatweel, directional marks, zero-width spaces, non-breaking spaces, repeated whitespace, and punctuation spacing.

`StudyProgramValueMapper` owns explicit mappings for:

- exam groups: `tajrobi`, `riazi`, `ensani`, `honar`, `zaban`
- course types: `day`, `evening`, `commitment`, `tuition`, `nonprofit`, `payame_noor`, `virtual`, `joint`, `other`
- admission types: `with_exam`, `academic_records`, `special_conditions`, `unknown`
- the official 31 province names

Official source text is preserved in fields such as `original_course_type`, `original_admission_type`, `description`, and `raw_data`.

## Commands

Backup before destructive reset:

```bash
php artisan education:backup-study-programs
```

Reset one year after a verified backup:

```bash
php artisan education:reset-study-programs --year=1404 --confirm-reset
```

Dry run:

```bash
php artisan education:import-study-programs --path="D:\project\Education\Docs\v2" --year=1404 --dry-run --include-review --chunk=1000
```

Actual import:

```bash
php artisan education:import-study-programs --path="D:\project\Education\Docs\v2" --year=1404 --include-review --chunk=1000
```

Forced idempotency check:

```bash
php artisan education:import-study-programs --path="D:\project\Education\Docs\v2" --year=1404 --force --include-review --chunk=1000
```

Audit:

```bash
php artisan education:audit-study-programs --year=1404 --format=json --output=storage\app\study-program-v2-audit.json
```

## Backup And Reset

Backups are written to:

```text
storage/app/backups/study-programs/YYYY-mm-dd_His/
```

Each backup contains JSONL table exports, schema/index metadata, row counts, and a manifest with SHA-256 checksums.

The reset command refuses to run without `--confirm-reset` and a successful backup manifest unless `--force-without-backup` is explicitly passed. Normal operation should not use `--force-without-backup`.

## Admin UI

Main listing:

```text
/admin/study-programs
```

Review staging:

```text
/admin/study-programs/reviews
```

Dependent city endpoint:

```text
/admin/study-programs/cities?province_id=ID&year=1404&exam_group=riazi&only_with_programs=1
```

The city selector is filtered by province, and the server validates that the submitted city belongs to the selected province.

## Performance

The importer streams XLSX rows with `ZipArchive` and XML readers. It does not load full workbooks into memory. Lookups are cached in memory and rows are flushed with chunked `upsert()` calls.

Indexes cover identity, source hash, code, year/group, province/city, institution/campus, academic field, course type, admission type, validation status, and booklet page.

## Final 1404 Import

Final import totals:

- files detected: 5
- files processed: 5
- reference rows: 1,700
- reference skipped: 1
- source rows: 54,920
- production rows inserted: 52,096
- review rows staged: 2,824
- failed rows: 0

Forced reimport totals:

- inserted: 0
- updated: 0
- unchanged: 52,096
- review rows: 2,824
- failed rows: 0

Audit result:

- malformed province values: 0
- city/province mismatches: 0
- missing institutions: 0
- missing academic fields: 0
- unknown course types: 0
- unknown admission types: 0
- duplicate identity hashes: 0
- duplicate source hashes: 0
- duplicate codes within each group: 0
- public records marked for review: 0

The audit still reports many source-derived city and institution display names that are unusual. They are retained because the V2 files mark those rows as structurally approved; review rows remain staged separately.
