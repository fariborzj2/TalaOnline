/**
 * Advanced Comment System Component
 */

class CommentSystem {
    constructor(options) {
        this.targetId = options.targetId;
        this.targetType = options.targetType;
        this.containerId = options.containerId || 'comments-app';
        this.container = document.getElementById(this.containerId);
        this.isLoggedIn = window.__AUTH_STATE__?.isLoggedIn || false;
        this.currentUsername = window.__AUTH_STATE__?.user?.username;
        this.csrfToken = window.__AUTH_STATE__?.csrfToken;
        this.comments = [];
        this.sentiment = { total: 0, bullish: 0, bearish: 0 };

        if (this.container) {
            this.init();
        }
    }

    async init() {
        this.renderSkeleton();

        // Lazy loading using IntersectionObserver
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
            }
        } catch (error) {
            console.error('Failed to load comments:', error);
        }
    }

    render() {
        if (!this.container) return;

        let html = `
            <div class="comments-section">
                <div class="flex items-center gap-2 mb-6">
                    <i data-lucide="messages-square" class="text-primary w-6 h-6"></i>
                    <h3 class="text-xl font-bold">نظرات کاربران (${this.getTotalCommentCount()})</h3>
                </div>

                ${this.targetType !== 'post' ? this.renderSentimentBar() : ''}

                ${this.renderCommentForm()}

                <div class="comment-list mt-8">
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
            <div class="sentiment-bar-container bg-block pd-md radius-16 border mb-6">
                <div class="sentiment-bar-info">
                    <span class="text-success flex items-center gap-1">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                        خوش‌بین (${Math.round(bullishPercent)}%)
                    </span>
                    <span class="text-error flex items-center gap-1">
                        <i data-lucide="trending-down" class="w-4 h-4"></i>
                        بدبین (${Math.round(bearishPercent)}%)
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
                <div class="bg-orange-light pd-md radius-16 border border-orange mb-4 text-center">
                    <p class="mb-3 font-bold text-orange">برای ثبت نظر و کسب امتیاز باید وارد حساب خود شوید</p>
                    <button class="btn btn-orange" onclick="window.showAuthModal?.('login')">ورود / ثبت‌نام سریع</button>
                </div>
            `;
        }

        return `
            <div class="comment-form ${parentId ? 'mt-3' : ''}" id="form-${parentId || 'main'}">
                <textarea placeholder="دیدگاه تخصصی خود را اینجا بنویسید (استفاده از @ برای منشن)..." id="textarea-${parentId || 'main'}">${initialContent}</textarea>
                <div class="comment-form-footer">
                    <div class="sentiment-selector">
                        ${this.targetType !== 'post' ? `
                            <div class="sentiment-option" data-sentiment="bullish">
                                <i data-lucide="trending-up" class="w-4 h-4"></i> خوش‌بین
                            </div>
                            <div class="sentiment-option" data-sentiment="bearish">
                                <i data-lucide="trending-down" class="w-4 h-4"></i> بدبین
                            </div>
                        ` : '<div></div>'}
                    </div>
                    <button class="btn btn-primary submit-comment" data-parent="${parentId || ''}" data-edit="${initialContent ? 'true' : 'false'}">
                        ${initialContent ? 'بروزرسانی نظر' : 'ارسال نظر'}
                    </button>
                </div>
            </div>
        `;
    }

    renderComments(comments) {
        if (comments.length === 0) {
            return `
                <div class="text-center py-12 bg-gray-50 radius-16 border border-dashed">
                    <i data-lucide="message-circle" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                    <p class="text-gray-400">هنوز نظری ثبت نشده است. اولین تحلیل‌گر باشید!</p>
                </div>
            `;
        }

        return comments.map(c => this.renderCommentItem(c)).join('');
    }

    renderCommentItem(c) {
        return `
            <div class="comment-item level-${c.user_level || 1}" id="comment-${c.id}">
                <div class="comment-header">
                    <div class="comment-user-info">
                        <img src="/${c.user_avatar || 'assets/images/default-avatar.png'}" class="comment-avatar" alt="${c.user_name}">
                        <div class="comment-meta">
                            <span class="comment-author">
                                ${c.user_name}
                                ${c.sentiment ? `<span class="comment-sentiment-badge ${c.sentiment}" title="${c.sentiment === 'bullish' ? 'خوش‌بین' : 'بدبین'}"></span>` : ''}
                                ${this.renderBadges(c)}
                            </span>
                            <span class="comment-date text-xs">${c.created_at}</span>
                        </div>
                    </div>
                    <div class="comment-share" title="کپی لینک مستقیم" data-id="${c.id}">
                        <i data-lucide="share-2" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="comment-content">
                    ${c.content_html}
                </div>
                <div class="comment-footer">
                    <div class="comment-actions">
                        <div class="comment-action-btn btn-react-trigger" data-id="${c.id}">
                            <i data-lucide="smile" class="w-4 h-4"></i>
                            <span>واکنش</span>
                        </div>
                        <div class="comment-reactions">
                            ${this.renderReaction(c, 'like', '👍')}
                            ${this.renderReaction(c, 'heart', '❤️')}
                            ${this.renderReaction(c, 'fire', '🔥')}
                            ${this.renderReaction(c, 'dislike', '👎')}
                        </div>
                        <div class="reactions-popover" id="popover-${c.id}">
                            <span class="emoji-btn" data-id="${c.id}" data-type="like">👍</span>
                            <span class="emoji-btn" data-id="${c.id}" data-type="heart">❤️</span>
                            <span class="emoji-btn" data-id="${c.id}" data-type="fire">🔥</span>
                            <span class="emoji-btn" data-id="${c.id}" data-type="dislike">👎</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        ${c.can_edit ? `
                            <div class="comment-action-btn btn-edit text-orange" data-id="${c.id}">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                                <span>ویرایش</span>
                            </div>
                        ` : ''}
                        <div class="comment-action-btn btn-report text-error" data-id="${c.id}">
                            <i data-lucide="flag" class="w-4 h-4"></i>
                            <span>گزارش</span>
                        </div>
                        <div class="comment-action-btn btn-reply" data-id="${c.id}">
                            <i data-lucide="reply" class="w-4 h-4"></i>
                            <span>پاسخ</span>
                        </div>
                    </div>
                </div>
                <div id="reply-form-container-${c.id}"></div>
                ${c.replies && c.replies.length > 0 ? `
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
            <span class="reaction-item ${comment.user_reaction === type ? 'active' : ''}" data-id="${comment.id}" data-type="${type}">
                ${emoji} <span class="text-xs">${count > 0 ? count : ''}</span>
            </span>
        `;
    }

    bindEvents() {
        // Sentiment Selector
        this.container.querySelectorAll('.sentiment-option').forEach(opt => {
            opt.onclick = () => {
                const parent = opt.parentElement;
                const isSelected = opt.classList.contains('selected');
                parent.querySelectorAll('.sentiment-option').forEach(o => o.classList.remove('selected'));
                if (!isSelected) opt.classList.add('selected');
            };
        });

        // Submit Comment
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
                    const payload = {
                        content: content
                    };

                    if (isEdit) {
                        payload.comment_id = parentId; // In edit mode, parentId is actually the comment id
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

        // Reaction Trigger
        this.container.querySelectorAll('.btn-react-trigger').forEach(btn => {
            btn.onclick = (e) => {
                e.stopPropagation();
                const id = btn.dataset.id;
                const popover = document.getElementById(`popover-${id}`);
                const isShown = popover.classList.contains('show');

                // Close all other popovers
                this.container.querySelectorAll('.reactions-popover').forEach(p => p.classList.remove('show'));

                if (!isShown) popover.classList.add('show');
            };
        });

        // Emoji Click & Reaction Removal
        this.container.querySelectorAll('.emoji-btn, .reaction-item').forEach(btn => {
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

        // Reply Button
        this.container.querySelectorAll('.btn-reply').forEach(btn => {
            btn.onclick = () => {
                if (!this.isLoggedIn) {
                    window.showAuthModal?.('login');
                    return;
                }
                const id = btn.dataset.id;
                const container = document.getElementById(`reply-form-container-${id}`);
                if (container.innerHTML === '') {
                    // Close other reply forms
                    this.container.querySelectorAll('[id^="reply-form-container-"]').forEach(c => c.innerHTML = '');

                    container.innerHTML = this.renderCommentForm(id);
                    if (window.lucide) lucide.createIcons();
                    this.bindEvents(); // Re-bind for the new form
                    container.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    container.innerHTML = '';
                }
            };
        });

        // Edit Button
        this.container.querySelectorAll('.btn-edit').forEach(btn => {
            btn.onclick = () => {
                const id = btn.dataset.id;
                const comment = this.findComment(id);
                const container = document.getElementById(`reply-form-container-${id}`);

                if (container.innerHTML === '') {
                    this.container.querySelectorAll('[id^="reply-form-container-"]').forEach(c => c.innerHTML = '');
                    container.innerHTML = this.renderCommentForm(id, comment.content);
                    if (window.lucide) lucide.createIcons();
                    this.bindEvents();
                    container.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    container.innerHTML = '';
                }
            };
        });

        // Report Button
        this.container.querySelectorAll('.btn-report').forEach(btn => {
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

        // Share Button (Direct Link)
        this.container.querySelectorAll('.comment-share').forEach(btn => {
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

        // Close popovers on click outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.comment-actions')) {
                this.container.querySelectorAll('.reactions-popover').forEach(p => p.classList.remove('show'));
            }
        });
    }

    renderBadges(c) {
        const level = c.user_level || 1;
        let badges = `<span class="comment-badge">سطح ${level}</span>`;

        if (level >= 5) {
            badges += `<span class="comment-badge bg-primary text-white" title="تحلیل‌گر برتر">
                <i data-lucide="award" class="w-3 h-3 inline-block"></i> تحلیل‌گر برتر
            </span>`;
        } else if (level >= 3) {
            badges += `<span class="comment-badge bg-secondary text-primary" title="کاربر فعال">
                <i data-lucide="zap" class="w-3 h-3 inline-block"></i> فعال
            </span>`;
        }

        return badges;
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

// Global initialization helper
window.initComments = (targetId, targetType) => {
    new CommentSystem({ targetId, targetType });
};
