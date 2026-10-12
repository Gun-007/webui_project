# EventNest profile and administration workflow plan

This document records the profile and administration workflow now implemented in EventNest.

## Student profile

- EventNest creates a **My Profile** page at `/my-profile/` with `[eventnest_my_profile]` if it is missing.
- Signed-in students can update full name, college email, mobile number, course, and batch/year. The email must be valid and unique.
- PRN is displayed as read-only because it is the student account's login and registration identifier. Admin correction remains possible.
- The student dashboard links to My Profile. Other roles cannot use the edit form.

## Recommended operating model

| Role | Main responsibility | Admin/workflow permissions |
| --- | --- | --- |
| Student | Discover and participate | Browse events and competitions, register, manage own profile, submit proposals and stories |
| Faculty | Faculty-level event support | Create/manage authorized events, view assigned registrations, review proposals |
| Faculty Head | Department oversight | Review faculty-stage proposals, manage department events, and view department registration reports |
| Club Head | Run an assigned club | Review that club's join applications and proposals; manage club-owned activities |
| Deputy Director | Institute-level supervision | Second-stage approval and institute-wide oversight |
| Director | Final authority | Final/override approval and governance review |
| Administrator | Platform operations | Manage users, role assignments, club assignments, settings, moderation, and audit records |

The current code has Faculty, Faculty Head, Club Head, Deputy Director, Director, and WordPress Administrator roles. Administrators create departments under **Users → Departments** and assign one while approving a faculty request or later from the WordPress user editor. Faculty Heads need an assigned department before they can manage/review department records. Deputy Directors and Directors retain institute-wide review/report access.

## Account lifecycle and deactivation

1. A public student account is active as soon as registration succeeds. A faculty request remains pending until an administrator verifies it and assigns a staff role.
2. Add an account state with `active`, `pending`, and `disabled` values. Keep disabled accounts and their content for audit/history; do not delete the WordPress user.
3. **Users → EventNest Accounts** provides **Disable** and **Reactivate** actions. Both require a reason and a nonce-protected administrator action. Changes are recorded in the EventNest audit log.
4. On disable, block new logins, revoke existing sessions, and preserve registrations, proposals, stories, authored events, club memberships, and review history.
5. On reactivation, restore access without changing the user's role, registrations, or content. A role/assignment review can be required before restoring staff access.
6. Show account state and the last action in Users and provide filters for active, pending, and disabled accounts.

## Club-head removal and succession

Club Head authority is both a WordPress role and a per-club assignment. Removing a person from one club must remove that club assignment without unintentionally removing authority over another club.

1. **Clubs → Club Leadership** provides **Change Club Head** and **Mark vacant** actions, restricted to administrators.
2. Require selection of a successor or explicitly mark the club **Vacant**. Ask for a reason and confirm the change.
3. Update the club's assigned head immediately. Pending applications and club-specific proposal review permission follow the current assignment automatically; the successor can access the queue and the previous head loses access for that club.
4. Preserve club membership, registrations, published content, and historical authorship. The former head loses access to that club's review queue and club-specific actions.
5. If the former head is assigned to no other clubs, remove only the Club Head role and preserve any other roles. Do not delete the account or rewrite historical author names.
6. Record old head, new head, actor, reason, and timestamp in the audit log. Show the current head, assignment date, and vacancy state on the club admin screen.

### Student changes to club join applications

- While an application is **Pending**, let its owner edit the application note from **My Clubs** or the club page. Keep the original submission time and record each edit in the application history so reviewers can see what changed.
- When a reviewer makes a decision, freeze that application. Do not let a student edit an approved or rejected record and thereby change what the reviewer decided.
- If rejected, let the student submit a new application when the club is accepting applications. Show the prior decision and reviewer note to the student; keep it in history rather than overwriting it.
- An approved application becomes membership. Membership exit or removal is a separate action with its own reason and history, not another application edit.

## Competition separation in WordPress Admin

### Recommended first release: separate admin workspace, shared event records

