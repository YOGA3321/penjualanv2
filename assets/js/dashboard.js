document.addEventListener('DOMContentLoaded', () => {
    const menuGrid = document.querySelector('.menu-grid');
    const cartFab = document.querySelector('.cart-fab');
    
    // Ambil data keranjang dari LocalStorage (agar tidak hilang saat refresh)
    let cart = JSON.parse(localStorage.getItem('cart_v2')) || [];

    // --- FUNGSI UPDATE TAMPILAN CART ---
    const updateCartUI = () => {
        const cartItemsContainer = document.getElementById('cart-items-container');
        const cartBadge = document.querySelector('.cart-badge');
        const cartTotalPriceEl = document.getElementById('cart-total-price');
        const checkoutBtn = document.querySelector('.btn-checkout-action');
        
        cartItemsContainer.innerHTML = '';
        let totalItems = 0;
        let totalPrice = 0;

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = `
                <div class="text-center text-muted my-5">
                    <i class="fas fa-shopping-basket fa-3x mb-3" style="color:#e9ecef;"></i><br>
                    Keranjangmu masih kosong!
                </div>`;
            if(checkoutBtn) checkoutBtn.classList.add('disabled');
        } else {
            if(checkoutBtn) checkoutBtn.classList.remove('disabled');
            
            cart.forEach(item => {
                totalItems += item.quantity;
                totalPrice += item.price * item.quantity;
                
                const cartItemEl = document.createElement('div');
                cartItemEl.className = 'cart-item d-flex align-items-center mb-3';
                cartItemEl.innerHTML = `
                    <img src="${item.image}" alt="${item.name}" class="rounded" style="width:60px; height:60px; object-fit:cover; margin-right:10px;">
                    <div class="item-details flex-grow-1">
                        <p class="mb-0 fw-bold text-dark" style="font-size:0.9rem; line-height:1.2;">${item.name}</p>
                        <span class="text-primary small fw-bold">Rp ${(item.price * item.quantity).toLocaleString('id-ID')}</span>
                    </div>
                    <div class="item-quantity d-flex align-items-center bg-light rounded-pill p-1">
                        <button class="btn btn-sm btn-white rounded-circle shadow-sm p-0 quantity-change" 
                                style="width:25px; height:25px;" data-id="${item.id}" data-action="decrease">-</button>
                        <span class="mx-2 small fw-bold">${item.quantity}</span>
                        <button class="btn btn-sm btn-white rounded-circle shadow-sm p-0 quantity-change" 
                                style="width:25px; height:25px;" data-id="${item.id}" data-action="increase">+</button>
                    </div>
                `;
                cartItemsContainer.appendChild(cartItemEl);
            });
        }
        
        if(cartBadge) cartBadge.textContent = totalItems;
        if(cartTotalPriceEl) cartTotalPriceEl.textContent = `Rp ${totalPrice.toLocaleString('id-ID')}`;
        
        // Simpan ke LocalStorage setiap ada perubahan
        localStorage.setItem('cart_v2', JSON.stringify(cart));
    };
    
    // --- ANIMASI TERBANG ---
    const flyToCartAnimation = (startElement) => {
        if(!cartFab) return;
        const imgToFly = startElement.cloneNode(true);
        const startRect = startElement.getBoundingClientRect();
        const endRect = cartFab.getBoundingClientRect();
        
        imgToFly.classList.add('fly-to-cart-image');
        document.body.appendChild(imgToFly);
        
        // Set posisi awal
        imgToFly.style.position = 'fixed';
        imgToFly.style.left = `${startRect.left}px`;
        imgToFly.style.top = `${startRect.top}px`;
        imgToFly.style.width = `${startRect.width}px`;
        imgToFly.style.height = `${startRect.height}px`;
        imgToFly.style.borderRadius = '15px';
        imgToFly.style.zIndex = '9999';
        imgToFly.style.transition = 'all 0.7s cubic-bezier(0.19, 1, 0.22, 1)';

        // Mulai animasi
        requestAnimationFrame(() => {
            imgToFly.style.left = `${endRect.left + 10}px`;
            imgToFly.style.top = `${endRect.top + 10}px`;
            imgToFly.style.width = '20px';
            imgToFly.style.height = '20px';
            imgToFly.style.opacity = '0';
            imgToFly.style.borderRadius = '50%';
        });

        setTimeout(() => {
            imgToFly.remove();
            // Efek getar pada tombol cart
            cartFab.style.transform = 'scale(1.2)';
            setTimeout(() => cartFab.style.transform = 'scale(1)', 200);
        }, 700);
    };

    // --- EVENT LISTENER TOMBOL TAMBAH ---
    if(menuGrid) {
        menuGrid.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-add-to-cart')) {
                const card = e.target.closest('.menu-card');
                const itemData = card.dataset;
                const itemId = parseInt(itemData.id);
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
                
                const cardImage = card.querySelector('.menu-card-img');
                if(cardImage) flyToCartAnimation(cardImage);
                
                updateCartUI();
            }
        });
    }

    // --- EVENT LISTENER TOMBOL +/- DI CART ---
    const container = document.getElementById('cart-items-container');
    if(container) {
        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('quantity-change')) {
                const itemId = parseInt(e.target.dataset.id);
                const action = e.target.dataset.action;
                const itemInCart = cart.find(item => item.id === itemId);
                if (!itemInCart) return;

                if (action === 'increase') {
                    itemInCart.quantity++;
                } else if (action === 'decrease') {
                    itemInCart.quantity--;
                    if (itemInCart.quantity <= 0) {
                        cart = cart.filter(item => item.id !== itemId);
                    }
                }
                updateCartUI();
            }
        });
    }

    // Load tampilan awal
    updateCartUI();
});