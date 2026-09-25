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
Implementation starts from this repository and will be consolidated into a single production-ready MUTQAN plugin/package.
