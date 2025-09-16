# WordPress.org SVN Submission Guide for Keyless Auth v2.0.11

## Step 1: SVN Checkout
```bash
# Navigate to your development directory
cd /Users/christianmartens/Downloads/

# Checkout the SVN repository (replace 'keyless-auth' with your actual slug if different)
svn co https://plugins.svn.wordpress.org/keyless-auth/ keyless-auth-svn
cd keyless-auth-svn
```

## Step 2: Copy Plugin Files to Trunk
```bash
# Copy all plugin files to trunk (excluding git files, SVN files, and this guide)
cp -r ../keyless-auth/* trunk/

# Remove files that shouldn't be in WordPress.org submission
cd trunk
rm -f .gitignore
rm -f .git
rm -rf .git/
rm -f SVN_SUBMISSION_GUIDE.md
rm -f .claude
```

## Step 3: Copy Screenshots to Assets Directory
```bash
# Go back to SVN root
cd ..

# Copy screenshots to assets directory
cp ../keyless-auth/assets/screenshot-*.png assets/

# Verify screenshots are copied
ls -la assets/
```

## Step 4: Create Version Tag
```bash
# Create tag for version 2.0.11
cp -r trunk tags/2.0.11
```

## Step 5: Add Files to SVN
```bash
# Add all new files
svn add trunk/* --force
svn add assets/* --force
svn add tags/2.0.11/* --force

# Check SVN status
svn status
```

## Step 6: Commit to WordPress.org
```bash
# Commit with descriptive message
svn commit -m "Adding Keyless Auth - Login without Passwords v2.0.11 - Enhanced passwordless authentication with SMTP support, email templates, and mail logging"
```

## Directory Structure Should Look Like:
```
keyless-auth-svn/
├── assets/
│   ├── screenshot-1.png
│   ├── screenshot-2.png
│   ├── screenshot-3.png
│   ├── screenshot-4.png
│   ├── screenshot-5.png
│   ├── screenshot-6.png
│   └── screenshot-7.png
├── tags/
│   └── 2.0.11/
│       └── [all plugin files]
└── trunk/
    ├── assets/
    │   ├── admin-script.js
    │   ├── admin-style.css
    │   ├── logo_150_150.png
    │   └── style-*.css
    ├── includes/
    │   └── [all class files]
    ├── inc/
    │   └── [notices class]
    ├── languages/
    ├── keyless-auth.php
    ├── readme.txt
    └── README.md
```

## Important Notes:
1. Screenshots go in the `assets` directory, NOT in trunk
2. The stable tag in readme.txt should match the tag directory name (2.0.11)
3. Make sure no development files (.git, .gitignore, .claude) are included in trunk
4. Version numbers in plugin header, readme.txt, and constants should all match 2.0.11