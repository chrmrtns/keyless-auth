# Keyless Auth v3.0.0 - PSR-4 Refactoring Guide

## Completed Files
✅ `autoload.php` - PSR-4 autoloader
✅ `includes/Core/Database.php` - Namespaced
✅ `includes/Security/TwoFA/TOTP.php` - Namespaced
✅ `includes/Security/TwoFA/Core.php` - Namespaced

## Remaining Files to Refactor

### 1. includes/Security/TwoFA/Frontend.php
**Source:** `includes/class-chrmrtns-kla-2fa-frontend.php`
**Transformations:**
```php
// Add at top (after <?php and docblock):
namespace Chrmrtns\KeylessAuth\Security\TwoFA;

use Chrmrtns\KeylessAuth\Core\Database;

// Change class name:
class Chrmrtns_KLA_2FA_Frontend → class Frontend

// Update instantiations:
new Chrmrtns_KLA_TOTP() → new TOTP()
new Chrmrtns_KLA_2FA_Core() → new Core()

// Update static calls:
Chrmrtns_KLA_2FA_Core::get_instance() → Core::get_instance()

// Update class_exists checks:
class_exists('Chrmrtns_KLA_Database') → class_exists('Chrmrtns\\KeylessAuth\\Core\\Database')
class_exists('Chrmrtns_KLA_2FA_Core') → class_exists('Chrmrtns\\KeylessAuth\\Security\\TwoFA\\Core')
```

### 2. includes/Email/Templates.php
**Source:** `includes/class-chrmrtns-kla-email-templates.php`
**Transformations:**
```php
namespace Chrmrtns\KeylessAuth\Email;

class Chrmrtns_KLA_Email_Templates → class Templates

// NO class dependencies to update (standalone)
```

### 3. includes/Email/SMTP.php
**Source:** `includes/class-chrmrtns-kla-smtp.php`
**Transformations:**
```php
namespace Chrmrtns\KeylessAuth\Email;

class Chrmrtns_KLA_SMTP → class SMTP

// NO dependencies
```

### 4. includes/Email/MailLogger.php
**Source:** `includes/class-chrmrtns-kla-mail-logger.php`
**Transformations:**
```php
namespace Chrmrtns\KeylessAuth\Email;

class Chrmrtns_KLA_Mail_Logger → class MailLogger

// NO dependencies
```

### 5. includes/Core/Core.php
**Source:** `includes/class-chrmrtns-kla-core.php`
**Transformations:**
```php
namespace Chrmrtns\KeylessAuth\Core;

use Chrmrtns\KeylessAuth\Security\TwoFA\Core as TwoFACore;
use Chrmrtns\KeylessAuth\Email\Templates;
use Chrmrtns\KeylessAuth\Admin\Admin;

class Chrmrtns_KLA_Core → class Core

// Update references:
Chrmrtns_KLA_Database → Database (or just use from global)
Chrmrtns_KLA_Email_Templates → Templates
Chrmrtns_KLA_Admin → Admin
Chrmrtns_KLA_2FA_Core::get_instance() → TwoFACore::get_instance()

// Update class_exists:
class_exists('Chrmrtns_KLA_*') → class_exists('Chrmrtns\\KeylessAuth\\...')
```

### 6. includes/Admin/Admin.php (LARGEST FILE - 1798 lines)
**Source:** `includes/class-chrmrtns-kla-admin.php`
**Transformations:**
```php
namespace Chrmrtns\KeylessAuth\Admin;

use Chrmrtns\KeylessAuth\Core\Database;
use Chrmrtns\KeylessAuth\Security\TwoFA\Core as TwoFACore;
use Chrmrtns\KeylessAuth\Security\TwoFA\TOTP;
use Chrmrtns\KeylessAuth\Email\Templates;
use Chrmrtns\KeylessAuth\Email\SMTP;
use Chrmrtns\KeylessAuth\Email\MailLogger;

class Chrmrtns_KLA_Admin → class Admin

// Update all class references to use namespaced versions
// Update all static methods: Chrmrtns_KLA_Admin::get_redirect_url() → Admin::get_redirect_url()
```

### 7. includes/Core/Main.php (Bootstrap)
**Source:** From `keyless-auth.php` lines 49-178
**Transformations:**
```php
namespace Chrmrtns\KeylessAuth\Core;

use Chrmrtns\KeylessAuth\Core\Database;
use Chrmrtns\KeylessAuth\Core\Core;
use Chrmrtns\KeylessAuth\Admin\Admin;
use Chrmrtns\KeylessAuth\Email\SMTP;
use Chrmrtns\KeylessAuth\Email\MailLogger;
use Chrmrtns\KeylessAuth\Security\TwoFA\Core as TwoFACore;
use Chrmrtns\KeylessAuth\Security\TwoFA\Frontend as TwoFAFrontend;

class Chrmrtns_KLA_Main → class Main

// Update all new Chrmrtns_KLA_* to new Namespaced\Class()
```

