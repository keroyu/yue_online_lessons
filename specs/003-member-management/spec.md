# Feature Specification: Member Management (會員管理)

**Feature Branch**: `003-member-management`
**Created**: 2026-01-17
**Updated**: 2026-01-18
**Updated**: 2026-03-09 - 改為同步發送 Email（Mail::send），移除 Queue 依賴（FR-017, FR-027, SC-007）
**Updated**: 2026-03-09 - 修正贈課 Email 模板檔名錯誤（.text.blade.php → .blade.php）；批次 Email 支援 Markdown 格式輸入
**Updated**: 2026-03-11 - 會員詳情課程列表新增取得方式標籤（贈送／購買）
**Updated**: 2026-03-30 - 補充 Purchase 語意：會員擁有權以 status 判斷，取得方式標籤以 type 判斷
**Updated**: 2026-05-03 - 新增 US8（匯出 CSV）、US9（匯入 Email 名單）；FR-030～039；SC-010～011
**Updated**: 2026-05-03 - US9 補強：無效 Email 清單顯示（FR-040、FR-041、Scenario 5-6 更新）
**Updated**: 2026-05-03 - 新增 US10（匯入 modal 新增 CSV 上傳模式）；FR-042～048；SC-012
**Status**: Draft
**Input**: User description: "後台功能新增：會員管理。1.可以查看、編輯會員的email, 暱稱，姓名, 電話, 生日, IP，註冊時間和最後登入時間 2.查看會員擁有的課程和完成進度 3.用checkbox 或 通過filter（例如:擁有xxx課程的）選定會員批次發送email（編寫email主旨和內文的功能用modal）"
**Update 2026-01-18**: "在批次選取會員的功能新增「贈送課程」的按鈕。贈送的同時發送 Email 通知會員, 內容包括贈送的課程名稱和簡介，並歡迎會員回到網站開始學習"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - View and Search Members (Priority: P1)

As an admin, I want to view a list of all members with their key information so I can quickly find and manage member accounts.

**Why this priority**: This is the foundation of member management. Without the ability to view members, no other functionality (editing, viewing courses, sending emails) is possible.

**Independent Test**: Can be fully tested by logging in as admin, navigating to the member management page, and verifying that members are listed with correct information. Delivers immediate value for customer support and account oversight.

**Acceptance Scenarios**:

1. **Given** I am logged in as an admin, **When** I navigate to the member management page, **Then** I see a paginated list of all members showing email, nickname, real name, phone, birthday, registration IP, registration time, and last login time.
2. **Given** I am on the member list, **When** I search by email or name, **Then** the list filters to show only matching members.
3. **Given** I am on the member list, **When** I sort by any column (e.g., registration date, last login), **Then** the list reorders accordingly.

---

### User Story 2 - Edit Member Information (Priority: P1)

As an admin, I want to edit member profile information so I can correct data errors or update information on behalf of members.

**Why this priority**: Essential for customer support operations. Members may request changes or admins may need to fix data issues.

**Independent Test**: Can be fully tested by selecting a member, editing their fields, saving, and verifying the changes persist. Delivers immediate value for data maintenance.

**Acceptance Scenarios**:

1. **Given** I am on the member list, **When** I inline-edit a member's email, real name, or phone and save, **Then** the changes are saved and displayed immediately in the table.
2. **Given** I am on the member list, **When** I click the copy button next to an email, **Then** the email is copied to clipboard with visual confirmation.
3. **Given** I open the member detail modal, **When** I edit nickname or birthday and save, **Then** the changes are saved and the modal reflects the update.
4. **Given** I edit a member's email to one that already exists, **When** I try to save, **Then** I see an error message indicating the email is already in use.
5. **Given** I edit a member's information, **When** I cancel without saving, **Then** no changes are made.

---

### User Story 3 - View Member Course Ownership and Progress (Priority: P2)

As an admin, I want to see which courses a member owns and their progress in each course so I can assist with support inquiries about course access.

