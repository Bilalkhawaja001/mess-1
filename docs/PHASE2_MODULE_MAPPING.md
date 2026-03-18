# Phase-2 Deep Mapping (Users, Members, Attendance)

## 1) Users module mapping

### Flask source behavior
- Route: `/users` (GET, POST)
- Guard: admin-only (`ROLE_ADMIN`/super admin equivalent)
- Actions (POST `action`):
  - `create`: username + password + role, unique username, role whitelist
  - `toggle_active`: flips `is_active`, cannot deactivate self
- Template: `templates/users.html`

### Laravel implementation
- Routes:
  - `GET /admin/users` -> `Admin\UserController@index`
  - `POST /admin/users` -> `Admin\UserController@store`
  - `PUT /admin/users/{user}` -> `Admin\UserController@update`
  - `POST /admin/users/{user}/toggle-active` -> `Admin\UserController@toggleActive`
- Validation:
  - `StoreUserRequest`, `UpdateUserRequest`
- View:
  - `resources/views/admin/users/index.blade.php`

Parity notes:
- Added inline edit capability (Flask had create + toggle in one action handler; Laravel split endpoints cleanly).
- Self-deactivation block preserved.

---

## 2) Members module mapping

### Flask source behavior
- Route: `/members` (GET, POST)
- Guard: super admin
- Create/edit/deactivate/reactivate/remove routes with member_id regex and join/leave rules
- Active filtering via `is_deleted` + leave_date semantics

### Laravel implementation
- Routes:
  - `GET /admin/members` -> `Admin\MemberController@index`
  - `POST /admin/members` -> `Admin\MemberController@store`
  - `PUT /admin/members/{member}` -> `Admin\MemberController@update`
  - `POST /admin/members/{member}/toggle-active` -> `Admin\MemberController@toggleActive`
- Validation:
  - `StoreMemberRequest`, `UpdateMemberRequest`
- Model linkage:
  - `members.user_id` nullable unique FK to `users`
- View:
  - `resources/views/admin/members/index.blade.php`

Parity notes:
- Flask `is_deleted` model represented as `is_active` toggle in Laravel scaffold for cleaner semantics.
- Regex for member code preserved (`^[A-Za-z0-9_-]+$`).

---

## 3) Attendance module mapping

### Flask source behavior
- Route: `/attendance` (GET, POST)
- Guard: operator/admin roles
- Date-based load
- Active members for selected date (join_date <= date and leave_date null or >= date)
- Save loop:
  - breakfast/lunch/dinner checkboxes
  - present = any selected
  - update existing else insert (unique by date+member)
  - optional `present_all`

### Laravel implementation
- Routes:
  - `GET /admin/attendance` -> `Admin\AttendanceController@index`
  - `POST /admin/attendance` -> `Admin\AttendanceController@store`
- Validation:
  - `StoreAttendanceRequest`
- Data table:
  - `attendances` with unique (`attendance_date`, `member_id`)
- View:
  - `resources/views/admin/attendance/index.blade.php`
- Logic parity:
  - date filter
  - active members for selected date
  - `present_all`
  - meal flags -> present
  - `updateOrCreate` by date+member

---

## Role guard mapping for Phase-2
- Group middleware: `auth + active + role:SUPER_ADMIN,ADMIN,DATA_ENTRY,AUDITOR`
- Module nuance:
  - Users and Members should be restricted further in Phase-3 with permission policy matrix (currently group-level role gate in place).

---

## Files touched in Phase-2
- Routes: `routes/web.php`
- Controllers:
  - `app/Http/Controllers/Admin/UserController.php`
  - `app/Http/Controllers/Admin/MemberController.php`
  - `app/Http/Controllers/Admin/AttendanceController.php`
- Requests:
  - `app/Http/Requests/Users/StoreUserRequest.php`
  - `app/Http/Requests/Users/UpdateUserRequest.php`
  - `app/Http/Requests/Members/StoreMemberRequest.php`
  - `app/Http/Requests/Members/UpdateMemberRequest.php`
  - `app/Http/Requests/Attendance/StoreAttendanceRequest.php`
- Models:
  - `app/Models/Attendance.php`
  - `app/Models/Member.php`
  - `app/Models/User.php`
- Migrations:
  - `2026_03_13_120000_create_attendances_table.php`
  - `2026_03_13_120100_add_user_link_to_members_table.php`
- Views:
  - `resources/views/admin/users/index.blade.php`
  - `resources/views/admin/members/index.blade.php`
  - `resources/views/admin/attendance/index.blade.php`
  - `resources/views/partials/sidebar.blade.php`
