let categories = [];
let restaurants = [];

let cart = [];
let currentRestaurant = null;

async function bootstrap() {
  const res = await fetch('api/data.php');
  if (!res.ok) throw new Error('Could not load catalog (' + res.status + ')');
  const data = await res.json();
  if (data.error) throw new Error(data.message || data.error);
  categories = data.categories;
  restaurants = data.restaurants;
  renderCategories();
  renderRestaurants();
  updateCart();
}

// ── RENDER CATEGORIES ──
function renderCategories() {
  const el = document.getElementById('categories-row');
  el.innerHTML = categories.map(c => `
    <div class="cat-chip" data-filter="${c.filter}">
      <span class="cat-icon">${c.icon}</span> ${c.name}
    </div>
  `).join('');
  el.querySelectorAll('.cat-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      setFilter(chip.dataset.filter);
    });
  });
}

// ── RENDER RESTAURANTS ──
function renderRestaurants(filter = 'all') {
  const grid = document.getElementById('restaurants-grid');
  const list = filter === 'all' ? restaurants : restaurants.filter(r => r.category === filter);
  if (!list.length) {
    grid.innerHTML = `<p style="color:var(--muted);grid-column:1/-1;padding:32px 0;text-align:center">No restaurants found.</p>`;
    return;
  }
  grid.innerHTML = list.map(r => `
    <div class="rest-card" data-id="${r.id}">
      <div class="rest-img" style="background-image:url('${r.image}')">
        <div class="rest-img-overlay"></div>
        <span class="rest-tag ${r.tagStyle}">${r.tag}</span>
        ${!r.open ? `<div class="rest-closed">Closed · Schedule Order</div>` : ''}
      </div>
      <div class="rest-body">
        <div class="rest-name">${r.name}</div>
        <div class="rest-meta">
          <span class="rest-rating"><i class="fas fa-star"></i> ${r.rating}</span>
          <span><i class="fas fa-clock" style="margin-right:3px"></i>${r.deliveryTime} min</span>
        </div>
        <div class="rest-cuisines">${r.cuisines.join(' · ')}</div>
        <div class="rest-delivery">
          <span class="delivery-fee">${r.deliveryFee === 0 ? '🎉 Free Delivery' : `₱${r.deliveryFee} delivery`}</span>
          <span class="delivery-time"><i class="fas fa-motorcycle"></i> Fast</span>
        </div>
      </div>
    </div>
  `).join('');
  grid.querySelectorAll('.rest-card').forEach(card => {
    card.addEventListener('click', () => openModal(parseInt(card.dataset.id)));
  });
}

function setFilter(filter) {
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.toggle('active', t.dataset.filter === filter));
  document.querySelectorAll('.cat-chip').forEach(c => c.classList.toggle('active', c.dataset.filter === filter));
  renderRestaurants(filter);
}

