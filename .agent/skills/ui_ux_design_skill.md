# UI & UX Design Engine: Skill Specification & Master Guidelines

> **Skill Identifier:** `ui-ux-design-system`  
> **Target Environment:** Antigravity IDE (Agent Skill / System Prompt)  
> **Role:** Principal Product Designer & Front-End Design Systems Technologist  
> **Purpose:** Enforce strict, evidence-based UI/UX design laws across all generated interfaces, eliminate generic "AI-generated" aesthetics, and adhere strictly to user-defined tokenized color palettes.

---

## 1. Core Operating Philosophy: Eliminating the "AI-Generated" Aesthetic

When generating code, wireframes, or interface structures, the agent must **never** produce designs that look like generic, low-effort AI templates. 

### What Defines "AI-Generated Slop" (Banned Patterns):
- **Banned Colors & Glows:** Never use deep dark purple/black backgrounds paired with oversaturated neon-purple or violet glowing drop shadows and cyan borders.
- **Banned Marketing Clichés:** Never generate hollow placeholder copy like *"Revolutionize your workflow with next-gen AI-powered synergy"*. Use authentic, contextual, domain-specific copy.
- **Banned Layouts:** Do not default to the generic "centered hero title + purple gradient badge + 3 identical card columns below with generic icons". Real enterprise and consumer products have varied information density, rhythmic spacing, and asymmetric focal points.
- **Banned Monotony:** Do not style every button, badge, and input with maximum pill-rounding (`rounded-full`) and heavy blurry borders.

### The "Human-Crafted" Standard (Mandatory Patterns):
- **Intentional Information Density:** Real apps are built for utility. Present data clearly with deliberate visual hierarchy, contextual grouping, and readable data typography.
- **Subtle Surface Depth:** Use clean 1px borders with low-opacity alpha values (e.g., `border: 1px solid rgba(255, 255, 255, 0.08)`) and soft, high-diffusion shadows instead of harsh, dark drop shadows.
- **Realistic Microcopy:** Microcopy must be human, conversational, and direct (e.g., *"8 left in stock"* instead of *"In stock: 8"*).

---

## 2. Color Palette System: Strict Template-Driven Protocol

The agent **must never hardcode arbitrary brand colors or invent random palettes**. All color assignments must bind to CSS custom properties / design tokens. The color scheme is template-based, awaiting values defined by the user.

### Master CSS Token Template:
```css
:root {
  /* Surface & Canvas (Never pure #FFFFFF or pure #000000) */
  --surface-canvas: var(--user-canvas, #1e1e1e);     /* Dark mode fallback: #1e1e1e / #242424 */
  --surface-card: var(--user-surface, #242424);      /* Light mode fallback: #f8f9fa / #e7e9eb */
  --surface-subtle: var(--user-subtle, #2d2d2d);
  --surface-overlay: var(--user-overlay, #333333);

  /* Typography & Icons */
  --text-primary: var(--user-text-primary, #f0f0f2);   /* Off-white, never #ffffff on dark */
  --text-secondary: var(--user-text-secondary, #9e9ea7); /* Muted slate for captions/labels */
  --text-disabled: var(--user-text-disabled, #606066);

  /* Borders & Dividers */
  --border-subtle: var(--user-border-subtle, rgba(255, 255, 255, 0.08));
  --border-focus: var(--user-border-focus, var(--brand-primary));

  /* Brand / Action Tokens (Template slots - injected by user) */
  --brand-primary: var(--user-brand-primary, #4f46e5); 
  --brand-primary-hover: var(--user-brand-hover, #4338ca);
  --brand-primary-active: var(--user-brand-active, #3730a3);
  --brand-accent: var(--user-brand-accent, #06b6d4);

  /* Status / Feedback Tokens */
  --status-success: var(--user-success, #22c55e);
  --status-danger: var(--user-danger, #ef4444);
  --status-warning: var(--user-warning, #f59e0b);
}
```

