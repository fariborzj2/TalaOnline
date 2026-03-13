/**
 * BaseEditor - Lightweight, Extensible, Production-Ready Rich Text Editor
 * Vanilla JS Modular Class
 */

class BaseEditor {
    constructor(config) {
        this.config = Object.assign({
            el: null,
            minLength: 0,
            maxLength: Infinity,
            toolbar: ['bold', 'italic', 'blockquote', 'ul', 'ol', 'clear'],
            autosave: false,
            autosaveKey: 'editor-draft',
            preview: true,
            placeholder: 'دیدگاه خود را اینجا بنویسید...',
            onSubmit: null,
            onChange: null,
            onFocus: null,
            onBlur: null,
            onValidateFail: null
        }, config);

        this.el = typeof this.config.el === 'string' ? document.querySelector(this.config.el) : this.config.el;
        if (!this.el) {
            console.error('BaseEditor: Target element not found');
            return;
        }

        this.state = {
            isDisabled: false,
            isPreviewMode: false
        };

        this.init();
    }

    init() {
        // Create container
        this.container = document.createElement('div');
        this.container.className = 'base-editor-container';

        // Hide original element (usually a textarea)
        this.el.style.display = 'none';
        this.el.parentNode.insertBefore(this.container, this.el.nextSibling);

        this.renderToolbar();
        this.renderContentArea();
        this.renderFooter();

        // Load initial content from original element or autosave
        let initialContent = this.el.value || this.el.innerHTML || '';
        if (this.config.autosave) {
            const saved = localStorage.getItem(this.config.autosaveKey);
            if (saved) initialContent = saved;
        }

        if (initialContent) {
            this.setContent(initialContent);
        }

        this.bindEvents();
        this.updateCharCount();

        // Force paragraphs on Enter
        document.execCommand('defaultParagraphSeparator', false, 'p');

        // Ensure initial structure is paragraph-based
        this.ensureParagraphStructure();

        // Initialize icons if lucide is available
        if (window.lucide) {
            window.lucide.createIcons({ root: this.container });
        }
    }

    renderToolbar() {
        this.toolbar = document.createElement('div');
        this.toolbar.className = 'base-editor-toolbar';

        const buttons = {
            bold: { icon: 'bold', command: 'bold', title: 'ضخیم (Ctrl+B)' },
            italic: { icon: 'italic', command: 'italic', title: 'کج (Ctrl+I)' },
            blockquote: { icon: 'quote', command: 'formatBlock', value: 'blockquote', title: 'نقل‌قول' },
            ul: { icon: 'list', command: 'insertUnorderedList', title: 'لیست نشانه‌دار' },
            ol: { icon: 'list-ordered', command: 'insertOrderedList', title: 'لیست شماره‌دار' },
            clear: { icon: 'remove-formatting', command: 'removeFormat', title: 'پاکسازی فرمت' }
        };

        this.config.toolbar.forEach(item => {
            if (buttons[item]) {
                const btn = this.createToolbarBtn(buttons[item]);
                this.toolbar.appendChild(btn);
            }
        });

        if (this.config.preview) {
            const divider = document.createElement('div');
            divider.className = 'base-editor-divider';
            this.toolbar.appendChild(divider);

            const previewBtn = document.createElement('button');
            previewBtn.type = 'button';
            previewBtn.className = 'base-editor-btn preview-toggle';
            previewBtn.dataset.title = 'پیش‌نمایش';
            previewBtn.innerHTML = '<i data-lucide="eye"></i>';
            previewBtn.onclick = (e) => {
                e.preventDefault();
                this.togglePreview();
            };
            this.toolbar.appendChild(previewBtn);
        }

        this.container.appendChild(this.toolbar);
    }

    createToolbarBtn(btnConfig) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'base-editor-btn';
        btn.dataset.command = btnConfig.command;
        if (btnConfig.value) btn.dataset.value = btnConfig.value;
        btn.dataset.title = btnConfig.title;
        btn.innerHTML = `<i data-lucide="${btnConfig.icon}"></i>`;