**Why this priority**: Important for customer support but depends on having the member list view (P1) first. Provides visibility into learning engagement.

**Independent Test**: Can be fully tested by selecting a member who owns courses and verifying their course list and progress percentages are displayed correctly.

**Acceptance Scenarios**:

1. **Given** I am viewing a member's details, **When** I look at their courses section, **Then** I see a list of all courses they have purchased.
2. **Given** a member owns a course with 10 lessons and has completed 3, **When** I view their course progress, **Then** I see 30% progress displayed.
3. **Given** a member has not purchased any courses, **When** I view their courses section, **Then** I see an appropriate empty state message.
4. **Given** a member owns courses acquired by different means, **When** I view their course list in the detail modal, **Then** each course displays a small label indicating "贈送" (purple) or "購買" (blue) based on the purchase type.

---

### User Story 4 - Filter Members by Course Ownership (Priority: P2)

As an admin, I want to filter the member list by course ownership so I can find all members who purchased a specific course.

**Why this priority**: Enables targeted communication and analysis. Necessary groundwork for batch email functionality.

**Independent Test**: Can be fully tested by selecting a course filter and verifying only members who own that course are displayed.

**Acceptance Scenarios**:

1. **Given** I am on the member list, **When** I select a course from the filter dropdown, **Then** only members who own that course are displayed.
2. **Given** I have applied a course filter, **When** I clear the filter, **Then** all members are displayed again.
3. **Given** I filter by a course with no purchasers, **When** the filter is applied, **Then** I see an empty state indicating no members found.

---

### User Story 5 - Select Members for Batch Operations (Priority: P2)

As an admin, I want to select multiple members using checkboxes so I can perform batch operations on them.

**Why this priority**: Prerequisite for batch email functionality. Selection mechanism is needed before sending emails.

**Independent Test**: Can be fully tested by checking individual member checkboxes and using select-all, verifying selection count is accurate.

**Acceptance Scenarios**:

1. **Given** I am on the member list, **When** I check individual member checkboxes, **Then** those members are selected and a count is displayed.
2. **Given** I am on the member list, **When** I click "select all" checkbox, **Then** all visible members on the current page are selected.
3. **Given** I have applied a filter showing 150 matching members across 3 pages, **When** I click "Select all 150 matching members", **Then** all 150 members are selected regardless of pagination.
4. **Given** I have selected members, **When** I navigate to another page and return, **Then** my previous selections are preserved.

---

### User Story 6 - Send Batch Email to Selected Members (Priority: P3)

As an admin, I want to compose and send an email to selected members so I can communicate promotions, updates, or important information efficiently.

**Why this priority**: Valuable marketing and communication feature but requires member selection (P2) to function. Complex feature that can be developed after core management features.

**Independent Test**: Can be fully tested by selecting members, composing an email in the modal, sending, and verifying all selected members receive the email.

**Acceptance Scenarios**:

1. **Given** I have selected one or more members, **When** I click "Send Email" button, **Then** a modal opens with fields for subject and body.
2. **Given** I am in the email composition modal, **When** I enter a subject and body and click send, **Then** emails are queued for delivery to all selected members.
3. **Given** I try to send an email with empty subject or body, **When** I click send, **Then** I see validation errors indicating required fields.
4. **Given** I have filtered members by a course, **When** I select members and send an email, **Then** only the selected members receive the email.
5. **Given** emails are being sent, **When** the process completes, **Then** I see a success message with the count of emails sent.
6. **Given** 管理員在批次 Email 內文輸入 Markdown 語法（粗體 `**text**`、清單 `- item`）, **When** Email 送達收件人, **Then** 收件人看到渲染後的 HTML 格式（粗體、清單），無裝飾性樣式

---

### User Story 7 - Gift Course to Selected Members (Priority: P3)

As an admin, I want to gift courses to selected members so I can provide free access for promotions, rewards, or customer support purposes.