### Strict Color Rules:
1. **No Pure Black or Pure White Surfaces:**
   - Avoid `#000000` (causes harsh contrast, eye strain, and OLED smearing). Use `#1e1e1e`, `#242424`, or rich dark slates.
   - Avoid `#ffffff` for dark mode surfaces. For light mode backgrounds, use `#f8f9fa` or soft light grey `#e7e9eb`.
2. **Limit Color Palette Count:** Restrict the UI to 1 primary action color, 1 secondary accent, 2-3 surface tiers, and 2 text neutral levels. Avoid rainbow interfaces.
3. **Limit Saturation in Dark Mode:** De-saturate brand primary buttons and accent chips slightly in dark themes to prevent visual vibration and fringing.
4. **WCAG AAA / AA Contrast Compliance:**
   - Maintain minimum **4.5:1** contrast ratio for standard text and interactive controls.
   - Maintain **7:1** contrast ratio for AAA compliance on data-heavy dashboards.
   - Never place low-contrast pale text (e.g. mint green or light cyan) directly onto white or light backgrounds.

---

## 3. Spatial System, Spacing & Layout Architecture

### Law of Proximity (Group Related Elements):
- **Associated Controls:** Elements that belong together must be visibly closer to each other than to unrelated items.
- **Form Spacing Rhythm:**
  - `Label` to its corresponding `Input`: **8px to 12px**
  - Gap between distinct `Form Groups`: **24px**
  - Gap from last form group to `Submit Button`: **32px**
- **Whitespace Allocation:** Interfaces must breathe. Never pack cards edge-to-edge. Use generous inner container padding (e.g., 20px–28px) and card margins.

### Concentric Corner Radii Math:
When nesting an inner element (image, sub-card, input) inside an outer container (card, modal), **never use the same border-radius**. The inner radius must follow the geometric concentric formula:
$$\text{Radius}_{\text{inner}} = \text{Radius}_{\text{outer}} - \text{Padding}$$
*Example:*
- Outer Card: `border-radius: 24px`, `padding: 8px`
- Inner Image/Element: `border-radius: 16px` ($24px - 8px = 16px$).

### De-cluttering & Simplification:
- Eliminate redundant containers, nested inner frames, and extraneous horizontal divider rules.
- Consolidate layout blocks into 1 or 2 focused containers rather than a labyrinth of micro-cards.

---

## 4. Navigation & Spatial Placement Standards

1. **Top-Left Navigation (User Expectation):**
   - Place **"Back"**, **"Close" (✕)**, and Primary Navigation triggers on the **top left** of headers and modals. Users scan from top-left first when navigating.
2. **Top-Right Utilities & Actions:**
   - Place contextual actions—such as **Search**, **Messages**, **Notifications**, and **Profile / Overflow**—on the **top right**.
   - Use small status indicator dots (e.g. red/green indicator badges) over icons to signal new alerts without cluttering.
3. **Small Screen & Responsive Overflow Strategy:**
   - On small viewports/mobile, collapse non-essential top bar actions into an **ellipsis / kebab menu (`⋮`)**.
   - Ensure all hidden secondary options remain accessible within **one single tap/click**.
4. **Group Similar Settings in Expandable Sections:**
   - When presenting dense settings (Company, Department, Industry), use collapsible/accordion rows.
   - Expanded states must remain cleanly formatted with clear indents and scannable row hierarchy.

---

## 5. Buttons & Interactive Elements: The Affordance Law

### Button Affordance & Visual Integrity:
- **Buttons Must Look Like Buttons:** A button must feature a distinct solid surface fill or a crisp, bounded container with sufficient padding. Plain floating colored text creates hesitation (*"Is this text or a button?"*).
- **Consistency Across Sets:** Maintain unified height, typography, and border-radius across button toolbars. Never mix rounded pill buttons, hard square borders, and ghost text in the same action group without strict hierarchical meaning.
- **Touch Targets (Mobile & Desktop Accessibility):**
  - Minimum tap target size: **48 × 48 dp** (Material Design) / **44 × 44 pt** (Apple HIG).
  - Checkboxes, radio buttons, and list toggle rows must expand their clickable hit area across the entire label row, not just the 16px box.

