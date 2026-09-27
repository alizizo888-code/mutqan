# STITCH Master UI Reference

Source archive: `stitch_ (1).zip`
This archive is the authoritative UI/reference source for the rebuild.

## Primary six administrative screens

- `stitch_/_11/code.html` — operations overview / live field monitoring
- `stitch_/_12/code.html` — technician network / provider performance
- `stitch_/_13/code.html` — inventory / spare parts
- `stitch_/_14/code.html` — item details / digital tracking
- `stitch_/_15/code.html` — vehicle custody / field inventory
- `stitch_/_16/code.html` — field operations room / dispatch

## Archive inventory
- HTML screens: 90
- Image assets: 114
- Uncompressed size: 36.21 MiB

## Rebuild rules
1. Preserve MUTQAN functional ideas and backend data model.
2. Start the operational system with customer, technician, and registration records empty.
3. Treat the uploaded ZIP as the authoritative visual/reference source before every UI implementation change.
4. Never present static reference/demo metrics as live database facts.
5. Reuse the reference screens, imagery, icons, terminology, and information hierarchy, adapting them to real MUTQAN data.
6. Use one shared application shell and reusable components; do not create disconnected page copies.