**Why this priority**: Adds value to the batch operation system but requires member selection (P2) to function. Similar complexity to batch email.

**Independent Test**: Can be fully tested by selecting members, choosing a course to gift, confirming the action, and verifying members receive the course and notification email.

**Acceptance Scenarios**:

1. **Given** I have selected one or more members, **When** I click the "贈送課程" (Gift Course) button, **Then** a modal opens allowing me to select a course to gift.
2. **Given** I am in the gift course modal, **When** I select a course from the dropdown, **Then** I see the course name and description displayed for confirmation.
3. **Given** I have selected a course to gift, **When** I click confirm, **Then** the selected members receive access to the course.
4. **Given** a course is gifted successfully, **When** the operation completes, **Then** each member receives a notification email containing the course name, course description, and a welcome message inviting them to start learning.
5. **Given** a member already owns the course being gifted, **When** the gift operation runs, **Then** that member is skipped (no duplicate purchase created) and included in the "already owned" count.
6. **Given** the gift operation completes, **When** I see the result, **Then** I see a success message showing: number of courses gifted, number of members who already owned it, and confirmation that emails were sent.
7. **Given** I try to gift a course with no members selected, **When** I click the gift button, **Then** the button is disabled or I see an error message.

---

### User Story 8 - Export Member Data as CSV (Priority: P3)

As an admin, I want to export member data to a CSV file so I can analyse, back up, or use the data in external tools.

**Why this priority**: Useful operational tool but does not block any core management workflow. Depends on the member list (P1) being in place.

**Independent Test**: Can be fully tested by clicking the Export button, choosing an export scope (all or selected), and verifying the downloaded CSV contains the correct members and fields.

**Acceptance Scenarios**:

1. **Given** I am on the member management page, **When** I look at the top-right area, **Then** I see a permanent "匯出" button regardless of whether any members are selected.
2. **Given** I click the "匯出" button, **When** the dropdown opens, **Then** I see two options: "匯出全部" and "匯出選定".
3. **Given** no search or filter is active, **When** I click "匯出全部", **Then** all members in the system are exported and the dropdown shows a hint "匯出全部會員".
4. **Given** a search or filter is active, **When** I click "匯出全部", **Then** only members matching the current filters are exported and the dropdown shows a hint describing the current scope (e.g., "匯出符合篩選條件的 N 位會員").
5. **Given** I have selected one or more members, **When** I click "匯出選定", **Then** only the selected members are exported.
6. **Given** no members are selected, **When** the dropdown opens, **Then** the "匯出選定" option is visually disabled and cannot be clicked.
7. **Given** any export is triggered, **When** the export runs, **Then** the browser immediately downloads a CSV file containing: 暱稱、真實姓名、Email、加入日期、最後登入時間.

---

### User Story 9 - Import Members from Email List (Priority: P3)

As an admin, I want to import a list of email addresses to create member accounts in bulk so I can onboard existing contacts without requiring them to register individually.

**Why this priority**: Useful for migrations and campaigns but does not affect existing member management functionality.

**Independent Test**: Can be fully tested by pasting a list of emails into the import modal, confirming, and verifying that new accounts are created and existing emails are skipped.

**Acceptance Scenarios**:

1. **Given** I am on the member management page, **When** I look at the top-right area, **Then** I see a permanent "匯入" button.
2. **Given** I click "匯入", **When** the modal opens, **Then** I see a textarea where I can paste email addresses.
3. **Given** I paste a list of emails (one per line or comma-separated), **When** I confirm the import, **Then** the system creates a member account for each valid, new email.
4. **Given** an email in the list already exists in the system, **When** the import runs, **Then** that email is skipped (no duplicate created) and counted in the "略過" summary.
5. **Given** an email in the list is malformed (no "@" or invalid format), **When** the import runs, **Then** it is skipped; the result shows the count AND lists each invalid email so the admin can correct them.
6. **Given** the import completes, **When** I see the result (still inside the modal), **Then** I see a summary showing: 新增會員數、略過（已存在）數、無效格式數; if any invalid emails exist, the full list is displayed below the summary. The modal stays open until I click "關閉".
7. **Given** I submit an empty textarea, **When** I click confirm, **Then** the system shows a validation error and does not proceed.