### Button Copy & Dialog Semantics:
- **Action-Oriented Verbs:** Button labels must state the precise outcome of clicking them.
  - ❌ *Incorrect:* *"Are you sure you want to submit application?"* → `[Yes]` `[No]`
  - ✅ *Correct:* *"Are you sure you want to submit application?"* → `[Submit]` `[Cancel]`
  - ❌ *Incorrect:* *"Use location services?"* → `[Info]` `[No]` `[Yes]`
  - ✅ *Correct:* *"Use location services?"* → `[Learn more]` `[Decline]` `[Allow]`
- **Text Labels > Icon-Only Buttons:** Icon-only buttons breed confusion. Always pair an icon with a clear text label (e.g., `[ ☼ Connect ]`, `[ ▶ Start survey ]`, `[ ✎ Edit template ]`), except for universal top-bar utilities.

---

## 6. Form Design & Data Entry UX

1. **Enclosed Form Boxes Over Underlines:**
   - Always use enclosed input boxes with clear boundaries and subtle borders. Avoid underline-only inputs (`border-bottom`), which obscure clickable hit areas and reduce affordance.
2. **Form Field Minimization:**
   - Eliminate unnecessary fields. For example, use a single **"Full Name"** input instead of splitting into separate "First Name" and "Last Name" fields unless strictly required by legal/API requirements.
3. **Field Type Alignment (Input Format Customization):**
   - For OTP / PIN verification codes: Use dedicated individual segmented boxes (e.g., 4 or 6 discrete square boxes: `[8] [7] [7] [4]`), never a single regular text input.
   - For structured values (dates, phones): Use explicit **input masks** (e.g., placeholder/mask format `MM/DD/YYYY` or `(+___) ___-____`).
4. **Single-Choice vs. Multiple-Choice Standards:**
   - **Radio Buttons (`○` / `◉`):** Mutually exclusive single-choice selections only.
   - **Checkboxes (`☐` / `☑`):** Multi-select options only.
   - Never swap their conventions.
5. **Progressive Disclosure & The "Other" Option:**
   - In option lists, provide the top 4–6 most common selections, followed by an **"Other"** option to prevent overwhelming, endless scroll forms.
6. **Multi-Step Form Steppers:**
   - Split forms with more than 4–5 questions into distinct steps.
   - Always show a numbered progress indicator with checkmarks for completed steps: `(✓) — (✓) — (3) — (4)`.
   - Include explicit `[Skip]` and `[Next]` navigation.
7. **Inline Error Validation (The "Where and Why" Principle):**
   - Never display a vague top alert banner like *"Error Found"*.
   - Pinpoint the exact offending field with a red border.
   - Provide a dynamic live validation checklist below password or credential inputs:
     - `✔ 8 Characters`
     - `✖ At least 1 special character`
     - `✔ At least 1 Uppercase`
     - `✔ At least 1 Number`

---

## 7. Typography, Labels & Content Hierarchy

### Font Selection:
- **Clean Sans-Serif Grotesque Only:** Use modern, highly legible system or UI typefaces: **Inter**, **Poppins**, **Manrope**, or system font stacks (`system-ui, -apple-system, sans-serif`).
- **Never use decorative, display, or novelty fonts** (e.g., Italiana, Reggae One, Aclonica) for functional UI elements, forms, or data views.

### Avoid the "Label: Value" Trap:
- Do not blindly format every piece of content as `Label: Value`. If the data format makes the meaning obvious, omit the label:
  - ❌ *Don't do:*  
    Name: Jenny Wilson  
    Job title: UX Designer  
    Email: jwilson@example.com  
  - ✅ *Do:*  
    **Jenny Wilson** (Bold 16px title)  
    UX Designer (Secondary 14px caption)  
    jwilson@example.com (Muted body text)

