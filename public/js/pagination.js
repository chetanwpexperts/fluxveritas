document.addEventListener('DOMContentLoaded', function () {

    /* ===== PAGINATION HELPER CLASS ===== */

    class AjaxPaginator {
        constructor(options) {
            this.container = document.querySelector(options.container);
            this.paginationWrapper = document.querySelector(options.paginationWrapper);
            this.url = options.url;
            this.perPage = options.perPage || 15;
            this.currentPage = 1;
            this.totalPages = 1;
            this.totalRecords = 0;
            this.extraParams = options.extraParams || {};
            this.onRender = options.onRender;
            this.onLoad = options.onLoad;
        }

        init() {
            this.load(1);
        }

        load(page) {
            if (!this.container) return;
            this.currentPage = page;
            this.showLoading();

            const params = new URLSearchParams({
                page: page,
                per_page: this.perPage,
                ...this.extraParams
            });

            fetch(`${this.url}?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content
                }
            })
            .then(r => r.json())
            .then(data => {
                this.totalPages = data.last_page || 1;
                this.totalRecords = data.total || 0;
                this.perPage = data.per_page || this.perPage;

                if (this.onRender) {
                    this.container.innerHTML = this.onRender(data.data);
                }

                if (this.onLoad) {
                    this.onLoad(data);
                }

                this.renderPagination();
                this.hideLoading();
            })
            .catch(() => {
                this.hideLoading();
            });
        }

        renderPagination() {
            if (!this.paginationWrapper) return;

            const from = this.totalRecords === 0 ? 0 : ((this.currentPage - 1) * this.perPage) + 1;
            const to = Math.min(this.currentPage * this.perPage, this.totalRecords);

            let pagesHtml = '';

            pagesHtml += `<button class="pg-btn pg-btn-prev"
                ${this.currentPage === 1 ? 'disabled' : ''}
                data-page="${this.currentPage - 1}">&#8592;</button>`;

            const pages = this.getPageNumbers();
            pages.forEach(p => {
                if (p === '...') {
                    pagesHtml += `<span class="pg-ellipsis">...</span>`;
                } else {
                    pagesHtml += `<button class="pg-btn${p === this.currentPage ? ' pg-btn-active' : ''}"
                        data-page="${p}">${p}</button>`;
                }
            });

            pagesHtml += `<button class="pg-btn pg-btn-next"
                ${this.currentPage === this.totalPages ? 'disabled' : ''}
                data-page="${this.currentPage + 1}">&#8594;</button>`;

            this.paginationWrapper.innerHTML = `
                <div class="pagination-info">
                    Showing <span>${from}–${to}</span> of
                    <span>${this.totalRecords}</span> records
                </div>
                <div class="pagination-controls">${pagesHtml}</div>
                <div class="pg-per-page">
                    Rows per page:
                    <select class="pg-per-page-select">
                        <option value="10" ${this.perPage == 10 ? 'selected' : ''}>10</option>
                        <option value="15" ${this.perPage == 15 ? 'selected' : ''}>15</option>
                        <option value="25" ${this.perPage == 25 ? 'selected' : ''}>25</option>
                        <option value="50" ${this.perPage == 50 ? 'selected' : ''}>50</option>
                    </select>
                </div>
                <div class="pg-loading">
                    <div class="pg-spinner"></div> Loading...
                </div>
            `;

            this.paginationWrapper.querySelectorAll('.pg-btn[data-page]')
                .forEach(btn => {
                    btn.addEventListener('click', () => {
                        this.load(parseInt(btn.dataset.page));
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    });
                });

            this.paginationWrapper.querySelector('.pg-per-page-select')
                ?.addEventListener('change', (e) => {
                    this.perPage = parseInt(e.target.value);
                    this.load(1);
                });
        }

        getPageNumbers() {
            const pages = [];
            const total = this.totalPages;
            const current = this.currentPage;

            if (total <= 7) {
                for (let i = 1; i <= total; i++) pages.push(i);
            } else {
                pages.push(1);
                if (current > 3) pages.push('...');
                for (let i = Math.max(2, current - 1);
                     i <= Math.min(total - 1, current + 1); i++) {
                    pages.push(i);
                }
                if (current < total - 2) pages.push('...');
                pages.push(total);
            }
            return pages;
        }

        showLoading() {
            this.paginationWrapper?.querySelector('.pg-loading')
                ?.classList.add('visible');
        }

        hideLoading() {
            this.paginationWrapper?.querySelector('.pg-loading')
                ?.classList.remove('visible');
        }
    }

    window.AjaxPaginator = AjaxPaginator;

});