---

### User Story 10 - Import Members from CSV File (Priority: P3)

As an admin, I want to upload a CSV file to import member accounts with full profile data (name and phone) so I can onboard existing contacts from a spreadsheet in a single step.

**Why this priority**: Extension of US9. Useful when the admin has structured contact data (name + phone) in spreadsheet format that cannot be expressed with the paste-only mode.

**Independent Test**: Can be fully tested by switching to the CSV upload tab, uploading a valid 3-column file, reviewing the preview, confirming import, and verifying that new members have email, real name, and phone populated.

**Acceptance Scenarios**:

1. **Given** the import modal is open, **When** I look at the top of the modal, **Then** I see two tabs: "貼上 Email 名單" and "上傳 CSV 檔案".
2. **Given** I click the "上傳 CSV 檔案" tab, **When** the tab becomes active, **Then** I see a file upload area and a format hint: "CSV 必須包含 3 欄（依序為 Email、姓名、電話），第一列為欄位標題列".
3. **Given** I select a valid CSV file with 3 columns and at least one data row, **When** the file is parsed, **Then** I see a preview showing the 3 column headers and the total number of data rows to be imported.
4. **Given** the preview is showing, **When** I click "確認匯入", **Then** the system imports each row: creating new accounts with email, real name, and phone; skipping rows where the email already exists; collecting rows with invalid email formats.
5. **Given** a CSV row has a valid email that already exists in the system, **When** the import runs, **Then** that row is skipped and counted in "略過（已存在）".
6. **Given** a CSV row has an invalid email format, **When** the import runs, **Then** that row is skipped and the invalid email is listed in the result the same way as the paste mode.
7. **Given** I upload a CSV file with fewer than 3 columns, **When** the system parses it, **Then** I see an error: "CSV 格式錯誤：至少需要 3 欄（依序為 Email、姓名、電話）" and import is blocked.
8. **Given** I upload a CSV file that contains only the header row and no data rows, **When** the system parses it, **Then** I see an error: "CSV 檔案不含任何資料列" and import is blocked.

---

### Edge Cases

