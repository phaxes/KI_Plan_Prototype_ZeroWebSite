# Mailchimp Configuration Guide for Zero-Cost Website

This guide walks you through configuring Mailchimp with the Zero-Cost Website platform step by step.

---

## Step 1: Create a Mailchimp Account

1. Go to [mailchimp.com](https://mailchimp.com)
2. Click **"Sign up free"** (top right)
3. Enter the following information:
   - **Email address**: Your email
   - **Username**: Choose a username
   - **Password**: Create a strong password
4. Click **"Sign up"**
5. Verify your email address (check your inbox for confirmation link)

---

## Step 2: Create an Audience (Mailing List)

### 2.1 Navigate to Audience Management

1. Log in to your Mailchimp account
2. In the left sidebar, click **"Audience"** or **"All audiences"**
3. Click the blue **"Create Audience"** button

### 2.2 Configure Your Audience

Fill in the following details:

**Basic Information:**
- **Audience name**: e.g., "Newsletter Subscribers"
- **Email address**: Your business email
- **Company name**: Your business name
- **Default "From" name**: Your name or business name
- **Default "From" email**: Your business email

**Default address:**
- Complete with your business address (required by law)

**Notifications:**
- Check **"Notify when subscribers join"** 
- Check **"Notify when subscribers unsubscribe"**
- Enter email address for notifications

3. Click **"Create Audience"**

---

## Step 3: Configure Audience Fields

### 3.1 Access Audience Settings

1. Click on your newly created audience
2. Go to **Settings** (gear icon, top right)
3. Click **"Audience fields and merge tags"**

### 3.2 Add Merge Tags (Optional)

For category-based subscriptions (News, Blog, Products), add custom fields:

**Add a "Categories" field:**
1. Click **"Add a field"**
2. Select **"Text"** as field type
3. Configure:
   - **Field name**: "Interests"
   - **Tag**: "INTERESTS" (auto-populated)
4. Click **"Save"**

This allows subscribers to select which content they want to receive.

---

## Step 4: Enable Double Opt-In (Important for GDPR)

1. In **Settings**, scroll to **"Subscription settings"**
2. Check **"Enable double opt-in"**
3. Configure confirmation email (optional):
   - Customize the confirmation email subject/content
4. Click **"Save"**

---

## Step 5: Generate API Key

### 5.1 Access API Keys

1. Click your **profile icon** (top right)
2. Select **"Account & Billing"**
3. Click **"Extras"** → **"API keys"**

### 5.2 Create and Copy API Key

1. Click **"Create A Key"**
2. A new API key is generated (e.g., `abc123def456789xyz-us5`)
3. Click **"Copy"** to copy the full key

**IMPORTANT**: Treat this API key like a password—never share it or commit it to GitHub!

---

## Step 6: Identify Your Server Prefix

Your API key contains a server prefix at the end:
```
abc123def456789xyz-us5
```

The server prefix is the part **after the last hyphen** (e.g., `us5`).

**Common server prefixes:**
- `us1`, `us2`, `us3`... (United States)
- `eu1`, `eu2`... (Europe)
- `ca1` (Canada)

**Save this for later—you'll need it!**

---

## Step 7: Get Your Audience ID (List ID)

### 7.1 Find Your Audience ID

1. Click on your **Audience name** (top left)
2. Go to **Settings** (gear icon)
3. Scroll to **"Audience ID"**
4. Copy the ID (e.g., `a1b2c3d4e5`)

---

## Step 8: Configure Environment Variables

### 8.1 On Your Local Machine

Create or update your `.env` file with:

```env
MAILCHIMP_API_KEY=abc123def456789xyz-us5
MAILCHIMP_SERVER_PREFIX=us5
MAILCHIMP_LIST_ID=a1b2c3d4e5
```

Where:
- `MAILCHIMP_API_KEY`: Your complete API key (including server prefix)
- `MAILCHIMP_SERVER_PREFIX`: Just the server prefix (e.g., `us5`)
- `MAILCHIMP_LIST_ID`: Your audience ID

### 8.2 On Render.com

1. Go to your **Render dashboard**
2. Select your **Web Service**
3. Go to **Environment** tab
4. Add the same three variables:
   - `MAILCHIMP_API_KEY`
   - `MAILCHIMP_SERVER_PREFIX`
   - `MAILCHIMP_LIST_ID`
5. Click **"Save"** and your service will redeploy

---

## Step 9: Test the Newsletter Form

### 9.1 Local Testing

1. Start your development server (`php -S localhost:8000`)
2. Open http://localhost:8000 in your browser
3. Scroll to the **newsletter signup form** (usually in footer)
4. Enter a test email (e.g., `test@example.com`)
5. Click **"Subscribe"** or **"Join"**

**Verify in Mailchimp:**
1. Go back to Mailchimp
2. Click your **Audience**
3. Click **"All contacts"** or **"Manage subscribers"**
4. Your test email should appear in the list

### 9.2 Test on Render

1. Go to your deployed website (e.g., https://yoursite.onrender.com)
2. Scroll to the newsletter form
3. Enter a real email address
4. Click **"Subscribe"**
5. Check your email inbox for a confirmation email (if double opt-in is enabled)
6. Verify the contact appears in Mailchimp

---

## Step 10: Set Up Welcome Email (Automation)

### 10.1 Create Welcome Automation

1. In Mailchimp, click **"Marketing"** (top menu)
2. Select **"Automations"**
3. Click **"Create an automation"**
4. Choose **"Welcome email"** or **"Automation workflows"**

### 10.2 Configure the Automation

1. Set trigger: **"When someone joins this audience"**
2. Click **"Next"**
3. Create your email:
   - **Subject**: "Welcome to our newsletter!"
   - **Content**: Greet the subscriber, explain what they'll receive
   - Include links to your News, Blog, or Shop pages
4. Click **"Save and publish"**

---

## Step 11: Create and Send Campaigns

### 11.1 Start a Campaign

1. Click **"Marketing"** (top menu)
2. Click **"Campaigns"**
3. Click **"Create campaign"**
4. Choose **"Email"**

### 11.2 Select Recipients

1. Select your **Audience**
2. Optional: Filter by interests/tags (e.g., only "News" subscribers)
3. Click **"Next"**

### 11.3 Compose Your Email

1. Add **Campaign name** (internal use)
2. Set **Subject line** (what subscribers see)
3. Add content:
   - Promotional text
   - Links to your latest News/Blog posts
   - Links to Shop products
4. Review and test with **"Send test email"**
5. Click **"Schedule"** or **"Send now"**

---

## Step 12: Add Signup Form to Your Website

If you want an embedded Mailchimp signup form on your site:

### 12.1 Get the Form Code

1. In your **Audience**, click **"Sign-up forms"**
2. Select **"Embedded form"** or **"Form builder"**
3. Customize the form:
   - Field text
   - Button text
   - Success message
4. Click **"Generate code"** or **"Copy"**

### 12.2 Add to Your Website

1. Copy the provided HTML/JavaScript code
2. Paste it into your website's footer or sidebar
3. Customize styling to match your site
4. Test that submissions work

---

## Troubleshooting

### Problem: "Invalid API Key"

**Solution:**
- Verify the API key is copied exactly (no extra spaces)
- Check the server prefix is correct (e.g., `us5`, `eu1`)
- Generate a new API key if needed
- Ensure `.env` is loaded correctly (restart your server)

### Problem: "List/Audience Not Found"

**Solution:**
- Verify `MAILCHIMP_LIST_ID` is correct
- Check that your audience still exists in Mailchimp
- Regenerate the List ID and update `.env`

### Problem: "Rate Limit Exceeded"

**Solution:**
- Mailchimp allows ~10 requests/second
- The website implements built-in rate limiting
- Usually not an issue with normal usage
- Wait a few seconds before retrying

### Problem: "E-mails Going to Spam"

**Solution:**
1. Verify your **domain** with Mailchimp:
   - Settings → **"Domain verification"**
   - Add DNS records (SPF, DKIM, CNAME)
2. Add a **physical address** to your audience:
   - Settings → **"Contact information"**
3. Test with [mail-tester.com](https://mail-tester.com)

### Problem: Subscribers Not Receiving Confirmation Email

**Solution:**
- Check **Audience → Settings → Subscription settings**
- Ensure **"Double opt-in"** is enabled
- Resend confirmation email from the contact details
- Check spam folder for confirmation email

---

## Security & Best Practices

### ✅ Do:

1. **Protect Your API Key**
   - Only store in `.env` files (local and Render)
   - Never commit to GitHub
   - Regenerate if accidentally exposed

2. **Enable Double Opt-In**
   - Settings → **"Subscription settings"**
   - Check **"Enable double opt-in"**
   - Ensures valid, consenting subscribers

3. **Add Legal Information**
   - Settings → **"Audience name and contact information"**
   - Include full business address (required by law)
   - Add phone and business details

4. **Create Privacy Policy**
   - Settings → **"Privacy"**
   - Link to your website's privacy policy
   - Explain data collection and use

5. **Monitor Bounce Rate**
   - High bounces can hurt your sender reputation
   - Clean inactive contacts regularly
   - Test forms before deployment

### ❌ Don't:

1. **Don't commit API keys** to version control
2. **Don't send spam**—Mailchimp will ban your account
3. **Don't enable auto-subscription** without consent
4. **Don't sell email lists**—violates Mailchimp's terms
5. **Don't scrape emails** without permission

---

## Website Integration Code

For reference, here's how the website sends subscriptions to Mailchimp:

```php
// In src/Controllers/NewsletterController.php
use DrewM\MailChimp\MailChimp;

$mailchimp = new MailChimp(getenv('MAILCHIMP_API_KEY'));

$result = $mailchimp->post('lists/' . getenv('MAILCHIMP_LIST_ID') . '/members', [
    'email_address' => $email,
    'status' => 'pending',  // Triggers double opt-in confirmation
    'merge_fields' => [
        'INTERESTS' => $interests
    ]
]);
```

---

## Useful Links

- **Mailchimp API Documentation**: https://mailchimp.com/developer/marketing/api/
- **Mailchimp Knowledge Base**: https://mailchimp.com/help/
- **Mailchimp Status Page**: https://status.mailchimp.com/
- **Mailchimp Support**: https://mailchimp.com/contact/

---

## Checklist

- [ ] Created Mailchimp account
- [ ] Created audience with correct name
- [ ] Generated and saved API key
- [ ] Identified server prefix
- [ ] Set up custom fields (optional)
- [ ] Enabled double opt-in
- [ ] Added legal information and privacy policy
- [ ] Configured environment variables (local and Render)
- [ ] Tested newsletter form locally
- [ ] Tested newsletter form on Render
- [ ] Set up welcome automation (optional)
- [ ] Created first campaign (optional)
- [ ] Verified emails not going to spam

---

**Need help?** Check the Mailchimp Knowledge Base or contact Mailchimp Support.

**Ready to launch your newsletter! 🚀**
