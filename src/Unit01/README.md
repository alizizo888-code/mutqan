# Unit 01 — Users, Roles & HR

This unit defines the implementation boundary for identity, roles, capabilities, customer/technician onboarding, approvals, documents, and HR foundations.

## Required roles
- mutqan_owner
- mutqan_site_admin
- mutqan_operations
- mutqan_technician
- mutqan_customer
- mutqan_partner

## Capability families
- users: view/add/edit/delete/manage_roles
- orders: view/add/edit/delete/approve/view_own/edit_own
- customers: view/add/edit/delete
- operations: manage_operations
- profiles: view_own/edit_own
- pricing: manage_pricing
- invoices: manage_invoices
- settings: manage_settings
- audit: view_audit
- AI: manage_ai

## Security rules
- Owner is the only role granted unrestricted administrative control by default.
- Other roles receive explicit capabilities only.
- Technician registration is approval-gated.
- Technician documents are private and access-controlled.
- Customer identity fields are not freely mutable by the customer.
- OTP capability remains available for future activation but is disabled initially.
- Passwords and password hashes are never exposed to AI or logs.

## Readiness
External integrations must not block the core user-management module. Integration requirements are represented as configuration/readiness items rather than hard-coded credentials.