- What happens when a member has no email set? Display warning and exclude from batch email.
- What happens when editing a member's email to an invalid format? Show validation error.
- What happens when trying to send email with no members selected? Disable send button or show error.
- What happens when the email service is unavailable? Show error message and suggest retry later.
- What happens when a member is deleted while viewing their details? Redirect to member list with notification.
- What happens when gifting a course to a member without an email? Gift the course (create purchase) but skip email notification; show warning in result summary.
- What happens when all selected members already own the course? Show message "所有選取的會員都已擁有此課程" (All selected members already own this course) with no gifts created.
- What happens when the course has no description? Display "（無課程簡介）" in the email instead of blank content.
- What happens when "匯出選定" is clicked with no selection? The option is disabled — cannot be triggered.
- What happens when CSV field values contain commas or line breaks? Wrap the field in double-quotes per RFC 4180.
- What happens when the import textarea contains only whitespace or blank lines? Treat as empty input and show a validation error.
- What happens when the same email appears twice in the import list? Process it once; the duplicate is silently de-duplicated before processing.
- What happens when a CSV row has a valid email but empty 姓名 or 電話 columns? Import the account with whatever fields are present; empty real name and phone are acceptable.
- What happens when the uploaded CSV has more than 3 columns? Ignore any columns beyond the first 3; process only Email (col 1), 姓名 (col 2), 電話 (col 3).
- What happens when a CSV row's email already exists and that row has updated name/phone data? Skip the row entirely — existing member data is never overwritten by import.
- What happens when the user switches tabs (paste ↔ CSV) mid-flow? Each tab retains its own input state; switching tabs does not clear the other tab's content.
- What happens when a CSV row's phone starts with "09" but has a digit count other than 10 (e.g., "091234567" = 9 digits)? Import the row with phone left empty; include the member's email in the import result's "電話格式有誤" list (FR-049a).
- What happens when a CSV row's phone does not start with "09" (e.g., "+1-555-1234", "02-1234-5678")? Treat as international/non-mobile number, store as-is without format enforcement.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST display a paginated list of all members with role "member" (excluding admins and editors).
- **FR-002**: System MUST display the following fields for each member: email, nickname, real name, phone, birthday, registration IP (from first login), registration time (created_at), and last login time.
- **FR-003**: System MUST allow searching members by email, nickname, or real name.
- **FR-004**: System MUST allow sorting the member list by email, real name, registration time, and last login time.
- **FR-005**: System MUST allow inline editing of email, real name, and phone directly in the member list table.
- **FR-005a**: System MUST provide a copy-to-clipboard button next to the email field.
- **FR-005b**: System MUST provide a modal dialog for editing additional fields (nickname, birthday) and viewing course details.
- **FR-006**: System MUST validate email uniqueness when editing member email.
- **FR-007**: System MUST validate email format when editing.
- **FR-008**: System MUST display the list of courses a member has purchased.
- **FR-009**: System MUST calculate and display course completion progress as a percentage (completed lessons / total lessons).
- **FR-010**: System MUST allow filtering members by course ownership (show members who own a specific course).
- **FR-011**: System MUST provide checkboxes for selecting individual members.
- **FR-012**: System MUST provide a "select all" checkbox that selects all members on the current page.
- **FR-012a**: System MUST provide a "Select all X matching members" option after filtering, allowing selection across all pages.
- **FR-013**: System MUST display a count of currently selected members.
- **FR-014**: System MUST preserve member selections when navigating between pages.
- **FR-015**: System MUST provide a modal for composing batch emails with subject and body fields; body MUST support Markdown syntax (bold, lists, links) which is rendered to minimal HTML on send.
- **FR-016**: System MUST validate that email subject and body are not empty before sending.
- **FR-017**: System MUST send batch emails synchronously using Mail::send() for immediate delivery (member count is small; no queue required).
- **FR-018**: System MUST display success/failure feedback after email sending operation.
- **FR-019**: System MUST only allow admin users to access member management features.
- **FR-020**: System MUST provide a "贈送課程" (Gift Course) button when members are selected.
- **FR-021**: System MUST display a modal with course selection dropdown when gift course button is clicked.
- **FR-022**: System MUST show selected course name and description in the gift course modal for confirmation.
- **FR-023**: System MUST create purchase records for selected members who do not already own the course.
- **FR-024**: System MUST skip members who already own the course being gifted (no duplicates).
- **FR-025**: System MUST send notification email to each member who receives a gifted course.
- **FR-026**: Gift notification email MUST include: course name, course description (or placeholder if empty), and a welcome message inviting the member to start learning.
- **FR-027**: System MUST send gift notification emails synchronously using Mail::send() immediately after each gift purchase record is created.
- **FR-028**: System MUST display result summary showing: courses gifted count, already owned count, and email sent count.
- **FR-029**: System MUST display an acquisition type label on each course in the member detail modal: "贈送" for purchases with type 'gift' or 'system_assigned', "購買" for all other types.

**匯出 CSV（US8）**