### 8. keyless-auth.php (Main Plugin File)
**NEW Content:**
```php
<?php
/**
 * Plugin Name: Keyless Auth - Login without Passwords
 * Version: 3.0.0
 * ... (keep all headers same)
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CHRMRTNS_KLA_VERSION', '3.0.0');
define('CHRMRTNS_KLA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHRMRTNS_KLA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CHRMRTNS_KLA_PLUGIN_FILE', __FILE__);

// Load autoloader
require_once CHRMRTNS_KLA_PLUGIN_DIR . 'autoload.php';

// Include existing notices class (not part of autoloading)
if (file_exists(CHRMRTNS_KLA_PLUGIN_DIR . 'inc/chrmrtns.class.notices.php')) {
    include_once CHRMRTNS_KLA_PLUGIN_DIR . 'inc/chrmrtns.class.notices.php';
}

// Use namespaced classes
use Chrmrtns\KeylessAuth\Core\Main;
use Chrmrtns\KeylessAuth\Core\Database;

/**
 * Initialize plugin
 */
function chrmrtns_kla_init() {
    return Main::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'chrmrtns_kla_init');

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'chrmrtns_kla_activation_hook');
function chrmrtns_kla_activation_hook() {
    $database = new Database();
    $database->create_tables();

    // Set default options (keep all existing)
    // ... (copy from current file)

    flush_rewrite_rules();
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'chrmrtns_kla_deactivation_hook');
function chrmrtns_kla_deactivation_hook() {
    $database = new Database();
    $database->cleanup_expired_tokens();

    // ... (keep existing cleanup code)

    flush_rewrite_rules();
}

/**
 * Uninstall hook
 */
register_uninstall_hook(__FILE__, 'chrmrtns_kla_uninstall_hook');
function chrmrtns_kla_uninstall_hook() {
    $database = new Database();
    $database->drop_tables();

    // ... (keep all existing cleanup)
}
```

## Global Variable Handling

For `global $chrmrtns_kla_database` usage:
- Keep using global for now (backward compatibility)
- Initialize in Main class:
```php
global $chrmrtns_kla_database;
$chrmrtns_kla_database = new Database();
```

## Search & Replace Patterns

Use your IDE's search/replace (Regex mode):

1. **Class declarations:**
   - Find: `class Chrmrtns_KLA_([A-Za-z_]+)`
   - Replace: `class $1`

2. **New instantiations:**
   - Find: `new Chrmrtns_KLA_([A-Za-z_]+)\(`
   - Replace: `new $1(`
   - (Then manually add use statements at top)

3. **Static calls:**
   - Find: `Chrmrtns_KLA_([A-Za-z_]+)::`
   - Replace: `$1::`

4. **Class exists checks:**
   - Find: `class_exists\('Chrmrtns_KLA_([A-Za-z_]+)'\)`
   - Manual: Add full namespace in checks

## Testing Checklist

After refactoring:
1. ✅ Plugin activates without errors
2. ✅ Database tables created
3. ✅ Magic link login works
4. ✅ 2FA setup works
5. ✅ 2FA verification works
6. ✅ Admin settings load
7. ✅ SMTP configuration works
8. ✅ Mail logging works
9. ✅ Deactivation cleanup works

## Version Update

Update these files to 3.0.0:
- ✅ `keyless-auth.php` header (line 6)
- ✅ `keyless-auth.php` constant (line 40)
- ✅ `readme.txt` stable tag (line 8)
- ✅ `readme.txt` changelog (add 3.0.0 section)

## Changelog Entry for 3.0.0

```markdown
= 3.0.0 =
* ARCHITECTURAL: Complete refactoring to PSR-4 autoloading with namespaces
* IMPROVEMENT: Modern PHP class organization - `Chrmrtns\KeylessAuth` namespace
* IMPROVEMENT: Better IDE support and code intelligence
* IMPROVEMENT: Cleaner code structure organized by functionality
* TECHNICAL: Autoloader replaces manual class loading
* TECHNICAL: Classes organized: Core/, Admin/, Email/, Security/TwoFA/
* BREAKING: Internal class names changed (no impact on users, data preserved)
* MAINTENANCE: All database tables, options, and user data remain unchanged
* MAINTENANCE: Seamless upgrade - no manual steps required
```

## Notes

- WordPress functions (get_option, wp_mail, etc.) are in global namespace - they work fine in namespaced classes
- IntelliSense warnings about "undefined function" are normal and can be ignored
- All database table names stay as `chrmrtns_kla_*` (no changes)
- All WordPress options stay as `chrmrtns_kla_*` (no changes)
- All user meta keys stay as `chrmrtns_kla_*` (no changes)
