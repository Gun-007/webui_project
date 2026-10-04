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
| `my-registrations` | `[eventnest_my_registrations]` |
| `submit-proposal` | `[eventnest_submit_proposal]` |
| `my-proposals` | `[eventnest_my_proposals]` |
| `review-proposals` | `[eventnest_review_proposals]` |

Student registration requires a unique PRN, college email, mobile, name and a password of at least 10 characters. Students can sign in with username, email or PRN. Faculty registration creates a low-privilege Subscriber account marked as pending; an administrator must verify the person and assign the appropriate EventNest role in Users before they receive faculty or club permissions. Never let a public registration form choose an elevated role.

Administrators can review pending staff signups under **Users → Faculty Requests** and approve each as Faculty or Club Head. Approval assigns the role and clears the pending state.

Faculty, Club Heads, Deputy Directors, Directors and Administrators with the corresponding review capability can review submissions on the `review-proposals` page. Decisions are recorded in the proposal approval history; rejecting or requesting changes requires a reviewer comment.

These forms use WordPress accounts and nonces. Passwords are stored through WordPress's password API; EventNest stores the additional profile fields as user metadata. Email verification and automated account approval are not included yet.

## Student participation pages

The event detail page uses EventNest's internal registration system when this plugin is active. Registrations are limited to signed-in accounts with the `en_register_event` capability. The My Registrations page shows only the current user's records and permits cancellation before the event date. Team-registration data functions exist, but the student-facing team form is a later enhancement. Proposal submissions use the core approval workflow; students can see their proposal status and review history on My Proposals, and can edit/resubmit proposals marked Needs changes.
