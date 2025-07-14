

document.addEventListener('DOMContentLoaded', function() {
    // --- Fungsionalitas Search Bar (di Header, ada di semua halaman utama) ---
    const searchInput = document.getElementById('search-input');
    const searchSuggestions = document.getElementById('search-suggestions');
    let xhr = null;

    if (searchInput && searchSuggestions) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();

            if (query.length === 0) {
                searchSuggestions.innerHTML = '';
                searchSuggestions.style.display = 'none';
                return;
            }

            if (xhr && xhr.readyState !== 4) {
                xhr.abort();
            }

            xhr = new XMLHttpRequest();
            xhr.open('GET', 'search_suggestions.php?query=' + encodeURIComponent(query), true);
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const suggestions = JSON.parse(xhr.responseText);
                        displaySuggestions(suggestions);
                    } catch (e) {
                        console.error('Error parsing JSON response for search suggestions:', e);
                        searchSuggestions.innerHTML = '';
                        searchSuggestions.style.display = 'none';
                    }
                } else {
                    console.error('Search suggestions request failed. Returned status of ' + xhr.status);
                    searchSuggestions.innerHTML = '';
                    searchSuggestions.style.display = 'none';
                }
            };
            xhr.onerror = function() {
                console.error('Network error during search suggestions AJAX request.');
                searchSuggestions.innerHTML = '';
                searchSuggestions.style.display = 'none';
            };
            xhr.send();
        });

        document.addEventListener('click', function(event) {
            if (!searchInput.contains(event.target) && !searchSuggestions.contains(event.target)) {
                searchSuggestions.style.display = 'none';
            }
        });

        function displaySuggestions(suggestions) {
            searchSuggestions.innerHTML = '';
            if (suggestions.length > 0) {
                suggestions.forEach(function(item) {
                    const div = document.createElement('div');
                    div.textContent = `${item.title} oleh ${item.author} (${item.genre})`;
                    div.setAttribute('data-id', item.id);
                    div.addEventListener('click', function() {
                        window.location.href = `view_review.php?id=${this.getAttribute('data-id')}`;
                    });
                    searchSuggestions.appendChild(div);
                });
                searchSuggestions.style.display = 'block';
            } else {
                searchSuggestions.style.display = 'none';
            }
        }
    }

    // --- Fungsionalitas Carousel (Hanya ada di index.php dan landing.php) ---
    const carouselTrack = document.getElementById('carousel-track');
    if (carouselTrack) {
        let carouselPosition = 0;
        
        const getCarouselItems = () => carouselTrack.children;
        
        const getItemWidth = () => {
            const items = getCarouselItems();
            return items.length > 0 ? items[0].offsetWidth + 15 : 0; 
        };

        window.moveCarousel = function(direction) {
            const items = getCarouselItems();
            const itemWidth = getItemWidth();
            if (itemWidth === 0) return;

            const containerWidth = carouselTrack.parentElement.offsetWidth;
            const maxScrollWidth = carouselTrack.scrollWidth;
            const maxPosition = Math.max(0, maxScrollWidth - containerWidth); 

            carouselPosition += direction * itemWidth * 3;

            if (carouselPosition < 0) {
                carouselPosition = 0;
            } else if (carouselPosition > maxPosition) {
                carouselPosition = maxPosition;
            }
            carouselTrack.style.transform = `translateX(-${carouselPosition}px)`;
        };

        window.addEventListener('resize', () => {
            const items = getCarouselItems();
            const itemWidth = getItemWidth();
            if (itemWidth === 0) return;

            const containerWidth = carouselTrack.parentElement.offsetWidth;
            const maxScrollWidth = carouselTrack.scrollWidth;
            const newMaxPosition = Math.max(0, maxScrollWidth - containerWidth);

            if (carouselPosition > newMaxPosition) {
                carouselPosition = newMaxPosition;
            }
            if (carouselPosition < 0) {
                carouselPosition = 0;
            }
            carouselTrack.style.transform = `translateX(-${carouselPosition}px)`;
        });
    }

    // --- Validasi Formulir (add.php dan edit.php) ---
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(event) {
            const title = document.getElementById('title').value.trim();
            const author = document.getElementById('author').value.trim();
            const genre = document.getElementById('genre').value.trim();
            const bookDescription = document.getElementById('book_description').value.trim();
            const physicalDescription = document.getElementById('physical_description').value.trim();
            const pageCountInput = document.getElementById('page_count');
            const pageCount = parseInt(pageCountInput ? pageCountInput.value : '0');
            const isbn = document.getElementById('isbn').value.trim();
            const rating = document.getElementById('rating').value;
            const reviewText = document.getElementById('review_text').value.trim();

            if (!title) { alert('Judul buku tidak boleh kosong.'); event.preventDefault(); return; }
            if (!author) { alert('Nama penulis tidak boleh kosong.'); event.preventDefault(); return; }
            if (!genre) { alert('Genre tidak boleh kosong.'); event.preventDefault(); return; }
            if (!bookDescription) { alert('Deskripsi buku tidak boleh kosong.'); event.preventDefault(); return; }
            if (!physicalDescription) { alert('Deskripsi fisik tidak boleh kosong.'); event.preventDefault(); return; }
            if (isNaN(pageCount) || pageCount <= 0) {
                alert('Jumlah halaman harus angka positif yang valid.');
                event.preventDefault();
                return;
            }
            if (!isbn) { alert('ISBN tidak boleh kosong.'); event.preventDefault(); return; }
            if (rating < 1 || rating > 5) { alert('Rating harus antara 1 dan 5.'); event.preventDefault(); return; }
            if (!reviewText) { alert('Isi ulasan tidak boleh kosong.'); event.preventDefault(); return; }
        });
    }

    // --- Fungsionalitas File Input Kustom (add.php dan edit.php) ---
    const coverImageInput = document.getElementById('cover_image');
    const fileNameDisplay = document.getElementById('file-name-display');

    if (coverImageInput && fileNameDisplay) {
        coverImageInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                fileNameDisplay.textContent = this.files[0].name;
            } else {
                fileNameDisplay.textContent = 'No file chosen';
            }
        });
    }

    // --- Validasi Formulir Login/Register (login.php dan register.php) ---
    const registerForm = document.querySelector('form[action="register.php"]');
    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const confirmPassword = document.getElementById('confirm_password').value.trim();

            if (!username || !password || !confirmPassword) { alert('Semua bidang harus diisi.'); event.preventDefault(); return; }
            if (password !== confirmPassword) { alert('Konfirmasi password tidak cocok.'); event.preventDefault(); return; }
            if (password.length < 6) { alert('Password minimal 6 karakter.'); event.preventDefault(); return; }
        });
    }

    const loginForm = document.querySelector('form[action="login.php"]');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!username || !password) { alert('Username dan password tidak boleh kosong.'); event.preventDefault(); return; }
        });
    }

    // --- Fungsionalitas Toggle Password (login.php dan register.php) ---
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.textContent = 'sembunyikan';
            } else {
                passwordInput.type = 'password';
                this.textContent = 'lihat';
            }
        });
        // Set teks awal saat halaman dimuat
        const initialTargetId = toggle.getAttribute('data-target');
        const initialPasswordInput = document.getElementById(initialTargetId);
        if (initialPasswordInput) {
            if (initialPasswordInput.type === 'password') {
                toggle.textContent = 'lihat';
            } else {
                toggle.textContent = 'sembunyikan';
            }
        }
    });

    // --- Fungsionalitas Halaman Ulasan Saya (khusus dashboard.php) ---
    window.reviews = window.reviews || [];

    const reviewsGrid = document.getElementById('reviewsGrid');
    const emptyState = document.getElementById('emptyState');
    const dashboardSearchInput = document.getElementById('searchInput');
    const filterSelect = document.getElementById('filterSelect');
    const editModal = document.getElementById('editModal');
    const editForm = document.getElementById('editForm');

    if (reviewsGrid && emptyState) { 
        let currentFilter = 'all';
        let currentSort = 'recent'; 
        let currentEditId = null;

        window.renderReviews = function(reviewsToRender = window.reviews) {
            if (!reviewsGrid || !emptyState) return; 

            if (reviewsToRender.length === 0) {
                reviewsGrid.innerHTML = '';
                reviewsGrid.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }
            
            reviewsGrid.style.display = 'grid';
            emptyState.style.display = 'none';
            
            reviewsGrid.innerHTML = reviewsToRender.map(review => `
                <div class="review-card">
                    <a href="view_review.php?id=${review.id}" class="book-info-link">
                        <div class="book-info">
                            <div class="book-cover">
                                <img src="${review.cover_image_url || 'uploads/covers/placeholder.png'}" alt="Cover" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                            </div>
                            <div class="book-details">
                                <h3>${review.title || 'Tidak Ada Judul'}</h3>
                                <p>oleh ${review.author || 'Penulis Tidak Dikenal'}</p>
                            </div>
                        </div>
                        <div class="rating">
                            <div class="stars">
                                ${Array.from({length: 5}, (_, i) => 
                                    `<span class="star ${i < (review.rating || 0) ? '' : 'empty'}">★</span>`
                                ).join('')}
                            </div>
                            <span>(${review.rating || 0}/5)</span>
                        </div>
                        <p class="review-text">${(review.review_text || 'Tidak ada teks ulasan.').substring(0, 150)}...</p>
                    </a>
                    <div class="review-meta">
                        <span>Diulas pada ${formatDate(review.created_at || new Date().toISOString())}</span>
                        <div class="review-actions">
                            <a href="view_review.php?id=${review.id}" class="action-btn view">Lihat</a>
                            <button class="action-btn edit" onclick="event.stopPropagation(); editReview(${review.id});">Edit</button>
                            <button class="action-btn delete" onclick="event.stopPropagation(); deleteReview(${review.id});">Hapus</button>
                        </div>
                    </div>
                </div>
            `).join('');
        };

        // Filter ulasan (untuk dashboard.php)
        window.filterReviews = function() {
            const searchTerm = dashboardSearchInput ? dashboardSearchInput.value.toLowerCase() : '';
            const ratingFilter = filterSelect ? filterSelect.value : 'all';
            
            let filtered = window.reviews; 
            
            if (searchTerm) {
                filtered = filtered.filter(review => 
                    (review.title || '').toLowerCase().includes(searchTerm) ||
                    (review.author || '').toLowerCase().includes(searchTerm) ||
                    (review.review_text || '').toLowerCase().includes(searchTerm)
                );
            }
            
            if (ratingFilter !== 'all') {
                filtered = filtered.filter(review => (review.rating || 0) === parseInt(ratingFilter));
            }
            
            renderReviews(filtered);
        };

        // Edit Ulasan (untuk dashboard.php)
        window.editReview = function(id) {
            const review = window.reviews.find(r => r.id === id);
            if (!review || !editModal || !editForm) return;

            currentEditId = id;
            document.getElementById('editReviewId').value = review.id;
            document.getElementById('editTitle').value = review.title;
            document.getElementById('editAuthor').value = review.author;
            document.getElementById('editRating').value = review.rating;
            document.getElementById('editReviewText').value = review.review_text;
            document.getElementById('editGenre').value = review.genre || '';
            document.getElementById('editBookDescription').value = review.book_description || '';
            document.getElementById('editPhysicalDescription').value = review.physical_description || '';
            document.getElementById('editPageCount').value = review.page_count || '';
            document.getElementById('editISBN').value = review.isbn || '';
            
            editModal.style.display = 'flex';
        };
        
        // Tutup Modal (untuk dashboard.php)
        window.closeModal = function() {
            if (editModal) {
                editModal.style.display = 'none';
            }
            currentEditId = null;
        };
        
        // Hapus Ulasan (untuk dashboard.php)
        window.deleteReview = function(id) {
            if (confirm('Apakah Anda yakin ingin menghapus ulasan ini?')) {
                
                window.reviews = window.reviews.filter(r => r.id !== id);
                renderReviews();
                alert('Ulasan berhasil dihapus!');
            }
        };
        
        // Tangani Pengiriman Formulir Edit (untuk dashboard.php)
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const reviewId = document.getElementById('editReviewId').value;
                const reviewIndex = window.reviews.findIndex(r => r.id === parseInt(reviewId));
                
                if (reviewIndex !== -1) {
                    window.reviews[reviewIndex] = {
                        ...window.reviews[reviewIndex],
                        title: document.getElementById('editTitle').value,
                        author: document.getElementById('editAuthor').value,
                        rating: parseInt(document.getElementById('editRating').value),
                        review_text: document.getElementById('editReviewText').value,
                        genre: document.getElementById('editGenre').value,
                        book_description: document.getElementById('editBookDescription').value,
                        physical_description: document.getElementById('editPhysicalDescription').value,
                        page_count: parseInt(document.getElementById('editPageCount').value),
                        isbn: document.getElementById('editISBN').value
                    };

                }
                
                closeModal();
                renderReviews();
                alert('Ulasan berhasil diperbarui!');
            });
        }

        // Event listener untuk Dashboard (Ulasan Saya)
        if (dashboardSearchInput) {
            dashboardSearchInput.addEventListener('input', window.filterReviews);
        }
        if (filterSelect) {
            filterSelect.addEventListener('change', window.filterReviews);
        }
        
        window.addEventListener('click', function(e) {
            if (editModal && e.target === editModal) {
                window.closeModal();
            }
        });

        // Render awal untuk Dashboard (Ulasan Saya)
        renderReviews();
    }

    // Fungsi pembantu (global)
    window.formatDate = function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { 
            day: 'numeric', 
            month: 'short',
            year: 'numeric'
        });
    };

    // Navigasi tab (ada di semua halaman utama)
    document.querySelectorAll('.nav-tabs .nav-tab').forEach(tab => {
        tab.addEventListener('click', function(event) {
            const targetUrl = this.getAttribute('href');
            if (targetUrl && targetUrl !== '#') {
                window.location.href = targetUrl;
            }
        });
    });
});