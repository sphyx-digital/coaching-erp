# Data dictionary (Phase 1)

Every entity, its purpose, and the SoT (the system that owns that fact). Money is
stored in integer paise. Every table carries `created_at`, `updated_at`,
`created_by`, `updated_by`. Percentages and tax rates are stored in basis points
(1800 = 18.00%) to keep floats out of the money path.

## Organisation and people

| Entity | Table | Purpose | SoT |
|--------|-------|---------|-----|
| Institute | institutes | Deployed institute identity and config (name, GSTIN, address, logo) | SoT for institute identity |
| Branch | branches | A physical centre; records are branch-scoped | SoT for a branch |
| AcademicSession | academic_sessions | A teaching year; one marked active | SoT for a session |
| User | users | A login account | SoT for authentication identity |
| Staff | staff | Employee profile linked 1:1 to a User (teacher, counsellor, accountant, admin) | SoT for staff identity |
| Student | students | A learner | SoT for student identity |
| Guardian | guardians | A parent or guardian | SoT for guardian identity |
| StudentGuardian | student_guardian | Student to guardian link with relationship and primary flag | SoT for the family link |

## Enquiry and admissions

| Entity | Table | Purpose | SoT |
|--------|-------|---------|-----|
| Enquiry | enquiries | A lead in the counsellor pipeline | SoT for a lead |
| EnquiryActivity | enquiry_activities | Dated follow-up log with next follow-up date | SoT for follow-up history |
| Course | courses | A programme a student enrols into | SoT for a course |
| Subject | subjects | A subject within a course | SoT for a subject |
| Enrollment | enrollments | A student enrolled into a course for a session; the fee-bearing record | SoT for an enrollment |
| ConsentRecord | consent_records | Guardian consent captured at enrollment (data, communication) | SoT for consent |

## Scheduling and academics

| Entity | Table | Purpose | SoT |
|--------|-------|---------|-----|
| Batch | batches | A cohort under a course for a session at a branch; the roster | SoT for a batch |
| Classroom | classrooms | A physical room for slot allocation and conflict checks | SoT for a room |
| TimetableSlot | timetable_slots | A recurring weekly slot (day, time, batch, subject, teacher, room) | SoT for the timetable |
| AttendanceSession | attendance_sessions | One attendance sitting for a batch on a date/slot | SoT for an attendance sitting |
| AttendanceRecord | attendance_records | One student's status in one session | SoT for attendance |
| Assessment | assessments | A test or exam for a batch | SoT for an assessment |
| AssessmentSubject | assessment_subjects | A subject within an assessment with maximum marks | SoT for assessment structure |
| Mark | marks | One student's score for one assessment subject (null = not entered) | SoT for a mark |
| GradeScale | grade_scales | Configurable percentage-to-grade mapping (with bands) | SoT for grading rules |
| ReportCard | report_cards | Versioned snapshot per student per assessment | SoT for a published result |

## Finance

| Entity | Table | Purpose | SoT |
|--------|-------|---------|-----|
| FeePlan | fee_plans | A course fee structure of components | SoT for fee structure |
| FeeComponent | fee_components | A fee line (tuition, registration, material), taxable or exempt | SoT for a fee line |
| FeeSchedule | fee_schedules | A dated installment of a plan for an enrollment | SoT for installments |
| Invoice | invoices | A fee charge with GST split | SoT for a fee charge |
| InvoiceLine | invoice_lines | One component on an invoice with its GST split | SoT for invoice detail |
| Payment | payments | A receipt (offline now, online in Phase 14) | SoT for a receipt |
| PaymentAllocation | payment_allocations | Applies a payment to an invoice or installment | SoT for payment application |
| Discount | discounts | A reduction at plan or invoice level, with approver reference | SoT for a discount |
| Scholarship | scholarships | A student-level award, with approver reference | SoT for a scholarship |
| Refund | refunds | A refund against a payment | SoT for a refund |
| RefundReason | refund_reasons | Configurable refund reasons | SoT for refund reasons |
| TaxRate | tax_rates | A GST rate (basis points) applied to components | SoT for tax rates |
| LedgerEntry | ledger_entries | Balanced double-entry rows for fee postings | SoT for the books |

## System

| Entity | Table | Purpose | SoT |
|--------|-------|---------|-----|
| ClientSetting | client_settings | Per-client key/value overriding config/client.php | SoT for runtime client config |
| FeatureFlag | feature_flags | DB override for a feature flag | SoT for flag overrides |
| NumberingSequence | numbering_sequences | Gapless per-scope document counters | SoT for document numbers |
| NotificationTemplate | notification_templates | Stored channel-agnostic templates | SoT for message templates |
| Notification | notifications | In-app notification for a user | SoT for in-app notices |
| MessageLog | message_logs | A row per dispatch across all channels | SoT for the delivery record |
| AuditLog | audit_logs | Who did what to which record, with before/after | SoT for the audit trail |
| FileAttachment | file_attachments | Polymorphic file linked to any record | SoT for attachments |
