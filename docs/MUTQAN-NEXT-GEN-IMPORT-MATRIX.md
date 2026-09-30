# MUTQAN Next-Gen Import Matrix — 2026-09-30

## Source
The uploaded `mutaqin(1).zip` is treated as a feature/reference export, not as a replacement WordPress application. Its README states that the export depends on a hosted backend and is not standalone; therefore MUTQAN keeps WordPress + PHP + MySQL + REST API as the production architecture.

## Adopted feature groups

### Client
- Home / Services / Offers / Store / My Orders / Account / Calculator / More
- New Order and order detail
- Warranty list, warranty detail/claim
- Contact
- Registration
- Customer AI conversation

### Staff / Operations
- Staff gate and dashboard
- Operations room
- Order detail
- Technician radar
- Handoff detail
- Technician requests / technician join
- Notifications
- Pricing rules
- Payment methods
- Warranty management
- AI settings / AI command center / Owner AI control
- WhatsApp Center

### Shared components
- Order cards
- AI order cards
- status badges/timelines
- service tree picker
- warranty claim form
- service banners
- ratings
- permission help
- team login/admin
- map view
- charts
- floating contact

### System behaviors to preserve
- location permission flow
- assignment and dispatch
- pricing rules
- warranty lookup
- notifications
- payment abstraction
- AI adapter
- WhatsApp number management
- live/realtime hooks
- security/rate limiting
- auditability

## WordPress implementation rule
The export is not copied as a React/Whacka runtime. Its feature structure is mapped into the existing MUTQAN Units and WordPress REST boundaries. No hosted-backend credentials or frontend secrets are imported.

## Responsive design contract
All shared MUTQAN screens use one design system:
- RTL Arabic first.
- Desktop operations shell.
- Tablet layout with reduced sidebar and 3-column KPI grid.
- Phone layout with drawer navigation, 2-column KPIs, stacked panels, 44px touch targets and horizontal table scrolling where tabular density requires it.
- Safe-area support for mobile browsers.
- Reduced-motion support.
- Material Symbols icons with semantic labels instead of text glyph icons.

## WhatsApp contract
The new WordPress data layer supports:
- unlimited logical WhatsApp account records
- Business and personal account types
- official API / provider / QR connector modes
- account status and QR status
- contacts
- conversations
- messages
- notifications
- routing rules
- audit log
- customer/order linkage

A QR image is never fabricated. A real QR requires a configured connector/provider. Until then the UI reports `SETUP REQUIRED`.

## AI contract
- Server-side Gemini readiness is reflected in the control center.
- No API key is exposed to JavaScript.
- Voice features remain connector/setup-dependent unless a real speech provider is configured.
- AI must use real MUTQAN service/pricing/order/warranty data and must not invent availability, prices, warranties or integrations.