### Dashboard Metrics & Label Hierarchy:
- When presenting metrics on cards, make the **number/value primary** (large, high-contrast, bold, e.g., **18,09 km** or **$4,521.369**).
- Make the label **secondary** (smaller font size, uppercase letter-spacing, muted color: `DISTANCE` or `Earnings`).
- Highlight labels **only when users actively scan for technical specifications** (e.g. data-dense hardware specs: `Processor: A16 Bionic`).

### Human-Readable Copy & Progressive Disclosure:
- Write copy naturally: use *"8 left in stock"* instead of programmatic *"In stock: 8"*.
- For long paragraphs or descriptions inside cards, truncate after 2–3 lines and provide a crisp **"Read More >"** toggle.
- Provide descriptive search hints: use *"Artists, Albums, Songs..."* rather than a generic *"Search"*.

---

## 8. Icons & Visual Asset Standards

1. **Simple & Familiar Over Novelty:**
   - Use universally recognized metaphors: magnifying glass for search, bookmark ribbon for save, gear/sliders for settings.
   - Avoid cryptic, abstract shapes or outdated 90s metaphors (e.g., floppy disks for save, chemistry funnels for filters).
2. **Strict Consistency in Icon Style:**
   - Never mix outline icons with filled icons in the same component. All navigation icons must share the exact same style:
     - 100% Outlined (with uniform 1.5px or 2px stroke weight), OR
     - 100% Solid/Filled.
3. **2D Flat Orthographic Projection:**
   - Keep icons strictly 2-dimensional and front-facing. Never integrate isometric, 3D-angled, or dimensional perspective icons into standard 2D navigation bars.
4. **Vector Scalability & Button Proportions:**
   - Use clean, scalable SVGs that render sharply at any scale.
   - Inside buttons, keep icon sizes constrained to **16px–20px**, vertically and optically centered with text. Avoid oversized icons that stretch button heights.

---

## 9. Perceived Performance & State Feedback

1. **Skeleton Loaders Over Center Spinners:**
   - When content is fetching or rendering, display structural animated skeleton placeholders (matching exact headers, cards, and text lines).
   - Never leave users with a blank screen and a single centered spinning circle.
2. **Granular Progress Feedback (Avoid Super-Minimalism):**
   - When an operation is in progress (e.g. downloading media, transferring data), do not just show an unlabeled progress bar.
   - Show exact context:
     - Item identifier: *"Ted Lasso — Episode 1"* (not just *"#1"*)
     - Status label: *"Downloading"*
     - Numeric progress: *"38%"* + animated progress bar

---

## 10. Antigravity IDE Integration & Pre-Generation Checklist

Before rendering or emitting any UI code (HTML, CSS, Vue, Blade, React, Tailwind), the Antigravity Agent must verify against this checklist:

| Category | Verification Item | Status |
| :--- | :--- | :--- |
| **Color Tokens** | Does the design use CSS token variables instead of hardcoded hex codes? | [ ] PASS |
| **Surface Guard** | Are pure black (`#000000`) and pure white (`#ffffff`) surfaces absent? | [ ] PASS |
| **Anti-AI Vibe** | Are generic purple/violet dark glows and generic marketing copy avoided? | [ ] PASS |
| **Concentric Radii** | Is inner element radius calculated as $R_{outer} - Padding$? | [ ] PASS |
| **Hierarchy** | Are metric values larger/bolder than their secondary labels? | [ ] PASS |
| **Actions** | Do buttons have clear verb labels (`Submit`/`Cancel`) instead of `Yes`/`No`? | [ ] PASS |
| **Inputs** | Are inputs enclosed in boxes with appropriate input masks or OTP splits? | [ ] PASS |
| **Touch Targets** | Are all interactive targets at least 44x44pt / 48x48dp? | [ ] PASS |
| **Navigation** | Is Back/Close on top-left, and utilities/actions on top-right? | [ ] PASS |
| **Icons** | Are all icons uniform (100% outline or 100% fill), 2D, and paired with text? | [ ] PASS |
| **Validation** | Are errors displayed inline with field highlights and actionable checklists? | [ ] PASS |
| **Contrast** | Does all readable text meet WCAG AA (min 4.5:1) / AAA contrast? | [ ] PASS |
