#!/bin/bash

# Test the Render.com deployment
BASE_URL="https://zero-cost-website.onrender.com"
COOKIE_JAR="/tmp/render_cookies.txt"

echo "=== Testing Render.com Deployment ==="
echo ""

# Step 1: Visit login page
echo "Step 1: Visiting login page..."
curl -s -c $COOKIE_JAR "$BASE_URL/login" | grep -q "Anmelden" && echo "✓ Login page loads" || echo "✗ Login page failed"

# Step 2: Simulate login via API
echo ""
echo "Step 2: Testing registration feedback (new user)..."
curl -s -X POST "$BASE_URL/auth/verify" \
  -H "Content-Type: application/json" \
  -d '{
    "idToken": "test-token",
    "email": "nonexistent@test.com",
    "uid": "test-uid-123",
    "displayName": "Test User"
  }' | grep -q "error" && echo "✓ Registration validation works" || echo "✗ No validation response"

# Step 3: Check admin status for test@example.com
echo ""
echo "Step 3: Checking test@example.com admin access..."

# Try to access admin page without auth
ADMIN_RESPONSE=$(curl -s -w "\n%{http_code}" "$BASE_URL/admin" | tail -1)
echo "  /admin (no auth) status: $ADMIN_RESPONSE"

# Step 4: Test profile page
echo ""
echo "Step 4: Testing profile page accessibility..."
curl -s "$BASE_URL/profile" | grep -q "302\|Anmelden\|Login" && echo "✓ Profile correctly redirects to login" || echo "✗ Profile redirect issue"

# Step 5: Test new routes
echo ""
echo "Step 5: Testing new profile feature routes..."

# Check change-password route exists
PASSWD_RESPONSE=$(curl -s -w "\n%{http_code}" "$BASE_URL/profile/change-password" | tail -1)
echo "  /profile/change-password: $PASSWD_RESPONSE (should be 302 redirect)"

# Check newsletter route exists
NEWS_RESPONSE=$(curl -s -w "\n%{http_code}" "$BASE_URL/profile/newsletter" | tail -1)
echo "  /profile/newsletter: $NEWS_RESPONSE (should be 302 redirect)"

# Step 6: Test admin panel routes
echo ""
echo "Step 6: Testing admin routes accessibility..."

ADMIN_NEWS=$(curl -s -w "\n%{http_code}" "$BASE_URL/admin/news" | tail -1)
echo "  /admin/news: $ADMIN_NEWS (should be 403 or 302)"

ADMIN_BLOG=$(curl -s -w "\n%{http_code}" "$BASE_URL/admin/blog" | tail -1)
echo "  /admin/blog: $ADMIN_BLOG (should be 403 or 302)"

ADMIN_PRODUCTS=$(curl -s -w "\n%{http_code}" "$BASE_URL/admin/products" | tail -1)
echo "  /admin/products: $ADMIN_PRODUCTS (should be 403 or 302)"

# Step 7: Check registration page
echo ""
echo "Step 7: Testing registration page..."
curl -s "$BASE_URL/register" | grep -q "Registrieren" && echo "✓ Registration page loads" || echo "✗ Registration page failed"

# Check for loading spinner
curl -s "$BASE_URL/register" | grep -q "register-loading\|animate-spin" && echo "✓ Loading spinner implemented" || echo "⚠ Loading spinner not found"

# Step 8: Check home page
echo ""
echo "Step 8: Testing home page..."
curl -s "$BASE_URL/" | grep -q "ZeroWeb" && echo "✓ Home page loads" || echo "✗ Home page failed"

echo ""
echo "=== Test Complete ==="
echo ""
echo "Summary:"
echo "- If /admin returns 403 and is unauthenticated, that's expected"
echo "- /profile/change-password and /profile/newsletter should redirect (302) when not logged in"
echo "- Registration page should have loading spinner"
echo ""
echo "To fully test with login:"
echo "1. Go to https://zero-cost-website.onrender.com/login"
echo "2. Login with test@example.com / password123"
echo "3. Visit /admin - should work now with the fallback mechanism"