- **FR-030**: System MUST display a permanent "匯出" dropdown button in the top-right of the member management page, visible at all times regardless of selection state.
- **FR-031**: The "匯出" dropdown MUST contain two options: "匯出全部" and "匯出選定".
- **FR-032**: "匯出全部" MUST export all members matching the current active filters (search keyword and/or course filter); if no filters are active, it exports all members in the system. The dropdown MUST display a hint describing the current export scope before the user clicks.
- **FR-033**: "匯出選定" MUST export all currently selected members, including those selected via the cross-page "Select all N matching" mode (FR-012a). This option MUST be disabled (unclickable) when no members are selected (neither individual nor cross-page).
- **FR-034**: The exported CSV MUST include the following columns in order: 暱稱 (nickname)、真實姓名 (real_name)、Email、加入日期 (created_at)、最後登入時間 (last_login_at). Fields with empty values MUST be exported as empty strings.
- **FR-035**: Selecting either export option MUST trigger an immediate browser file download of a CSV file named `members-YYYY-MM-DD.csv`, encoded as UTF-8 with BOM for Excel compatibility. No page navigation or reload should occur.

**匯入 Email 名單（US9）**

- **FR-036**: System MUST display a permanent "匯入" button in the top-right of the member management page.
- **FR-037**: Clicking "匯入" MUST open a modal with a textarea. The textarea MUST accept email addresses formatted as: one per line, comma-separated, or a mix of both.
- **FR-038**: On import confirmation, the system MUST de-duplicate the parsed email list before processing (same email appearing twice counts as one).
- **FR-039**: For each unique, valid email in the list, the system MUST: (a) skip if the email already exists in the system (count as "已存在"); (b) create a new member account with role="member" and nickname defaulting to the part before "@" in the email, if the email does not exist. No password is required — members authenticate via email verification code.
- **FR-040**: Emails that fail basic format validation (no "@", invalid structure) MUST be skipped and counted separately as "無效格式". The API response MUST include the full list of invalid email strings (not just a count) so the frontend can display them.
- **FR-041**: After import completes, the system MUST display a result summary inside the modal showing: 新增會員數、略過（已存在）數、無效格式數. If there are invalid emails, their full list MUST be displayed below the summary so the admin can identify and correct them. The modal MUST remain open until the admin explicitly closes it. Closing the modal MUST trigger a member list refresh to reflect newly added members.

**匯入 CSV 上傳（US10）**

- **FR-042**: The import modal MUST provide two input mode tabs: "貼上 Email 名單" (existing textarea mode, selected by default) and "上傳 CSV 檔案" (new file upload mode). Each tab retains its own input state independently.
- **FR-043**: The "上傳 CSV 檔案" tab MUST display a persistent format hint: "CSV 欄位順序固定：第 1 欄 Email、第 2 欄 姓名、第 3 欄 電話。第一列為標題列（名稱不限），第 2 列起為資料。"
- **FR-044**: The CSV file MUST be parsed without a server round-trip for the preview step. Column mapping is position-based: column 1 = Email, column 2 = 姓名 (real name), column 3 = 電話 (phone). The first row is skipped as a header regardless of its content — header names are NOT validated. Columns beyond the first 3 are silently ignored. The system MUST reject the file if the column count is fewer than 3 (FR-048).
- **FR-045**: After a CSV file is selected, the system MUST display a preview showing: the 3 expected column labels (Email, 姓名, 電話) and the total number of data rows parsed from the file.
- **FR-046**: The preview MUST include a "確認匯入" button and a "取消" button. Confirming triggers import; cancelling clears the selected file and returns to the upload area.
- **FR-047**: On CSV import, for each data row: (a) if the email is already in the system, skip the row (count as "略過"); (b) if the email is new, create a member account with email, real_name (from 姓名 column), and phone (from 電話 column) — empty 姓名 or 電話 values are accepted; nickname defaults to the part before "@". Import result display MUST follow the same format as paste mode (FR-041): existing member data is never modified by import.
- **FR-048**: If the uploaded CSV has fewer than 3 columns or contains no data rows after the header, the system MUST reject the file immediately with a descriptive error message and block import.
- **FR-049**: When the 電話 field in a CSV row is non-empty, the system MUST validate it before storing: (a) strip leading/trailing whitespace; (b) if the value starts with "09" and is exactly 10 digits → valid Taiwan mobile, store the normalized digit-only value; (c) if the value starts with "09" but is NOT exactly 10 digits → invalid Taiwan mobile format, apply FR-049a; (d) if the value does not start with "09" → treat as international number, store without further validation. Empty 電話 values are always accepted.
- **FR-049a**: When a CSV row's phone fails Taiwan mobile validation (FR-049c), the row MUST still be imported — the member account is created with email and 姓名 as normal, but the phone field is stored as empty. The import result summary MUST include a list of the affected members' emails so the admin can identify and correct the phone data afterwards.

