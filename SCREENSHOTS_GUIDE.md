# Screenshot Guide for Passwordless Auth Plugin

## Screenshot Requirements

For the WordPress.org plugin repository, screenshots should be placed in the `/assets/` directory with specific naming:

### File Naming Convention
- `screenshot-1.png` - Front-end login form
- `screenshot-2.png` - Main admin dashboard
- `screenshot-3.png` - Email template settings
- `screenshot-4.png` - WYSIWYG email editor
- `screenshot-5.png` - SMTP configuration
- `screenshot-6.png` - Mail logs
- `screenshot-7.png` - Test email functionality
- `screenshot-8.png` - Email preview

### Recommended Dimensions
- **Width:** 1200px (minimum 880px)
- **Height:** Variable (keep reasonable)
- **Format:** PNG or JPG (PNG preferred for UI screenshots)

## How to Take Screenshots

### 1. Front-end Login Form (screenshot-1.png)
- Navigate to a page with `[chrmrtns-passwordless-auth]` shortcode
- Show the clean login form with email/username field
- Include any success/error messages if relevant

### 2. Main Admin Dashboard (screenshot-2.png)
- Go to **Passwordless Auth** main page
- Show the welcome screen with:
  - Logo and title
  - Shortcode section
  - Success counter
  - "An alternative to passwords" section

### 3. Email Template Settings (screenshot-3.png)
- Go to **Passwordless Auth > PA Settings**
- Show the template selection dropdown
- Include the template preview area
- Show color customization options

### 4. WYSIWYG Email Editor (screenshot-4.png)
- Still in **PA Settings**
- Show the custom template editor in action
- Display the TinyMCE toolbar
- Show some formatted content being edited

### 5. SMTP Configuration (screenshot-5.png)
- Go to **Passwordless Auth > SMTP**
- Show the SMTP settings form
- **Highlight the new credential storage options**
- Show both Database and wp-config.php radio buttons
- Include the wp-config instructions box

### 6. Mail Logs (screenshot-6.png)
- Go to **Passwordless Auth > Mail Logs**
- Show the email log table with entries
- Include columns: Date/Time, To, Subject, Status
- Show the preview functionality if possible

### 7. Test Email Functionality (screenshot-7.png)
- In **SMTP** settings page
- Show the "Send Test Email" section
- Include the success message after sending
- Show the email address field

### 8. Email Preview (screenshot-8.png)
- Show an actual received email
- Display the styled template with:
  - Login button
  - Proper formatting
  - Your branding

## Taking Quality Screenshots

### Browser Tips
- Use Chrome or Firefox in incognito/private mode
- Set zoom to 100%
- Use a clean WordPress installation if possible
- Clear any admin notices before capturing

### Tools Recommendations
1. **macOS:** 
   - Built-in: `Cmd + Shift + 4` for area selection
   - CleanShot X (paid) for annotations
   - Shottr (free) for quick edits

2. **Windows:**
   - Built-in: Windows + Shift + S
   - ShareX (free, powerful)
   - Greenshot (free, simple)

3. **Cross-platform:**
   - Browser extensions like FireShot
   - Awesome Screenshot

### Post-Processing
- Crop unnecessary whitespace
- Add subtle drop shadows if needed
- Highlight important features with arrows/boxes
- Keep file sizes reasonable (optimize with TinyPNG)

## Where to Upload

### For GitHub Repository
Place screenshots in `/assets/` directory:
```
/passwordless-auth/
  /assets/
    screenshot-1.png
    screenshot-2.png
    ... etc
```

### For WordPress.org
When submitting to WordPress.org, the same `/assets/` directory structure is used.

## Sample Data for Screenshots

Use this sample data for consistency:

- **Test User:** John Doe (john@example.com)
- **Success Counter:** Show a number like 42 or 156
- **Email Logs:** Mix of successful entries
- **SMTP Host:** smtp.gmail.com or smtp.mailgun.org
- **Test Email:** admin@yoursite.com

## Important Notes

1. **Hide Sensitive Information**
   - Never show real email addresses (except yours if intended)
   - Hide real SMTP passwords
   - Blur any private API keys

2. **Consistency**
   - Use the same WordPress theme across screenshots
   - Keep the same admin color scheme
   - Use consistent window sizes

3. **Quality**
   - Ensure text is readable
   - Avoid compression artifacts
   - Use high contrast for clarity

---

Once you have all screenshots ready:
1. Name them according to the convention above
2. Place them in the `/assets/` directory
3. Commit and push to GitHub
4. They'll be automatically used by WordPress.org when you submit the plugin