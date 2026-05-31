# Question Bank Accordion - Visual Guide

## UI Layout

```
┌─────────────────────────────────────────────────────────────┐
│  6. Question Bank Management                                │
│  Track availability by category...                          │
├─────────────────────────────────────────────────────────────┤
│  ┌──────────────────────────────────────────────────────┐   │
│  │ 🔍 Search Questions: [_________________]  [+ Add Q]  │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  ✓ Successfully loaded question bank (50 questions)         │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ ▶ JavaScript (3 questions)                          │    │  Category Collapsed
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ ▼ React (5 questions)                               │    │  Category Expanded
│  ├─────────────────────────────────────────────────────┤    │
│  │ Which method converts a JSON string to object?      │    │
│  │   [easy]  [2 marks]                                 │    │
│  │                                                      │    │
│  │ Explain event delegation and performance.           │    │
│  │   [medium]  [4 marks]                               │    │
│  │                                                      │    │
│  │ Design a debounce utility function.                 │    │
│  │   [hard]  [5 marks]                                 │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ ▶ Laravel (8 questions)                             │    │  Category Collapsed
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
┌──────────────────────────┐
│   User Selects           │
│   Categories from        │
│   Main Form              │
└────────────┬─────────────┘
             │
             ▼
┌──────────────────────────┐
│  Accordion Listener      │
│  Detects Change          │
└────────────┬─────────────┘
             │
             ▼
┌──────────────────────────────────────────┐
│  setSelectedCategories(categoryIds)      │
│  - Update internal state                 │
│  - Filter questions                      │
└────────────┬─────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────┐
│  filterQuestions()                       │
│  - Match categoryId with selected ids    │
│  - Create filteredQuestions array        │
└────────────┬─────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────┐
│  renderAccordion()                       │
│  - Get leaf categories                   │
│  - Build HTML for each category          │
│  - Insert into DOM                       │
└────────────┬─────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────┐
│  UI Displays Questions                   │
│  Grouped by Category                     │
└──────────────────────────────────────────┘
```

## State Management

```
accordion.state = {
  allQuestions: [
    { id: 1001, categoryId: 'javascript', marks: 2, ... },
    { id: 1002, categoryId: 'javascript', marks: 4, ... },
    { id: 1101, categoryId: 'laravel', marks: 3, ... },
    ...
  ],
  
  allCategories: [
    { id: 'javascript', name: 'JavaScript', level: 1, ... },
    { id: 'react', name: 'React', level: 1, ... },
    { id: 'laravel', name: 'Laravel', level: 1, ... },
    ...
  ],
  
  selectedCategories: Set ['javascript', 'react'],
  
  expandedCategories: Set ['react'],
  
  filteredQuestions: [
    { id: 1001, categoryId: 'javascript', ... },
    { id: 1002, categoryId: 'javascript', ... },
    { id: 1201, categoryId: 'react', ... },
    { id: 1202, categoryId: 'react', ... },
    { id: 1203, categoryId: 'react', ... },
  ]
}
```

## Accordion Item Structure

```
┌─────────────────────────────────────────────────┐
│ .question-category-card                         │
├─────────────────────────────────────────────────┤
│ .question-category-head [role="button"]         │
│  ┌──────────────────────────────────────────┐   │
│  │ .question-category-toggle                │   │
│  │  [▶] or [▼] (rotates on expand)          │   │
│  │                                          │   │
│  │ .question-category-title                 │   │
│  │  JavaScript                              │   │
│  │ .question-category-count                 │   │
│  │  3 questions                             │   │
│  └──────────────────────────────────────────┘   │
│                                                 │
│ .question-category-body (hidden when collapsed) │
│  ┌──────────────────────────────────────────┐   │
│  │ .question-snippet-list                   │   │
│  │  ┌────────────────────────────────────┐  │   │
│  │  │ .question-item                     │  │   │
│  │  │  .question-text                    │  │   │
│  │  │   Which method converts...         │  │   │
│  │  │  .question-meta                    │  │   │
│  │  │   [easy] [2 marks]                 │  │   │
│  │  └────────────────────────────────────┘  │   │
│  │  ┌────────────────────────────────────┐  │   │
│  │  │ .question-item                     │  │   │
│  │  │  ... (more questions)              │  │   │
│  │  └────────────────────────────────────┘  │   │
│  └──────────────────────────────────────────┘   │
└─────────────────────────────────────────────────┘
```

## Interaction Flows

### Flow 1: Expanding a Category

```
User clicks category header
       │
       ▼
handleAccordionToggle() is called
       │
       ▼
expandCategory(categoryId)
       │
       ▼
state.expandedCategories.add(categoryId)
       │
       ▼
updateCategoryUI(categoryId, true)
       │
       ├─ Add 'is-expanded' class
       ├─ Set aria-expanded="true"
       ├─ CSS transition shows .question-category-body
       │
       ▼
Animation: max-height 0 → 2000px (300ms)
       │
       ▼
Questions fade in and are visible
```

### Flow 2: Searching Questions

```
User types in search input
       │
       ▼
searchQuestions(keyword) called
       │
       ▼
Filter filteredQuestions by keyword
  - Question text includes keyword
  - Case-insensitive match
       │
       ▼
renderAccordion() (with filtered questions)
       │
       ▼
Accordion updates to show only matching questions
       │
       ▼
Category counts update
       │
       ▼
User sees filtered results
```

