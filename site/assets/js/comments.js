/**
 * Advanced Comment System Component - Refined to match UI/UX design
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
        this.readOnly = options.readOnly || (this.targetType === 'user_profile');

        if (options.initialComments) {
            this.render();
        } else {
            // Check if already rendered by server
            if (this.container.querySelector('.comment-item')) {
                this.bindEvents();
                this.handleAnchorScroll();
            } else {
                this.init();
            }
        }

        // Listen for global auth changes
        document.addEventListener('auth:status-changed', (e) => {
            const state = e.detail;
            this.isLoggedIn = state.isLoggedIn;
            this.currentUsername = state.user?.username;
            this.csrfToken = state.csrfToken;
            this.render();
        });
    }

    async init() {
        // Strictly non-AJAX for initial load as per requirements
        if (this.container.querySelector('.comment-item')) return;

        // If no comments and not rendered by server, we just bind events to the form
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
            // Give a small delay for DOM to settle and images to load
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

    renderSkeleton() {
        this.container.innerHTML = `<div class="pd-md text-center text-gray-400">در حال بارگذاری بخش نظرات...</div>`;
    }

    async loadComments() {
        try {
            const response = await fetch(`/api/comments.php?action=list&target_id=${this.targetId}&target_type=${this.targetType}`);
            const data = await response.json();
            if (data.success) {
                this.comments = data.comments;
                this.totalCount = data.total_count;
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

                <div class="comment-list ${this.readOnly ? 'mt-0' : 'mt-8'}">
                    ${this.renderComments(this.comments)}
                </div>
            </div>
        `;

        this.container.innerHTML = html;
        if (window.lucide) lucide.createIcons();
        this.bindEvents();
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
        if (!this.isLoggedIn) {
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

        return `
            <div class="comment-form ${parentId ? 'mt-3' : ''}" id="form-${parentId || 'main'}">
                <textarea placeholder="دیدگاه تخصصی خود را اینجا بنویسید (استفاده از @ برای منشن)..." id="textarea-${parentId || 'main'}">${initialContent}</textarea>
                <div class="comment-form-footer">
                    <div></div>
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
        const isExpert = c.user_role === 'admin' || c.user_role === 'editor';
        const hasReplies = !isReply && ((c.replies && c.replies.length > 0) || (c.total_replies > 0));
        const baseUrl = window.location.origin;
        const defaultAvatar = `${baseUrl}/assets/images/default-avatar.png`;

        let avatarUrl = c.user_avatar;
        if (avatarUrl) {
            if (!avatarUrl.startsWith('https')) {
                // Ensure no double slashes
                const path = avatarUrl.startsWith('/') ? avatarUrl.substring(1) : avatarUrl;
                avatarUrl = `${baseUrl}/${path}`;
            }
        } else {
            avatarUrl = defaultAvatar;
        }

        const replyPreview = c.reply_to_content ? `
            <div class="reply-preview-block">
                <div>در پاسخ به <a href="/profile/${c.reply_to_user_id}/${c.reply_to_username || 'user'}" class="reply-preview-author">@${c.reply_to_username || 'user'}</a></div>
                <div class="reply-preview-content">${c.reply_to_content.substring(0, 100)}${c.reply_to_content.length > 100 ? '...' : ''}</div>
            </div>
        ` : '';

        return `
            <div class="comment-wrapper ${hasReplies ? 'has-replies' : ''}" id="comment-wrapper-${c.id}">
                <div class="comment-item ${isExpert ? 'is-expert' : ''} ${isReply ? 'is-reply' : ''}" id="comment-${c.id}">
                    <div class="comment-header">
                        <div class="comment-user-info">
                            <div class="avatar-container">
                                <img src="${avatarUrl}"
                                     class="comment-avatar"
                                     alt="${c.user_name}"
                                     onerror="this.src='${defaultAvatar}'">
                                <div class="online-dot"></div>
                            </div>
                            <div class="comment-meta">
                                <a href="/profile/${c.user_id}/${c.user_username || 'user'}" class="comment-author">
                                    ${c.user_name}
                                    <span class="user-level-badge level-${c.user_level || 1}">سطح ${c.user_level || 1}</span>
                                </a>
                                ${c.target_info ? `<span class="text-gray-400 font-size-0-8 mx-1">در</span> <a href="${c.target_info.url}" class="text-primary hover-underline d-inline-block ltr font-size-0-8">${c.target_info.title}</a>` : ''}
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
                        ${replyPreview}
                        <div class="comment-body-text">${c.content_html}</div>
                        ${isExpert ? `<div class="attachment-btn"><i data-lucide="file-text" class="icon-size-4"></i> مشاهده پیوست</div>` : ''}
                    </div>

                    <div class="comment-footer">
                        ${!this.readOnly ? `
                        <div class="comment-footer-btn reply-btn" data-id="${c.id}">
                            <i data-lucide="reply" class="icon-size-4"></i>
                            <span>پاسخ</span>
                        </div>
                        ` : ''}

                        <div class="footer-right">
                            <div class="reaction-pill">
                                ${this.renderReaction(c, 'like', '👍')}
                                ${this.renderReaction(c, 'heart', '❤️')}
                                ${this.renderReaction(c, 'fire', '🔥')}
                                ${this.renderReaction(c, 'dislike', '👎')}
                            </div>
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

                ${!isReply ? `
                    <div class="replies-container" id="replies-container-${c.id}">
                        <div class="replies-list">
                            ${c.replies ? c.replies.map(r => this.renderCommentItem(r, true)).join('') : ''}
                        </div>
                        ${c.total_replies > 3 ? `
                            <button class="btn btn-sm btn-secondary w-full mt-2 view-more-replies"
                                    data-id="${c.id}"
                                    data-total="${c.total_replies}">
                                مشاهده پاسخ‌های بیشتر (${this.toPersianDigits(c.total_replies - 3)})
                            </button>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
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

    updateStatsUI() {
        const countBadge = this.container.querySelector('.comments-count-badge');
        if (countBadge) {
            countBadge.innerText = `(${this.toPersianDigits(this.totalCount)})`;
        }
    }

    bindEvents() {
        this.container.querySelectorAll('.submit-comment').forEach(btn => {
            btn.onclick = async () => {
                const parentId = btn.dataset.parent || null;
                const isEdit = btn.dataset.edit === 'true';
                const suffix = parentId || 'main';
                const textarea = document.getElementById(`textarea-${suffix}`);
                const content = textarea.value;

                if (!content.trim()) return;

                btn.disabled = true;
                const originalText = btn.innerText;
                btn.innerText = 'در حال ارسال...';

                try {
                    const action = isEdit ? 'edit' : 'add';
                    const payload = { content: content };

                    if (isEdit) {
                        payload.comment_id = parentId;
                    } else {
                        payload.target_id = this.targetId;
                        payload.target_type = this.targetType;
                        payload.parent_id = parentId;
                        if (parentId) {
                            payload.reply_to_id = parentId;
                        }
                    }

                    const res = await fetch(`/api/comments.php?action=${action}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': this.csrfToken
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (data.success) {
                        textarea.value = '';
                        if (isEdit) {
                            // Update existing comment in DOM
                            const commentItem = document.getElementById(`comment-${parentId}`);
                            if (commentItem) {
                                commentItem.querySelector('.comment-content').innerHTML = data.content_html;
                                // Update internal state if necessary
                                const comment = this.findComment(parentId);
                                if (comment) comment.content = data.content;
                            }
                        } else {
                            // Add new comment to DOM
                            const comment = data.comment;
                            const commentHtml = this.renderCommentItem(comment, !!comment.parent_id);

                            if (parentId) {
                                // Hide the form that was used
                                const formContainer = document.getElementById(`reply-form-container-${parentId}`);
                                if (formContainer) formContainer.innerHTML = '';

                                // Always use the ACTUAL parent_id from server (enforces depth limit 1)
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
                                const list = repliesContainer.querySelector('.replies-list');
                                list.insertAdjacentHTML('beforeend', commentHtml);
                            } else {
                                // It's a top-level comment
                                const list = this.container.querySelector('.comment-list');
                                // Remove "no comments" message if present
                                if (list.querySelector('.text-gray-400')) {
                                    list.innerHTML = '';
                                }
                                list.insertAdjacentHTML('afterbegin', commentHtml);
                            }

                            // Update count
                            this.totalCount = data.total_count;
                            this.updateStatsUI();
                        }

                        if (window.lucide) lucide.createIcons();
                        this.bindEvents(); // Re-bind events for new elements
                    } else {
                        if (window.showAlert) {
                            window.showAlert(data.message, 'error');
                        } else {
                            alert(data.message);
                        }
                    }
                } catch (error) {
                    console.error(error);
                    if (window.showAlert) {
                        window.showAlert('خطا در برقراری ارتباط با سرور', 'error');
                    } else {
                        alert('خطا در برقراری ارتباط با سرور');
                    }
                } finally {
                    btn.disabled = false;
                    btn.innerText = originalText;
                }
            };
        });

        this.container.querySelectorAll('.btn-react-trigger').forEach(btn => {
            btn.onclick = (e) => {
                e.stopPropagation();
                if (!this.isLoggedIn) {
                    window.showAuthModal?.('login');
                    return;
                }
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
                if (!this.isLoggedIn) {
                    window.showAuthModal?.('login');
                    return;
                }
                const id = btn.dataset.id;
                const type = btn.dataset.type;
                const comment = this.findComment(id);
                const currentReaction = comment ? comment.user_reaction : null;
                const newType = (currentReaction === type) ? null : type;

                btn.classList.add('loading');

                try {
                    const res = await fetch('/api/comments.php?action=react', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': this.csrfToken
                        },
                        body: JSON.stringify({ comment_id: id, reaction_type: newType })
                    });
                    const data = await res.json();
                    if (data.success) {
                        // Update DOM directly
                        const commentId = id;
                        const counts = data.counts;
                        const userReaction = data.user_reaction;

                        const comment = this.findComment(commentId);
                        if (comment) {
                            comment.likes = counts.likes;
                            comment.dislikes = counts.dislikes;
                            comment.hearts = counts.hearts;
                            comment.fires = counts.fires;
                            comment.user_reaction = userReaction;
                        }

                        const pill = document.querySelector(`#comment-${commentId} .reaction-pill`);
                        if (pill) {
                            pill.innerHTML = `
                                ${this.renderReaction({id: commentId, likes: counts.likes, user_reaction: userReaction}, 'like', '👍')}
                                ${this.renderReaction({id: commentId, hearts: counts.hearts, user_reaction: userReaction}, 'heart', '❤️')}
                                ${this.renderReaction({id: commentId, fires: counts.fires, user_reaction: userReaction}, 'fire', '🔥')}
                                ${this.renderReaction({id: commentId, dislikes: counts.dislikes, user_reaction: userReaction}, 'dislike', '👎')}
                            `;
                            this.bindEvents(); // Re-bind for new reaction items
                        }
                    } else if (data.message) {
                        if (window.showAlert) {
                            window.showAlert(data.message, 'warning');
                        } else {
                            alert(data.message);
                        }
                    }
                } catch (error) {
                    console.error(error);
                } finally {
                    btn.classList.remove('loading');
                }
            };
        });

        this.container.querySelectorAll('.reply-btn').forEach(btn => {
            btn.onclick = () => {
                if (!this.isLoggedIn) {
                    window.showAuthModal?.('login');
                    return;
                }
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
                const originalHtml = body.innerHTML;

                // Show form instead of body (use content_edit which has @usernames)
                body.innerHTML = this.renderCommentForm(id, comment.content_edit || comment.content);
                if (window.lucide) lucide.createIcons();
                this.bindEvents();
            };
        });

        this.container.querySelectorAll('.report-btn').forEach(btn => {
            btn.onclick = async () => {
                if (!this.isLoggedIn) {
                    window.showAuthModal?.('login');
                    return;
                }
                const id = btn.dataset.id;
                const reason = prompt('علت گزارش این نظر چیست؟');
                if (!reason) return;

                try {
                    const res = await fetch('/api/comments.php?action=report', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': this.csrfToken
                        },
                        body: JSON.stringify({ comment_id: id, reason: reason })
                    });
                    const data = await res.json();
                    if (window.showAlert) {
                        window.showAlert(data.message, data.success ? 'success' : 'error');
                    } else {
                        alert(data.message);
                    }
                } catch (error) {
                    console.error(error);
                }
            };
        });

        this.container.querySelectorAll('.view-more-replies').forEach(btn => {
            btn.onclick = async () => {
                const id = btn.dataset.id;
                const total = parseInt(btn.dataset.total);

                if (btn.classList.contains('showing-all')) {
                    // Collapse
                    const list = document.querySelector(`#replies-container-${id} .replies-list`);
                    const allReplies = list.querySelectorAll('.comment-item');
                    for (let i = 3; i < allReplies.length; i++) {
                        allReplies[i].closest('.comment-wrapper')?.remove();
                    }
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
                        data.replies.forEach(r => {
                            list.insertAdjacentHTML('beforeend', this.renderCommentItem(r, true));
                        });
                        btn.innerText = 'پنهان کردن پاسخ‌ها';
                        btn.classList.add('showing-all');
                        if (window.lucide) lucide.createIcons();
                        this.bindEvents();
                    }
                } catch (error) {
                    console.error(error);
                } finally {
                    btn.disabled = false;
                }
            };
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.footer-right')) {
                this.container.querySelectorAll('.reactions-popover').forEach(p => p.classList.remove('show'));
            }
            if (!e.target.closest('.mentions-autocomplete')) {
                document.querySelectorAll('.mentions-autocomplete').forEach(a => a.remove());
            }
        });

        // Initialize autocomplete for textareas
        this.container.querySelectorAll('textarea').forEach(textarea => {
            this.initAutocomplete(textarea);
        });
    }

    initAutocomplete(textarea) {
        let autocompleteList = null;
        let selectedIndex = -1;
        let query = "";
        let mentionStartPos = -1;

        textarea.addEventListener('input', async (e) => {
            const val = textarea.value;
            const cursor = textarea.selectionStart;
            const beforeCursor = val.substring(0, cursor);
            const lastAt = beforeCursor.lastIndexOf('@');

            if (lastAt !== -1 && !/\s/.test(beforeCursor.substring(lastAt + 1))) {
                query = beforeCursor.substring(lastAt + 1);
                mentionStartPos = lastAt;

                if (query.length >= 1) {
                    const users = await this.searchUsers(query);
                    if (users.length > 0) {
                        this.showAutocomplete(textarea, users, lastAt);
                        return;
                    }
                }
            }
            this.removeAutocomplete();
        });

        textarea.addEventListener('keydown', (e) => {
            if (!this.autocompleteList) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.moveSelection(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.moveSelection(-1);
            } else if (e.key === 'Enter' || e.key === 'Tab') {
                if (this.selectedIndex !== -1) {
                    e.preventDefault();
                    this.selectUser(textarea, mentionStartPos);
                }
            } else if (e.key === 'Escape') {
                this.removeAutocomplete();
            }
        });
    }

    async searchUsers(q) {
        try {
            const res = await fetch(`/api/users.php?action=search&q=${encodeURIComponent(q)}`);
            const data = await res.json();
            return data.success ? data.users : [];
        } catch (e) {
            return [];
        }
    }

    showAutocomplete(textarea, users, pos) {
        this.removeAutocomplete();

        const rect = textarea.getBoundingClientRect();
        const list = document.createElement('div');
        list.className = 'mentions-autocomplete';

        // Position roughly near the cursor
        // A more advanced solution would use a hidden mirror div to find exact cursor coordinates
        list.style.position = 'fixed';
        list.style.top = (rect.top + 30) + 'px';
        list.style.left = (rect.left + 10) + 'px';
        list.style.width = (rect.width - 20) + 'px';
        list.style.maxWidth = '300px';
        list.style.zIndex = '1000';

        users.forEach((user, index) => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.innerHTML = `
                <img src="${user.avatar}" class="autocomplete-avatar">
                <div class="autocomplete-info">
                    <div class="autocomplete-name">${user.name}</div>
                    <div class="autocomplete-username">@${user.username}</div>
                </div>
            `;
            item.onclick = () => {
                this.selectedIndex = index;
                this.selectUser(textarea, pos);
            };
            list.appendChild(item);
        });

        document.body.appendChild(list);
        this.autocompleteList = list;
        this.autocompleteUsers = users;
        this.selectedIndex = 0;
        this.updateSelection();
    }

    removeAutocomplete() {
        if (this.autocompleteList) {
            this.autocompleteList.remove();
            this.autocompleteList = null;
        }
        this.selectedIndex = -1;
    }

    moveSelection(dir) {
        this.selectedIndex += dir;
        if (this.selectedIndex < 0) this.selectedIndex = this.autocompleteUsers.length - 1;
        if (this.selectedIndex >= this.autocompleteUsers.length) this.selectedIndex = 0;
        this.updateSelection();
    }

    updateSelection() {
        const items = this.autocompleteList.querySelectorAll('.autocomplete-item');
        items.forEach((item, i) => {
            item.classList.toggle('active', i === this.selectedIndex);
        });
    }

    selectUser(textarea, pos) {
        const user = this.autocompleteUsers[this.selectedIndex];
        const val = textarea.value;
        const before = val.substring(0, pos);
        const cursor = textarea.selectionStart;
        const after = val.substring(cursor);

        textarea.value = before + '@' + user.username + ' ' + after;
        textarea.focus();
        const newCursor = pos + user.username.length + 2;
        textarea.setSelectionRange(newCursor, newCursor);
        this.removeAutocomplete();
    }

    findComment(id) {
        let found = null;
        const search = (list) => {
            for (const c of list) {
                if (c.id == id) { found = c; return; }
                if (c.replies) search(c.replies);
                if (found) return;
            }
        };
        search(this.comments);
        return found;
    }
}

window.initComments = (targetId, targetType) => {
    window.commentSystem = new CommentSystem({ targetId, targetType });
};