### Key Entities

- **Member (User)**: User with role "member". Key attributes: email, nickname, real_name, phone, birth_date, last_login_ip, last_login_at, created_at.
- **Purchase**: Links member to course. Represents course ownership. `status` indicates whether access is still active (`paid` / `refunded`); `type` indicates how the course was acquired (`paid` / `gift` / `system_assigned`). Member ownership filters use `status`; UI labels use `type`.
- **Course**: The course product that members can purchase or receive as gift. Key attributes include name and description for gift notification emails.
- **LessonProgress**: Tracks which lessons a member has completed for progress calculation.
- **BatchEmail**: A record of batch email operations including subject, body, recipient count, and send status.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Admins can find any member by email or name within 10 seconds.
- **SC-002**: Member information edits are saved and reflected immediately upon save.
- **SC-003**: Course progress is accurately calculated (matches actual completed lessons).
- **SC-004**: Batch emails reach 100% of selected recipients with valid email addresses.
- **SC-005**: Email composition and sending workflow completes in under 1 minute for up to 500 recipients.
- **SC-006**: Member list loads within 2 seconds for up to 10,000 members.
- **SC-007**: Gift course operation (including email sending) completes within 30 seconds for the platform's current member scale.
- **SC-008**: 100% of gifted courses are accessible to members immediately after gift operation.
- **SC-009**: Gift notification emails reach 100% of recipients with valid email addresses.
- **SC-010**: Exporting up to 1,000 members triggers a file download within 5 seconds.
- **SC-011**: Importing a list of up to 200 emails completes processing and displays the result summary within 10 seconds.
- **SC-012**: Uploading and parsing a CSV file of up to 500 data rows displays the preview within 3 seconds (client-side parsing; no server round-trip required for preview).

## Clarifications

### Session 2026-01-17

- Q: What interface pattern for member editing? → A: Hybrid - email, real name, phone are inline-editable in table (email has copy button); other fields (nickname, birthday, courses) open in modal dialog.
- Q: What is the scope of "select all" behavior? → A: After filtering, provide "Select all X matching members" option that selects across all pages, not just current page.
- Q: Should member edits be audit logged? → A: No audit logging needed for this feature.

### Session 2026-03-09 (Email Sending Strategy)

- Q: Should batch email and gift notification use async queue or synchronous sending? → A: Synchronous (Mail::send), consistent with login verification code. Platform member count is small; no Queue Worker complexity needed. Can be switched to `queue()` later without changing Mailable classes.
- Q: What is the performance expectation for synchronous email sending? → A: Completes within 30 seconds for current member scale; SC-007 updated accordingly.

### Session 2026-05-03 (Import / Export Feature)

- Q: 當跨頁全選（FR-012a）啟用時，「匯出選定」匯出哪些人？ → A: 匯出全部 N 位符合條件的會員，尊重跨頁選取結果。FR-033 已更新。
- Q: 匯入建立新會員時，是否需要發送通知或設定密碼？ → A: 不需要。系統採 email 驗證碼登入，無密碼欄位，被匯入的會員直接以 email 收驗證碼即可登入。FR-039 已移除隨機密碼描述。
- Q: 匯出 CSV 的字元編碼？ → A: UTF-8 with BOM，確保 Excel 開啟中文欄位不亂碼。FR-035 已更新。

### Session 2026-05-03 (CSV Import Feature)