### Flow 3: Selecting Categories from Main Form

```
User selects categories in main form
       │
       ▼
'selected_categories' hidden input changes
       │
       ▼
MutationObserver detects change
       │
       ▼
updateAccordionWithSelectedCategories() called
       │
       ▼
Parse selected category IDs
       │
       ▼
accordion.setSelectedCategories(ids)
       │
       ├─ Update selectedCategories Set
       ├─ Call filterQuestions()
       ├─ Call renderAccordion()
       │
       ▼
Accordion re-renders with new questions
```

## CSS Animation Sequence

### Expand Animation

```
Step 1: User clicks (0ms)
  .question-category-card
    max-height: 0
    opacity: 0
    padding: 0

         │
         ▼ (Add 'is-expanded' class)
         
Step 2-3: Animation (0-300ms)
  .question-category-card.is-expanded
    .question-category-body {
      transition: max-height 300ms cubic-bezier(...),
                  padding 300ms cubic-bezier(...),
                  opacity 300ms ease;
      
      max-height: 0 → 2000px
      opacity: 0 → 1
      padding: 0 → 1rem
    }

         │
         ▼ (At 300ms)

Step 4: Complete (300ms+)
  Questions fully visible
  Content displayed normally
```

## Keyboard Navigation

```
TAB → Move to next category header
      (Focus outline visible)

SHIFT+TAB → Move to previous category header

SPACE or ENTER → Toggle expand/collapse

ARROW KEYS in search → Navigate text

ARROW DOWN/UP → Navigate items (if implemented)
```

## Search Badge Colors

```
Easy Questions    [easy]        Green background
Medium Questions  [medium]      Orange/Amber background  
Hard Questions    [hard]        Red background

Marks             [X marks]     Blue background
```

## Responsive Breakpoints

```
Desktop (> 1200px)
  ┌─────────────────────────────────────┐
  │ [▶] Category Title (Count)          │
  │                                     │
  │ [Question text here...]             │
  │ [easy] [2 marks]                    │
  └─────────────────────────────────────┘

Tablet (768px - 1199px)
  ┌────────────────────────┐
  │ [▶] Category (Count)   │
  │                        │
  │ [Question text...]     │
  │ [easy] [2 marks]       │
  └────────────────────────┘

Mobile (< 768px)
  ┌──────────────────┐
  │ [▶] Cat. (Count) │
  │                  │
  │ [Question...]    │
  │ [easy] [2 m.]    │
  └──────────────────┘
```

## Component Lifecycle

```
┌─────────────────┐
│ new Accordion() │ ← Constructor
└────────┬────────┘
         │
         ▼
┌──────────────────────┐
│ initialize()         │ ← Setup
│ ├─ loadData()       │
│ ├─ bindEvents()     │
│ └─ renderAccordion()│ (initial, empty)
└────────┬─────────────┘
         │
         ▼
┌──────────────────────────────┐
│ User selects categories      │
│ accordion.setSelected...()   │
│ ├─ filterQuestions()        │
│ ├─ renderAccordion()        │
│ └─ UI updates              │
└────────┬─────────────────────┘
         │
         ▼
┌──────────────────────────────┐
│ User interacts               │
│ ├─ Click to expand          │
│ ├─ Search questions         │
│ ├─ Keyboard navigation      │
│ └─ State updates            │
└────────┬─────────────────────┘
         │
         ▼
┌──────────────────────────────┐
│ Form submission              │
│ accordion.exportData()       │
│ ├─ Get selected questions   │
│ ├─ Return structured data   │
│ └─ Form submits             │
└──────────────────────────────┘
```

## Event Flow

```
User Action          Event Handler        Component Method   UI Update
─────────────────────────────────────────────────────────────────────

Click category  →  click event  →  handleAccordionToggle()  →  expand/collapse
                                        │
                                        ├─ expandCategory()
                                        │  or
                                        └─ collapseCategory()
                                              │
                                              ▼
                                        updateCategoryUI()  →  Add/remove class


Type in search  →  input event  →  searchQuestions()  →  re-render filtered


Select category →  MutationObserver  →  setSelectedCategories()  →  full re-render
```

## Example Usage Timeline

```
Page Load (t=0ms)
├─ HTML loads
├─ CSS loads
├─ JavaScript loads
└─ accordion = new QuestionBankAccordion(...)

Data Loading (t=50-100ms)
├─ Fetch question-bank.json
├─ Fetch categories.json
├─ Parse data
└─ Store in state

Page Ready (t=100-200ms)
├─ Render empty accordion
├─ Show "Select categories to view questions"
└─ Wait for user

User Selects Categories (t=500ms)
├─ Category selection changes in main form
├─ MutationObserver detects change
├─ Accordion updates
└─ Questions display (animation: 300ms)

User Clicks to Expand (t=1000ms)
├─ Category expands (animation: 300ms)
└─ Questions become visible

User Searches (t=2000ms)
├─ Filter questions
└─ Accordion updates instantly
```

This visual guide shows how the Question Bank Accordion component works, how data flows through it, and how users interact with it!
