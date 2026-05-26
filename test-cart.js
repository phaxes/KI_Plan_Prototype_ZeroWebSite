const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();

  // Clear localStorage to start fresh
  await page.goto('http://localhost:8000');
  await page.evaluate(() => localStorage.clear());

  console.log('\n=== Test 1: First attempt to add product ===');

  // Go to shop
  await page.goto('http://localhost:8000/shop');
  await page.waitForSelector('[data-product-id]', { timeout: 5000 });

  // Get cart count before
  let cartBadge = await page.locator('#cart-count').textContent().catch(() => '0');
  console.log('Cart count before first add:', cartBadge || '0');

  // Click first add to cart button
  await page.click('[data-product-id]');

  // Wait a moment for any updates
  await page.waitForTimeout(500);

  // Check cart count after
  cartBadge = await page.locator('#cart-count').textContent().catch(() => '0');
  console.log('Cart count after first add (immediate):', cartBadge);

  // Go to cart page
  console.log('\n=== Checking cart page ===');
  await page.goto('http://localhost:8000/cart');
  await page.waitForTimeout(1000);

  // Check if product is showing
  const cartItems = await page.locator('#cart-items-container').innerHTML();
  const hasProduct = cartItems.includes('Stück') || cartItems.includes('€');
  console.log('Product showing on cart page after first add:', hasProduct ? 'YES' : 'NO');
  console.log('Cart HTML:', cartItems.substring(0, 200));

  // Go back to shop for second add
  console.log('\n=== Test 2: Second attempt to add product ===');
  await page.goto('http://localhost:8000/shop');
  await page.waitForSelector('[data-product-id]', { timeout: 5000 });

  // Click add to cart again (different product or same)
  const buttons = await page.locator('[data-product-id]').all();
  if (buttons.length > 1) {
    await buttons[1].click();
  } else {
    await buttons[0].click();
  }

  await page.waitForTimeout(500);

  // Check cart count
  cartBadge = await page.locator('#cart-count').textContent().catch(() => '0');
  console.log('Cart count after second add:', cartBadge);

  // Go to cart page again
  await page.goto('http://localhost:8000/cart');
  await page.waitForTimeout(1000);

  const cartItems2 = await page.locator('#cart-items-container').innerHTML();
  const hasProducts = cartItems2.includes('Stück') && (cartItems2.match(/Stück/g) || []).length >= 1;
  console.log('Products showing on cart page after second add:', hasProducts ? 'YES' : 'NO');

  await browser.close();
})();
