document.addEventListener('DOMContentLoaded', () => {

    const cartFab = document.querySelector('.cart-fab');
    const menuGrid = document.querySelector('.menu-grid');
    let cart = []; // Array untuk menyimpan item di keranjang

    // --- FUNGSI UTAMA ---

    const updateCartUI = () => {
        const cartItemsContainer = document.getElementById('cart-items-container');
        const cartBadge = document.querySelector('.cart-badge');
        const cartTotalPriceEl = document.getElementById('cart-total-price');
        const emptyCartMessage = document.querySelector('.empty-cart-message');

        cartItemsContainer.innerHTML = '';
        let totalItems = 0;
        let totalPrice = 0;

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '<p class="text-center text-muted empty-cart-message">Keranjangmu masih kosong!</p>';
        } else {
            cart.forEach(item => {
                totalItems += item.quantity;
                totalPrice += item.price * item.quantity;
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
    
    const flyToCartAnimation = (startElement) => {
        const imgToFly = startElement.cloneNode();
        const startRect = startElement.getBoundingClientRect();
        const endRect = cartFab.getBoundingClientRect();
        imgToFly.classList.add('fly-to-cart-image');
        document.body.appendChild(imgToFly);
        
        imgToFly.style.left = `${startRect.left}px`;
        imgToFly.style.top = `${startRect.top}px`;
        imgToFly.style.width = `${startRect.width}px`;
        imgToFly.style.height = `${startRect.height}px`;

        requestAnimationFrame(() => {
            imgToFly.style.left = `${endRect.left + (endRect.width / 4)}px`;
            imgToFly.style.top = `${endRect.top + (endRect.height / 4)}px`;
            imgToFly.style.width = '0px';
            imgToFly.style.height = '0px';
            imgToFly.style.opacity = '0.5';
            imgToFly.style.transform = 'rotate(360deg)';
        });

        setTimeout(() => {
            imgToFly.remove();
        }, 700);
    };

    // --- EVENT LISTENERS ---

    // Menggunakan Event Delegation untuk tombol "Tambah ke Keranjang"
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
                    id: itemId, name: itemData.name,
                    price: parseFloat(itemData.price),
                    image: itemData.image, quantity: 1
                });
            }
            
            const cardImage = card.querySelector('.menu-card-img');
            flyToCartAnimation(cardImage);
            updateCartUI();
        }
    });

    document.getElementById('cart-items-container').addEventListener('click', (e) => {
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

    updateCartUI(); // Initial call
});