        btn.onclick = (e) => {
            e.preventDefault();
            if (this.state.isDisabled || this.state.isPreviewMode) return;

            this.editor.focus();
            if (btnConfig.value) {
                document.execCommand(btnConfig.command, false, btnConfig.value);

                // If we applied blockquote, ensure there's a paragraph after it if it's the last element
                if (btnConfig.value === 'blockquote') {
                    const selection = window.getSelection();
                    if (selection.rangeCount > 0) {
                        const container = selection.getRangeAt(0).commonAncestorContainer;
                        const blockquote = container.nodeType === 1 ? container.closest('blockquote') : container.parentElement.closest('blockquote');
                        if (blockquote && !blockquote.nextSibling) {
                            const p = document.createElement('p');
                            p.innerHTML = '<br>';
                            blockquote.parentNode.appendChild(p);
                        }
                    }
                }
            } else {
                document.execCommand(btnConfig.command, false, null);
            }
            this.updateActiveState();
            this.handleInput();
        };
        return btn;
    }

    renderContentArea() {
        this.editor = document.createElement('div');
        this.editor.className = 'base-editor-content';
        this.editor.contentEditable = true;
        this.editor.setAttribute('placeholder', this.config.placeholder);
        this.editor.setAttribute('spellcheck', 'false');

        this.previewArea = document.createElement('div');
        this.previewArea.className = 'base-editor-preview';

        this.container.appendChild(this.editor);
        this.container.appendChild(this.previewArea);
    }

    renderFooter() {
        this.footer = document.createElement('div');
        this.footer.className = 'base-editor-footer';

        this.charCounter = document.createElement('div');
        this.charCounter.className = 'base-editor-char-counter';

        this.footer.appendChild(this.charCounter);
        this.container.appendChild(this.footer);
    }

    bindEvents() {
        this.editor.oninput = () => this.handleInput();

        this.editor.onfocus = () => {
            this.container.classList.add('focused');
            if (this.config.onFocus) this.config.onFocus();
        };

        this.editor.onblur = () => {
            this.container.classList.remove('focused');
            if (this.config.onBlur) this.config.onBlur();
        };

        this.editor.onkeydown = (e) => this.handleKeydown(e);
        this.editor.onmouseup = () => this.updateActiveState();
        this.editor.onkeyup = () => this.updateActiveState();

        // Drag & Drop prevention (minimal implementation, can be extended)
        this.editor.ondrop = (e) => {
            e.preventDefault();
            return false;
        };
    }

    handleInput() {
        const content = this.getContent();
        this.el.value = content; // Keep original element in sync
        this.updateCharCount();

        if (this.config.onChange) this.config.onChange(content);

        if (this.config.autosave) {
            localStorage.setItem(this.config.autosaveKey, content);
        }
    }

    ensureParagraphStructure() {
        if (!this.editor.innerHTML.trim() || this.editor.innerHTML === '<br>') {
            this.editor.innerHTML = '<p><br></p>';
            return;
        }

        let changed = false;

        // 1. Convert DIVs to Ps
        this.editor.querySelectorAll('div').forEach(div => {
            const p = document.createElement('p');
            while (div.firstChild) p.appendChild(div.firstChild);
            div.parentNode.replaceChild(p, div);
            changed = true;
        });

        // 2. Wrap orphans
        const blockTags = ['P', 'BLOCKQUOTE', 'UL', 'OL'];
        let nodes = Array.from(this.editor.childNodes);
        let hasOrphans = nodes.some(n => n.nodeType === 3 && n.textContent.trim() || (n.nodeType === 1 && !blockTags.includes(n.tagName)));

        if (hasOrphans) {
            const fragment = document.createDocumentFragment();
            let currentP = null;

            nodes.forEach(node => {
                const isBlock = node.nodeType === 1 && blockTags.includes(node.tagName);
                if (isBlock) {
                    fragment.appendChild(node);
                    currentP = null;
                } else {
                    if (!currentP) {
                        currentP = document.createElement('p');
                        fragment.appendChild(currentP);
                    }
                    currentP.appendChild(node);
                }
            });
            this.editor.innerHTML = '';
            this.editor.appendChild(fragment);
            changed = true;
        }

        // 3. Fix nesting
        let hasNested = true;
        let nestingIterations = 0;
        while (hasNested && nestingIterations < 100) {
            hasNested = false;
            nestingIterations++;
            this.editor.querySelectorAll('p ul, p ol, p blockquote').forEach(nested => {
                const p = nested.closest('p');
                if (p && p.contains(nested)) {
                    const parent = p.parentNode;
                    const nextSibling = p.nextSibling;

                    // Split p at nested
                    const range = document.createRange();
                    range.setStartAfter(nested);
                    range.setEndAfter(p.lastChild);
                    const afterContent = range.extractContents();

                    parent.insertBefore(nested, nextSibling);

                    if (afterContent.textContent.trim() || afterContent.querySelector('br')) {
                        const nextP = document.createElement('p');
                        nextP.appendChild(afterContent);
                        parent.insertBefore(nextP, nested.nextSibling);
                    }

                    if (!p.textContent.trim() && !p.querySelector('br')) {
                        p.remove();
                    }
                    changed = true;
                    hasNested = true;
                }
            });
        }

        // 4. Ensure blockquote children are paragraphs
        this.editor.querySelectorAll('blockquote').forEach(bq => {
            let bqNodes = Array.from(bq.childNodes);
            let needsWrap = bqNodes.some(n => n.nodeType === 3 && n.textContent.trim() || (n.nodeType === 1 && !['P', 'UL', 'OL'].includes(n.tagName)));
            if (needsWrap) {
                const frag = document.createDocumentFragment();
                let curP = null;
                bqNodes.forEach(node => {
                    if (node.nodeType === 1 && ['P', 'UL', 'OL'].includes(node.tagName)) {
                        frag.appendChild(node);
                        curP = null;
                    } else {
                        if (!curP) {
                            curP = document.createElement('p');
                            frag.appendChild(curP);
                        }
                        curP.appendChild(node);
                    }
                });
                bq.innerHTML = '';
                bq.appendChild(frag);
                changed = true;
            }
        });

        if (changed) {
            this.updateActiveState();
        }
    }

    handleKeydown(e) {
        if (e.key === 'Enter') {
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                const container = range.commonAncestorContainer;
                const blockquote = container.nodeType === 1 ? container.closest('blockquote') : container.parentElement.closest('blockquote');

                if (blockquote) {
                    // Find the paragraph we are currently in
                    let p = range.startContainer;
                    while (p && p !== blockquote && p.tagName !== 'P') {
                        p = p.parentNode;
                    }

                    if (p && p.tagName === 'P' && p.innerText.trim() === '') {
                        // Pressed Enter on empty line in blockquote -> Break out
                        e.preventDefault();

                        const nextP = document.createElement('p');
                        nextP.innerHTML = '<br>';

                        // Split blockquote
                        const parent = blockquote.parentNode;
                        const brange = document.createRange();
                        brange.setStartAfter(p);
                        brange.setEndAfter(blockquote.lastChild);
                        const afterContent = brange.extractContents();

                        parent.insertBefore(nextP, blockquote.nextSibling);

                        // If there was content after the empty P, put it in a new blockquote
                        if (afterContent.textContent.trim() || afterContent.querySelector('p')) {
                            const nextBQ = document.createElement('blockquote');
                            nextBQ.appendChild(afterContent);
                            parent.insertBefore(nextBQ, nextP.nextSibling);
                        }

                        // Remove the empty P from original blockquote
                        p.remove();

                        // If blockquote is now empty, remove it
                        if (!blockquote.textContent.trim() && !blockquote.querySelector('p')) {
                            blockquote.remove();
                        }

                        // Move cursor to new paragraph
                        const newRange = document.createRange();
                        newRange.setStart(nextP, 0);
                        newRange.collapse(true);
                        selection.removeAllRanges();
                        selection.addRange(newRange);

                        this.handleInput();
                        return;
                    }
                }
            }
        }

        if (e.ctrlKey || e.metaKey) {
            if (e.key === 'b' || e.key === 'B') {
                e.preventDefault();
                document.execCommand('bold', false, null);
            } else if (e.key === 'i' || e.key === 'I') {
                e.preventDefault();
                document.execCommand('italic', false, null);
            } else if (e.key === 'Enter' && this.config.onSubmit) {
                e.preventDefault();
                if (this.validate()) this.config.onSubmit(this.getContent());
            }
        }
        this.updateActiveState();
    }

    updateActiveState() {
        const commands = ['bold', 'italic', 'insertUnorderedList', 'insertOrderedList'];
        this.container.querySelectorAll('.base-editor-btn').forEach(btn => {
            const cmd = btn.dataset.command;
            if (commands.includes(cmd)) {
                if (document.queryCommandState(cmd)) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            }
            if (cmd === 'formatBlock' && btn.dataset.value === 'blockquote') {
                const val = document.queryCommandValue('formatBlock');
                if (val === 'blockquote') {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            }
        });
    }

    updateCharCount() {
        const text = this.editor.innerText.trim();
        const count = text.length;

        let countText = `${this.toPersianDigits(count)} کاراکتر`;

        this.charCounter.classList.remove('limit-near', 'limit-exceeded');
        if (this.config.maxLength !== Infinity) {
            countText += ` / ${this.toPersianDigits(this.config.maxLength)}`;
            if (count > this.config.maxLength) {
                this.charCounter.classList.add('limit-exceeded');
            } else if (count > this.config.maxLength * 0.9) {
                this.charCounter.classList.add('limit-near');
            }
        }

        this.charCounter.innerText = countText;
    }

    togglePreview() {
        this.state.isPreviewMode = !this.state.isPreviewMode;
        const btn = this.container.querySelector('.preview-toggle');

        if (this.state.isPreviewMode) {
            this.container.classList.add('preview-mode');
            this.previewArea.innerHTML = this.getContent();
            btn.innerHTML = '<i data-lucide="edit-2"></i>';
            btn.dataset.title = 'ویرایش';
        } else {
            this.container.classList.remove('preview-mode');
            btn.innerHTML = '<i data-lucide="eye"></i>';
            btn.dataset.title = 'پیش‌نمایش';
            this.editor.focus();
        }

        if (window.lucide) {
            window.lucide.createIcons({ root: btn });
        }
    }

    toPersianDigits(num) {
        if (num === null || num === undefined) return '';
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return num.toString().replace(/\d/g, x => persian[x]);
    }

    // Public API Implementation
    getContent() {
        this.ensureParagraphStructure();
        return this.sanitize(this.editor.innerHTML);
    }

    setContent(html) {
        this.editor.innerHTML = this.sanitize(html);
        this.handleInput();
    }

    clear() {
        this.editor.innerHTML = '';
        this.handleInput();
        if (this.config.autosave) {
            localStorage.removeItem(this.config.autosaveKey);
        }
    }

    destroy() {
        this.container.remove();
        this.el.style.display = '';
    }

    validate() {
        const text = this.editor.innerText.trim();
        const length = text.length;

        let valid = true;
        let errors = [];

        if (length < this.config.minLength) {
            valid = false;
            errors.push(`حداقل تعداد کاراکتر مجاز ${this.config.minLength} است.`);
        }
        if (length > this.config.maxLength) {
            valid = false;
            errors.push(`حداکثر تعداد کاراکتر مجاز ${this.config.maxLength} است.`);
        }

        if (!valid && this.config.onValidateFail) {
            this.config.onValidateFail(errors);
        }

        return valid;
    }

    enable() {
        this.state.isDisabled = false;
        this.container.classList.remove('disabled');
        this.editor.contentEditable = true;
    }

    disable() {
        this.state.isDisabled = true;
        this.container.classList.add('disabled');
        this.editor.contentEditable = false;
    }

    sanitize(html) {
        if (!html) return '';

        // Use a temporary div for DOM-based sanitization
        const temp = document.createElement('div');
        temp.innerHTML = html;

        const allowedTags = ['p', 'strong', 'em', 'b', 'i', 'blockquote', 'ul', 'ol', 'li', 'br'];

        const sanitizeNode = (node) => {
            const children = Array.from(node.childNodes);
            children.forEach(child => {
                if (child.nodeType === 1) { // Element Node
                    const tagName = child.tagName.toLowerCase();

                    if (!allowedTags.includes(tagName)) {
                        if (['script', 'style', 'iframe', 'object', 'embed'].includes(tagName)) {
                            child.remove();
                        } else {
                            // Replace unallowed tag with its children (unwrapping)
                            while (child.firstChild) {
                                child.parentNode.insertBefore(child.firstChild, child);
                            }
                            child.remove();
                        }
                    } else {
                        // Strip all attributes for security and clean output
                        while (child.attributes.length > 0) {
                            child.removeAttribute(child.attributes[0].name);
                        }
                        // Recurse into allowed elements
                        sanitizeNode(child);
                    }
                }
            });
        };

        sanitizeNode(temp);

        // Final cleanup of extra whitespace or empty tags if necessary
        return temp.innerHTML.trim();
    }
}

// Attach to window for global access
window.BaseEditor = BaseEditor;
