/**
 * Advanced Comment System Component - Refined with Tag-based Mentions
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

        const initialData = window.__COMMENTS_INITIAL_DATA__?.[`${this.targetType}_${this.targetId}`];
        this.comments = options.initialComments || initialData?.comments || [];
        this.totalCount = initialData?.total_count || 0;
        this.totalPages = initialData?.total_pages || 1;
        this.readOnly = options.readOnly || (this.targetType === 'user_profile');
        this.guestCommentEnabled = this.container.dataset.guestComment === '1';
        this.filterType = 'all';
        this.sort = 'newest';
        this.currentPage = initialData?.current_page || 1;
        this.threadModalId = 'comment-thread-modal';

        if (options.initialComments) {
            this.render();
        } else {
            if (this.container.querySelector('.comment-item')) {
                this.bindEvents();
                this.handleAnchorScroll();
            } else {
                this.init();
            }
        }

        document.addEventListener('auth:status-changed', (e) => {
            const state = e.detail;
            this.isLoggedIn = state.isLoggedIn;
            this.currentUsername = state.user?.username;
            this.csrfToken = state.csrfToken;
            this.render();
        });
    }

    async init() {
        if (this.container.querySelector('.comment-item')) return;
        this.bindEvents();
    }

    async loadAndRender() {
        await this.loadComments();
        this.render();
        this.handleAnchorScroll();
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
                this.comments = data.comments;
                this.totalCount = data.total_count;
                this.totalPages = data.total_pages;
            }
        } catch (error) {
            console.error('Failed to load comments:', error);
        }
    }

    render() {
        if (!this.container) return;

        let html = `
            <div class="comments-section ${this.readOnly ? 'read-only' : ''}">
                ${!this.readOnly ? `
                <div class="comments-header">
                    <i data-lucide="message-square" class="text-primary icon-size-6"></i>
                    <h3>نظرات کاربران <span class="comments-count-badge">(${this.toPersianDigits(this.totalCount || this.getTotalCommentCount())})</span></h3>
                </div>

                ${this.renderCommentForm()}
                ` : ''}

                <div class="comments-filters d-column gap-1-5 mb-4 mt-8">
                    ${this.targetType !== 'post' ? `
                        <div class="filter-group-container">
                            <div class="pill-toggle-group filter-toggle-group">
                                <button class="pill-btn filter-btn ${this.filterType === 'all' ? 'active' : ''}" data-filter="all">همه</button>
                                <button class="pill-btn filter-btn ${this.filterType === 'comment' ? 'active' : ''}" data-filter="comment">نظرات</button>
                                <button class="pill-btn filter-btn ${this.filterType === 'analysis' ? 'active' : ''}" data-filter="analysis">تحلیل‌ها</button>
                            </div>
                        </div>
                    ` : ''}

                    <div class="sort-group d-flex align-center just-between gap-1">
                        <div class="d-flex align-center gap-05 text-title">
                            <span class="font-bold font-size-0-9">مرتب‌سازی:</span>
                            <i data-lucide="arrow-down-wide-narrow" class="icon-size-4"></i>
                        </div>
                        <div class="d-flex align-center gap-1-5">
                            <span class="sort-item sort-btn ${this.sort === 'newest' ? 'active' : ''}" data-sort="newest">جدیدترین</span>
                            <span class="sort-item sort-btn ${this.sort === 'popular' ? 'active' : ''}" data-sort="popular">محبوب‌ترین</span>
                            <span class="sort-item sort-btn ${this.sort === 'most_replies' ? 'active' : ''}" data-sort="most_replies">بیشترین پاسخ</span>
                        </div>
                    </div>
                </div>

                <div class="comment-list ${this.readOnly ? 'mt-0' : 'mt-2'}">
                    ${this.renderComments(this.comments)}
                </div>

                ${this.renderPagination()}
            </div>
        `;

        this.container.innerHTML = html;
        if (window.lucide) lucide.createIcons();
        this.bindEvents();
    }

    initRichEditors() {
        if (this.readOnly || typeof BaseEditor === 'undefined') return;
        this.editors = this.editors || {};

        const textareas = this.container.querySelectorAll('textarea[id^="textarea-"]');
        textareas.forEach(textarea => {
            const id = textarea.id;
            // Only initialize if not already initialized and still in DOM
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

    getTotalCommentCount() {
        let count = 0;
        const countReplies = (list) => {
            count += list.length;
            list.forEach(c => {
                if (c.replies) countReplies(c.replies);
            });
        };
        countReplies(this.comments);
        return count;
    }

    renderCommentForm(parentId = null, initialContent = '') {
        if (!this.isLoggedIn && !this.guestCommentEnabled) {
            return `
                <div class="bg-orange-light pd-md radius-16 border mb-2 border-orange d-flex-wrap just-between align-center gap-1">
                    <p class="font-bold text-orange">برای ثبت نظر و کسب امتیاز باید وارد حساب خود شوید</p>
                    <div class="d-flex gap-1">
                        <button class="btn btn-orange btn-sm" onclick="window.showAuthModal?.('login')">ورود به حساب</button>
                        <button class="btn btn-secondary btn-sm bg-block" onclick="window.showAuthModal?.('register')">عضویت رایگان</button>
                    </div>
                </div>
            `;
        }

        const suffix = parentId || 'main';
        return `
            <div class="comment-form ${parentId ? 'mt-3' : ''}" id="form-${suffix}">
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

                <textarea placeholder="دیدگاه تخصصی خود را اینجا بنویسید..." id="textarea-${suffix}">${initialContent}</textarea>
                <input type="text" id="hp-${suffix}" class="d-none" tabindex="-1" autocomplete="off">

                ${this.isLoggedIn ? `
                <div class="mention-tag-area mb-2" id="mention-area-${suffix}">
                    <div class="mention-input-wrapper relative d-flex-wrap gap-05 align-center" id="mentions-container-${suffix}">
                        <input type="text"
                               class="mention-tag-input font-size-0-8"
                               placeholder="منشن کردن کاربر..."
                               id="mention-input-${suffix}">
                        <div class="mention-suggestions d-none" id="suggestions-${suffix}"></div>
                    </div>
                </div>
                ` : ''}

                <div class="comment-image-upload d-none mb-2" id="image-upload-container-${suffix}">
                    <label for="comment-image-${suffix}" class="upload-zone d-flex align-center just-center gap-1 pd-md radius-12 border-dashed pointer transition-all">
                        <i data-lucide="image" class="text-gray icon-size-5"></i>
                        <div class="text-right">
                            <div class="font-bold font-size-2 text-title">آپلود تصویر تحلیل</div>
                            <div class="font-size-1 text-gray">فرمت‌های مجاز: PNG, JPG, WebP, AVIF</div>
                        </div>
                        <input type="file" id="comment-image-${suffix}" class="d-none comment-image-input" accept="image/*" data-suffix="${suffix}">
                    </label>
                    <div class="image-preview d-none mt-2 relative radius-12 overflow-hidden border" style="width: 100px; height: 100px;">
                        <img src="" class="w-full h-full object-cover">
                        <button type="button" class="remove-preview absolute top-0 left-0 m-05 radius-50 p-05" data-suffix="${suffix}">
                            <i data-lucide="x" class="icon-size-1"></i>
                        </button>
                    </div>
                </div>

                <div class="comment-form-footer">
                    <div>
                        ${!this.isLoggedIn ? '<span class="text-[10px] font-black text-amber-600 bg-amber-50 px-2 py-1 rounded border border-amber-100">در حال ارسال به عنوان مهمان</span>' : ''}
                    </div>
                    <button class="btn btn-primary submit-comment radius-10" data-parent="${parentId || ''}" data-edit="${initialContent ? 'true' : 'false'}">
                        ${initialContent ? 'بروزرسانی نظر' : 'ارسال نظر'}
                    </button>
                </div>
            </div>
        `;
    }

    renderComments(comments) {
        if (comments.length === 0) {
            return `
                <div class="bg-block text-center pd-md radius-16 border d-column just-center align-center">
                    <i data-lucide="message-circle" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                    <p class="text-gray-400">هنوز نظری ثبت نشده است. اولین تحلیل‌گر باشید!</p>
                </div>
            `;
        }
        return comments.map(c => this.renderCommentItem(c)).join('');
    }

    renderCommentItem(c, isReply = false) {
        const hasReplies = !isReply && ((c.replies && c.replies.length > 0) || (c.total_replies > 0));
        const showInlineReplies = hasReplies && (this.targetType !== 'user_profile' || this.isInsideModal);

        if (this.isInsideModal && isReply) {
            return this.renderMinimalReply(c);
        }
        const baseUrl = window.location.origin;
        const defaultAvatar = `${baseUrl}/assets/images/default-avatar.png`;

        let avatarUrl = c.user_avatar;
        if (avatarUrl) {
            if (!avatarUrl.startsWith('https')) {
                const path = avatarUrl.startsWith('/') ? avatarUrl.substring(1) : avatarUrl;
                avatarUrl = `${baseUrl}/${path}`;
            }
        } else {
            avatarUrl = defaultAvatar;
        }

        return `
            <div class="comment-wrapper ${hasReplies ? 'has-replies' : ''}" id="comment-wrapper-${c.id}">
                <div class="comment-item ${c.user_role === 'admin' || c.user_role === 'editor' ? 'is-expert' : ''} ${isReply ? 'is-reply' : ''}" id="comment-${c.id}">
                    <div class="comment-header">
                        <div class="comment-user-info">
                            <div class="avatar-container">
                                <img src="${avatarUrl}" class="comment-avatar" alt="${c.user_name}" onerror="this.src='${defaultAvatar}'">
                                <div class="online-dot"></div>
                            </div>
                            <div class="comment-meta">
                                ${c.user_id ? `
                                    <a href="/profile/${c.user_id}/${c.user_username || 'user'}" class="comment-author">
                                        ${c.user_name}
                                        <span class="user-level-badge level-${c.user_level || 1}">سطح ${c.user_level || 1}</span>
                                    </a>
                                ` : `
                                    <span class="comment-author">
                                        ${c.guest_name || 'مهمان'}
                                        <span class="user-level-badge !bg-slate-400">مهمان</span>
                                    </span>
                                `}

                                ${c.target_info ? `
                                    <div class="d-inline-flex font-bold font-size-0-8 mx-1">
                                        <span class="text-gray-400 ml-05">در </span>
                                        <a href="${c.target_info.url}" class="text-primary hover-underline">${c.target_info.title}</a>
                                    </div>
                                ` : ''}

                                <span class="comment-date">${c.created_at_fa || c.created_at}</span>
                            </div>
                        </div>
                        <div class="header-actions">
                            ${c.can_edit ? `<div class="comment-header-btn edit-btn" title="ویرایش" data-id="${c.id}"><i data-lucide="edit-3" class="icon-size-4"></i></div>` : ''}
                            <div class="comment-header-btn report-btn" title="گزارش تخلف" data-id="${c.id}"><i data-lucide="flag" class="icon-size-4"></i></div>
                            <div class="comment-header-btn comment-share-btn" title="کپی لینک مستقیم" data-id="${c.id}">
                                <i data-lucide="share-2" class="icon-size-3"></i>
                            </div>
                        </div>
                    </div>

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
                        <div class="view-thread-btn" data-id="${c.id}">
                            <i data-lucide="message-circle" class="icon-size-3"></i>
                            <span>${c.total_replies > 0 ? this.toPersianDigits(c.total_replies) + ' پاسخ' : 'مشاهده گفتگو'}</span>
                        </div>
                        ` : '')}
                        <div class="footer-right">
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
                        </div>
                        <div class="reactions-popover" id="popover-${c.id}">
                            <span class="emoji-btn" data-id="${c.id}" data-type="like">👍</span>
                            <span class="emoji-btn" data-id="${c.id}" data-type="heart">❤️</span>
                            <span class="emoji-btn" data-id="${c.id}" data-type="fire">🔥</span>
                            <span class="emoji-btn" data-id="${c.id}" data-type="dislike">👎</span>
                        </div>
                    </div>
                </div>
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

    renderMinimalReply(c) {
        const baseUrl = window.location.origin;
        const defaultAvatar = `${baseUrl}/assets/images/default-avatar.png`;
        let avatarUrl = c.user_avatar || defaultAvatar;
        if (avatarUrl && !avatarUrl.startsWith('http')) {
            avatarUrl = `${baseUrl}/${avatarUrl.startsWith('/') ? avatarUrl.substring(1) : avatarUrl}`;
        }

        return `
            <div class="minimal-reply border-bottom py-3" id="comment-${c.id}">
                <div class="d-flex gap-1">
                    <div class="shrink-0">
                        <img src="${avatarUrl}" class="radius-50 w-8 h-8 object-cover border" alt="${c.user_name}" onerror="this.src='${defaultAvatar}'">
                    </div>
                    <div class="grow-1">
                        <div class="d-flex just-between align-center mb-1">
                            <div class="d-flex align-center gap-05">
                                <span class="font-bold font-size-0-9">${c.user_name}</span>
                                <span class="text-gray-400 font-size-0-8">${c.created_at_fa || c.created_at}</span>
                            </div>
                            <div class="reaction-pill-mini">
                                ${this.renderReaction(c, 'like', '👍')}
                            </div>
                        </div>
                        <div class="comment-content font-size-0-9 text-gray-700">
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
                <div class="comment-attachment mt-2">
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

    renderPagination() {
        if (!this.totalPages || this.totalPages <= 1) return '';
        let html = '<div class="pagination mt-3 d-flex just-center gap-05">';
        for (let i = 1; i <= this.totalPages; i++) {
            html += `<button class="btn ${i === this.currentPage ? 'btn-primary' : 'btn-secondary'} btn-sm radius-8 page-btn" data-page="${i}">${this.toPersianDigits(i)}</button>`;
        }
        html += '</div>';
        return html;
    }

    toPersianDigits(num) {
        if (num === null || num === undefined) return '';
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return num.toString().replace(/\d/g, x => persian[x]);
    }

    updateStatsUI() {
        const countBadge = this.container.querySelector('.comments-count-badge');
        if (countBadge) countBadge.innerText = `(${this.toPersianDigits(this.totalCount)})`;
    }

    bindEvents() {
        this.initRichEditors();
        this.container.querySelectorAll('.submit-comment').forEach(btn => {
            btn.onclick = async () => {
                const parentId = btn.dataset.parent || null;
                const isEdit = btn.dataset.edit === 'true';
                const suffix = parentId || 'main';
                const form = document.getElementById(`form-${suffix}`);
                const typeInput = form.querySelector('input.comment-type-radio:checked');
                const type = typeInput ? typeInput.value : 'comment';
                const textarea = document.getElementById(`textarea-${suffix}`);
                const hp = document.getElementById(`hp-${suffix}`)?.value;
                if (hp) return; // Honeypot filled

                // Get content from Rich Editor if available
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
                }

                if (!this.isLoggedIn && this.guestCommentEnabled) {
                    if (!guestNameInput?.value.trim()) {
                        window.showAlert?.('لطفا نام خود را وارد کنید.', 'warning') || alert('لطفا نام خود را وارد کنید.');
                        return;
                    }
                    if (!guestEmailInput?.value.trim() || !guestEmailInput.value.includes('@')) {
                        window.showAlert?.('لطفا یک ایمیل معتبر وارد کنید.', 'warning') || alert('لطفا یک ایمیل معتبر وارد کنید.');
                        return;
                    }
                }
                btn.disabled = true;
                const originalText = btn.innerText;
                btn.innerText = 'در حال ارسال...';

                try {
                    const action = isEdit ? 'edit' : 'add';
                    let res;

                    if (action === 'add' && !isEdit) {
                        const formData = new FormData();
                        formData.append('content', content);
                        formData.append('type', type);
                        formData.append('mentions', JSON.stringify(mentionIds));
                        formData.append('hp', document.getElementById(`hp-${suffix}`)?.value || '');
                        formData.append('guest_name', guestNameInput?.value || '');
                        formData.append('guest_email', guestEmailInput?.value || '');
                        formData.append('target_id', this.targetId);
                        formData.append('target_type', this.targetType);
                        formData.append('parent_id', parentId || '');
                        if (parentId) formData.append('reply_to_id', parentId);

                        if (imageInput && imageInput.files[0]) {
                            formData.append('image', imageInput.files[0]);
                        }

                        res = await fetch(`/api/comments.php?action=add`, {
                            method: 'POST',
                            headers: { 'X-CSRF-Token': this.csrfToken },
                            body: formData
                        });
                    } else {
                        const payload = {
                            content,
                            mentions: mentionIds,
                            hp: document.getElementById(`hp-${suffix}`)?.value || '',
                            comment_id: parentId
                        };
                        res = await fetch(`/api/comments.php?action=edit`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfToken },
                            body: JSON.stringify(payload)
                        });
                    }
                    const data = await res.json();
                    if (data.success) {
                        if (editor) editor.clear();
                        textarea.value = '';
                        if (mentionsContainer) {
                            mentionsContainer.querySelectorAll('.mention-tag').forEach(tag => tag.remove());
                        }
                        if (imageInput) imageInput.value = '';
                        const previewContainer = document.querySelector(`#form-${suffix} .image-preview`);
                        if (previewContainer) {
                            previewContainer.classList.add('d-none');
                            previewContainer.querySelector('img').src = '';
                        }
                        if (isEdit) {
                            const commentItem = document.getElementById(`comment-${parentId}`);
                            if (commentItem) {
                                const comment = data.comment || this.findComment(parentId);
                                if (data.comment) this.updateCommentInCache(data.comment);
                                commentItem.querySelector('.comment-content').innerHTML = this.renderCommentBody(data.comment || comment);
                            }
                        } else {
                            const comment = data.comment;
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
                                const list = this.container.querySelector('.comment-list');
                                if (list.querySelector('.text-gray-400')) list.innerHTML = '';
                                list.insertAdjacentHTML('afterbegin', commentHtml);
                            }
                            this.totalCount = data.total_count;
                            this.updateStatsUI();
                        }
                        if (window.lucide) lucide.createIcons();
                        this.bindEvents();
                    } else {
                        window.showAlert?.(data.message, 'error') || alert(data.message);
                    }
                } catch (error) {
                    console.error(error);
                    window.showAlert?.('خطا در برقراری ارتباط با سرور', 'error') || alert('خطا در برقراری ارتباط با سرور');
                } finally {
                    btn.disabled = false;
                    btn.innerText = originalText;
                }
            };
        });

        this.container.querySelectorAll('.btn-react-trigger').forEach(btn => {
            btn.onclick = (e) => {
                e.stopPropagation();
                if (!this.isLoggedIn) { window.showAuthModal?.('login'); return; }
                const id = btn.dataset.id;
                const popover = document.getElementById(`popover-${id}`);
                const isShown = popover.classList.contains('show');
                this.container.querySelectorAll('.reactions-popover').forEach(p => p.classList.remove('show'));
                if (!isShown) popover.classList.add('show');
            };
        });

        this.container.querySelectorAll('.emoji-btn, .reaction-pill-item').forEach(btn => {
            btn.onclick = async (e) => {
                e.stopPropagation();
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
                        const comment = this.findComment(id);
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
                            if (reactionsHtml) {
                                pill.classList.remove('d-none');
                            } else {
                                pill.classList.add('d-none');
                            }
                            this.bindEvents();
                        }
                    } else if (data.message) {
                        window.showAlert?.(data.message, 'warning') || alert(data.message);
                    }
                } catch (error) { console.error(error); } finally { btn.classList.remove('loading'); }
            };
        });

        this.container.querySelectorAll('.reply-btn').forEach(btn => {
            btn.onclick = () => {
                if (!this.isLoggedIn && !this.guestCommentEnabled) { window.showAuthModal?.('login'); return; }
                const id = btn.dataset.id;
                const container = document.getElementById(`reply-form-container-${id}`);
                if (container.innerHTML === '') {
                    this.container.querySelectorAll('[id^="reply-form-container-"]').forEach(c => c.innerHTML = '');
                    container.innerHTML = this.renderCommentForm(id);
                    if (window.lucide) lucide.createIcons();
                    this.bindEvents();
                    container.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    container.innerHTML = '';
                }
            };
        });

        this.container.querySelectorAll('.comment-share-btn').forEach(btn => {
            btn.onclick = () => {
                const id = btn.dataset.id;
                const url = window.location.origin + window.location.pathname + '#comment-' + id;
                navigator.clipboard.writeText(url).then(() => {
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<i data-lucide="check" class="w-4 h-4 text-success"></i>';
                    if (window.lucide) lucide.createIcons();
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        if (window.lucide) lucide.createIcons();
                    }, 2000);
                });
            };
        });

        this.container.querySelectorAll('.edit-btn').forEach(btn => {
            btn.onclick = () => {
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
                if (window.lucide) lucide.createIcons();
                this.bindEvents();
            };
        });

        this.container.querySelectorAll('.report-btn').forEach(btn => {
            btn.onclick = async () => {
                if (!this.isLoggedIn) { window.showAuthModal?.('login'); return; }
                const id = btn.dataset.id;
                const reason = prompt('علت گزارش این نظر چیست؟');
                if (!reason) return;
                try {
                    const res = await fetch('/api/comments.php?action=report', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfToken },
                        body: JSON.stringify({ comment_id: id, reason: reason })
                    });
                    const data = await res.json();
                    window.showAlert?.(data.message, data.success ? 'success' : 'error') || alert(data.message);
                } catch (error) { console.error(error); }
            };
        });

        this.container.querySelectorAll('.comment-type-radio').forEach(radio => {
            radio.onchange = () => {
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
                if (uploadContainer) {
                    if (type === 'analysis') {
                        uploadContainer.classList.remove('d-none');
                    } else {
                        uploadContainer.classList.add('d-none');
                    }
                }
            };
        });

        this.container.querySelectorAll('.upload-zone').forEach(zone => {
            zone.ondragover = (e) => {
                e.preventDefault();
                zone.classList.add('drag-over');
            };
            zone.ondragleave = () => {
                zone.classList.remove('drag-over');
            };
            zone.ondrop = (e) => {
                e.preventDefault();
                zone.classList.remove('drag-over');
                const file = e.dataTransfer.files[0];
                const suffix = zone.querySelector('input').dataset.suffix;
                const input = document.getElementById(`comment-image-${suffix}`);
                if (file && input) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    input.files = dataTransfer.files;
                    input.dispatchEvent(new Event('change'));
                }
            };
        });

        this.container.querySelectorAll('.comment-image-input').forEach(input => {
            input.onchange = (e) => {
                const suffix = input.dataset.suffix;
                const form = input.closest('.comment-form');
                const file = e.target.files[0] || e.dataTransfer?.files[0];
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
            };
        });

        this.container.querySelectorAll('.remove-preview').forEach(btn => {
            btn.onclick = () => {
                const suffix = btn.dataset.suffix;
                const form = btn.closest('.comment-form');
                const input = document.getElementById(`comment-image-${suffix}`);
                const previewContainer = form ? form.querySelector('.image-preview') : null;
                if (input) input.value = '';
                if (previewContainer) {
                    previewContainer.classList.add('d-none');
                    previewContainer.querySelector('img').src = '';
                }
            };
        });

        this.container.querySelectorAll('.view-more-replies').forEach(btn => {
            btn.onclick = async () => {
                const id = btn.dataset.id;
                const total = parseInt(btn.dataset.total);
                if (btn.classList.contains('showing-all')) {
                    const list = document.querySelector(`#replies-container-${id} .replies-list`);
                    const allReplies = list.querySelectorAll('.comment-item');
                    for (let i = 3; i < allReplies.length; i++) allReplies[i].closest('.comment-wrapper')?.remove();
                    btn.innerText = `مشاهده پاسخ‌های بیشتر (${this.toPersianDigits(total - 3)})`;
                    btn.classList.remove('showing-all');
                    return;
                }
                btn.disabled = true;
                const originalText = btn.innerText;
                btn.innerText = 'در حال دریافت...';
                try {
                    const res = await fetch(`/api/comments.php?action=replies&parent_id=${id}&offset=3&limit=100`);
                    const data = await res.json();
                    if (data.success) {
                        const list = document.querySelector(`#replies-container-${id} .replies-list`);
                        data.replies.forEach(r => list.insertAdjacentHTML('beforeend', this.renderCommentItem(r, true)));
                        btn.innerText = 'پنهان کردن پاسخ‌ها';
                        btn.classList.add('showing-all');
                        if (window.lucide) lucide.createIcons();
                        this.bindEvents();
                    }
                } catch (error) { console.error(error); } finally { btn.disabled = false; }
            };
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.footer-right')) {
                this.container.querySelectorAll('.reactions-popover').forEach(p => p.classList.remove('show'));
            }
            if (!e.target.closest('.mention-input-wrapper')) {
                this.container.querySelectorAll('.mention-suggestions').forEach(s => s.classList.add('d-none'));
            }
        });

        this.initTagMentions();

        this.container.querySelectorAll('.view-thread-btn').forEach(btn => {
            btn.onclick = () => this.openThreadModal(btn.dataset.id);
        });

        // Filter and Sort Events
        this.container.querySelectorAll('.filter-btn').forEach(btn => {
            btn.onclick = () => {
                if (btn.classList.contains('active')) return;
                this.filterType = btn.dataset.filter;
                this.currentPage = 1;
                this.loadAndRender();
            };
        });

        this.container.querySelectorAll('.sort-btn').forEach(btn => {
            btn.onclick = () => {
                if (btn.classList.contains('active')) return;
                this.sort = btn.dataset.sort;
                this.currentPage = 1;
                this.loadAndRender();
            };
        });

        this.container.querySelectorAll('.page-btn').forEach(btn => {
            btn.onclick = () => {
                const page = parseInt(btn.dataset.page);
                if (page === this.currentPage) return;
                this.currentPage = page;
                this.loadAndRender();
                this.container.scrollIntoView({ behavior: 'smooth' });
            };
        });
    }

    initTagMentions() {
        this.container.querySelectorAll('.mention-tag-input').forEach(input => {
            const suffix = input.id.replace('mention-input-', '');
            const suggestionsEl = document.getElementById(`suggestions-${suffix}`);
            const containerEl = document.getElementById(`mentions-container-${suffix}`);
            const areaEl = document.getElementById(`mention-area-${suffix}`);
            if (!suggestionsEl || !containerEl) return;

            input.oninput = async () => {
                const q = input.value.trim();
                if (q.length < 1) { suggestionsEl.classList.add('d-none'); return; }
                const users = await this.searchUsers(q);
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
                    if (tags.length > 0) {
                        tags[tags.length - 1].remove();
                    }
                }
            };
            if (areaEl) {
                areaEl.onclick = () => input.focus();
            }
        });
    }

    async searchUsers(q) {
        try {
            const res = await fetch(`/api/users.php?action=search&q=${encodeURIComponent(q)}`);
            const data = await res.json();
            return data.success ? data.users : [];
        } catch (e) { return []; }
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
            item.onclick = () => {
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
        tag.querySelector('.remove-tag').onclick = (e) => {
            e.stopPropagation();
            tag.remove();
        };

        // Insert before the input field
        const input = container.querySelector('.mention-tag-input');
        if (input) {
            container.insertBefore(tag, input);
        } else {
            container.appendChild(tag);
        }
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
        const contentArea = modal.querySelector('.thread-content');
        const loader = modal.querySelector('.thread-loader');

        this.modalContentArea = contentArea;
        this.modalLoader = loader;

        this.modalContentArea.classList.add('d-none');
        this.modalLoader.classList.remove('d-none');

        const targetInfoArea = modal.querySelector('#modal-thread-target-info');
        if (targetInfoArea) targetInfoArea.classList.add('d-none');

        try {
            const res = await fetch(`/api/comments.php?action=thread&comment_id=${commentId}`);
            const data = await res.json();
            if (data.success) {
                const thread = data.thread;

                if (targetInfoArea && thread.target_info) {
                    targetInfoArea.innerHTML = `
                        <div class="d-flex align-center just-between w-full">
                            <div class="d-flex align-center gap-1">
                                <i data-lucide="external-link" class="text-gray icon-size-4"></i>
                                <div class="font-bold font-size-0-9">در <a href="${thread.target_info.url}" class="text-primary hover-underline">${thread.target_info.title}</a></div>
                            </div>
                        </div>
                    `;
                    targetInfoArea.classList.remove('d-none');
                }

                contentArea.innerHTML = `
                    <div class="thread-list">
                        ${this.renderCommentItem(thread)}
                    </div>
                `;
                this.modalLoader.classList.add('d-none');
                this.modalContentArea.classList.remove('d-none');
                if (window.lucide) lucide.createIcons();
                this.bindEventsInModal(contentArea);

                // Highlight the target comment in the thread
                const targetEl = contentArea.querySelector(`#comment-${commentId}`);
                if (targetEl) {
                    targetEl.classList.add('highlight-comment');
                    setTimeout(() => targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' }), 300);
                }
            } else {
                window.showAlert?.(data.message, 'error');
                this.closeModal(modal);
            }
        } catch (error) {
            console.error(error);
            this.closeModal(modal);
        }
    }

    bindEventsInModal(container) {
        // Limited subset of events for modal view
        container.querySelectorAll('.btn-react-trigger').forEach(btn => {
            btn.onclick = (e) => {
                e.stopPropagation();
                if (!this.isLoggedIn) { window.showAuthModal?.('login'); return; }
                const id = btn.dataset.id;
                const popover = container.querySelector(`#popover-${id}`);
                const isShown = popover.classList.contains('show');
                container.querySelectorAll('.reactions-popover').forEach(p => p.classList.remove('show'));
                if (!isShown) popover.classList.add('show');
            };
        });

        container.querySelectorAll('.emoji-btn, .reaction-pill-item').forEach(btn => {
            btn.onclick = async (e) => {
                e.stopPropagation();
                if (btn.classList.contains('loading')) return;
                if (!this.isLoggedIn) { window.showAuthModal?.('login'); return; }
                const id = btn.dataset.id;
                const type = btn.dataset.type;
                const res = await fetch('/api/comments.php?action=react', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfToken },
                    body: JSON.stringify({ comment_id: id, reaction_type: type })
                });
                const data = await res.json();
                if (data.success) {
                    // Update UI in modal (minimal sync)
                    this.loadAndRender(); // Update main list in background
                }
            };
        });

        container.querySelectorAll('.reply-btn').forEach(btn => {
            btn.onclick = () => {
                const modal = document.getElementById(this.threadModalId);
                this.closeModal(modal);
                // In a real Instagram-like experience, we'd show the form in modal.
                // For now, let's just close and let them reply if they are on the right page.
            };
        });
    }

    closeModal(modal) {
        this.isInsideModal = false;
        modal.querySelector('.modal-content').classList.add('closing');
        setTimeout(() => {
            modal.classList.add('d-none');
            modal.querySelector('.modal-content').classList.remove('closing');
        }, 300);
    }
}

window.initComments = (targetId, targetType) => {
    window.commentSystem = new CommentSystem({ targetId, targetType });
};