The **Events → Competitions** admin workspace shows only competitions and provides **Add Competition**, which creates a competition-tagged draft with Intra-College scope selected. Competition records are hidden from the regular Events list. They remain event records, preserving URLs, registrations, proposals, and event dates. Non-competition campus activities stay under **Events**.

The public Competitions page remains its own listing. When a proposal is finally approved as a competition, create the resulting draft in the competition workspace and retain its proposal history and featured image.

If the college later requires competitions to have a genuinely separate record type and capabilities, plan that as a second step: inventory records; migrate records, taxonomies, metadata, images, registrations, and links; provide redirects; verify counts and ownership; then remove the old type only after a tested rollback point. Do not start by splitting the database model.

## Student edits to event proposals

- **Under review:** keep the submitted revision read-only so the student cannot change details while reviewers are considering them. If cancellation is needed, add a separate withdraw action before a decision and record it in history.
- **Needs changes:** allow the owner to edit and resubmit. Preserve the reviewer comment and the prior version, append the new revision to review history, and restart the required approval stage.
- **Approved or rejected:** make the proposal read-only. A student who wants to change an approved or rejected idea submits a new proposal, linked to the earlier record if useful.
- **Published event:** event details are edited through the authorized Event editor workflow; changing the proposal should not silently change the published event.

## Implemented workflow controls

1. Student profile editing is available from the student dashboard; PRN remains read-only.
2. Administrators can disable/reactivate non-admin accounts with reasons; existing records and user data are retained and active sessions are revoked.
3. Administrators can appoint or remove Club Heads per club. Vacancies and successors are supported, multi-club assignments are respected, and changes are audited.
4. Applicants can edit a pending club-join note. Each submission, edit, resubmission, and review decision is retained in application history; decided applications cannot be edited.
5. Students can revise proposals after **Needs changes**. Each submitted version is retained, including its photo, and prior versions and review decisions appear in My Proposals.
6. **Events → Competitions** provides the separate filtered admin workspace over the shared event record model.
7. Existing approval stages are retained. Faculty Head is a distinct role. Department assignments scope Faculty Head and assigned Faculty event management, proposal review, and registration reports. Club events inherit their club's department; direct events can be assigned in the event editor. Faculty Heads can edit their own announcements, not site-wide announcements.

## Departments and scope

1. Create each department under **Users → Departments**. Use one record per department.
2. Assign Faculty and Faculty Head accounts to a department from **Users → Faculty Requests** during approval, or **Users → Edit User → EventNest department** later.
3. Assign each club to its owning department in the Club editor. Its Faculty in-charge must belong to the same department. Existing events and proposals linked to the club inherit the department when the club assignment is saved.
4. Assign direct events from the Event editor's Department box. Proposals linked to a department-owned club inherit that department; proposals without such a club include a department choice.
5. Faculty Heads only see/edit events and proposals for their assigned department and their faculty-stage review queue is limited to proposals for that department. Faculty assigned to a department receive the same department boundary; unassigned Faculty retain legacy event-edit access but only see their own event registrations.
6. Faculty Heads can review join applications for clubs in their department; the assigned Faculty in-charge and Club Head also review their club's applications. Registration reports scope Faculty Heads and assigned Faculty to their department, Club Heads to their assigned clubs, and Deputy Directors/Directors/Administrators to institute-wide records. Do not add a global report cap to a new role without defining its scope.
7. Department assignment is administrative metadata, not a public visibility filter. Published events remain publicly discoverable.

Proposal editing is part of the current workflow foundation: students can revise proposals after a **Needs changes** decision, and each resubmission is added to review history. Preserve that behavior. The workflow enhancement should make the decision state clear in **My Proposals** and follow the state rules above.

## Operational verification still recommended

- Exercise student, faculty, club-head, deputy, director, and administrator journeys on the local WordPress site.
- Verify disabled accounts cannot log in, club reassignment changes review access, and existing event registrations remain linked.
- Review account and club audit records after sample actions.
- No application-level database migration is required beyond the plugin's automatic dbDelta schema update.
- Starter sample clubs/events are opt-in under **Events → Starter Demo Content**; normal site visits no longer create sample records.
