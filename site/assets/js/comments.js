/**
 * Advanced Comment System Component - Refactored for Performance & Maintainability
 */

class CommentSystem {
    constructor(options) {
        this.containerId = options.containerId || 'comments-app';
        this.container = document.getElementById(this.containerId);
        if (!this.container) return;

        this.targetId = options.targetId || this.container.dataset.targetId;
        this.targetType = options.targetType || this.container.dataset.targetType;
        this.isLoggedIn = window.__AUTH_STATE__?.isLoggedIn || false;
        this.currentUsername = window.__AUTH_STATE__?.user?.username;
        this.csrfToken = window.__AUTH_STATE__?.csrfToken;

        // Highly resilient initial data lookup
        let initialData = null;
        if (window.__COMMENTS_INITIAL_DATA__) {
            const tid = this.targetId ? this.targetId.toString() : '';
            const keys = Object.keys(window.__COMMENTS_INITIAL_DATA__);

            // 1. Direct match
            initialData = window.__COMMENTS_INITIAL_DATA__[`${this.targetType}_${tid}`];

            // 2. Numeric match
            if (!initialData && tid && !isNaN(parseInt(tid))) {
                initialData = window.__COMMENTS_INITIAL_DATA__[`${this.targetType}_${parseInt(tid)}`];
            }

            // 3. Fuzzy match (any key that contains both type and ID)
            if (!initialData && this.targetType && tid) {
                const fuzzyKey = keys.find(k => k.includes(this.targetType) && k.includes(tid));
                if (fuzzyKey) initialData = window.__COMMENTS_INITIAL_DATA__[fuzzyKey];
            }
        }

        const ds = this.container.dataset;
        const perPage = parseInt(ds.perPage) || (this.targetType === 'user_profile' ? 10 : 20);
        this.comments = options.initialComments || initialData?.comments || [];
        this.totalCount = parseInt(initialData?.total_count || ds.totalCount || 0) || 0;

        // If it's the initial load from DOM dataset, we need to make sure we only use top-level count
        // (Server-side already does this, but we're defensive here)
        this.totalPages = parseInt(initialData?.total_pages || ds.totalPages || 0) || Math.ceil(this.totalCount / perPage) || 1;
        this.currentPage = parseInt(initialData?.current_page || ds.currentPage || 1) || 1;

        this.readOnly = options.readOnly || (this.targetType === 'user_profile');
        this.guestCommentEnabled = ds.guestComment === '1';
        this.filterType = 'all';
        this.sort = 'newest';
        this.threadModalId = 'comment-thread-modal';
        this.isInsideModal = false;
        this.isLoading = false;
        this.editors = {};

        window.commentSystem = this;

        this.init();

        // Final fallback trigger to ensure infinite scroll is initialized correctly
        setTimeout(() => {
            if (!this.isLoading && this.currentPage < this.totalPages) {
                this.updateSentinelVisibility();
                this.checkSentinelVisibility();
            }
        }, 1000);

        document.addEventListener('auth:status-changed', (e) => {
            const state = e.detail;
            const wasLoggedIn = this.isLoggedIn;
            this.isLoggedIn = state.isLoggedIn;
            this.currentUsername = state.user?.username;
            this.csrfToken = state.csrfToken;

            if (wasLoggedIn !== this.isLoggedIn) {
                this.updateFormUI();
                this.loadAndRender();
            }
        });
    }

    init() {
        this.bindEvents();
        this.initRichEditors();
        this.initTagMentions();
        this.handleAnchorScroll();

        // Enable infinite scroll EXCLUSIVELY for user profiles
        if (this.targetType === 'user_profile') {
            this.initInfiniteScroll();
        }
    }

    async loadAndRender(append = false) {
        if (this.isLoading) return;
        this.isLoading = true;
        this.updateSentinelVisibility();

        const newComments = await this.loadComments();
        if (append) {
            this.comments = [...this.comments, ...newComments];
        } else {
            this.comments = newComments;
        }

        this.updateUI(append);
        this.handleAnchorScroll();
        this.isLoading = false;
        this.updateSentinelVisibility();
    }

    async loadMore(page) {
        if (this.targetType !== 'user_profile' || this.isLoading || page > this.totalPages || page <= this.currentPage) return;
        this.isLoading = true;
        this.updateSentinelVisibility();

        try {
            const response = await fetch(`/api/comments.php?action=list&target_id=${this.targetId}&target_type=${this.targetType}&filter_type=${this.filterType}&sort=${this.sort}&page=${page}`);
            const data = await response.json();
            if (data.success) {
                this.currentPage = page;
                this.totalCount = data.total_count;
                this.totalPages = data.total_pages;
                this.comments = [...this.comments, ...data.comments];
                this.updateCommentList(true);
            }
        } catch (error) {
            console.error('Failed to load more comments:', error);
        } finally {
            this.isLoading = false;
            this.updateSentinelVisibility();
        }
    }