// ── MODAL ──
function openModal(idOrType) {
  if (idOrType === 'login') return;
  const r = restaurants.find(x => x.id === idOrType);
  if (!r) return;
  currentRestaurant = r;
  document.getElementById('modal-hero-img').style.backgroundImage = `url('${r.image}')`;
  document.getElementById('modal-info').innerHTML = `
    <h2>${r.name}</h2>
    <div class="rest-meta" style="margin-top:8px">
      <span class="rest-rating"><i class="fas fa-star"></i> ${r.rating}</span>
      <span><i class="fas fa-clock" style="margin-right:3px"></i>${r.deliveryTime} min</span>
      <span><i class="fas fa-motorcycle" style="margin-right:3px"></i>${r.deliveryFee === 0 ? 'Free Delivery' : `₱${r.deliveryFee} delivery`}</span>
    </div>
    <div class="rest-cuisines" style="margin-top:6px">${r.cuisines.join(' · ')}</div>
  `;
  document.getElementById('menu-items').innerHTML = r.menu.map(item => `
    <div class="menu-row">
      ${item.image ? `<img src="${item.image}" class="menu-row-img" alt="${item.name}">` : ''}
      <div class="menu-row-info">
        <h4>${item.name}</h4>
        <p>${item.description}</p>
      </div>
      <span class="menu-row-price">₱${item.price}</span>
      <button class="menu-add-btn" data-id="${item.id}"><i class="fas fa-plus"></i></button>
    </div>
  `).join('');
  document.querySelectorAll('.menu-add-btn').forEach(btn => {
    btn.addEventListener('click', e => { e.stopPropagation(); addToCart(parseInt(btn.dataset.id)); });
  });
  document.getElementById('modal-overlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('modal-overlay').classList.remove('active');
  document.body.style.overflow = '';
}

// ── CART ──
function saveCart() {
  try {
    sessionStorage.setItem('foodieph_cart', JSON.stringify(cart));
  } catch (e) {
    console.warn('Could not save cart', e);
  }
}

function addToCart(itemId) {
  if (typeof window.IS_LOGGED_IN !== 'undefined' && !window.IS_LOGGED_IN) {
    alert("You must be logged in to order or add items to your cart.");
    window.location.href = 'login.php';
    return;
  }
  const item = currentRestaurant.menu.find(m => m.id === itemId);
  if (!item) return;
  if (cart.length && cart[0].restaurantId !== currentRestaurant.id) {
    if (!confirm('Your cart has items from another restaurant. Clear cart and add this item?')) return;
    cart = [];
  }
  const existing = cart.find(c => c.id === itemId);
  if (existing) existing.quantity++;
  else cart.push({ ...item, quantity: 1, restaurantId: currentRestaurant.id, restaurantName: currentRestaurant.name, deliveryFee: currentRestaurant.deliveryFee });
  updateCart();
  saveCart();
  toast(`${item.name} added to cart`);
}

window.removeFromCart = itemId => {
  const i = cart.findIndex(c => c.id === itemId);
  if (i > -1) { if (cart[i].quantity > 1) cart[i].quantity--; else cart.splice(i, 1); }
  updateCart();
  saveCart();
};
window.addCartQty = itemId => {
  const item = cart.find(c => c.id === itemId);
  if (item) { item.quantity++; updateCart(); saveCart(); }
};

function updateCart() {
  const total = cart.reduce((s, i) => s + i.quantity, 0);
  document.getElementById('cart-count').textContent = total;
  const el = document.getElementById('cart-items-list');
  if (!cart.length) {
    el.innerHTML = `<div class="cart-empty-state"><i class="fas fa-shopping-bag"></i><p>Your cart is empty</p></div>`;
    document.getElementById('cart-subtotal').textContent = '₱0.00';
    document.getElementById('cart-delivery').textContent = '₱0.00';
    document.getElementById('cart-total').textContent = '₱0.00';
    return;
  }
  el.innerHTML = cart.map(item => `
    <div class="cart-row">
      <div class="cart-row-info">
        <h4>${item.name}</h4>
        <p>₱${item.price} × ${item.quantity}</p>
      </div>
      <div class="cart-qty">
        <button class="cart-qty-btn" onclick="removeFromCart(${item.id})"><i class="fas fa-minus"></i></button>
        <span>${item.quantity}</span>
        <button class="cart-qty-btn" onclick="addCartQty(${item.id})"><i class="fas fa-plus"></i></button>
      </div>
    </div>
  `).join('');
  const subtotal = cart.reduce((s, i) => s + i.price * i.quantity, 0);
  const fee = cart.length ? cart[0].deliveryFee : 0;
  document.getElementById('cart-subtotal').textContent = `₱${subtotal.toFixed(2)}`;
  document.getElementById('cart-delivery').textContent = `₱${fee.toFixed(2)}`;
  document.getElementById('cart-total').textContent = `₱${(subtotal + fee).toFixed(2)}`;
}

function openCart() {
  document.getElementById('cart-sidebar').classList.add('active');
  document.getElementById('cart-overlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeCart() {
  document.getElementById('cart-sidebar').classList.remove('active');
  document.getElementById('cart-overlay').classList.remove('active');
  document.body.style.overflow = '';
}

function toast(msg) {
  const t = document.getElementById('toast');
  document.getElementById('toast-msg').textContent = msg;
  t.classList.add('active');
  setTimeout(() => t.classList.remove('active'), 3000);
}

function handleSearch() {
  const val = document.getElementById('address-input').value.trim();
  if (!val) { toast('Please enter your address first'); return; }
  toast(`Searching restaurants near "${val}"…`);
}

// ── EVENTS ──
document.getElementById('modal-overlay').addEventListener('click', e => { if (e.target === document.getElementById('modal-overlay')) closeModal(); });
function scrollToRestaurants() {
  document.getElementById('restaurants-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

document.getElementById('promo-free-delivery')?.addEventListener('click', () => {
  scrollToRestaurants();
  setFilter('fast-food');
  toast('Free delivery on orders over ₱500 — add items and checkout!');
});
document.getElementById('promo-free-delivery')?.addEventListener('keydown', e => {
  if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); document.getElementById('promo-free-delivery')?.click(); }
});

document.getElementById('promo-new-arrivals')?.addEventListener('click', () => {
  scrollToRestaurants();
  setFilter('all');
  toast('Browse all restaurants — including our newest partners!');
});
document.getElementById('promo-new-arrivals')?.addEventListener('keydown', e => {
  if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); document.getElementById('promo-new-arrivals')?.click(); }
});

document.getElementById('checkout-btn').addEventListener('click', () => {
  if (typeof window.IS_LOGGED_IN !== 'undefined' && !window.IS_LOGGED_IN) {
    alert("You must be logged in to proceed to checkout.");
    window.location.href = 'login.php';
    return;
  }
  if (!cart.length) { toast('Your cart is empty!'); return; }
  saveCart();
  window.location.href = 'checkout.php';
});
document.querySelectorAll('.filter-tab').forEach(btn => {
  btn.addEventListener('click', () => setFilter(btn.dataset.filter));
});
document.querySelectorAll('.zone-pill').forEach(pill => {
  pill.addEventListener('click', () => {
    document.querySelectorAll('.zone-pill').forEach(p => p.classList.remove('active'));
    pill.classList.add('active');
    toast(`Delivery zone: ${pill.textContent.trim()}`);
  });
});
document.querySelectorAll('.pop-tag').forEach(tag => {
  tag.addEventListener('click', () => toast(`Searching for "${tag.textContent}"…`));
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeModal(); closeCart(); } });

bootstrap().catch(err => {
  console.error(err);
  const grid = document.getElementById('restaurants-grid');
  if (grid) {
    grid.innerHTML = '<p style="color:var(--muted);grid-column:1/-1;padding:32px 16px;text-align:center">Could not load the menu from <code>api/data.php</code>. Use Apache in XAMPP (<code>http://localhost/...</code>), not a <code>file://</code> link. If you enabled MySQL in <code>config.php</code>, run <code>sql/schema.sql</code> and <code>php api/seed.php</code>.</p>';
  }
});