- Q: 若 CSV 某列的 email 已存在，該列的姓名/電話是否更新現有會員？ → A: 否，略過整列，不修改任何現有會員資料。FR-047 已明確。
- Q: CSV 欄位超過 3 欄時如何處理？ → A: 第 4 欄以後靜默忽略，只取前 3 欄。Edge case 已記錄。
- Q: CSV 上傳是 client-side 解析還是 server-side？ → A: 預覽階段在 client 解析（SC-012）；確認後將解析出的資料送後端，與貼上模式共用同一匯入 API（擴充欄位接受 real_name、phone）。
- Q: CSV header 欄位名稱是否要驗證？ → A: 否，只驗證欄位數（≥3），header 名稱不限；欄位對應以位置（第 1 欄=Email、第 2 欄=姓名、第 3 欄=電話）決定。格式 hint 說明順序。另：電話須驗證格式——台灣手機（09 開頭、10 碼）強制規範，非台灣格式不驗證。FR-043、FR-044、FR-049 已更新。
- Q: 電話格式錯誤時（09 開頭但非 10 碼），該列如何處理？ → A: 該列仍匯入（建立帳號），電話欄留空；結果摘要列出電話格式有誤的 email 供管理員修正。FR-049a 已更新。

### Session 2026-01-18 (Gift Course Feature)

- Q: How to distinguish gifted courses from purchased courses? → A: Use existing Purchase model with type='gift' to indicate gifted courses. Three types exist: 'paid' (normal purchase), 'gift' (admin gifted), 'system_assigned' (auto-assigned to course creator).
- Q: Should gift notification email be customizable? → A: No, use a fixed template with course name, description, and welcome message. No admin-editable fields.
- Q: Can admin gift the same course multiple times to a member? → A: No, system prevents duplicates. If member already owns the course, skip and report in summary.

## Assumptions

- The existing `last_login_ip` field in the users table captures the IP address on login (registration IP can be derived from first login or stored separately if needed).
- Members are users with role "member" - admins and editors are excluded from this management view.
- The existing Resend.com integration will be used for email delivery.
- Phone numbers do not require format validation beyond basic string constraints.
- Birthday is optional and stored as date without time.
- Course progress is binary per lesson (complete or not complete) - no partial completion tracking needed.
- Gifted courses use the same Purchase model as regular purchases, with type='gift' to distinguish from type='paid' (normal purchases) and type='system_assigned' (auto-assigned).
- Course description is available in the Course model for inclusion in gift notification emails.
- Gifted courses grant immediate full access - no separate activation required.
- Gift notification emails use a fixed template in Chinese (中文) matching the platform language.
- Both batch emails and gift notification emails are sent synchronously (Mail::send), consistent with the login verification code approach. Member count is small; no Queue Worker required. If member base grows significantly, change `send()` to `queue()` without modifying the Mailable class.
- Members authenticate via email verification code (magic link / OTP); there is no password field. Imported members can log in immediately with their email address without any additional setup.
- CSV upload parsing is performed entirely client-side (in the browser) before the admin confirms import; no server round-trip is required to display the preview.
- The CSV import shares the same backend import endpoint as paste mode; the endpoint is extended to optionally accept real_name and phone per row.
- CSV file size is not explicitly capped in the spec; SC-012 defines the performance target of ≤3 seconds for up to 500 rows, which serves as the effective practical limit.

## Deployment Notes

### Email Configuration

Both batch emails and gift notification emails use synchronous Mail::send() via Resend. No queue worker setup required.

| Environment | Setup |
|-------------|-------|
| Local Dev | Set `MAIL_MAILER=resend` (or `log` for testing) in `.env` |
| Production | Set `MAIL_MAILER=resend` and `RESEND_KEY` in `.env` |

### Admin Page Layout

Admin pages must use `defineOptions({ layout: AdminLayout })` instead of wrapping content with `<AdminLayout>` in template. This prevents duplicate flash toasts caused by both AppLayout (default) and AdminLayout rendering simultaneously.
