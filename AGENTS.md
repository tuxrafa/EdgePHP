# EdgePHP CMS — Agent Notes

Read `docs/architecture.md` and `docs/compatibility.md` before changing EDS behavior.

## Hard rules

- Do not modify `scripts/aem.js` or vendor block JS/CSS under `blocks/**` to “fix” CMS issues.
- Do not rewrite blocks as React or PHP.
- PHP emits EDS *input* markup; browser runtime decorates.
- Never mark compatibility PASS without a test.
- Prefer vertical slices; keep Pest green before advancing.

## Slice order

See product plan in `docs/architecture.md`. Slice 1 = Columns golden path.
