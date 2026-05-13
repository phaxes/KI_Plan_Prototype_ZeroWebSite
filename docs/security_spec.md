# Security Specification

## Data Invariants
1. A **Post** cannot be created without a valid `authorId` matching the current user.
2. A **Post**'s `createdAt` must be the server time.
3. A **Product** can only be modified by an **Admin**.
4. A **User Profile** cannot be elevation to `isAdmin: true` by the user themselves.
5. **Newsletter Subscriptions** are public for creation but private for reading/admin use.

## The Dirty Dozen (Attacker Payloads)

| # | Collection | Description | Expected Result |
| :--- | :--- | :--- | :--- |
| 1 | `posts` | Create post as unauthenticated. | PERMISSION_DENIED |
| 2 | `posts` | Create post with `authorId` of another user. | PERMISSION_DENIED |
| 3 | `posts` | Create post with client-provided `createdAt` (not server time). | PERMISSION_DENIED |
| 4 | `posts` | Update someone else's post as a non-admin. | PERMISSION_DENIED |
| 5 | `products` | Create a product as a non-admin user. | PERMISSION_DENIED |
| 6 | `users` | Update own profile to `isAdmin: true`. | PERMISSION_DENIED |
| 7 | `users` | Read someone else's private profile data. | PERMISSION_DENIED |
| 8 | `subscribers` | Read all subscribers as an unauthenticated user. | PERMISSION_DENIED |
| 9 | `posts` | Inject 1MB string into `title`. | PERMISSION_DENIED |
| 10 | `posts` | Shadow update: Add `isVerified: true` field during update. | PERMISSION_DENIED |
| 11 | `admins` | Self-add to `admins` collection. | PERMISSION_DENIED |
| 12 | `users` | Modify `email` field during update (immutable if PII-locked). | PERMISSION_DENIED |

## Technical Implementation Notes
- Rule version: `2`.
- All updates use `affectedKeys().hasOnly()`.
- Validation is centralized in `isValidEntity` helpers.
