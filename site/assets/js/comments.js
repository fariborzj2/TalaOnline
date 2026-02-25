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
        this.sentiment = initialData?.sentiment || { total: 0, bullish: 0, bearish: 0 };
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
    }

    async init() {
        // Only load via AJAX if not already present
        if (this.container.querySelector('.comment-item')) return;

        this.renderSkeleton();
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                this.loadAndRender();
                observer.disconnect();
            }
        }, { threshold: 0.1 });

        observer.observe(this.container);
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
                this.sentiment = data.sentiment;
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

                ${this.targetType !== 'post' ? this.renderSentimentBar() : ''}

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

    renderSentimentBar() {
        const bullishPercent = this.sentiment.total > 0 ? (this.sentiment.bullish / this.sentiment.total * 100) : 50;
        const bearishPercent = this.sentiment.total > 0 ? (this.sentiment.bearish / this.sentiment.total * 100) : 50;

        return `
            <div class="sentiment-bar-container">
                <div class="sentiment-bar-info">
                    <span class="text-success d-flex align-center gap-1">
                        <i data-lucide="trending-up" class="icon-size-4"></i>
                        خوش‌بین (${this.toPersianDigits(Math.round(bullishPercent))}%)
                    </span>
                    <span class="text-error d-flex align-center gap-1">
                        <i data-lucide="trending-down" class="icon-size-4"></i>
                        بدبین (${this.toPersianDigits(Math.round(bearishPercent))}%)
                    </span>
                </div>
                <div class="sentiment-bar">
                    <div class="sentiment-bullish" style="width: ${bullishPercent}%"></div>
                    <div class="sentiment-bearish" style="width: ${bearishPercent}%"></div>
                </div>
            </div>
        `;
    }

    renderCommentForm(parentId = null, initialContent = '') {
        if (!this.isLoggedIn) {
            return `
                <div class="bg-orange-light pd-md radius-24 border border-orange mb-4 text-center">
                    <p class="mb-3 font-bold text-orange">برای ثبت نظر و کسب امتیاز باید وارد حساب خود شوید</p>
                    <button class="btn btn-orange" onclick="window.showAuthModal?.('login')">ورود / ثبت‌نام سریع</button>
                </div>
            `;
        }

        const showSentiment = !parentId && this.targetType !== 'post';

        return `
            <div class="comment-form ${parentId ? 'mt-3' : ''}" id="form-${parentId || 'main'}">
                <textarea placeholder="دیدگاه تخصصی خود را اینجا بنویسید (استفاده از @ برای منشن)..." id="textarea-${parentId || 'main'}">${initialContent}</textarea>
                <div class="comment-form-footer">
                    <div class="sentiment-selector">
                        ${showSentiment ? `
                            <div class="sentiment-option" data-sentiment="bullish">
                                <i data-lucide="trending-up" class="w-4 h-4"></i> خوش‌بین
                            </div>
                            <div class="sentiment-option" data-sentiment="bearish">
                                <i data-lucide="trending-down" class="w-4 h-4"></i> بدبین
                            </div>
                        ` : '<div></div>'}
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
                <div class="text-center py-12 bg-gray-50 radius-24 border border-dashed">
                    <i data-lucide="message-circle" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                    <p class="text-gray-400">هنوز نظری ثبت نشده است. اولین تحلیل‌گر باشید!</p>
                </div>
            `;
        }

        return comments.map(c => this.renderCommentItem(c)).join('');
    }

    renderCommentItem(c) {
        const isExpert = c.user_role === 'admin' || c.user_role === 'editor';
        const hasReplies = c.replies && c.replies.length > 0;
        const baseUrl = window.location.origin;
        const defaultAvatar = `${baseUrl}/assets/images/default-avatar.png`;

        let avatarUrl = c.user_avatar;
        if (avatarUrl) {
            if (!avatarUrl.startsWith('http')) {
                // Ensure no double slashes
                const path = avatarUrl.startsWith('/') ? avatarUrl.substring(1) : avatarUrl;
                avatarUrl = `${baseUrl}/${path}`;
            }
        } else {
            avatarUrl = defaultAvatar;
        }

        return `
            <div class="comment-wrapper ${hasReplies ? 'has-replies' : ''}" id="comment-wrapper-${c.id}">
                <div class="comment-item ${isExpert ? 'is-expert' : ''}" id="comment-${c.id}">
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
                                <span class="comment-author">
                                    ${c.user_name}
                                    <span class="user-level-badge level-${c.user_level || 1}">سطح ${c.user_level || 1}</span>
                                    ${c.sentiment ? `<span class="comment-sentiment-badge ${c.sentiment}" title="${c.sentiment === 'bullish' ? 'خوش‌بین' : 'بدبین'}"></span>` : ''}
                                </span>
                                ${c.target_info ? `<span class="text-gray-400 font-size-0-8 mx-1">در</span> <a href="${c.target_info.url}" class="text-primary hover-underline font-size-0-8">${c.target_info.title}</a>` : ''}
                                <span class="comment-date">${c.created_at_fa || c.created_at}</span>
                            </div>
                        </div>
                        <div class="header-actions">
                            ${c.can_edit ? `<div class="comment-header-btn edit-btn" title="ویرایش" data-id="${c.id}"><i data-lucide="edit-3" class="icon-size-4"></i></div>` : ''}
                            <div class="comment-header-btn report-btn" title="گزارش تخلف" data-id="${c.id}"><i data-lucide="flag" class="icon-size-4"></i></div>
                            <div class="comment-header-btn comment-share-btn" title="کپی لینک مستقیم" data-id="${c.id}">
                                <i data-lucide="share-2" class="icon-size-4"></i>
                            </div>
                        </div>
                    </div>

                    <div class="comment-content">
                        ${c.content_html}
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

                ${hasReplies ? `
                    <div class="replies-container">
                        ${c.replies.map(r => this.renderCommentItem(r)).join('')}
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

    bindEvents() {
        this.container.querySelectorAll('.sentiment-option').forEach(opt => {
            opt.onclick = () => {
                const parent = opt.parentElement;
                const isSelected = opt.classList.contains('selected');
                parent.querySelectorAll('.sentiment-option').forEach(o => o.classList.remove('selected'));
                if (!isSelected) opt.classList.add('selected');
            };
        });

        this.container.querySelectorAll('.submit-comment').forEach(btn => {
            btn.onclick = async () => {
                const parentId = btn.dataset.parent || null;
                const isEdit = btn.dataset.edit === 'true';
                const suffix = parentId || 'main';
                const textarea = document.getElementById(`textarea-${suffix}`);
                const content = textarea.value;
                const sentiment = document.querySelector(`#form-${suffix} .sentiment-option.selected`)?.dataset.sentiment || null;

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
                        payload.sentiment = sentiment;
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
                        await this.loadComments();
                        this.render();
                    } else {
                        alert(data.message);
                    }
                } catch (error) {
                    console.error(error);
                    alert('خطا در برقراری ارتباط با سرور');
                } finally {
                    btn.disabled = false;
                    btn.innerText = originalText;
                }
            };
        });

        this.container.querySelectorAll('.btn-react-trigger').forEach(btn => {
            btn.onclick = (e) => {
                e.stopPropagation();
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
                if (!this.isLoggedIn) {
                    window.showAuthModal?.('login');
                    return;
                }
                const id = btn.dataset.id;
                const type = btn.dataset.type;
                const comment = this.findComment(id);
                const currentReaction = comment ? comment.user_reaction : null;
                const newType = (currentReaction === type) ? null : type;

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
                        await this.loadComments();
                        this.render();
                    }
                } catch (error) {
                    console.error(error);
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

                // Show form instead of body
                body.innerHTML = this.renderCommentForm(id, comment.content);
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
                    alert(data.message);
                } catch (error) {
                    console.error(error);
                }
            };
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.footer-right')) {
                this.container.querySelectorAll('.reactions-popover').forEach(p => p.classList.remove('show'));
            }
        });
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
    new CommentSystem({ targetId, targetType });
};
