\document.addEventListener('DOMContentLoaded', () => {

    const cartFab = document.querySelector('.cart-fab');
    let cart = []; // Array untuk menyimpan item di keranjang

    // --- FUNGSI UTAMA ---

    // Fungsi untuk memperbarui tampilan keranjang (di Offcanvas dan badge)
    const updateCartUI = () => {
        const cartItemsContainer = document.getElementById('cart-items-container');
        const cartBadge = document.querySelector('.cart-badge');
        const cartTotalPriceEl = document.getElementById('cart-total-price');
        const emptyCartMessage = document.querySelector('.empty-cart-message');

        cartItemsContainer.innerHTML = ''; // Kosongkan kontainer
        let totalItems = 0;
        let totalPrice = 0;

        if (cart.length === 0) {
            emptyCartMessage.style.display = 'block';
        } else {
            emptyCartMessage.style.display = 'none';
            cart.forEach(item => {
                totalItems += item.quantity;
                totalPrice += item.price * item.quantity;

                // Buat elemen HTML untuk setiap item di keranjang
                const cartItemEl = document.createElement('div');
                cartItemEl.className = 'cart-item';
                cartItemEl.innerHTML = `
                    <img src="${item.image}" alt="${item.name}">
                    <div class="item-details">
                        <p>${item.name}</p>
                        <span>Rp ${item.price.toLocaleString('id-ID')}</span>
                    </div>
                    <div class="item-quantity">
                        <button class="quantity-change" data-id="${item.id}" data-action="decrease">-</button>
                        <span>${item.quantity}</span>
                        <button class="quantity-change" data-id="${item.id}" data-action="increase">+</button>
                    </div>
                `;
                cartItemsContainer.appendChild(cartItemEl);
            });
        }

        cartBadge.textContent = totalItems;
        cartTotalPriceEl.textContent = `Rp ${totalPrice.toLocaleString('id-ID')}`;
    };

    // Fungsi untuk animasi "terbang ke keranjang"
    const flyToCartAnimation = (startElement) => {
        const imgToFly = startElement.cloneNode();
        const startRect = startElement.getBoundingClientRect();
        const endRect = cartFab.getBoundingClientRect();

        imgToFly.classList.add('fly-to-cart-image');
        document.body.appendChild(imgToFly);

        // Set posisi awal
        imgToFly.style.left = `${startRect.left}px`;
        imgToFly.style.top = `${startRect.top}px`;
        imgToFly.style.width = `${startRect.width}px`;
        imgToFly.style.height = `${startRect.height}px`;

        // Trigger animasi
        requestAnimationFrame(() => {
            imgToFly.style.left = `${endRect.left + (endRect.width / 2)}px`;
            imgToFly.style.top = `${endRect.top + (endRect.height / 2)}px`;
            imgToFly.style.width = '20px';
            imgToFly.style.height = '20px';
            imgToFly.style.opacity = '0';
            imgToFly.style.transform = 'rotate(360deg)';
        });

        // Hapus elemen setelah animasi selesai
        setTimeout(() => {
            imgToFly.remove();
        }, 700); // Durasi transisi di CSS
    };

    // --- EVENT LISTENERS ---

    // Event listener untuk semua tombol "Tambah ke Keranjang"
    const addToCartButtons = document.querySelectorAll('.btn-add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const card = e.target.closest('.menu-card');
            const itemData = card.dataset;
            const itemId = parseInt(itemData.id);

            // Cek apakah item sudah ada di keranjang
            const existingItem = cart.find(item => item.id === itemId);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({
                    id: itemId,
                    name: itemData.name,
                    price: parseFloat(itemData.price),
                    image: itemData.image,
                    quantity: 1
                });
            }
            
            // Panggil animasi
            const cardImage = card.querySelector('img');
            flyToCartAnimation(cardImage);

            // Perbarui UI Keranjang
            updateCartUI();
        });
    });

    // Event listener untuk tombol +/- di dalam keranjang (menggunakan event delegation)
    document.getElementById('cart-items-container').addEventListener('click', (e) => {
        if (e.target.classList.contains('quantity-change')) {
            const itemId = parseInt(e.target.dataset.id);
            const action = e.target.dataset.action;
            const itemInCart = cart.find(item => item.id === itemId);

            if (itemInCart) {
                if (action === 'increase') {
                    itemInCart.quantity++;
                } else if (action === 'decrease') {
                    itemInCart.quantity--;
                    if (itemInCart.quantity <= 0) {
                        // Hapus item dari keranjang jika kuantitas 0 atau kurang
                        cart = cart.filter(item => item.id !== itemId);
                    }
                }
                updateCartUI();
            }
        }
    });

    // Panggil updateCartUI() saat pertama kali load untuk menampilkan pesan kosong
    updateCartUI();
});