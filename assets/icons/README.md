# Interface icons

The interface uses Phosphor's regular outline icons, selected and retrieved through the Supericons plugin. `interface.svg` contains only the icons used by the application and is served locally; no runtime icon CDN or font is required.

Use an existing symbol with an accessible text label on its parent control:

```html
<button aria-label="Search">
  <svg class="ui-icon" viewBox="0 0 256 256" aria-hidden="true" focusable="false">
    <use href="../assets/icons/interface.svg?v=20260914-icons1#magnifying-glass"/>
  </svg>
</button>
```

Paths above are relative to teacher/student pages. Root pages use `assets/icons/`. The symbols explicitly set their fill and stroke so they work within existing colored icon containers. Keep the brand mark and database-configured anatomy content separate from interface symbols.

When adding an icon, retrieve its SVG from Supericons, add a named symbol, and preserve its original geometry and 256 × 256 view box. Advance the sprite's version in callers when updating it.

Source: https://github.com/phosphor-icons/core
License: MIT, reproduced in `LICENSE`.
