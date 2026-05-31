# Question Bank Accordion Component

## Overview

The **Question Bank Accordion** is a reusable, lightweight JavaScript component that displays questions in a collapsible category-wise accordion format. It's designed for the Exam Module's "Question Bank Management" section.

## Features

✅ **Category-wise Accordion Display** - Questions grouped and collapsed by category
✅ **Dynamic Filtering** - Questions load based on selected categories (up to N categories)
✅ **Smooth Animations** - Expand/collapse with smooth CSS transitions
✅ **Question Count Display** - Shows number of questions per category in the header
✅ **Search Functionality** - Filter questions within accordion by keyword
✅ **Accessibility** - Full keyboard navigation and ARIA attributes
✅ **Responsive Design** - Works on mobile, tablet, and desktop
✅ **Static JSON Data** - No backend required, loads from static JSON files
✅ **Reusable Component** - Can be instantiated multiple times with different containers
✅ **Clean UI** - Consistent with exam management system design language

## File Structure

```
public/
├── js/
│   ├── components/
│   │   └── question-bank-accordion.js      # Main component class
│   └── backend/
│       └── question-bank-init.js           # Integration & initialization
├── css/
│   └── question-bank-accordion.css         # Styling & animations
└── data/exam-create/
    ├── question-bank.json                  # Static questions data
    └── categories.json                     # Category hierarchy
resources/views/backend/exams/
└── create.blade.php                        # Blade template (updated)
```

## Quick Start

### 1. HTML Structure

The component expects this basic HTML structure:

```html
<div id="question-bank-container" data-question-bank>
    <div class="question-bank-toolbar">
        <label class="question-search">
            <span>Search Questions</span>
            <input type="search" data-question-search-input placeholder="Search by keyword">
        </label>
    </div>
    
    <div data-question-bank-feedback></div>
    <div data-question-category-cards></div>
</div>
```

### 2. Include Required Files

```html
<!-- Styles -->
<link rel="stylesheet" href="css/question-bank-accordion.css">

<!-- Scripts (in order) -->
<script src="js/components/question-bank-accordion.js"></script>
<script src="js/backend/question-bank-init.js"></script>
```

### 3. Initialize

```javascript
const container = document.getElementById('question-bank-container');
const accordion = new QuestionBankAccordion(container, {
    questionBankEndpoint: 'data/exam-create/question-bank.json',
    categoriesEndpoint: 'data/exam-create/categories.json',
    animationDuration: 300
});
```

## Component API

### Constructor Options

```javascript
new QuestionBankAccordion(container, {
    questionBankEndpoint: 'data/exam-create/question-bank.json',  // Required
    categoriesEndpoint: 'data/exam-create/categories.json',       // Required
    animationDuration: 300,                                        // Optional (ms)
    onCategoryToggle: (categoryId, isExpanded) => {}              // Optional callback
});
```

### Public Methods

#### `setSelectedCategories(categoryIds)`
Set which categories to display questions from.

```javascript
accordion.setSelectedCategories(['javascript', 'react', 'laravel']);
```

#### `searchQuestions(keyword)`
Filter questions by keyword within currently displayed categories.

```javascript
accordion.searchQuestions('json');
```

#### `toggleAll(expand)`
Expand or collapse all categories at once.

```javascript
accordion.toggleAll(true);   // Expand all
accordion.toggleAll(false);  // Collapse all
```

#### `getState()`
Get current accordion state.

```javascript
const state = accordion.getState();
```

#### `exportData()`
Export questions for form submission.

```javascript
const data = accordion.exportData();
```

#### `getLeafCategories()`
Get all leaf-level (non-hierarchical) categories.

```javascript
const leafCats = accordion.getLeafCategories();
```

## Data Format

### Questions JSON Structure

```json
[
  {
    "id": 1001,
    "categoryId": "javascript",
    "marks": 2,
    "difficulty": "easy",
    "text": "Which method converts a JSON string into a JavaScript object?"
  }
]
```

### Categories JSON Structure (Hierarchical)

```json
[
  {
    "id": "computer_science",
    "name": "Computer Science",
    "availableQuestions": 105,
    "children": [
      {
        "id": "javascript",
        "name": "JavaScript",
        "availableQuestions": 12,
        "children": []
      }
    ]
  }
]
```

## Integration with Exam Module

The component integrates seamlessly with the exam creation form:

1. **Automatic Category Sync** - Updates when user selects categories
2. **Form Data Export** - Exports selected questions on form submit
3. **Public API** - Access via `window.QuestionBankAPI`

## Testing

Test file available at: `/public/test-question-bank.html`

Click buttons to test:
- Select 3 Categories
- Select All
- Expand/Collapse All
- Search functionality
- Reset

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers
