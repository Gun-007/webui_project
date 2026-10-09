# EventNest Core
Roles, data model, proposal approval and event registration for the EventNest college portal.
Approval order is the `enc_approval_chain` option (default: faculty -> deputy). Use ['faculty'] for A, or filter `enc_approval_chain`.

## Account pages

Create these WordPress pages and place the matching shortcode in each page's content:

| Page slug | Shortcode |
| --- | --- |
| `login` | `[eventnest_login]` |
| `register-student` | `[eventnest_register_student]` |
| `register-faculty` | `[eventnest_register_faculty]` |
| `dashboard` | `[eventnest_dashboard]` |
| `my-profile` | `[eventnest_my_profile]` (created automatically) |
| `event-registrations` | `[eventnest_all_registrations]` |
| `my-registrations` | `[eventnest_my_registrations]` |
| `my-clubs` | `[eventnest_my_clubs]` |
| `submit-proposal` | `[eventnest_submit_proposal]` |
| `my-proposals` | `[eventnest_my_proposals]` |
| `review-proposals` | `[eventnest_review_proposals]` |
| `share-story` | `[eventnest_submit_story]` |
| `my-stories` | `[eventnest_my_stories]` |

Student registration requires a unique PRN, college email, mobile, name and a password of at least 10 characters. Students can sign in with username, email or PRN. Faculty registration creates a low-privilege Subscriber account marked as pending; an administrator must verify the person and assign the appropriate EventNest role in Users before they receive faculty or club permissions. Never let a public registration form choose an elevated role.

The **Share Your Story** and **My Stories** pages are created automatically if missing; the other shortcode pages in the table are created manually by the site administrator.

Students can edit their full name, college email, mobile number, course, and batch on **My Profile**. PRN is read-only because it identifies the account and its registration history.

Administrators can disable or reactivate accounts under **Users → EventNest Accounts**. Actions require a reason, revoke active sessions on disable, and preserve the user's records. Club Head assignments are managed per club under **Clubs → Club Leadership**; a club can be left vacant or assigned to another approved Club Head. Assignment changes are audited and do not erase memberships or authored content.

Administrators can review pending staff signups under **Users → Faculty Requests** and approve each as Faculty, Faculty Head, or Club Head. Approval assigns the selected role and, for Faculty/Faculty Head, the chosen department. Administrators can change department assignments later in the WordPress user editor.

Faculty, Club Heads, Deputy Directors, Directors and Administrators with the corresponding review capability can review submissions on the `review-proposals` page. Decisions are recorded in the proposal approval history; rejecting or requesting changes requires a reviewer comment.

These forms use WordPress accounts and nonces. Passwords are stored through WordPress's password API; EventNest stores the additional profile fields as user metadata. Email verification and automated account approval are not included yet.

## Departments and role scope

- Administrators create department records under **Users → Departments**.
- Assign Faculty and Faculty Head users to a department while approving a staff request, or later from **Users → Edit User → EventNest department**.
- Assign clubs to departments in the Club editor. Their Faculty in-charge must belong to the same department. Linked events and proposals inherit the club department; direct events use the Department box and proposals without a department-owned club can select a department.
- Faculty Heads need a department assignment. Their event editing, proposal reviews, club-application oversight, and registration reports are limited to that department. Assigned Faculty use the same event/proposal/report boundary. Club Heads see club-specific registration and application queues; Deputy Directors, Directors, and Administrators retain institute-wide reports.
- Department assignment does not hide public events.

## Optional starter content

Sample clubs and events are not created during normal site requests. An administrator can add them from **Events → Starter Demo Content** on a demo installation. Existing records are preserved.

## Student participation pages

The event detail page uses EventNest's internal registration system when this plugin is active. Registrations are limited to signed-in accounts with the `en_register_event` capability. The My Registrations page shows only the current user's records and permits cancellation before the event date. Team-registration data functions exist, but the student-facing team form is a later enhancement. Proposal submissions use the core approval workflow; students can see their proposal status and review history on My Proposals, and can edit/resubmit proposals marked Needs changes.

The Event Registrations shortcode shows participant name, PRN, email, event, team, status, and registration time, with filters and pagination. Access is role and department scoped. The public Clubs archive and club detail templates live in the EventNest theme at `/clubs/` and `/clubs/{club}/`.


Students can apply to join a club from its public detail page. Only that club's assigned faculty in-charge or club head, plus administrators, can review pending applications. Students see application decisions on the club page and their My Clubs page.

Students can edit the note on a **pending** club application from the club page. My Clubs retains a history of submissions, edits, resubmissions, and reviewer decisions; reviewers can see the submitted note history before deciding. Approved or rejected requests are read-only; students may submit a new application after rejection if applications remain open.

In WordPress Admin, use **Events → Competitions** to see only competitions or create a competition draft with the Competition type selected. Competitions remain event records, so existing public URLs and registration records stay intact. The standard **Events** list excludes competitions.

Students may edit and resubmit a proposal only after a reviewer selects **Needs changes**. My Proposals shows review history and saved submitted versions, including photos. A proposal being reviewed, approved, rejected, or published cannot be edited by the student.

Student Stories are public entries at `/student-stories/`. Students submit stories from **Share Your Story**; submissions stay pending until an administrator reviews and publishes them under **Student Stories** in WordPress Admin. **My Stories** lets each student check pending and published submissions. Story author name, course, year, event, rating, written experience, favourite moment and learning are shown publicly after approval. Student stories and event proposals accept an optional JPG, PNG, GIF, or WebP photo up to 5 MB. Proposal photos carry over to the event after final approval. The Share Your Story and My Stories pages are created automatically; add **Student Stories** to any custom navigation menu.
