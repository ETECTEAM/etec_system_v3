# Scholarship Class Certificate Request

Summary of how a **Scholarship Class** gets its certificates: the instructor requests, the admin prints. Diagrams are Mermaid (render in GitHub, VS Code Markdown Preview, etc.).

## 1. Overview

| | |
|---|---|
| **Who requests** | The class's instructor (owner or shared), from the class attendance page |
| **Who prints** | `super_admin` / `admin` (and `instructor`, same route group) at `/dashboard/certificates?type=scholarship` |
| **When a request is allowed** | Class status is `active` only |
| **Which students** | Active enrolled students the instructor ticks, minus any student who is attendance-blocked |
| **Output** | A "Certificate of Completion" with the scholarship layout (navy corner dots), saved per student in `student_certificate_normal` |

A class is treated as *scholarship* when its class type name contains `scholar` (seeded as `Scholarship Class`, `class_type_id = 5`). Everything else (free / internship / office / normal) follows the same pipeline with a different `certificate_type` value.

## 2. End-to-end flow

```mermaid
sequenceDiagram
    autonumber
    actor I as Instructor
    participant AR as AttendanceRecord.vue
    participant CR as CertificateRequest.vue
    participant IC as InstructorClassController
    participant DB as class_certificate_requests
    actor A as Admin
    participant CC as CertificateController
    participant CI as Certificates/Index.vue
    participant SC as student_certificate_normal

    I->>AR: Open class attendance page
    AR->>CR: "Request Certificate" (only if class is active and no pending request)
    CR->>IC: GET /instructor/classes/{id}/certificate-request
    IC-->>CR: Roster, certificateType = "scholarship", blocked flags
    I->>CR: Tick students (blocked students cannot be ticked)
    CR->>IC: POST /instructor/classes/{id}/certificate-request (student_ids, throttle 10/min)
    IC->>IC: Validate: class active, ids are active students, none blocked
    IC->>DB: updateOrCreate(study_class_id) status = pending, requested_student_ids, student_count
    IC-->>I: Redirect to attendance page, "Request submitted"

    A->>CI: Open Scholarship Certificate menu
    CI->>CC: GET /certificates/classes?type=scholarship
    CC->>DB: Classes with status = pending and certificate_type = scholarship
    CC-->>CI: Classes with remaining_students > 0
    A->>CI: Pick a class
    CI->>CC: GET /certificates/classes/{id}/students
    CC-->>CI: Requested students + is_printed flag
    A->>CI: Print / Re-print a student
    CI->>CC: GET /certificates/generate-id
    CI->>CC: POST /certificates/printed
    CC->>SC: updateOrInsert (student, class, "scholarship")
    CI->>CI: window.print()
```

## 3. Instructor-side rules (request submission)

```mermaid
flowchart TD
    A([Instructor clicks Request Certificate]) --> B{Class status = active?}
    B -- No --> B1[/"Warning: certificates can only be requested while the class is active"/]
    B -- Yes --> C{Already a pending request?}
    C -- Yes --> C1[Button shows 'Certificate Requested', disabled]
    C -- No --> D[Load roster of active students]
    D --> E{Student locked by absence block?}
    E -- Yes --> E1[Shown as 'blocked by system', cannot be ticked]
    E -- No --> F[Instructor ticks student]
    E1 --> G
    F --> G[Submit student_ids]
    G --> H{Server validation}
    H -- "ids not active in class" --> H1[/ValidationException/]
    H -- "any id is blocked" --> H2[/"Admin must unblock them first"/]
    H -- "no students / none ticked" --> H3[/Warning/]
    H -- OK --> I[(updateOrCreate on study_class_id<br/>status = pending<br/>certificate_type = scholarship<br/>requested_student_ids, student_count, note)]
    I --> J([Redirect to attendance page])
```

## 4. Certificate type detection

Class type name (from `class_type.type_name`) is lower-cased and checked **in this order**, first match wins:

```mermaid
flowchart LR
    T["class_type_name"] --> F{contains 'free'}
    F -- yes --> free
    F -- no --> S{contains 'scholar'}
    S -- yes --> scholarship
    S -- no --> N{contains 'intern'}
    N -- yes --> internship
    N -- no --> O{contains 'office'}
    O -- yes --> office
    O -- no --> normal
```

Implemented in `certificateTypeForClass()` ([InstructorClassController.php:710](../app/Modules/Instructor/Controllers/InstructorClassController.php#L710)) and duplicated in `certificateRequestTypeForClass()` ([InstructorClassService.php:990](../app/Modules/Instructor/Services/InstructorClassService.php#L990)). The admin list filters with `type_name LIKE '%scholar%'` ([CertificateController.php:25-30](../app/Modules/Certificate/Controllers/CertificateController.php#L25-L30)).

## 5. Request lifecycle

```mermaid
stateDiagram-v2
    [*] --> NoRequest
    NoRequest --> Pending: Instructor submits (class active)
    Pending --> Pending: Blocked student unblocked, added to requested_student_ids
    Pending --> FullyPrinted: All requested students printed (remaining = 0)
    FullyPrinted --> [*]

    note right of Pending
        Stored status stays 'pending' the whole time.
        reviewed_by / reviewed_at are never set.
    end note
    note right of FullyPrinted
        Derived, not stored. Class simply drops off the
        admin print list; row status is still 'pending'.
    end note
```

### Unblocking a student after the request was sent

When an absence block is approved, rejected, or a hard lock is unlocked, [AddUnblockedStudentToPendingCertificateRequest](../app/Modules/Certificate/Actions/AddUnblockedStudentToPendingCertificateRequest.php) runs:

```mermaid
flowchart TD
    U([Block approved / rejected / hard lock unlocked]) --> A{Student still active in that class?}
    A -- No --> X([Nothing])
    A -- Yes --> B{Pending request for that class<br/>with requested_at <= unblock time?}
    B -- No --> X
    B -- Yes --> C{Student already in requested_student_ids?}
    C -- Yes --> X
    C -- No --> D[Append student id, recompute student_count]
```

The `requested_at <= unblock time` check means a *later* request where the instructor deliberately left the student out is not altered.

## 6. Admin print screen

```mermaid
flowchart TD
    M[Menu: Scholarship Certificate] --> L["GET /dashboard/certificates/classes?type=scholarship"]
    L --> Q["StudyClass where status in upcoming, pre_end, ended, completed, active<br/>AND has certificateRequest(type = scholarship, status = pending, month/year filter)<br/>AND class type name LIKE '%scholar%'"]
    Q --> R["requested = request.student_count (fallback: active enrollments)<br/>printed = distinct students in student_certificate_normal<br/>remaining = requested - printed"]
    R --> S{remaining > 0?}
    S -- No --> Hidden[Hidden from list]
    S -- Yes --> Row[Class row: course, teacher, time, total / printed / remaining]
    Row --> ST["Open class: GET .../classes/{id}/students<br/>= active enrollments filtered by requested_student_ids"]
    ST --> P[Print or Print all]
    P --> ID["GET /generate-id: yymm + 3-digit sequence + ' ETEC'"]
    ID --> Save["POST /certificates/printed<br/>updateOrInsert on student + class + type"]
    Save --> Print["window.print() with scholarship template"]
```

`Certificate Report` (`?type=report`) reads the same `class_certificate_requests` rows and shows `printed` / `not_printed` per class, filterable by type, track, month and year.

## 7. Data model

```mermaid
erDiagram
    study_classes ||--o| class_certificate_requests : "has one (unique study_class_id)"
    users ||--o{ class_certificate_requests : "requested_by / reviewed_by"
    study_classes ||--o{ student_certificate_normal : has
    students ||--o{ student_certificate_normal : receives
    class_type ||--o{ study_classes : "class_type_id"

    class_certificate_requests {
        bigint id PK
        bigint study_class_id UK
        bigint requested_by FK
        bigint reviewed_by FK "never written"
        string certificate_type "scholarship"
        string status "pending"
        int student_count
        json requested_student_ids
        text note
        timestamp requested_at
        timestamp reviewed_at "never written"
    }
    student_certificate_normal {
        bigint id PK
        bigint student_id FK
        bigint study_class_id FK
        string certificate_type "scholarship"
        string student_name
        string course
        string granted_date
        string certificate_id "yymmNNN ETEC"
    }
    class_type {
        bigint class_type_id PK
        string type_name "Scholarship Class"
    }
```

## 8. Scholarship-specific behaviour

The only difference from other types is the visual template: `certificate_type === 'scholarship'` adds the `scholarship-certificate-preview` class and four navy (`#2d2e81`) corner dots on the border ([CertificatePreview.vue:12](../resources/js/pages/backend/certificates/CertificatePreview.vue#L12), [Index.vue:2318](../resources/js/pages/backend/certificates/Index.vue#L2318)). Wording, ID format, signatory and print flow are the same as the normal certificate.

## 9. Key files

| Layer | File |
|---|---|
| Instructor request page | [CertificateRequest.vue](../resources/js/pages/backend/instructors/CertificateRequest.vue) |
| Request button / status badge | [AttendanceRecord.vue:504](../resources/js/pages/backend/instructors/AttendanceRecord.vue#L504) |
| Request routes | [instructor.php:36-39](../routes/web/backend/instructor.php#L36-L39) |
| Request creation | `storeCertificateRequest()` in [InstructorClassController.php:350](../app/Modules/Instructor/Controllers/InstructorClassController.php#L355) |
| Admin routes | [certificate.php](../routes/web/backend/certificate.php) |
| Admin list / students / print save | [CertificateController.php](../app/Modules/Certificate/Controllers/CertificateController.php) |
| Admin UI + templates | [Index.vue](../resources/js/pages/backend/certificates/Index.vue), [CertificatePreview.vue](../resources/js/pages/backend/certificates/CertificatePreview.vue) |
| Unblock re-add | [AddUnblockedStudentToPendingCertificateRequest.php](../app/Modules/Certificate/Actions/AddUnblockedStudentToPendingCertificateRequest.php) |
| Models | [ClassCertificateRequest.php](../app/Models/ClassCertificateRequest.php), [StudentCertificateNormal.php](../app/Models/StudentCertificateNormal.php) |
| Migrations | `2026_08_29_000001`, `2026_08_30_000001`, `2026_09_02_000001` (certificate tables, requests, `requested_student_ids`) |

## 10. Things worth knowing (observed in code, not changed)

1. **No approval step.** The instructor UI says "for super admin review", but nothing sets `status` beyond `pending` or writes `reviewed_by` / `reviewed_at`. The admin prints directly. A fully printed class only disappears from the list because `remaining_students` hits 0.
2. **A pending request can never be re-submitted from the UI**, and since status never leaves `pending`, an instructor cannot request again for the same class (for example to add a student left out) without a DB edit. The unblock action is the only thing that grows `requested_student_ids` afterwards.
3. **One request per class.** `study_class_id` is `unique` and the write is `updateOrCreate` on it, so there is no per-type history.
4. **Legacy path.** `requestCertificate()` ([InstructorClassController.php:232](../app/Modules/Instructor/Controllers/InstructorClassController.php#L232)) and `certificate_class_requests` / `CertificateClassRequest` have no route pointing at them and the admin screens never read that table.
5. **Empty `requested_student_ids` means all.** `students()` only filters by requested IDs when the list is non-empty; otherwise every active enrollee is shown.
6. **Type detection is duplicated** in the controller, the service and the admin keyword filter. A class type named e.g. "Free Scholarship" resolves to `free` because `free` is checked first.
7. **Certificate IDs share one sequence** across all types (`student_certificate_normal.certificate_id`, `yymm` + 3 digits + ` ETEC`), generated with a read-then-increment and no lock.
8. **Report and list year filter** is clamped to 2018 through the current year.