    handleAnchorScroll() {
        const hash = window.location.hash;
        if (hash && hash.startsWith('#comment-')) {
            setTimeout(() => {
                const el = document.querySelector(hash);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('highlight-comment');
                    setTimeout(() => el.classList.remove('highlight-comment'), 3000);
                }
            }, 500);
        }
    }

    async loadComments() {
        try {
            const response = await fetch(`/api/comments.php?action=list&target_id=${this.targetId}&target_type=${this.targetType}&filter_type=${this.filterType}&sort=${this.sort}&page=${this.currentPage}`);
            const data = await response.json();
            if (data.success) {
                this.totalCount = data.total_count;
                this.totalPages = data.total_pages;
                return data.comments;
            }
        } catch (error) {
            console.error('Failed to load comments:', error);
        }
        return [];
    }

    updateUI(append = false) {
        this.updateCommentList(append);
        this.updatePagination();
        this.updateStatsUI();
        this.updateFiltersUI();
        this.updateSortUI();
        if (window.lucide) lucide.createIcons({ root: this.container });
    }

    updateCommentList(append = false) {
        const list = document.getElementById('comment-list');
        if (!list) return;

        // Ensure sentinel is always AFTER the list
        const sentinel = document.getElementById('comments-sentinel');
        if (sentinel && list.nextSibling !== sentinel) {
            list.after(sentinel);
        }

        if (this.comments.length === 0) {
            list.innerHTML = `
                <div class="bg-block text-center pd-md radius-16 border d-column just-center align-center">
                    <i data-lucide="message-circle" class="w-12 h-12 text-gray-300 mx-auto mb-1"></i>
                    <p class="text-gray-400">${this.readOnly ? 'هنوز نظری ثبت نشده است.' : 'هنوز نظری ثبت نشده است. اولین تحلیل‌گر باشید!'}</p>
                </div>
            `;
        } else {
            if (append) {
                const existingIds = Array.from(list.querySelectorAll('[id^="comment-wrapper-"]')).map(el => el.id.replace('comment-wrapper-', ''));
                const newComments = this.comments.filter(c => !existingIds.includes(c.id.toString()));

                const html = newComments.map(c => {
                    const itemHtml = this.renderCommentItem(c, !!c.parent_id);
                    // Add fade-in class to the wrapper
                    return itemHtml.replace('class="comment-wrapper', 'class="comment-wrapper comment-fade-in');
                }).join('');

                list.insertAdjacentHTML('beforeend', html);
            } else {
                list.innerHTML = this.comments.map(c => this.renderCommentItem(c, !!c.parent_id)).join('');
            }
        }
        if (window.lucide) lucide.createIcons({ root: list });
    }

    updatePagination() {
        const pagination = document.getElementById('comments-pagination');

        // Lazy loading is EXCLUSIVELY for user profiles.
        // For all other types, we show the standard pagination.
        if (this.targetType === 'user_profile') {
            if (pagination) pagination.classList.add('d-none');
            return;
        }

        if (pagination) {
            pagination.classList.remove('d-none');
        }

        if (!pagination) {
            if (this.totalPages > 1) {
                const list = document.getElementById('comment-list');
                const newPagination = document.createElement('div');
                newPagination.id = 'comments-pagination';
                newPagination.className = 'pagination d-flex just-center gap-05';
                list.after(newPagination);
                this.renderPaginationUI(newPagination);
            }
            return;
        }

        if (this.totalPages <= 1) {
            pagination.remove();
        } else {
            this.renderPaginationUI(pagination);
        }
    }

    renderPaginationUI(container) {
        let html = '';
        const pageWindow = 2;
        const pages = [];

        for (let i = 1; i <= this.totalPages; i++) {
            if (
                i === 1 ||
                i === this.totalPages ||
                (i >= this.currentPage - pageWindow && i <= this.currentPage + pageWindow)
            ) {
                pages.push(i);
            } else if (
                i === this.currentPage - (pageWindow + 1) ||
                i === this.currentPage + (pageWindow + 1)
            ) {
                pages.push('...');
            }
        }

        const items = [];
        let last = null;
        pages.forEach(p => {
            if (p === '...' && last === '...') return;
            items.push(p);
            last = p;
        });

        if (this.currentPage > 1) {
            html += `
                <button class="pagination-link page-btn" data-page="${this.currentPage - 1}" title="صفحه قبل">
                    <i data-lucide="chevron-right" class="icon-size-4"></i>
                </button>
            `;
        }

        items.forEach(item => {
            if (item === '...') {
                html += `<span class="pagination-dots">...</span>`;
            } else {
                html += `
                    <button class="pagination-link page-btn ${item === this.currentPage ? 'active' : ''}" data-page="${item}">
                        ${this.toPersianDigits(item)}
                    </button>
                `;
            }
        });

        if (this.currentPage < this.totalPages) {
            html += `
                <button class="pagination-link page-btn" data-page="${this.currentPage + 1}" title="صفحه بعد">
                    <i data-lucide="chevron-left" class="icon-size-4"></i>
                </button>
            `;
        }

        container.innerHTML = html;
        if (window.lucide) lucide.createIcons({ root: container });
    }

    updateStatsUI() {
        const countBadge = this.container.querySelector('.comments-count-badge');
        if (countBadge) countBadge.innerText = `(${this.toPersianDigits(this.totalCount)})`;
    }

    updateFiltersUI() {
        this.container.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.filter === this.filterType);
        });
    }

    updateSortUI() {
        this.container.querySelectorAll('.sort-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.sort === this.sort);
        });
    }

    updateFormUI() {
        const formContainer = document.getElementById('main-form-container');
        if (!formContainer || this.readOnly) return;

        if (!this.isLoggedIn && !this.guestCommentEnabled) {
            formContainer.innerHTML = `
                <div class="bg-orange-light pd-md radius-16 border border-orange d-flex-wrap just-between align-center gap-1">
                    <p class="font-bold text-orange">برای ثبت نظر و کسب امتیاز باید وارد حساب خود شوید</p>
                    <div class="d-flex gap-1">
                        <button class="btn btn-orange btn-sm" onclick="window.showAuthModal?.('login')">ورود به حساب</button>
                        <button class="btn btn-secondary btn-sm bg-block" onclick="window.showAuthModal?.('register')">عضویت رایگان</button>
                    </div>
                </div>
            `;
        } else {
            formContainer.innerHTML = this.renderCommentForm();
            if (window.lucide) lucide.createIcons({ root: formContainer });
            this.initRichEditors();
            this.initTagMentions();
        }
    }

    initRichEditors() {
        if (this.readOnly || typeof BaseEditor === 'undefined') return;

        const textareas = this.container.querySelectorAll('textarea[id^="textarea-"]');
        textareas.forEach(textarea => {
            const id = textarea.id;
            if (this.editors[id] && document.body.contains(this.editors[id].container)) {
                return;
            }

            if (this.editors[id]) {
                this.editors[id].destroy();
            }

            this.editors[id] = new BaseEditor({
                el: textarea,
                toolbar: ['bold', 'italic', 'blockquote', 'ul', 'ol', 'clear'],
                placeholder: 'دیدگاه تخصصی خود را اینجا بنویسید...',
                autosave: id === 'textarea-main',
                autosaveKey: `comment-draft-${this.targetType}-${this.targetId}`,
                preview: true
            });
        });
    }

    renderCommentForm(parentId = null, initialContent = '') {
        const suffix = parentId || 'main';
        const isEdit = initialContent !== '';
        const showTypeSelector = suffix === 'main' || isEdit;

        return `
            <div class="comment-form ${parentId && !isEdit ? 'mt-2 mb-2' : 'mb-2'}" id="form-${suffix}">
                ${showTypeSelector ? `
                <div class="comment-type-selector d-flex gap-1-5 mb-1 pr-1 ${this.targetType === 'post' ? 'd-none' : ''}">
                    <label class="d-flex align-center gap-05 cursor-pointer font-bold text-sm">
                        <input type="radio" name="comment_type_${suffix}" value="comment" class="comment-type-radio" data-suffix="${suffix}" checked>
                        <span>نظر</span>
                    </label>
                    <label class="d-flex align-center gap-05 cursor-pointer font-bold text-sm">
                        <input type="radio" name="comment_type_${suffix}" value="analysis" class="comment-type-radio" data-suffix="${suffix}">
                        <span>تحلیل</span>
                    </label>
                </div>
                ` : `
                <input type="hidden" name="comment_type_${suffix}" value="comment" class="comment-type-radio" data-suffix="${suffix}">
                `}

                ${!this.isLoggedIn ? `
                    <div class="d-flex-wrap gap-1 mb-1">
                        <div class="input-item grow-1">
                            <i data-lucide="user" class="text-gray icon-size-3"></i>
                            <input type="text" id="guest-name-${suffix}" placeholder="نام شما">
                        </div>
                        <div class="input-item grow-1">
                            <i data-lucide="mail" class="text-gray icon-size-3"></i>
                            <input type="email" id="guest-email-${suffix}" placeholder="ایمیل شما" dir="ltr" class="text-left">
                        </div>
                    </div>
                ` : ''}

                <div class="mb-1">
                    <textarea placeholder="دیدگاه تخصصی خود را اینجا بنویسید..." id="textarea-${suffix}">${initialContent}</textarea>
                </div>
                <input type="text" id="hp-${suffix}" class="d-none" tabindex="-1" autocomplete="off">

                ${this.isLoggedIn ? `
                <div class="mention-tag-area mb-2" id="mention-area-${suffix}">
                    <div class="mention-input-wrapper relative d-flex-wrap gap-05 align-center" id="mentions-container-${suffix}">
                        <i data-lucide="user-search" class="text-gray icon-size-5"></i>
                        <input type="text"
                               class="mention-tag-input font-size-0-8"
                               placeholder="منشن کردن کاربر..."
                               id="mention-input-${suffix}">
                        <div class="mention-suggestions d-none" id="suggestions-${suffix}"></div>
                    </div>
                </div>
                ` : ''}

                <div class="comment-image-upload d-none mb-2" id="image-upload-container-${suffix}">
                    <label for="comment-image-${suffix}" class="upload-zone d-flex align-center just-center gap-1 p-1 radius-8 border-dashed pointer transition-all">
                        <i data-lucide="image" class="text-gray icon-size-5"></i>
                        <div class="text-right">
                            <div class="font-bold font-size-2 text-title">آپلود تصویر تحلیل</div>
                        </div>
                        <input type="file" id="comment-image-${suffix}" class="d-none comment-image-input" accept="image/*" data-suffix="${suffix}">
                    </label>
                    <div class="font-size-1 text-gray">فرمت‌های مجاز: PNG, JPG, WebP, AVIF</div>
                    <div class="image-preview d-none mt-2 relative radius-12 overflow-hidden border" style="width: 100px; height: 100px;">
                        <img src="" class="w-full h-full object-cover">
                        <button type="button" class="remove-preview absolute top-0 left-0 m-05 radius-50 p-05" data-suffix="${suffix}">
                            <i data-lucide="x" class="icon-size-1"></i>
                        </button>
                    </div>
                </div>

                <div class="comment-form-footer">
                    <div class="comment-form-footer-left mr-auto d-flex align-center gap-1">
                        <button class="btn btn-secondary cancel-comment radius-10" data-suffix="${suffix}">انصراف</button>
                        ${!this.isLoggedIn ? '<span class="text-[10px] font-black text-amber-600 bg-amber-50 px-2 py-1 rounded border border-amber-100">در حال ارسال به عنوان مهمان</span>' : ''}
                        <button class="btn btn-primary submit-comment radius-10" data-parent="${parentId || ''}" data-edit="${isEdit}">
                            ${isEdit ? 'بروزرسانی نظر' : 'ارسال نظر'}
                        </button>
                        
                    </div>
                </div>
            </div>
        `;
    }

    renderCommentItem(c, isReply = false) {
        const hasReplies = !isReply && ((c.replies && c.replies.length > 0) || (c.total_replies > 0));
        const showInlineReplies = hasReplies && (this.targetType !== 'user_profile' || this.isInsideModal);

        if (this.isInsideModal && isReply) {
            return this.renderModalReplyItem(c);
        }
        const baseUrl = window.location.origin;
        const defaultAvatar = `${baseUrl}/assets/images/default-avatar.png`;

        let avatarUrl = c.user_avatar;
        if (avatarUrl) {
            if (!avatarUrl.startsWith('https') && !avatarUrl.startsWith('http')) {
                const path = avatarUrl.startsWith('/') ? avatarUrl.substring(1) : avatarUrl;
                avatarUrl = `${baseUrl}/${path}`;
            }
        } else {
            avatarUrl = defaultAvatar;
        }

        return `
            <div class="comment-wrapper ${hasReplies ? 'has-replies' : ''}" id="comment-wrapper-${c.id}">
                ${(this.isInsideModal && !isReply) ? this.renderThreadRootReference(c) : `
                <div class="comment-item ${c.user_role === 'admin' || c.user_role === 'editor' ? 'is-expert' : ''} ${isReply ? 'is-reply' : ''}" id="comment-${c.id}">
                    <div class="comment-header">
                        <div class="comment-user-info">
                            <div class="avatar-container">
                                <img src="${avatarUrl}" class="comment-avatar" alt="${c.user_name || c.guest_name || 'ناشناس'}" onerror="this.src='${defaultAvatar}'">
                                <div class="online-dot"></div>
                            </div>
                            <div class="comment-meta">
                                ${c.user_id ? `
                                    <a href="/profile/${c.user_id}/${c.user_username || 'user'}" class="comment-author">
                                        <span class="d-inline-block ellipsis-x max-w100">${c.user_name}</span>
                                        <span class="user-level-badge level-${c.user_level || 1}">سطح ${c.user_level || 1}</span>
                                    </a>
                                ` : `
                                    <span class="comment-author">
                                        <span class="d-inline-block ellipsis-x max-w100">${c.guest_name || 'مهمان'}</span>
                                        <span class="user-level-badge !bg-slate-400">مهمان</span>
                                    </span>
                                `}

                                <span class="comment-date">${c.created_at_fa || c.created_at}</span>
                            </div>
                        </div>
                        <div class="header-actions">
                            ${c.can_edit ? `
                                <div class="comment-header-btn delete-btn" title="حذف" data-id="${c.id}"><i data-lucide="trash-2" class="icon-size-4"></i></div>
                                <div class="comment-header-btn edit-btn" title="ویرایش" data-id="${c.id}"><i data-lucide="edit-3" class="icon-size-4"></i></div>
                            ` : ''}
                            <div class="comment-header-btn report-btn" title="گزارش تخلف" data-id="${c.id}"><i data-lucide="flag" class="icon-size-4"></i></div>
                            <div class="comment-header-btn comment-share-btn" title="کپی لینک مستقیم" data-id="${c.id}">
                                <i data-lucide="share-2" class="icon-size-3"></i>
                            </div>
                        </div>
                    </div>

                    ${c.target_info ? `
                        <div class="d-inline-flex font-bold font-size-2 mb-1">
                            <span class="text-gray-400">در </span>
                            <a href="${c.target_info.url}" class="text-primary hover-underline">${c.target_info.title}</a>
                        </div>
                    ` : ''}

                    <div class="comment-content">
                        ${this.renderCommentBody(c)}
                    </div>

                    <div class="comment-footer">
                        ${!this.readOnly ? `
                        <div class="comment-footer-btn reply-btn" data-id="${c.id}">
                            <i data-lucide="reply" class="icon-size-4"></i>
                            <span>پاسخ</span>
                        </div>
                        ` : (this.targetType === 'user_profile' && !this.isInsideModal ? `
                        <div class="view-thread-btn comment-footer-btn" data-id="${c.id}">
                            <i data-lucide="message-circle" class="icon-size-3"></i>
                            <span>${c.total_replies > 0 ? this.toPersianDigits(c.total_replies) + ' پاسخ' : 'بدون پاسخ'}</span>
                        </div>
                        ` : '')}
                        <div class="footer-right mr-auto">
                            ${(() => {
                                const reactionsHtml = (
                                    this.renderReaction(c, 'like', '👍') +
                                    this.renderReaction(c, 'heart', '❤️') +
                                    this.renderReaction(c, 'fire', '🔥') +
                                    this.renderReaction(c, 'dislike', '👎')
                                ).trim();
                                return `<div class="reaction-pill ${reactionsHtml ? '' : 'd-none'}">${reactionsHtml}</div>`;
                            })()}
                            <div class="comment-footer-btn btn-react-trigger" data-id="${c.id}">
                                <i data-lucide="smile" class="icon-size-4"></i>
                                <span>واکنش</span>
                            </div>
                            <div class="reactions-popover" id="popover-${c.id}">
                                <span class="emoji-btn" data-id="${c.id}" data-type="like">👍</span>
                                <span class="emoji-btn" data-id="${c.id}" data-type="heart">❤️</span>
                                <span class="emoji-btn" data-id="${c.id}" data-type="fire">🔥</span>
                                <span class="emoji-btn" data-id="${c.id}" data-type="dislike">👎</span>
                            </div>
                        </div>
                    </div>
                </div>
                `}
                <div id="reply-form-container-${c.id}"></div>
                ${!isReply && showInlineReplies ? `
                    <div class="replies-container" id="replies-container-${c.id}">
                        <div class="replies-list">
                            ${c.replies ? c.replies.map(r => this.renderCommentItem(r, true)).join('') : ''}
                        </div>
                        ${c.total_replies > 3 ? `
                            <button class="btn btn-sm btn-secondary w-full mt-2 view-more-replies" data-id="${c.id}" data-total="${c.total_replies}">
                                مشاهده پاسخ‌های بیشتر (${this.toPersianDigits(c.total_replies - 3)})
                            </button>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
        `;
    }

    renderThreadRootReference(c) {
        const contentSnippet = c.content_edit || c.content || '';
        const plainText = contentSnippet.replace(/<[^>]*>?/gm, '').substring(0, 150);
        const dots = contentSnippet.length > 150 ? '...' : '';

        return `
            <div class="thread-root-reference mb-1" id="comment-${c.id}">
                <div class="reply-preview-block">
                    <div>در پاسخ به <a href="/profile/${c.user_id}/${c.user_username || 'user'}" class="reply-preview-author">@${c.user_username || 'user'}</a></div>
                    <div class="reply-preview-content font-size-0-9 text-gray-600">${plainText}${dots}</div>
                </div>
            </div>
        `;
    }

    renderModalReplyItem(c) {
        const baseUrl = window.location.origin;
        const defaultAvatar = `${baseUrl}/assets/images/default-avatar.png`;
        let avatarUrl = c.user_avatar || defaultAvatar;
        if (avatarUrl && !avatarUrl.startsWith('http')) {
            avatarUrl = `${baseUrl}/${avatarUrl.startsWith('/') ? avatarUrl.substring(1) : avatarUrl}`;
        }

        return `
            <div class="thread-reply-item border-bottom py-1" id="comment-${c.id}">
                <div class="d-flex gap-1">
                    <div class="shrink-0">
                        <img src="${avatarUrl}" class="radius-50 w-8 h-8 object-cover border" alt="${c.user_name || c.guest_name || 'ناشناس'}" onerror="this.src='${defaultAvatar}'">
                    </div>
                    <div class="grow-1">
                        <div class="d-flex just-between align-center mb-1">
                            <div class="d-flex align-center gap-05">
                                <span class="font-bold font-size-0-9">${c.user_name || c.guest_name || 'ناشناس'}</span>
                                <span class="text-gray-400 font-size-0-8">${c.created_at_fa || c.created_at}</span>
                            </div>
                            <div class="thread-reply-reactions">
                                ${this.renderReaction(c, 'like', '👍')}
                            </div>
                        </div>
                        <div class="thread-reply-content font-size-0-9 text-gray-700">
                            ${this.renderCommentBody(c, true)}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderCommentBody(c, isMinimal = false) {
        const isExpert = c.user_role === 'admin' || c.user_role === 'editor';
        const replyPreview = !isMinimal && c.reply_to_content ? `
            <div class="reply-preview-block">
                <div>در پاسخ به <a href="/profile/${c.reply_to_user_id}/${c.reply_to_username || 'user'}" class="reply-preview-author">@${c.reply_to_username || 'user'}</a></div>
                <div class="reply-preview-content">${c.reply_to_content.substring(0, 100)}${c.reply_to_content.length > 100 ? '...' : ''}</div>
            </div>
        ` : '';

        let imageHtml = '';
        if (c.type === 'analysis' && c.image_url) {
            imageHtml = `
                <div class="comment-attachment mb-2">
                    <a href="/${c.image_url}" target="_blank" class="radius-12 overflow-hidden transition-all">
                        <img src="/${c.image_url}" alt="تحلیل کاربر" class="w-full object-contain bg-secondary">
                    </a>
                </div>
            `;
        }

        return `
            ${replyPreview}
            <div class="comment-body-text">${c.content_html}</div>
            ${imageHtml}
            ${isExpert ? `<div class="attachment-btn"><i data-lucide="file-text" class="icon-size-4"></i> مشاهده پیوست</div>` : ''}
        `;
    }

    renderReaction(comment, type, emoji) {
        const count = comment[type + 's'] || 0;
        if (count === 0 && comment.user_reaction !== type) return '';
        return `
            <div class="reaction-pill-item ${comment.user_reaction === type ? 'active' : ''}" data-id="${comment.id}" data-type="${type}">
                <span>${this.toPersianDigits(count)}</span> ${emoji}
            </div>
        `;
    }

    toPersianDigits(num) {
        if (num === null || num === undefined) return '';
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return num.toString().replace(/\d/g, x => persian[x]);
    }

    bindEvents() {
        // Use event delegation on this.container
        this.container.onclick = async (e) => {
            const target = e.target;

            // Submit Comment
            const submitBtn = target.closest('.submit-comment');
            if (submitBtn) {
                this.handleSubmit(submitBtn);
                return;
            }

            // Reply Toggle
            const replyBtn = target.closest('.reply-btn');
            if (replyBtn) {
                this.toggleReplyForm(replyBtn);
                return;
            }

            // Edit Toggle
            const editBtn = target.closest('.edit-btn');
            if (editBtn) {
                this.toggleEditForm(editBtn);
                return;
            }

            // Delete Comment
            const deleteBtn = target.closest('.delete-btn');
            if (deleteBtn) {
                this.handleDelete(deleteBtn);
                return;
            }

            // Reactions Popover Toggle
            const reactTrigger = target.closest('.btn-react-trigger');
            if (reactTrigger) {
                e.stopPropagation();
                this.toggleReactionPopover(reactTrigger);
                return;
            }

            // Reaction Click
            const reactionBtn = target.closest('.emoji-btn, .reaction-pill-item');
            if (reactionBtn) {
                e.stopPropagation();
                this.handleReaction(reactionBtn);
                return;
            }

            // Share Click
            const shareBtn = target.closest('.comment-share-btn');
            if (shareBtn) {
                this.handleShare(shareBtn);
                return;
            }

            // Report Click
            const reportBtn = target.closest('.report-btn');
            if (reportBtn) {
                this.handleReport(reportBtn);
                return;
            }

            // View More Replies
            const viewMoreBtn = target.closest('.view-more-replies');
            if (viewMoreBtn) {
                this.handleViewMoreReplies(viewMoreBtn);
                return;
            }

            // View Thread
            const viewThreadBtn = target.closest('.view-thread-btn');
            if (viewThreadBtn) {
                this.openThreadModal(viewThreadBtn.dataset.id);
                return;
            }

            // Filters
            const filterBtn = target.closest('.filter-btn');
            if (filterBtn) {
                if (filterBtn.classList.contains('active')) return;
                this.filterType = filterBtn.dataset.filter;
                this.currentPage = 1;
                this.loadAndRender();
                return;
            }

            // Cancel Comment
            const cancelBtn = target.closest('.cancel-comment');
            if (cancelBtn) {
                this.handleCancel(cancelBtn);
                return;
            }

            // Sort
            const sortBtn = target.closest('.sort-btn');
            if (sortBtn) {
                if (sortBtn.classList.contains('active')) return;
                this.sort = sortBtn.dataset.sort;
                this.currentPage = 1;
                this.loadAndRender();
                return;
            }

            // Pagination
            const pageBtn = target.closest('.page-btn');
            if (pageBtn) {
                const page = parseInt(pageBtn.dataset.page);
                if (page === this.currentPage) return;
                this.currentPage = page;
                this.loadAndRender();
                this.container.scrollIntoView({ behavior: 'smooth' });
                return;
            }

            // Remove Image Preview
            const removePreview = target.closest('.remove-preview');
            if (removePreview) {
                this.handleRemovePreview(removePreview);
                return;
            }

            // Global Popover/Suggestion Close
            if (!target.closest('.footer-right')) {
                this.container.querySelectorAll('.reactions-popover').forEach(p => p.classList.remove('show'));
            }
        };

        this.container.onchange = (e) => {
            const target = e.target;

            // Comment Type Radio
            if (target.classList.contains('comment-type-radio')) {
                this.handleTypeChange(target);
            }

            // Image Input Change
            if (target.classList.contains('comment-image-input')) {
                this.handleImageChange(target);
            }
        };

        // Drag & Drop
        this.container.ondragover = (e) => {
            const zone = e.target.closest('.upload-zone');
            if (zone) {
                e.preventDefault();
                zone.classList.add('drag-over');
            }
        };

        this.container.ondragleave = (e) => {
            const zone = e.target.closest('.upload-zone');
            if (zone) zone.classList.remove('drag-over');
        };

        this.container.ondrop = (e) => {
            const zone = e.target.closest('.upload-zone');
            if (zone) {
                e.preventDefault();
                zone.classList.remove('drag-over');
                const file = e.dataTransfer.files[0];
                const input = zone.querySelector('input');
                if (file && input) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    input.files = dataTransfer.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        };
    }

    async handleSubmit(btn) {
        const parentId = btn.dataset.parent || null;
        const isEdit = btn.dataset.edit === 'true';
        const suffix = parentId || 'main';
        const form = document.getElementById(`form-${suffix}`);
        const typeInput = form.querySelector('input.comment-type-radio:checked');
        const type = typeInput ? typeInput.value : 'comment';
        const textarea = document.getElementById(`textarea-${suffix}`);
        const hp = document.getElementById(`hp-${suffix}`)?.value;
        if (hp) return;

        const editor = this.editors?.[`textarea-${suffix}`];
        const content = editor ? editor.getContent() : textarea.value;
        const guestNameInput = document.getElementById(`guest-name-${suffix}`);
        const guestEmailInput = document.getElementById(`guest-email-${suffix}`);
        const imageInput = document.getElementById(`comment-image-${suffix}`);

        const mentionsContainer = document.getElementById(`mentions-container-${suffix}`);
        const mentionIds = Array.from(mentionsContainer?.querySelectorAll('.mention-tag') || []).map(tag => tag.dataset.userId);

        if (!content.trim()) return;

        if (!this.isLoggedIn) {
            if (type === 'analysis') {
                window.showAuthModal?.('login');
                return;
            }
            if (this.guestCommentEnabled) {
                if (!guestNameInput?.value.trim()) {
                    window.showAlert?.('لطفا نام خود را وارد کنید.', 'warning');
                    return;
                }
                if (!guestEmailInput?.value.trim() || !guestEmailInput.value.includes('@')) {
                    window.showAlert?.('لطفا یک ایمیل معتبر وارد کنید.', 'warning');
                    return;
                }
            } else {
                window.showAuthModal?.('login');
                return;
            }
        }

        btn.disabled = true;
        const originalText = btn.innerText;
        btn.innerText = 'در حال ارسال...';

        try {
            let res;
            if (!isEdit) {
                const formData = new FormData();
                formData.append('content', content);
                formData.append('type', type);
                formData.append('mentions', JSON.stringify(mentionIds));
                formData.append('hp', hp || '');
                formData.append('guest_name', guestNameInput?.value || '');
                formData.append('guest_email', guestEmailInput?.value || '');
                formData.append('target_id', this.targetId);
                formData.append('target_type', this.targetType);
                formData.append('parent_id', parentId || '');
                if (parentId) formData.append('reply_to_id', parentId);
                if (imageInput && imageInput.files[0]) formData.append('image', imageInput.files[0]);

                res = await fetch(`/api/comments.php?action=add`, {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': this.csrfToken },
                    body: formData
                });
            } else {
                res = await fetch(`/api/comments.php?action=edit`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfToken },
                    body: JSON.stringify({ content, mentions: mentionIds, hp: hp || '', comment_id: parentId, type: type })
                });
            }

            const data = await res.json();
            if (data.success) {
                if (editor) editor.clear();
                textarea.value = '';
                if (mentionsContainer) mentionsContainer.querySelectorAll('.mention-tag').forEach(tag => tag.remove());
                this.handleRemovePreview({ dataset: { suffix } });

                if (isEdit) {
                    const commentItem = document.getElementById(`comment-${parentId}`);
                    if (commentItem) {
                        const comment = data.comment || this.findComment(parentId);
                        if (data.comment) this.updateCommentInCache(data.comment);
                        commentItem.querySelector('.comment-content').innerHTML = this.renderCommentBody(data.comment || comment);
                    }
                } else {
                    const comment = data.comment;
                    this.comments.push(comment);
                    const commentHtml = this.renderCommentItem(comment, !!comment.parent_id);
                    if (parentId) {
                        document.getElementById(`reply-form-container-${parentId}`).innerHTML = '';
                        const actualParentId = comment.parent_id;
                        let repliesContainer = document.getElementById(`replies-container-${actualParentId}`);
                        if (!repliesContainer) {
                            const wrapper = document.getElementById(`comment-wrapper-${actualParentId}`);
                            repliesContainer = document.createElement('div');
                            repliesContainer.className = 'replies-container';
                            repliesContainer.id = `replies-container-${actualParentId}`;
                            repliesContainer.innerHTML = '<div class="replies-list"></div>';
                            wrapper.appendChild(repliesContainer);
                            wrapper.classList.add('has-replies');
                        }
                        repliesContainer.querySelector('.replies-list').insertAdjacentHTML('beforeend', commentHtml);
                    } else {
                        const list = document.getElementById('comment-list');
                        if (list.querySelector('.text-gray-400')) list.innerHTML = '';
                        list.insertAdjacentHTML('afterbegin', commentHtml);
                    }
                    this.totalCount = data.total_count;
                    this.updateStatsUI();
                }
                if (window.lucide) lucide.createIcons({ root: this.container });
            } else {
                window.showAlert?.(data.message, 'error');
            }
        } catch (error) {
            console.error(error);
            window.showAlert?.('خطا در برقراری ارتباط با سرور', 'error');
        } finally {
            btn.disabled = false;
            btn.innerText = originalText;
        }
    }

    toggleReplyForm(btn) {
        if (!this.isLoggedIn && !this.guestCommentEnabled) { window.showAuthModal?.('login'); return; }
        const id = btn.dataset.id;
        const container = document.getElementById(`reply-form-container-${id}`);
        if (container.innerHTML === '') {
            this.container.querySelectorAll('[id^="reply-form-container-"]').forEach(c => c.innerHTML = '');
            container.innerHTML = this.renderCommentForm(id);
            if (window.lucide) lucide.createIcons({ root: container });
            this.initRichEditors();
            this.initTagMentions();
            container.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            container.innerHTML = '';
        }
    }

    toggleEditForm(btn) {
        const id = btn.dataset.id;
        const comment = this.findComment(id);
        if (!comment) return;
        const wrapper = document.getElementById(`comment-${id}`);
        const body = wrapper.querySelector('.comment-content');
        body.innerHTML = this.renderCommentForm(id, comment.content_edit || comment.content);
        if (comment.mentioned_users) {
            const container = document.getElementById(`mentions-container-${id}`);
            comment.mentioned_users.forEach(u => this.addMentionTag(container, u));
        }
        if (window.lucide) lucide.createIcons({ root: body });
        this.initRichEditors();
        this.initTagMentions();
    }

    toggleReactionPopover(btn) {
        if (!this.isLoggedIn) { window.showAuthModal?.('login'); return; }
        const id = btn.dataset.id;
        const popover = document.getElementById(`popover-${id}`);
        const isShown = popover.classList.contains('show');
        this.container.querySelectorAll('.reactions-popover').forEach(p => p.classList.remove('show'));
        if (!isShown) popover.classList.add('show');
    }

    async handleReaction(btn) {
        if (btn.classList.contains('loading')) return;
        if (!this.isLoggedIn) { window.showAuthModal?.('login'); return; }
        const id = btn.dataset.id;
        const type = btn.dataset.type;
        const comment = this.findComment(id);
        const currentReaction = comment ? comment.user_reaction : null;
        const newType = (currentReaction === type) ? null : type;
        btn.classList.add('loading');
        try {
            const res = await fetch('/api/comments.php?action=react', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfToken },
                body: JSON.stringify({ comment_id: id, reaction_type: newType })
            });
            const data = await res.json();
            if (data.success) {
                const counts = data.counts;
                const userReaction = data.user_reaction;
                if (comment) {
                    Object.assign(comment, { likes: counts.likes, dislikes: counts.dislikes, hearts: counts.hearts, fires: counts.fires, user_reaction: userReaction });
                }
                const pill = document.querySelector(`#comment-${id} .reaction-pill`);
                if (pill) {
                    const reactionsHtml = `
                        ${this.renderReaction({id, likes: counts.likes, user_reaction: userReaction}, 'like', '👍')}
                        ${this.renderReaction({id, hearts: counts.hearts, user_reaction: userReaction}, 'heart', '❤️')}
                        ${this.renderReaction({id, fires: counts.fires, user_reaction: userReaction}, 'fire', '🔥')}
                        ${this.renderReaction({id, dislikes: counts.dislikes, user_reaction: userReaction}, 'dislike', '👎')}
                    `.trim();
                    pill.innerHTML = reactionsHtml;
                    pill.classList.toggle('d-none', !reactionsHtml);
                }
            } else {
                window.showAlert?.(data.message, 'warning');
            }
        } catch (error) { console.error(error); } finally { btn.classList.remove('loading'); }
    }

    handleShare(btn) {
        const id = btn.dataset.id;
        const pageParam = (this.currentPage > 1) ? `?page=${this.currentPage}` : '';
        const url = window.location.origin + window.location.pathname + pageParam + '#comment-' + id;
        navigator.clipboard.writeText(url).then(() => {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="check" class="icon-size-4 text-success"></i>';
            if (window.lucide) lucide.createIcons({ root: btn });
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                if (window.lucide) lucide.createIcons({ root: btn });
            }, 2000);
        });
    }

    async handleReport(btn) {
        if (!this.isLoggedIn) { window.showAuthModal?.('login'); return; }
        const id = btn.dataset.id;
        const reason = prompt('علت گزارش این نظر چیست؟');
        if (!reason) return;
        try {
            const res = await fetch('/api/comments.php?action=report', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfToken },
                body: JSON.stringify({ comment_id: id, reason })
            });
            const data = await res.json();
            window.showAlert?.(data.message, data.success ? 'success' : 'error');
        } catch (error) { console.error(error); }
    }

    async handleViewMoreReplies(btn) {
        const id = btn.dataset.id;
        const total = parseInt(btn.dataset.total);
        const list = document.querySelector(`#replies-container-${id} .replies-list`);
        if (btn.classList.contains('showing-all')) {
            const allReplies = list.querySelectorAll('.comment-item');
            for (let i = 3; i < allReplies.length; i++) allReplies[i].closest('.comment-wrapper')?.remove();
            btn.innerText = `مشاهده پاسخ‌های بیشتر (${this.toPersianDigits(total - 3)})`;
            btn.classList.remove('showing-all');
            return;
        }
        btn.disabled = true;
        btn.innerText = 'در حال دریافت...';
        try {
            const res = await fetch(`/api/comments.php?action=replies&parent_id=${id}&offset=3&limit=100`);
            const data = await res.json();
            if (data.success) {
                data.replies.forEach(r => list.insertAdjacentHTML('beforeend', this.renderCommentItem(r, true)));
                btn.innerText = 'پنهان کردن پاسخ‌ها';
                btn.classList.add('showing-all');
                if (window.lucide) lucide.createIcons({ root: list });
            }
        } catch (error) { console.error(error); } finally { btn.disabled = false; }
    }

    handleTypeChange(radio) {
        const type = radio.value;
        const suffix = radio.dataset.suffix;
        if (!this.isLoggedIn && type === 'analysis') {
            window.showAuthModal?.('login');
            radio.checked = false;
            const commentRadio = radio.closest('.comment-type-selector').querySelector('input[value="comment"]');
            if (commentRadio) commentRadio.checked = true;
            return;
        }
        const uploadContainer = document.getElementById(`image-upload-container-${suffix}`);
        if (uploadContainer) uploadContainer.classList.toggle('d-none', type !== 'analysis');
    }

    handleImageChange(input) {
        const suffix = input.dataset.suffix;
        const form = input.closest('.comment-form');
        const file = input.files[0];
        const previewContainer = form ? form.querySelector('.image-preview') : null;
        if (!previewContainer) return;
        const previewImg = previewContainer.querySelector('img');

        if (file) {
            if (!file.type.startsWith('image/')) {
                window.showAlert?.('لطفا فقط فایل تصویری انتخاب کنید.', 'warning');
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (re) => {
                previewImg.src = re.target.result;
                previewContainer.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.classList.add('d-none');
            previewImg.src = '';
        }
    }

    handleRemovePreview(btn) {
        const suffix = btn.dataset.suffix;
        const form = document.getElementById(`form-${suffix}`);
        const input = document.getElementById(`comment-image-${suffix}`);
        const previewContainer = form ? form.querySelector('.image-preview') : null;
        if (input) input.value = '';
        if (previewContainer) {
            previewContainer.classList.add('d-none');
            previewContainer.querySelector('img').src = '';
        }
    }

    initTagMentions() {
        this.container.querySelectorAll('.mention-tag-input').forEach(input => {
            if (input.dataset.mentionsInitialized) return;
            input.dataset.mentionsInitialized = 'true';

            const suffix = input.id.replace('mention-input-', '');
            const suggestionsEl = document.getElementById(`suggestions-${suffix}`);
            const containerEl = document.getElementById(`mentions-container-${suffix}`);

            input.oninput = async () => {
                const q = input.value.trim();
                if (q.length < 1) { suggestionsEl.classList.add('d-none'); return; }
                const res = await fetch(`/api/users.php?action=search&q=${encodeURIComponent(q)}`);
                const data = await res.json();
                const users = data.success ? data.users : [];
                if (users.length > 0) {
                    this.renderSuggestions(suggestionsEl, users, containerEl, input);
                } else {
                    suggestionsEl.classList.add('d-none');
                }
            };
            input.onfocus = () => { if (input.value.trim().length >= 1) suggestionsEl.classList.remove('d-none'); };
            input.onkeydown = (e) => {
                if (e.key === 'Backspace' && input.value === '') {
                    const tags = containerEl.querySelectorAll('.mention-tag');
                    if (tags.length > 0) tags[tags.length - 1].remove();
                }
            };
        });
    }

    renderSuggestions(el, users, container, input) {
        el.innerHTML = users.map(u => `
            <div class="suggestion-item" data-id="${u.id}" data-username="${u.username}" data-name="${u.name}" data-avatar="${u.avatar}">
                <img src="${u.avatar}" class="suggestion-avatar">
                <div class="suggestion-info">
                    <div class="suggestion-name">${u.name}</div>
                    <div class="suggestion-username">@${u.username}</div>
                </div>
            </div>
        `).join('');
        el.classList.remove('d-none');
        el.querySelectorAll('.suggestion-item').forEach(item => {
            item.onclick = (e) => {
                e.stopPropagation();
                this.addMentionTag(container, item.dataset);
                input.value = '';
                el.classList.add('d-none');
            };
        });
    }

    addMentionTag(container, data) {
        if (container.querySelector(`.mention-tag[data-user-id="${data.id}"]`)) return;
        const tag = document.createElement('div');
        tag.className = 'mention-tag';
        tag.dataset.userId = data.id;
        tag.innerHTML = `<span>@${data.username}</span><i class="remove-tag">&times;</i>`;
        tag.querySelector('.remove-tag').onclick = (e) => { e.stopPropagation(); tag.remove(); };
        container.insertBefore(tag, container.querySelector('.mention-tag-input'));
    }

    findComment(id) {
        let found = null;
        const search = (list) => {
            for (const c of list) {
                if (c.id == id) { found = c; return; }
                if (c.replies && search(c.replies)) return;
            }
        };
        search(this.comments);
        return found;
    }

    updateCommentInCache(updatedComment) {
        const searchAndReplace = (list) => {
            for (let i = 0; i < list.length; i++) {
                if (list[i].id == updatedComment.id) {
                    list[i] = { ...list[i], ...updatedComment };
                    return true;
                }
                if (list[i].replies && searchAndReplace(list[i].replies)) return true;
            }
            return false;
        };
        searchAndReplace(this.comments);
    }

    async openThreadModal(commentId) {
        this.isInsideModal = true;
        let modal = document.getElementById(this.threadModalId);
        if (!modal) return;
        modal.classList.remove('d-none');
        document.body.style.overflow = 'hidden';
        const contentArea = modal.querySelector('.thread-content');
        const loader = modal.querySelector('.thread-loader');
        contentArea.classList.add('d-none');
        loader.classList.remove('d-none');
        const targetInfoArea = modal.querySelector('#modal-thread-target-info');
        if (targetInfoArea) targetInfoArea.classList.add('d-none');

        try {
            const res = await fetch(`/api/comments.php?action=thread&comment_id=${commentId}`);
            const data = await res.json();
            if (data.success) {
                const thread = data.thread;
                if (targetInfoArea && thread.target_info) {
                    targetInfoArea.innerHTML = `<div class="d-flex align-center just-between w-full"><div class="d-flex align-center gap-1"><div class="font-bold font-size-0-9">در <a href="${thread.target_info.url}" class="text-primary hover-underline">${thread.target_info.title}</a></div></div></div>`;
                    targetInfoArea.classList.remove('d-none');
                }
                contentArea.innerHTML = `<div class="thread-list">${this.renderCommentItem(thread)}</div>`;
                loader.classList.add('d-none');
                contentArea.classList.remove('d-none');
                if (window.lucide) lucide.createIcons({ root: contentArea });
            } else {
                window.showAlert?.(data.message, 'error');
                this.closeModal(modal);
            }
        } catch (error) {
            console.error(error);
            this.closeModal(modal);
        }
    }

    closeModal(modal) {
        if (window.closeModal) window.closeModal();
    }

    handleCancel(btn) {
        const suffix = btn.dataset.suffix;
        if (suffix === 'main') {
            const editor = this.editors['textarea-main'];
            if (editor) editor.clear();
            const textarea = document.getElementById('textarea-main');
            if (textarea) textarea.value = '';
            const mentionsContainer = document.getElementById('mentions-container-main');
            if (mentionsContainer) mentionsContainer.querySelectorAll('.mention-tag').forEach(tag => tag.remove());
            return;
        }

        const isEdit = document.querySelector(`#form-${suffix} .submit-comment`)?.dataset.edit === 'true';
        if (isEdit) {
            const comment = this.findComment(suffix);
            const commentItem = document.getElementById(`comment-${suffix}`);
            if (commentItem && comment) {
                commentItem.querySelector('.comment-content').innerHTML = this.renderCommentBody(comment);
                if (window.lucide) lucide.createIcons({ root: commentItem });
            }
        } else {
            const container = document.getElementById(`reply-form-container-${suffix}`);
            if (container) container.innerHTML = '';
        }

        if (this.editors[`textarea-${suffix}`]) {
            this.editors[`textarea-${suffix}`].destroy();
            delete this.editors[`textarea-${suffix}`];
        }
    }

    async handleDelete(btn) {
        const id = btn.dataset.id;
        if (!confirm('آیا از حذف این نظر اطمینان دارید؟')) return;

        try {
            const res = await fetch('/api/comments.php?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfToken },
                body: JSON.stringify({ comment_id: id })
            });
            const data = await res.json();
            if (data.success) {
                const wrapper = document.getElementById(`comment-wrapper-${id}`);
                const commentItem = document.getElementById(`comment-${id}`);
                if (commentItem) {
                    commentItem.querySelector('.comment-content').innerHTML = '<p class="text-gray-400 italic">این نظر توسط نویسنده حذف شده است.</p>';
                    commentItem.querySelector('.header-actions').innerHTML = '';
                    commentItem.querySelector('.comment-footer').remove();
                }
                window.showAlert?.(data.message, 'success');
            } else {
                window.showAlert?.(data.message, 'error');
            }
        } catch (error) {
            console.error(error);
            window.showAlert?.('خطا در حذف نظر', 'error');
        }
    }

    initInfiniteScroll() {
        const list = document.getElementById('comment-list');
        if (!list) return;

        if (this.observer) this.observer.disconnect();

        // The site uses a custom scroll container (.container) which might interfere with window-based IntersectionObserver
        const scrollContainer = document.querySelector('.container');

        const observerOptions = {
            root: scrollContainer || null,
            threshold: 0.01,
            rootMargin: '1000px'
        };

        this.observer = new IntersectionObserver((entries) => {
            const entry = entries[0];
            if (entry.isIntersecting && !this.isLoading) {
                if (this.currentPage < this.totalPages) {
                    this.loadMore(this.currentPage + 1);
                }
            }
        }, observerOptions);

        // Create or find sentinel element
        let sentinel = document.getElementById('comments-sentinel');
        if (!sentinel) {
            sentinel = document.createElement('div');
            sentinel.id = 'comments-sentinel';
            sentinel.className = 'infinite-scroll-sentinel py-4 text-center d-column align-center gap-1';
            sentinel.innerHTML = `
                <div class="sentinel-loader d-none">
                    <i data-lucide="loader-2" class="spin text-primary w-8 h-8"></i>
                    <p class="text-gray-400 font-size-0-9">در حال بارگذاری نظرات بیشتر...</p>
                </div>
            `;
            list.after(sentinel);
            if (window.lucide) lucide.createIcons({ root: sentinel });
        }

        this.observer.observe(sentinel);

        // Immediate and delayed checks to handle initial positioning
        setTimeout(() => this.checkSentinelVisibility(), 500);
        setTimeout(() => this.checkSentinelVisibility(), 2000);

        window.addEventListener('resize', () => this.checkSentinelVisibility(), { passive: true });

        // Manual scroll listener as fallback for custom containers
        if (scrollContainer) {
            scrollContainer.addEventListener('scroll', () => {
                if (!this.isLoading && this.currentPage < this.totalPages) {
                    this.checkSentinelVisibility();
                }
            }, { passive: true });
        }
    }

    checkSentinelVisibility() {
        if (this.targetType !== 'user_profile' || this.isLoading || this.currentPage >= this.totalPages) return;

        const sentinel = document.getElementById('comments-sentinel');
        if (!sentinel) return;

        const rect = sentinel.getBoundingClientRect();
        const viewportHeight = window.innerHeight;

        // Trigger if sentinel top is within 1000px of the bottom of the viewport
        const isVisible = rect.top < (viewportHeight + 1000);

        if (isVisible && rect.top !== 0) { // rect.top 0 might mean hidden
            this.loadMore(this.currentPage + 1);
        }
    }

    updateSentinelVisibility() {
        const sentinel = document.getElementById('comments-sentinel');
        if (!sentinel) return;

        if (this.targetType !== 'user_profile' || this.currentPage >= this.totalPages || this.totalCount === 0) {
            sentinel.style.display = 'none';
        } else {
            sentinel.style.display = 'flex';
            const loader = sentinel.querySelector('.sentinel-loader');
            if (loader) {
                loader.classList.toggle('d-none', !this.isLoading);
            }
        }
    }
}

window.initComments = (targetId, targetType) => {
    window.commentSystem = new CommentSystem({ targetId, targetType });
};
