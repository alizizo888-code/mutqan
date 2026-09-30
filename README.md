# MUTQAN — Unified Maintenance & Operations System

MUTQAN is a unified WordPress platform for maintenance, field operations, customers, technicians, finance, marketing, quality, audit, and AI-assisted operations.

## Units 1–6

### Unit 01 — Users, Roles & HR
- Customers, technicians, supervisors, owners, partners
- Roles and granular capabilities
- Registration and approval workflows
- Technician documents and profile
- HR foundation

### Unit 02 — Operations & Logistics
- Central operations room
- Dispatch and reassignment
- Technician capacity
- GPS and live tracking
- Maps and route foundation
- Fleet and mobile workshops
- Inventory
- Suppliers and stores
- Emergency protocol

### Unit 03 — Services & Customer Relations
- Customers
- Service catalog and packages
- Customer assets
- Tickets and complaints
- Ratings and quality
- Warranty and after-service
- CRM

### Unit 04 — Finance & Sales
- Orders
- Pricing engine
- Invoices and payments
- Wallets, settlements and advances
- B2B quotations and contracts
- Accounting
- Expenses and purchasing
- Commissions
- Financial dashboards
- Financial audit and integrations

### Unit 05 — Marketing & Loyalty
- Offers and discounts
- Coupons
- Loyalty points
- Campaigns
- Referrals
- Segmentation
- Multichannel notifications
- Campaign analytics
- AI marketing
- Permissions and audit

### Unit 06 — Quality, Security & Advanced Systems
- Gold warranty and quality monitoring
- Field checklists
- Advanced notifications
- Central audit trail
- Analytics and BI
- Dynamic extensions
- Gemini AI
- Security and data protection
- Central settings
- Final integration testing

## Engineering Rules
1. One unified core — no disconnected module copies.
2. Server-side authorization for every sensitive action.
3. Granular WordPress capabilities.
4. Audit trail for sensitive changes.
5. REST endpoints protected by authorization and appropriate authentication/nonces.
6. External integrations are explicitly marked as requiring setup.
7. AI must never receive plaintext passwords.
8. Production credentials belong in configuration/secrets, never source control.
9. Every incomplete external dependency gets a visible setup/readiness status and an explanatory action.
10. Final release must be tested as one integrated system.

## Status
The presentation layer is maintained in the repository under `src/UI` and `src/Theme`; WordPress remains the runtime and data store. The main branch deploys only the MUTQAN plugin path.


## MUTQAN 0.7.0 / Next-Gen UI
The current WordPress implementation remains the production source of truth. The uploaded feature export is mapped into the existing PHP/MySQL/REST architecture; it is not treated as a standalone React runtime.

### New in this update
- Responsive Stitch shell for desktop, tablet and phone.
- Accessible Material Symbols navigation and touch targets.
- Mobile drawer navigation with safe-area support.
- AI readiness control based on real server-side configuration.
- Connector-neutral multi-account WhatsApp data layer.
- WhatsApp accounts, contacts, conversations, messages, notifications, routing and audit tables.
- Unified WhatsApp command-center UI with customer → account → conversation → order mapping.
- Explicit SETUP REQUIRED state for unconfigured external providers and QR connectors.
- No fabricated QR codes, credentials or operational records.

See `docs/MUTQAN-NEXT-GEN-IMPORT-MATRIX.md` for the feature mapping from the uploaded export.
