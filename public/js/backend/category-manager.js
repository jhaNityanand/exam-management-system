class CategoryManager {
    constructor(containerId, formId) {
        this.container = document.getElementById(containerId);
        this.form = document.getElementById(formId);
        this.counter = 0;
        this.levelColours = ['#4f46e5', '#0f766e', '#d97706', '#dc2626', '#7c3aed', '#2563eb'];

        this.init();
    }

    init() {
        if (!this.container || !this.form) {
            return;
        }

        this.container.innerHTML = '';
        this.renderInitialTree();
        this.bindEvents();
    }

    bindEvents() {
        this.container.addEventListener('click', (event) => {
            const addButton = event.target.closest('.add-child-btn');
            const removeButton = event.target.closest('.remove-node-btn');

            if (addButton) {
                this.addChildNode(addButton.closest('.category-node'));
            }

            if (removeButton) {
                this.removeNode(removeButton.closest('.category-node'));
            }
        });

        this.container.addEventListener('input', (event) => {
            if (event.target.matches('.category-node__textarea')) {
                this.autosizeTextarea(event.target);
            }

            if (event.target.matches('.category-node__input')) {
                this.clearFieldError(event.target);
            }
        });

        this.form.addEventListener('submit', (event) => {
            if (!this.validateForm()) {
                event.preventDefault();
            }
        });
    }

    renderInitialTree() {
        const rootNode = this.createNode(0);
        this.container.appendChild(rootNode);
        this.reindexTree();
        this.recalculateSiblingState(this.container);
    }

    getColour(level) {
        return this.levelColours[level] || this.levelColours[this.levelColours.length - 1];
    }

    createNode(level) {
        const id = `node-${this.counter++}`;
        const node = document.createElement('div');

        node.className = `category-node${level > 0 ? ' category-node--nested' : ''} is-entering`;
        node.dataset.nodeId = id;
        node.dataset.level = String(level);
        node.style.setProperty('--tree-color', this.getColour(level));

        node.innerHTML = `
            <div class="category-node__card">
                <div class="category-node__level">
                    <span class="category-node__badge">${this.getLevelLabel(level)}</span>
                </div>

                <div class="category-node__field category-node__name">
                    <label class="category-node__label" for="category-name-${id}">
                        Name <span class="category-node__required">*</span>
                    </label>
                    <input
                        id="category-name-${id}"
                        type="text"
                        name="categories[${id}][name]"
                        class="category-node__input"
                        placeholder="${level === 0 ? 'Enter category name' : 'Enter child category name'}"
                    >
                    <p class="category-node__error" aria-live="polite">Name is required.</p>
                </div>

                <div class="category-node__field category-node__description">
                    <label class="category-node__label" for="category-description-${id}">Description</label>
                    <textarea
                        id="category-description-${id}"
                        rows="2"
                        name="categories[${id}][description]"
                        class="category-node__textarea"
                        placeholder="Add a short description"
                    ></textarea>
                </div>

                <div class="category-node__actions">
                    <button type="button" class="category-node__btn add-child-btn" aria-label="Add child category" title="Add child">+</button>
                    ${level > 0 ? '<button type="button" class="category-node__icon remove-node-btn" aria-label="Delete category" title="Remove category">X</button>' : ''}
                </div>
            </div>
            <div class="category-node__children"></div>
        `;

        requestAnimationFrame(() => node.classList.add('is-visible'));
        this.autosizeTextarea(node.querySelector('.category-node__textarea'));

        return node;
    }

    addChildNode(parentNode) {
        if (!parentNode) {
            return;
        }

        const childLevel = Number(parentNode.dataset.level) + 1;
        const childrenContainer = parentNode.querySelector(':scope > .category-node__children');
        const childNode = this.createNode(childLevel);

        childrenContainer.style.setProperty('--tree-color', this.getColour(childLevel));
        childrenContainer.appendChild(childNode);

        this.reindexTree();
        this.recalculateSiblingState(childrenContainer);
        childNode.querySelector('.category-node__input')?.focus();
    }

    removeNode(node) {
        if (!node || Number(node.dataset.level) === 0) {
            return;
        }

        const siblingContainer = node.parentElement;
        node.remove();
        this.reindexTree();
        this.recalculateSiblingState(siblingContainer);
    }

    recalculateSiblingState(container) {
        if (!container) {
            return;
        }

        const siblings = Array.from(container.children).filter((child) => child.classList.contains('category-node'));

        siblings.forEach((sibling, index) => {
            sibling.classList.toggle('is-last', index === siblings.length - 1);

            const nestedChildren = sibling.querySelector(':scope > .category-node__children');
            if (nestedChildren) {
                nestedChildren.style.setProperty('--tree-color', this.getColour(Number(sibling.dataset.level) + 1));
                this.recalculateSiblingState(nestedChildren);
            }
        });
    }

    reindexTree() {
        const nodes = Array.from(this.container.querySelectorAll('.category-node'));

        nodes.forEach((node, index) => {
            const level = Number(node.dataset.level);
            const badge = node.querySelector('.category-node__badge');
            const input = node.querySelector('.category-node__input');
            const textarea = node.querySelector('.category-node__textarea');

            badge.textContent = this.getLevelLabel(level);

            input.name = `categories[node-${index}][name]`;
            textarea.name = `categories[node-${index}][description]`;
            input.id = `category-name-node-${index}`;
            textarea.id = `category-description-node-${index}`;

            const inputLabel = node.querySelector('.category-node__name .category-node__label');
            const descriptionLabel = node.querySelector('.category-node__description .category-node__label');
            inputLabel.setAttribute('for', input.id);
            descriptionLabel.setAttribute('for', textarea.id);

            node.style.setProperty('--tree-color', this.getColour(level));
        });
    }

    validateForm() {
        let isValid = true;
        const nameInputs = this.container.querySelectorAll('.category-node__input');

        nameInputs.forEach((input) => {
            if (!input.value.trim()) {
                this.showFieldError(input);
                isValid = false;
            } else {
                this.clearFieldError(input);
            }
        });

        if (!isValid) {
            this.container.querySelector('.category-node__input.is-invalid')?.focus();
        }

        return isValid;
    }

    showFieldError(input) {
        input.classList.add('is-invalid');
        input.closest('.category-node__field')?.querySelector('.category-node__error')?.classList.add('is-visible');
    }

    clearFieldError(input) {
        input.classList.remove('is-invalid');
        input.closest('.category-node__field')?.querySelector('.category-node__error')?.classList.remove('is-visible');
    }

    getLevelLabel(level) {
        return `L${level}`;
    }

    autosizeTextarea(textarea) {
        if (!textarea) {
            return;
        }

        textarea.style.height = 'auto';
        textarea.style.height = `${Math.max(textarea.scrollHeight, 62)}px`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new CategoryManager('tree-container', 'category-tree-form');
});
